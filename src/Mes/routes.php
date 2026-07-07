<?php

declare(strict_types=1);

use App\Shared\Middleware\RedirectGuestToLoginMiddleware;
use App\Mes\Task\Actions\CreateAction;
use App\Mes\Task\Actions\DeleteAction;
use App\Mes\Task\Actions\IndexAction;
use App\Mes\Task\Actions\UpdateAction;
use App\Mes\Task\Actions\ViewAction;
use Yiisoft\Http\Method;
use Yiisoft\Router\Route;

// Module routes: collected automatically by config/common/routes.php.
// May contain Route or Group instances.
return [
    Route::get('/task')
        ->middleware(RedirectGuestToLoginMiddleware::class)
        ->action(IndexAction::class)
        ->name('task/index'),
    Route::get('/task/view/{id:\d+}')
        ->action(ViewAction::class)
        ->name('task/view'),
    Route::methods([Method::GET, Method::POST], '/task/create')
        ->action(CreateAction::class)
        ->name('task/create'),
    Route::methods([Method::GET, Method::POST], '/task/update/{id:\d+}')
        ->action(UpdateAction::class)
        ->name('task/update'),
    Route::post('/task/delete/{id:\d+}')
        ->action(DeleteAction::class)
        ->name('task/delete'),
];
