<?php

declare(strict_types=1);

use App\Data\Core\Scope\OwnershipScope;
use App\Data\Core\Scope\OwnershipScopeInterface;
use App\Params\Core\ApplicationParams;
use App\Params\Core\AuthParams;
use App\Params\Core\LayoutParams;

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
    OwnershipScopeInterface::class => OwnershipScope::class,
    OwnershipScope::class => OwnershipScope::class,
];
