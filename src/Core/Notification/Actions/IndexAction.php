<?php

declare(strict_types=1);

namespace App\Core\Notification\Actions;

use App\Core\Notification\NotificationPolicy;
use App\Core\Notification\NotificationReader;
use App\Services\Core\WebActionService;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Yiisoft\Yii\View\Renderer\WebViewRenderer;

final readonly class IndexAction
{
    private WebViewRenderer $viewRenderer;

    public function __construct(
        WebViewRenderer $viewRenderer,
        private NotificationReader $notificationReader,
        private NotificationPolicy $notificationPolicy,
        private WebActionService $webAction,
    ) {
        $this->viewRenderer = $viewRenderer->withViewPath('@src/Core/Notification/views');
    }

    public function __invoke(ServerRequestInterface $request): ResponseInterface
    {
        if (!$this->notificationPolicy->canAccess()) {
            return $this->webAction->forbidden();
        }

        $query = $request->getQueryParams();
        $this->webAction->rememberCurrent('notification.index', $request);
        $reader = $this->notificationReader->getIndex(
            $this->webAction->sort($query, '-created_at'),
        );

        return $this->viewRenderer->render('index', [
            'reader' => $reader,
            'gridUrlCreator' => $this->webAction->gridUrlCreator('notification/index', $query),
        ]);
    }
}
