<?php

declare(strict_types=1);

namespace App\Data\Core\Scope;

use Yiisoft\Db\Query\Query;

interface OwnershipScopeInterface
{
    public function apply(Query $query, string $permissionGroup, string $ownerColumn = 'created_by'): Query;
}
