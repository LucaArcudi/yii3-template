<?php

declare(strict_types=1);

use App\Shared\Middleware\TrustedProxyMiddleware;

/**
 * @var array $params
 */

return [
    TrustedProxyMiddleware::class => [
        '__construct()' => [
            'trustedIps' => $params['proxy']['trustedIps'],
        ],
    ],
];
