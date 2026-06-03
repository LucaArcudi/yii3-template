<?php

declare(strict_types=1);

use Yiisoft\Cookies\CookieEncryptor;
use Yiisoft\Cookies\CookieMiddleware;
use Yiisoft\Cookies\CookieSigner;

/** @var array $params */

return [
    CookieEncryptor::class => [
        '__construct()' => [
            'key' => $params['cookies']['secretKey'],
        ],
    ],
    CookieSigner::class => [
        '__construct()' => [
            'key' => $params['cookies']['secretKey'],
        ],
    ],
    CookieMiddleware::class => [
        '__construct()' => [
            'cookiesSettings' => [
                'autoLogin' => CookieMiddleware::ENCRYPT,
            ],
        ],
    ],
];
