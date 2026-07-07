<?php

declare(strict_types=1);

namespace App\Core\User;

use App\Shared\Data\AccessPolicyInterface;
use App\Shared\Services\AuthorizationService;
use Yiisoft\User\CurrentUser;

final readonly class UserPolicy implements AccessPolicyInterface
{
    public const GROUP = 'USER';
    public const ACCESS = 'ACCESS';
    public const VIEW_ALL = 'VIEW_ALL';
    public const VIEW_OWN = 'VIEW_OWN';
    public const CREATE = 'CREATE';
    public const UPDATE = 'UPDATE';
    public const DELETE = 'DELETE';

    public function __construct(
        private CurrentUser $currentUser,
        private AuthorizationService $authorizationService,
    ) {}

    public function canAccess(): bool
    {
        return $this->can(self::ACCESS);
    }

    public function canView(): bool
    {
        return $this->canViewAll() || $this->canViewOwn();
    }

    public function canViewAll(): bool
    {
        return $this->can(self::VIEW_ALL);
    }

    public function canViewOwn(): bool
    {
        return $this->can(self::VIEW_OWN);
    }

    public function canCreate(): bool
    {
        return $this->can(self::CREATE);
    }

    public function canUpdate(): bool
    {
        return $this->can(self::UPDATE);
    }

    public function canDelete(): bool
    {
        return $this->can(self::DELETE);
    }

    private function can(string $action): bool
    {
        if ($this->currentUser->isGuest()) {
            return false;
        }

        $userId = $this->currentUser->getId();

        if ($userId === null || $userId === '') {
            return false;
        }

        return $this->authorizationService->userHasPermission($userId, self::GROUP, self::GROUP . '_' . $action);
    }
}
