<?php

declare(strict_types=1);

namespace App\Core\Notification\Actions;

use App\Core\Notification\NotificationPolicy;
use App\Core\Notification\NotificationRepository;
use App\Services\Core\WebActionService;
use Psr\Http\Message\ResponseInterface;
use Yiisoft\Router\HydratorAttribute\RouteArgument;
use Yiisoft\User\CurrentUser;

final readonly class OpenAction
{
    public function __construct(
        private NotificationRepository $notificationRepository,
        private NotificationPolicy $notificationPolicy,
        private CurrentUser $currentUser,
        private WebActionService $webAction,
    ) {}

    public function __invoke(#[RouteArgument('id')] int $id): ResponseInterface
    {
        if (!$this->notificationPolicy->canUse()) {
            return $this->webAction->forbidden();
        }

        $userId = (int) $this->currentUser->getId();
        $url = $this->notificationRepository->urlForUser($id, $userId);

        if ($url === null) {
            return $this->webAction->notFound();
        }

        $this->notificationRepository->markRead($id, $userId, $userId);

        return $this->webAction->redirect($url);
    }
}
