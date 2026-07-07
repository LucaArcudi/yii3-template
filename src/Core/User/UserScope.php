<?php

declare(strict_types=1);

namespace App\Core\User;

use App\Data\Core\Scope\OwnershipScopeInterface;
use Yiisoft\Db\Query\Query;

final readonly class UserScope
{
    public function __construct(
        private OwnershipScopeInterface $ownershipScope,
    ) {}

    public function apply(Query $query): Query
    {
        return $this->ownershipScope->apply($query, UserPolicy::GROUP, 'created_by');
    }
}
