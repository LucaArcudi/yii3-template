<?php

declare(strict_types=1);

use App\Data\Mes\Task\TaskReader;
use App\Data\Mes\Task\TaskRepository;
use App\Data\Mes\Task\TaskScope;
use Yiisoft\Validator\Validator;
use Yiisoft\Validator\ValidatorInterface;

return [
    ValidatorInterface::class => Validator::class,

    TaskRepository::class => TaskRepository::class,
    TaskReader::class => TaskReader::class,
    TaskScope::class => TaskScope::class,
];
