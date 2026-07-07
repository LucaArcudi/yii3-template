<?php

declare(strict_types=1);

namespace App\Core\PermissionGroup;

use App\Shared\Data\Scope\OwnershipScopeInterface;
use Yiisoft\Db\Query\Query;

final readonly class PermissionGroupScope
{
    public function __construct(
        private OwnershipScopeInterface $ownershipScope,
    ) {}

    public function apply(Query $query): Query
    {
        return $this->ownershipScope->apply($query, PermissionGroupPolicy::GROUP, 'created_by');
    }
}
