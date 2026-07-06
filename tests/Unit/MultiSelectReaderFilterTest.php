<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\Data\Core\Scope\OwnershipScopeInterface;
use App\Data\Core\User\UserFilter;
use App\Data\Core\User\UserReader;
use App\Data\Core\User\UserScope;
use App\Mes\Task\TaskFilter;
use App\Mes\Task\TaskReader;
use App\Mes\Task\TaskScope;
use Codeception\Test\Unit;
use Yiisoft\Cache\ArrayCache;
use Yiisoft\Db\Cache\SchemaCache;
use Yiisoft\Db\Connection\ConnectionInterface;
use Yiisoft\Db\Mysql\Connection;
use Yiisoft\Db\Mysql\Driver;
use Yiisoft\Db\Query\Query;
use Yiisoft\Validator\Validator;

use function getenv;

final class MultiSelectReaderFilterTest extends Unit
{
    public function testTaskStatusesUseAnySelectedStatus(): void
    {
        $sql = $this->taskReader()
            ->getIndex((new TaskFilter(new Validator()))->validate(['status' => ['0', '1']]), '')
            ->getPreparedQuery()
            ->createCommand()
            ->getRawSql();

        self::assertStringContainsString('WHERE `status` IN (0, 1)', $sql);
    }

    public function testTaskDateFiltersUseExactDateColumns(): void
    {
        $sql = $this->taskReader()
            ->getIndex((new TaskFilter(new Validator()))->validate([
                'start_date' => '2026-05-01',
                'end_date' => '2026-05-31',
            ]), '')
            ->getPreparedQuery()
            ->createCommand()
            ->getRawSql();

        self::assertStringContainsString('`start_date` = \'2026-05-01\'', $sql);
        self::assertStringContainsString('`end_date` = \'2026-05-31\'', $sql);
    }

    public function testUserRolesRequireEverySelectedRole(): void
    {
        $sql = $this->userReader()
            ->getIndex((new UserFilter(new Validator()))->validate(['role_ids' => ['1', '2']]), '')
            ->getPreparedQuery()
            ->createCommand()
            ->getRawSql();

        self::assertStringContainsString(
            'WHERE `id` IN (SELECT `user_id` FROM `core_user_role` WHERE `role_id` IN (1, 2) GROUP BY `user_id` HAVING COUNT(DISTINCT `role_id`) = 2)',
            $sql,
        );
    }

    private function db(): ConnectionInterface
    {
        $driver = new Driver(
            dsn: $this->env('DB_DSN', 'mysql:host=127.0.0.1;port=3306;dbname=yii3_template'),
            username: $this->env('DB_USERNAME', 'root'),
            password: $this->env('DB_PASSWORD', ''),
        );
        $driver->charset('utf8mb4');

        return new Connection($driver, new SchemaCache(new ArrayCache()));
    }

    private function env(string $name, string $default): string
    {
        $value = getenv($name);

        return $value === false ? $default : $value;
    }

    private function taskReader(): TaskReader
    {
        return new TaskReader($this->db(), new TaskScope($this->noopOwnershipScope()));
    }

    private function userReader(): UserReader
    {
        return new UserReader($this->db(), new UserScope($this->noopOwnershipScope()));
    }

    private function noopOwnershipScope(): OwnershipScopeInterface
    {
        return new class implements OwnershipScopeInterface {
            public function apply(Query $query, string $permissionGroup, string $ownerColumn = 'created_by'): Query
            {
                return $query;
            }
        };
    }
}
