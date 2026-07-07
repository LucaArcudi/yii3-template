<?php

declare(strict_types=1);

use App\Core\Log\EntityLogRepository;
use App\Core\Notification\NotificationReader;
use App\Core\Notification\NotificationRepository;
use App\Core\Permission\PermissionReader;
use App\Core\Permission\PermissionRepository;
use App\Core\Permission\PermissionScope;
use App\Core\PermissionGroup\PermissionGroupPolicy;
use App\Core\PermissionGroup\PermissionGroupReader;
use App\Core\PermissionGroup\PermissionGroupRepository;
use App\Core\PermissionGroup\PermissionGroupScope;
use App\Core\Role\RoleReader;
use App\Core\Role\RoleRepository;
use App\Core\Role\RoleScope;
use App\Core\User\UserIdentityRepository;
use App\Core\User\UserReader;
use App\Core\User\UserScope;
use App\Params\Core\EntityLogParams;
use Psr\Container\ContainerInterface;
use Yiisoft\Auth\IdentityRepositoryInterface;
use Yiisoft\Db\Connection\ConnectionInterface;
use Yiisoft\RequestProvider\RequestProviderInterface;

// Module DI definitions: collected automatically by config/common/di/modules.php.
return [
    IdentityRepositoryInterface::class => UserIdentityRepository::class,

    UserReader::class => UserReader::class,
    UserScope::class => UserScope::class,

    RoleRepository::class => RoleRepository::class,
    RoleReader::class => RoleReader::class,
    RoleScope::class => RoleScope::class,

    PermissionRepository::class => PermissionRepository::class,
    PermissionReader::class => PermissionReader::class,
    PermissionScope::class => PermissionScope::class,

    PermissionGroupPolicy::class => PermissionGroupPolicy::class,
    PermissionGroupRepository::class => PermissionGroupRepository::class,
    PermissionGroupReader::class => PermissionGroupReader::class,
    PermissionGroupScope::class => PermissionGroupScope::class,

    NotificationReader::class => NotificationReader::class,
    NotificationRepository::class => NotificationRepository::class,

    EntityLogRepository::class => static function (
        ConnectionInterface $db,
        ContainerInterface $container,
        EntityLogParams $entityLogParams,
    ): EntityLogRepository {
        /** @var RequestProviderInterface|null $requestProvider */
        $requestProvider = $container->has(RequestProviderInterface::class)
            ? $container->get(RequestProviderInterface::class)
            : null;

        return new EntityLogRepository($db, $requestProvider, $entityLogParams);
    },
];
