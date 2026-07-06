<?php

declare(strict_types=1);

namespace App\Mes\Task;

use App\Data\Core\Scope\OwnershipScopeInterface;
use Yiisoft\Db\Query\Query;

final readonly class TaskScope
{
    public function __construct(
        private OwnershipScopeInterface $ownershipScope,
    ) {}

    public function apply(Query $query): Query
    {
        return $this->ownershipScope->apply($query, TaskPolicy::GROUP, 'created_by');
    }
}
