<?php

declare(strict_types=1);

namespace App\Data\Core\Log;

use App\Services\Core\AuthorizationService;
use Yiisoft\User\CurrentUser;

final readonly class LogPolicy
{
    public const GROUP = 'LOG';
    public const ACCESS = 'ACCESS';

    public function __construct(
        private CurrentUser $currentUser,
        private AuthorizationService $authorizationService,
    ) {
    }

    public function canAccess(): bool
    {
        if ($this->currentUser->isGuest()) {
            return false;
        }

        $userId = $this->currentUser->getId();

        return $this->authorizationService->userHasPermission($userId, self::GROUP, self::GROUP . '_' . self::ACCESS);
    }
}
