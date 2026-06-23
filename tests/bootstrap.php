<?php

declare(strict_types=1);

$_ENV['APP_ENV'] = 'test';
$_SERVER['APP_ENV'] = 'test';
putenv('APP_ENV=test');

$sessionPath = dirname(__DIR__) . '/runtime/sessions';

if (!is_dir($sessionPath)) {
    mkdir($sessionPath, 0777, true);
}

ini_set('session.save_path', $sessionPath);

App\Environment::prepare();
