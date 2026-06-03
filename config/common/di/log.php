<?php

declare(strict_types=1);

use App\Data\Core\Log\EntityLogRepository;
use App\Params\Core\EntityLogParams;
use Psr\Container\ContainerInterface;
use Yiisoft\Db\Connection\ConnectionInterface;
use Yiisoft\RequestProvider\RequestProviderInterface;

/** @var array $params */

return [
    EntityLogParams::class => [
        '__construct()' => [
            'enabled' => (bool) $params['entityLog']['enabled'],
            'webEnabled' => (bool) $params['entityLog']['webEnabled'],
            'consoleEnabled' => (bool) $params['entityLog']['consoleEnabled'],
            'systemEnabled' => (bool) $params['entityLog']['systemEnabled'],
        ],
    ],

    EntityLogRepository::class => static function (
        ConnectionInterface $db,
        ContainerInterface $container,
        EntityLogParams $entityLogParams,
    ): EntityLogRepository {
        $requestProvider = $container->has(RequestProviderInterface::class)
            ? $container->get(RequestProviderInterface::class)
            : null;

        return new EntityLogRepository($db, $requestProvider, $entityLogParams);
    },
];
