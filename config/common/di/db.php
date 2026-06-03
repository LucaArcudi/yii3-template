<?php

declare(strict_types=1);

use Psr\SimpleCache\CacheInterface;
use Yiisoft\Cache\ArrayCache;
use Yiisoft\Db\Cache\SchemaCache;
use Yiisoft\Db\Connection\ConnectionInterface;
use Yiisoft\Db\Mysql\Connection;
use Yiisoft\Db\Mysql\Driver;

return [
    CacheInterface::class => ArrayCache::class,

    SchemaCache::class => static fn (CacheInterface $cache) => new SchemaCache($cache),

    ConnectionInterface::class => static function (SchemaCache $schemaCache): ConnectionInterface {
        $driver = new Driver(
            dsn: $_ENV['DB_DSN'] ?? 'mysql:host=127.0.0.1;port=3306;dbname=yii3_template',
            username: $_ENV['DB_USERNAME'] ?? 'root',
            password: $_ENV['DB_PASSWORD'] ?? '',
        );

        $driver->charset('utf8mb4');

        return new Connection($driver, $schemaCache);
    },
];