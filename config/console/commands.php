<?php

declare(strict_types=1);

use App\Shared\Commands\HelloCommand;
use App\Core\User\Commands\UserCreateCommand;

return [
    'hello' => HelloCommand::class,
    'user:create' => UserCreateCommand::class,
];
