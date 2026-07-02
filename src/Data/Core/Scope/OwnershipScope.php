<?php

declare(strict_types=1);

namespace App\Data\Core\Scope;

use App\Services\Core\AuthorizationService;
use Yiisoft\Db\Expression\Expression;
use Yiisoft\Db\Query\Query;
use Yiisoft\User\CurrentUser;

final readonly class OwnershipScope implements OwnershipScopeInterface
{
    public const VIEW_ALL = 'VIEW_ALL';
    public const VIEW_OWN = 'VIEW_OWN';
    public const ACCESS = 'ACCESS';

    public function __construct(
        private CurrentUser $currentUser,
        private AuthorizationService $authorizationService,
    ) {}

    public function apply(Query $query, string $permissionGroup, string $ownerColumn = 'created_by'): Query
    {
        if ($this->currentUser->isGuest()) {
            return $this->deny($query);
        }

        $userId = $this->currentUser->getId();

        if ($userId === null || $userId === '') {
            return $this->deny($query);
        }

        if (
            $this->hasPermission($userId, $permissionGroup, self::VIEW_ALL)
        ) {
            return $query;
        }

        if (
            $this->hasPermission($userId, $permissionGroup, self::VIEW_OWN)
        ) {
            return $query->andWhere([$ownerColumn => $userId]);
        }

        return $this->deny($query);
    }

    private function deny(Query $query): Query
    {
        return $query->andWhere(new Expression('1 = 0'));
    }

    private function hasPermission(int|string $userId, string $permissionGroup, string $action): bool
    {
        return $this->authorizationService->userHasPermission(
            $userId,
            $permissionGroup,
            $permissionGroup . '_' . $action,
        );
    }
}
