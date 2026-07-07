<?php

declare(strict_types=1);

namespace App\Core\User\Actions;

use Psr\Http\Message\ResponseInterface;
use Yiisoft\Yii\View\Renderer\WebViewRenderer;

final readonly class ForgotEmailAction
{
    private WebViewRenderer $viewRenderer;

    public function __construct(
        WebViewRenderer $viewRenderer,
    ) {
        $this->viewRenderer = $viewRenderer->withViewPath('@src/Core/User/views');
    }

    public function __invoke(): ResponseInterface
    {
        return $this->viewRenderer
            ->withLayout('@resources/layouts/guest')
            ->render('forgot-email');
    }
}
