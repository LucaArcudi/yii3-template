<?php

declare(strict_types=1);

namespace App\Core\Permission;

use App\Shared\Data\Scope\OwnershipScopeInterface;
use Yiisoft\Db\Query\Query;

final readonly class PermissionScope
{
    public function __construct(
        private OwnershipScopeInterface $ownershipScope,
    ) {}

    public function apply(Query $query): Query
    {
        return $this->ownershipScope->apply($query, PermissionPolicy::GROUP, 'p.created_by');
    }
}
