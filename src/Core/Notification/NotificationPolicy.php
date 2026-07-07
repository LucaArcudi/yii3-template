<?php

declare(strict_types=1);

namespace App\Core\Notification;

use App\Data\AccessPolicyInterface;
use App\Services\Core\AuthorizationService;
use Yiisoft\User\CurrentUser;

final readonly class NotificationPolicy implements AccessPolicyInterface
{
    public const GROUP = 'NOTIFICATION';
    public const ACCESS = 'ACCESS';

    public function __construct(
        private CurrentUser $currentUser,
        private AuthorizationService $authorizationService,
    ) {}

    public function canUse(): bool
    {
        return $this->currentUserId() !== null;
    }

    public function canAccess(): bool
    {
        $userId = $this->currentUserId();

        if ($userId === null) {
            return false;
        }

        return $this->authorizationService->userHasPermission(
            $userId,
            self::GROUP,
            self::GROUP . '_' . self::ACCESS,
        );
    }

    private function currentUserId(): int|string|null
    {
        if ($this->currentUser->isGuest()) {
            return null;
        }

        $userId = $this->currentUser->getId();

        return $userId === null || $userId === '' ? null : $userId;
    }
}
