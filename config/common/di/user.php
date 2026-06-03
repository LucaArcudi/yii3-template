<?php

declare(strict_types=1);

use App\Data\Core\User\UserReader;
use App\Data\Core\User\UserScope;

return [
    UserReader::class => UserReader::class,
    UserScope::class => UserScope::class,
];
