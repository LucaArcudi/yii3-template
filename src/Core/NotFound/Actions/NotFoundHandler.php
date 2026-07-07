<?php

declare(strict_types=1);

namespace App\Core\NotFound\Actions;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Yiisoft\Http\Status;
use Yiisoft\Router\CurrentRoute;
use Yiisoft\Router\UrlGeneratorInterface;
use Yiisoft\Yii\View\Renderer\WebViewRenderer;

final readonly class NotFoundHandler implements RequestHandlerInterface
{
    private WebViewRenderer $viewRenderer;

    public function __construct(
        private UrlGeneratorInterface $urlGenerator,
        private CurrentRoute $currentRoute,
        WebViewRenderer $viewRenderer,
    ) {
        $this->viewRenderer = $viewRenderer->withViewPath('@src/Core/NotFound/views');
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        return $this->viewRenderer
            ->render('index', ['urlGenerator' => $this->urlGenerator, 'currentRoute' => $this->currentRoute])
            ->withStatus(Status::NOT_FOUND);
    }
}
