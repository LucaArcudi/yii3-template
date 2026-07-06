<?php

declare(strict_types=1);

use App\Mes\Task\TaskReader;
use App\Mes\Task\TaskRepository;
use App\Mes\Task\TaskScope;

// Module DI definitions: collected automatically by config/common/di/modules.php.
return [
    TaskRepository::class => TaskRepository::class,
    TaskReader::class => TaskReader::class,
    TaskScope::class => TaskScope::class,
];
