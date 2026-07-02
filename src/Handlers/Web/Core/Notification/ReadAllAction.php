<?php

declare(strict_types=1);

namespace App\Handlers\Web\Core\Notification;

use App\Data\Core\Notification\NotificationPolicy;
use App\Data\Core\Notification\NotificationRepository;
use App\Services\Core\WebActionService;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Yiisoft\User\CurrentUser;

final readonly class ReadAllAction
{
    public function __construct(
        private NotificationRepository $notificationRepository,
        private NotificationPolicy $notificationPolicy,
        private CurrentUser $currentUser,
        private WebActionService $webAction,
    ) {}

    public function __invoke(ServerRequestInterface $request): ResponseInterface
    {
        if (!$this->notificationPolicy->canUse()) {
            return $this->webAction->forbidden();
        }

        $userId = (int) $this->currentUser->getId();
        $this->notificationRepository->markAllRead($userId, $userId);

        return $this->webAction->redirect($this->webAction->previous('notification.index', '/notification'));
    }
}
