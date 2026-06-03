<?php

declare(strict_types=1);

namespace App\Handlers\Web\Core\Notification;

use App\Data\Core\Notification\NotificationPolicy;
use App\Data\Core\Notification\NotificationReader;
use App\Services\Core\WebActionService;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Yiisoft\Yii\View\Renderer\WebViewRenderer;

final readonly class IndexAction
{
    public function __construct(
        private WebViewRenderer $viewRenderer,
        private NotificationReader $notificationReader,
        private NotificationPolicy $notificationPolicy,
        private WebActionService $webAction,
    ) {
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

        return $this->viewRenderer->render('core/notification/index', [
            'reader' => $reader,
            'gridUrlCreator' => $this->webAction->gridUrlCreator('notification/index', $query),
        ]);
    }
}
