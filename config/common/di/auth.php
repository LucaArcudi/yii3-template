<?php

declare(strict_types=1);

use App\Data\Core\User\UserIdentityRepository;
use Yiisoft\Auth\IdentityRepositoryInterface;

return [
    IdentityRepositoryInterface::class => UserIdentityRepository::class,
];