<?php

declare(strict_types=1);

namespace App\Handlers\Web\Core\Home;

use App\Dashboard\DashboardComponentProvider;
use App\Dashboard\DashboardComponentRenderer;
use App\Services\Core\WebActionService;
use Psr\Http\Message\ResponseInterface;
use Yiisoft\User\CurrentUser;
use Yiisoft\Yii\View\Renderer\WebViewRenderer;

final readonly class IndexAction
{
    public function __construct(
        private WebViewRenderer $viewRenderer,
        private DashboardComponentProvider $componentProvider,
        private DashboardComponentRenderer $componentRenderer,
        private CurrentUser $currentUser,
        private WebActionService $webAction,
    ) {
    }

    public function __invoke(): ResponseInterface
    {
        $userId = $this->currentUser->getId();

        if ($userId === null || $userId === '') {
            return $this->webAction->forbidden();
        }

        return $this->viewRenderer->render('core/home/index', [
            'components' => $this->componentProvider->findVisible(),
            'componentRenderer' => $this->componentRenderer,
        ]);
    }
}
