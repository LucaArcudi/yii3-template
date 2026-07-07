<?php

declare(strict_types=1);

namespace App\Core\Error\Actions;

use App\Handlers\Middleware\Core\StatusPageMiddleware;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Yiisoft\Http\Status;
use Yiisoft\User\CurrentUser;
use Yiisoft\Yii\View\Renderer\WebViewRenderer;

final readonly class TooManyRequestsHandler implements RequestHandlerInterface
{
    private WebViewRenderer $viewRenderer;

    public function __construct(
        WebViewRenderer $viewRenderer,
        private CurrentUser $currentUser,
    ) {
        $this->viewRenderer = $viewRenderer->withViewPath('@src/Core/Error/views');
    }

    public function __invoke(ServerRequestInterface $request): ResponseInterface
    {
        return $this->handle($request);
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $retryAfterSeconds = $this->retryAfterSeconds($request);

        $response = $this->renderer()
            ->render('too-many-requests', [
                'retryAfterSeconds' => $retryAfterSeconds,
            ])
            ->withStatus(Status::TOO_MANY_REQUESTS);

        return $retryAfterSeconds === null
            ? $response
            : $response->withHeader('Retry-After', (string) $retryAfterSeconds);
    }

    private function renderer(): WebViewRenderer
    {
        return $this->currentUser->isGuest()
            ? $this->viewRenderer->withLayout('@resources/layouts/guest')
            : $this->viewRenderer;
    }

    private function retryAfterSeconds(ServerRequestInterface $request): ?int
    {
        $value = $request->getAttribute(StatusPageMiddleware::RETRY_AFTER_ATTRIBUTE);

        return is_int($value) && $value > 0 ? $value : null;
    }
}
