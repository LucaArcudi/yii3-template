<?php

declare(strict_types=1);

namespace App\Data\Core\User;

use Yiisoft\Data\Db\QueryDataReader;
use Yiisoft\Data\Reader\Filter\All;
use Yiisoft\Data\Reader\Filter\AndX;
use Yiisoft\Data\Reader\Filter\Equals;
use Yiisoft\Data\Reader\Filter\Like;
use Yiisoft\Data\Reader\Sort;
use Yiisoft\Db\Connection\ConnectionInterface;
use Yiisoft\Db\Expression\Expression;
use Yiisoft\Db\Query\Query;

use function is_array;

final readonly class UserReader
{
    public function __construct(
        private ConnectionInterface $db,
        private UserScope $scope,
    ) {}

    public function getIndex(
        array $filters = [],
        string $sort = '-id',
    ): QueryDataReader {
        $query = (new Query($this->db))
            ->select([
                'id',
                'email',
                'name',
                'status',
                'created_at',
                'updated_at',
                'last_login_at',
                'password_changed_at',
                'password_expires_at',
                'created_by',
                'updated_by',
            ])
            ->from('{{%core_user}}');

        $roleIds = is_array($filters['role_ids'] ?? null) ? $filters['role_ids'] : [];

        if ($roleIds !== []) {
            $matchingUsers = (new Query($this->db))
                ->select(['user_id'])
                ->from('{{%core_user_role}}')
                ->where(['role_id' => $roleIds])
                ->groupBy(['user_id'])
                ->having(new Expression('COUNT(DISTINCT [[role_id]]) = ' . count($roleIds)));

            $query = $query->andWhere(['id' => $matchingUsers]);
        }

        $query = $this->scope->apply($query);

        $sortDefinition = Sort::only([
            'id',
            'email',
            'name',
            'status',
            'created_at',
            'last_login_at',
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
                'email',
                'name',
                'status',
                'created_at',
                'updated_at',
                'last_login_at',
                'password_changed_at',
                'password_expires_at',
                'created_by',
                'updated_by',
            ])
            ->from('{{%core_user}}')
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

        if (($filters['email'] ?? null) !== null) {
            $items[] = new Like('email', (string) $filters['email']);
        }

        if (($filters['name'] ?? null) !== null) {
            $items[] = new Like('name', (string) $filters['name']);
        }

        if (($filters['status'] ?? null) !== null) {
            $items[] = new Equals('status', (int) $filters['status']);
        }

        if (($filters['last_login_at'] ?? null) !== null) {
            $items[] = new Like('last_login_at', (string) $filters['last_login_at']);
        }

        return $items === [] ? new All() : new AndX(...$items);
    }
}
