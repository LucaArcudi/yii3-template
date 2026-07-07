<?php

declare(strict_types=1);

use App\Commands\Core\HelloCommand;
use App\Core\User\Commands\UserCreateCommand;

return [
    'hello' => HelloCommand::class,
    'user:create' => UserCreateCommand::class,
];
