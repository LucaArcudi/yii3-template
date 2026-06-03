<?php

declare(strict_types=1);

use App\Data\Core\Role\RoleReader;
use App\Data\Core\Role\RoleRepository;
use App\Data\Core\Role\RoleScope;

return [
    RoleRepository::class => RoleRepository::class,
    RoleReader::class => RoleReader::class,
    RoleScope::class => RoleScope::class,
];
