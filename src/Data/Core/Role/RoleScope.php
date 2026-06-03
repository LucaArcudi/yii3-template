<?php

declare(strict_types=1);

namespace App\Data\Core\Role;

use App\Data\Core\Scope\OwnershipScopeInterface;
use Yiisoft\Db\Query\Query;

final readonly class RoleScope
{
    public function __construct(
        private OwnershipScopeInterface $ownershipScope,
    ) {
    }

    public function apply(Query $query): Query
    {
        return $this->ownershipScope->apply($query, RolePolicy::GROUP, 'created_by');
    }
}
