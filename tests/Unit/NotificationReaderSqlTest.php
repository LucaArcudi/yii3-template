<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\Data\Core\Notification\NotificationReader;
use App\Data\Core\User\UserIdentity;
use Codeception\Test\Unit;
use Psr\EventDispatcher\EventDispatcherInterface;
use Yiisoft\Auth\IdentityInterface;
use Yiisoft\Auth\IdentityRepositoryInterface;
use Yiisoft\Cache\ArrayCache;
use Yiisoft\Db\Cache\SchemaCache;
use Yiisoft\Db\Connection\ConnectionInterface;
use Yiisoft\Db\Mysql\Connection;
use Yiisoft\Db\Mysql\Driver;
use Yiisoft\User\CurrentUser;

final class NotificationReaderSqlTest extends Unit
{
    public function testNotificationJoinUsesAliasColumnInsteadOfQuotedLiteral(): void
    {
        $sql = (new NotificationReader($this->db(), $this->currentUser()))
            ->getIndex()
            ->getPreparedQuery()
            ->createCommand()
            ->getRawSql();

        self::assertStringContainsString('ON nu.notification_id = n.id', $sql);
        self::assertStringNotContainsString('`nu.notification_id`', $sql);
    }

    public function testIndexCountExecutesAgainstCurrentSchema(): void
    {
        $count = (new NotificationReader($this->db(), $this->currentUser()))
            ->getIndex()
            ->count();

        self::assertGreaterThanOrEqual(0, $count);
    }

    private function db(): ConnectionInterface
    {
        $driver = new Driver(
            dsn: 'mysql:host=127.0.0.1;port=3306;dbname=yii3_template',
            username: 'root',
            password: '',
        );
        $driver->charset('utf8mb4');

        return new Connection($driver, new SchemaCache(new ArrayCache()));
    }

    private function currentUser(): CurrentUser
    {
        $currentUser = new CurrentUser(
            new class () implements IdentityRepositoryInterface {
                public function findIdentity(string $id): ?IdentityInterface
                {
                    return null;
                }
            },
            new class () implements EventDispatcherInterface {
                public function dispatch(object $event): object
                {
                    return $event;
                }
            },
        );

        $currentUser->overrideIdentity(new UserIdentity('8', 'user@example.test', 'User'));

        return $currentUser;
    }
}
