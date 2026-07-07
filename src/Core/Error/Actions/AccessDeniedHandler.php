<?php

declare(strict_types=1);

namespace App\Core\Error\Actions;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Yiisoft\Http\Status;
use Yiisoft\User\CurrentUser;
use Yiisoft\Yii\View\Renderer\WebViewRenderer;

final readonly class AccessDeniedHandler implements RequestHandlerInterface
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
        return $this->renderer()
            ->render('access-denied')
            ->withStatus(Status::FORBIDDEN);
    }

    private function renderer(): WebViewRenderer
    {
        return $this->currentUser->isGuest()
            ? $this->viewRenderer->withLayout('@resources/layouts/guest')
            : $this->viewRenderer;
    }
}
