<?php

declare(strict_types=1);

namespace App\Core\Home\Actions;

use App\Dashboard\DashboardComponentProvider;
use App\Dashboard\DashboardComponentRenderer;
use App\Services\Core\WebActionService;
use Psr\Http\Message\ResponseInterface;
use Yiisoft\User\CurrentUser;
use Yiisoft\Yii\View\Renderer\WebViewRenderer;

final readonly class IndexAction
{
    private WebViewRenderer $viewRenderer;

    public function __construct(
        WebViewRenderer $viewRenderer,
        private DashboardComponentProvider $componentProvider,
        private DashboardComponentRenderer $componentRenderer,
        private CurrentUser $currentUser,
        private WebActionService $webAction,
    ) {
        $this->viewRenderer = $viewRenderer->withViewPath('@src/Core/Home/views');
    }

    public function __invoke(): ResponseInterface
    {
        $userId = $this->currentUser->getId();

        if ($userId === null || $userId === '') {
            return $this->webAction->forbidden();
        }

        return $this->viewRenderer->render('index', [
            'components' => $this->componentProvider->findVisible(),
            'componentRenderer' => $this->componentRenderer,
        ]);
    }
}
