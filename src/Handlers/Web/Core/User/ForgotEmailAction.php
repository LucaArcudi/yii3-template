<?php

declare(strict_types=1);

namespace App\Handlers\Web\Core\User;

use Psr\Http\Message\ResponseInterface;
use Yiisoft\Yii\View\Renderer\WebViewRenderer;

final readonly class ForgotEmailAction
{
    public function __construct(
        private WebViewRenderer $viewRenderer,
    ) {}

    public function __invoke(): ResponseInterface
    {
        return $this->viewRenderer
            ->withLayout('@resources/layouts/guest')
            ->render('core/user/forgot-email');
    }
}
