<?php

declare(strict_types=1);

namespace App\Data\Core\Permission;

use Yiisoft\Data\Db\QueryDataReader;
use Yiisoft\Data\Reader\Filter\All;
use Yiisoft\Data\Reader\Filter\AndX;
use Yiisoft\Data\Reader\Filter\Equals;
use Yiisoft\Data\Reader\Filter\Like;
use Yiisoft\Data\Reader\Sort;
use Yiisoft\Db\Connection\ConnectionInterface;
use Yiisoft\Db\Query\Query;

final readonly class PermissionGroupReader
{
    public function __construct(
        private ConnectionInterface $db,
        private PermissionGroupScope $scope,
    ) {
    }

    public function getIndex(array $filters = [], string $sort = '-id'): QueryDataReader
    {
        $query = (new Query($this->db))
            ->select([
                'id',
                'name',
                'code',
                'created_at',
                'updated_at',
                'created_by',
                'updated_by',
            ])
            ->from('{{%core_permission_group}}');

        $query = $this->scope->apply($query);

        $sortDefinition = Sort::only([
            'id',
            'name',
            'code',
            'created_at',
        ]);

        $sortDefinition = $sort !== ''
            ? $sortDefinition->withOrderString($sort)
            : $sortDefinition->withOrder(['id' => 'desc']);

        $reader = new QueryDataReader(query: $query);
        $reader = $reader->withFilter($this->buildFilter($filters));
        $reader = $reader->withSort($sortDefinition);

        return $reader;
    }

    public function getView(int $id): ?array
    {
        $query = (new Query($this->db))
            ->select([
                'id',
                'name',
                'code',
                'created_at',
                'updated_at',
                'created_by',
                'updated_by',
            ])
            ->from('{{%core_permission_group}}')
            ->where(['id' => $id]);

        $row = $this->scope->apply($query)->one();

        return $row === null ? null : $row;
    }

    private function buildFilter(array $filters): All|AndX
    {
        $items = [];

        if (($filters['id'] ?? null) !== null) {
            $items[] = new Equals('id', (int) $filters['id']);
        }

        if (($filters['name'] ?? null) !== null) {
            $items[] = new Like('name', (string) $filters['name']);
        }

        if (($filters['code'] ?? null) !== null) {
            $items[] = new Like('code', (string) $filters['code']);
        }

        if (($filters['created_at'] ?? null) !== null) {
            $items[] = new Like('created_at', (string) $filters['created_at']);
        }

        return $items === [] ? new All() : new AndX(...$items);
    }
}
