<?php

declare(strict_types=1);

return [
    'yiisoft/yii-console' => [
        'commands' => require __DIR__ . '/commands.php',
    ],

    'yiisoft/db-migration' => [
        'newMigrationNamespace' => 'App\\Migrations',
        'sourceNamespaces' => ['App\\Migrations'],
    ],
];
