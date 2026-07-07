<?php

declare(strict_types=1);

use App\Shared\Data\Scope\OwnershipScope;
use App\Shared\Data\Scope\OwnershipScopeInterface;
use App\Shared\Params\ApplicationParams;
use App\Shared\Params\AuthParams;
use App\Shared\Params\LayoutParams;
use App\Shared\Services\PolicyAccessResolver;
use Psr\Container\ContainerInterface;

/** @var array $params */

return [
    ApplicationParams::class => [
        '__construct()' => [
            'name' => $params['application']['name'],
            'charset' => $params['application']['charset'],
            'locale' => $params['application']['locale'],
        ],
    ],
    AuthParams::class => [
        '__construct()' => [
            'passwordMaxAgeDays' => $params['auth']['passwordMaxAgeDays'],
            'passwordResetTokenTtlMinutes' => $params['auth']['passwordResetTokenTtlMinutes'],
            'rateLimitWindowSeconds' => $params['auth']['rateLimitWindowSeconds'],
            'rateLimitBlockSeconds' => $params['auth']['rateLimitBlockSeconds'],
            'loginMaxAttempts' => $params['auth']['loginMaxAttempts'],
            'registrationMaxAttempts' => $params['auth']['registrationMaxAttempts'],
            'passwordResetMaxAttempts' => $params['auth']['passwordResetMaxAttempts'],
            'passwordChangeMaxAttempts' => $params['auth']['passwordChangeMaxAttempts'],
            'defaultRegistrationRoleCode' => $params['auth']['defaultRegistrationRoleCode'],
        ],
    ],
    LayoutParams::class => [
        '__construct()' => [
            'logo' => $params['layout']['logo'],
            'logoSmall' => $params['layout']['logoSmall'],
            'footerLeft' => $params['layout']['footerLeft'],
            'footerRight' => $params['layout']['footerRight'],
        ],
    ],
    PolicyAccessResolver::class => static fn(
        ContainerInterface $container,
    ): PolicyAccessResolver => new PolicyAccessResolver($container),
    OwnershipScopeInterface::class => OwnershipScope::class,
    OwnershipScope::class => OwnershipScope::class,
];
