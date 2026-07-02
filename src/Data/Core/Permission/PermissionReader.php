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

final readonly class PermissionReader
{
    public function __construct(
        private ConnectionInterface $db,
        private PermissionScope $scope,
    ) {}

    public function getIndex(
        array $filters = [],
        string $sort = '-id',
    ): QueryDataReader {
        $query = (new Query($this->db))
            ->select([
                'p.id',
                'p.group_id',
                'p.name',
                'p.code',
                'p.weight',
                'p.created_at',
                'p.updated_at',
                'p.created_by',
                'p.updated_by',
                'group_name' => 'pg.name',
                'group_code' => 'pg.code',
            ])
            ->from(['p' => '{{%core_permission}}'])
            ->leftJoin(['pg' => '{{%core_permission_group}}'], 'pg.id = p.group_id');

        $query = $this->scope->apply($query);

        $sortDefinition = Sort::only([
            'id' => [
                'asc' => ['p.id' => SORT_ASC],
                'desc' => ['p.id' => SORT_DESC],
            ],
            'name' => [
                'asc' => ['p.name' => SORT_ASC],
                'desc' => ['p.name' => SORT_DESC],
            ],
            'code' => [
                'asc' => ['p.code' => SORT_ASC],
                'desc' => ['p.code' => SORT_DESC],
            ],
            'weight' => [
                'asc' => ['p.weight' => SORT_ASC, 'p.name' => SORT_ASC],
                'desc' => ['p.weight' => SORT_DESC, 'p.name' => SORT_ASC],
            ],
            'group_name' => [
                'asc' => ['pg.name' => SORT_ASC, 'p.name' => SORT_ASC],
                'desc' => ['pg.name' => SORT_DESC, 'p.name' => SORT_ASC],
            ],
            'created_at' => [
                'asc' => ['p.created_at' => SORT_ASC],
                'desc' => ['p.created_at' => SORT_DESC],
            ],
        ]);

        $sortDefinition = $sort !== ''
            ? $sortDefinition->withOrderString($sort)
            : $sortDefinition->withOrder(['id' => 'desc']);

        $reader = new QueryDataReader(
            query: $query,
        );

        $reader = $reader->withFilter($this->buildFilter($filters));
        $reader = $reader->withSort($sortDefinition);

        return $reader;
    }

    public function getView(int $id): ?array
    {
        $query = (new Query($this->db))
            ->select([
                'p.id',
                'p.group_id',
                'p.name',
                'p.code',
                'p.weight',
                'p.created_at',
                'p.updated_at',
                'p.created_by',
                'p.updated_by',
                'group_name' => 'pg.name',
                'group_code' => 'pg.code',
            ])
            ->from(['p' => '{{%core_permission}}'])
            ->leftJoin(['pg' => '{{%core_permission_group}}'], 'pg.id = p.group_id')
            ->where(['p.id' => $id]);

        $row = $this->scope->apply($query)->one();

        return $row === null ? null : $row;
    }

    private function buildFilter(array $filters): All|AndX
    {
        $items = [];

        if (($filters['id'] ?? null) !== null) {
            $items[] = new Equals('p.id', (int) $filters['id']);
        }

        if (($filters['name'] ?? null) !== null) {
            $items[] = new Like('p.name', (string) $filters['name']);
        }

        if (($filters['code'] ?? null) !== null) {
            $items[] = new Like('p.code', (string) $filters['code']);
        }

        if (($filters['group_name'] ?? null) !== null) {
            $items[] = new Like('pg.name', (string) $filters['group_name']);
        }

        if (($filters['weight'] ?? null) !== null) {
            $items[] = new Equals('p.weight', (int) $filters['weight']);
        }

        if (($filters['created_at'] ?? null) !== null) {
            $items[] = new Like('p.created_at', (string) $filters['created_at']);
        }

        return $items === [] ? new All() : new AndX(...$items);
    }
}
