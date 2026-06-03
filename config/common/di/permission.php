<?php

declare(strict_types=1);

use App\Data\Core\Permission\PermissionReader;
use App\Data\Core\Permission\PermissionGroupPolicy;
use App\Data\Core\Permission\PermissionGroupRepository;
use App\Data\Core\Permission\PermissionGroupReader;
use App\Data\Core\Permission\PermissionGroupScope;
use App\Data\Core\Permission\PermissionRepository;
use App\Data\Core\Permission\PermissionScope;

return [
    PermissionGroupPolicy::class => PermissionGroupPolicy::class,
    PermissionGroupRepository::class => PermissionGroupRepository::class,
    PermissionGroupReader::class => PermissionGroupReader::class,
    PermissionGroupScope::class => PermissionGroupScope::class,
    PermissionRepository::class => PermissionRepository::class,
    PermissionReader::class => PermissionReader::class,
    PermissionScope::class => PermissionScope::class,
];
