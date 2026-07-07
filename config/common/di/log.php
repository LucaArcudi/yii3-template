<?php

declare(strict_types=1);

use App\Shared\Params\EntityLogParams;

/** @var array $params */

return [
    EntityLogParams::class => [
        '__construct()' => [
            'enabled' => (bool) $params['entityLog']['enabled'],
            'webEnabled' => (bool) $params['entityLog']['webEnabled'],
            'consoleEnabled' => (bool) $params['entityLog']['consoleEnabled'],
            'systemEnabled' => (bool) $params['entityLog']['systemEnabled'],
        ],
    ],
];
