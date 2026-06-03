<?php

declare(strict_types=1);

namespace App\Data\Mes\Task;

use Yiisoft\Data\Db\QueryDataReader;
use Yiisoft\Data\Reader\Filter\All;
use Yiisoft\Data\Reader\Filter\AndX;
use Yiisoft\Data\Reader\Filter\Equals;
use Yiisoft\Data\Reader\Filter\In;
use Yiisoft\Data\Reader\Filter\Like;
use Yiisoft\Data\Reader\Sort;
use Yiisoft\Db\Connection\ConnectionInterface;
use Yiisoft\Db\Query\Query;

use function is_array;

final readonly class TaskReader
{
    public function __construct(
        private ConnectionInterface $db,
        private TaskScope $scope,
    ) {
    }

    public function getIndex(
        array $filters = [],
        string $sort = '-id',
    ): QueryDataReader {
        $query = (new Query($this->db))
            ->select([
                'id',
                'title',
                'description',
                'status',
                'start_date',
                'end_date',
                'created_at',
                'updated_at',
                'created_by',
                'updated_by',
            ])
            ->from('{{%mes_task}}');

        $query = $this->scope->apply($query);

        $sortDefinition = Sort::only([
            'id',
            'title',
            'description',
            'status',
            'start_date',
            'end_date',
            'created_at',
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
                'id',
                'title',
                'description',
                'status',
                'start_date',
                'end_date',
                'created_at',
                'updated_at',
                'created_by',
                'updated_by',
            ])
            ->from('{{%mes_task}}')
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

        if (($filters['title'] ?? null) !== null) {
            $items[] = new Like('title', (string) $filters['title']);
        }

        $statuses = $filters['status'] ?? [];
        if (is_array($statuses) && $statuses !== []) {
            $items[] = new In('status', $statuses);
        }

        if (($filters['description'] ?? null) !== null) {
            $items[] = new Like('description', (string) $filters['description']);
        }

        if (($filters['start_date'] ?? null) !== null) {
            $items[] = new Equals('start_date', (string) $filters['start_date']);
        }

        if (($filters['end_date'] ?? null) !== null) {
            $items[] = new Equals('end_date', (string) $filters['end_date']);
        }

        if (($filters['created_at'] ?? null) !== null) {
            $items[] = new Like('created_at', (string) $filters['created_at']);
        }

        return $items === [] ? new All() : new AndX(...$items);
    }
}
