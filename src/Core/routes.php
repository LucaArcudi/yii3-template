<?php

declare(strict_types=1);

use App\Core\Permission\Actions\CreateAction as PermissionCreateAction;
use App\Core\Permission\Actions\DeleteAction as PermissionDeleteAction;
use App\Core\Permission\Actions\IndexAction as PermissionIndexAction;
use App\Core\Permission\Actions\UpdateAction as PermissionUpdateAction;
use App\Core\Permission\Actions\ViewAction as PermissionViewAction;
use App\Core\Error\Actions\AccessDeniedHandler;
use App\Core\Error\Actions\InvalidRequestHandler;
use App\Core\Error\Actions\TooManyRequestsHandler;
use App\Core\PermissionGroup\Actions\CreateAction as PermissionGroupCreateAction;
use App\Core\PermissionGroup\Actions\DeleteAction as PermissionGroupDeleteAction;
use App\Core\PermissionGroup\Actions\IndexAction as PermissionGroupIndexAction;
use App\Core\PermissionGroup\Actions\UpdateAction as PermissionGroupUpdateAction;
use App\Core\PermissionGroup\Actions\ViewAction as PermissionGroupViewAction;
use App\Core\Notification\Actions\IndexAction as NotificationIndexAction;
use App\Core\Notification\Actions\OpenAction as NotificationOpenAction;
use App\Core\Notification\Actions\ReadAllAction as NotificationReadAllAction;
use App\Core\Role\Actions\CreateAction as RoleCreateAction;
use App\Core\Role\Actions\DeleteAction as RoleDeleteAction;
use App\Core\Role\Actions\IndexAction as RoleIndexAction;
use App\Core\Role\Actions\UpdateAction as RoleUpdateAction;
use App\Core\Role\Actions\ViewAction as RoleViewAction;
use App\Core\User\Actions\CreateAction as UserCreateAction;
use App\Core\User\Actions\DeleteAction as UserDeleteAction;
use App\Core\User\Actions\DeleteProfileAction;
use App\Core\User\Actions\ChangePasswordAction;
use App\Core\User\Actions\ForgotEmailAction;
use App\Core\User\Actions\ForgotPasswordAction;
use App\Core\User\Actions\IndexAction as UserIndexAction;
use App\Core\User\Actions\LoginAction;
use App\Core\User\Actions\LogoutAction;
use App\Core\User\Actions\ProfileAction;
use App\Core\User\Actions\RegisterAction;
use App\Core\User\Actions\UpdateAction as UserUpdateAction;
use App\Core\User\Actions\ViewAction as UserViewAction;
use App\Core\Home\Actions\IndexAction as HomeIndexAction;
use App\Core\Language\Actions\SwitchAction as LanguageSwitchAction;
use App\Shared\Middleware\RedirectGuestToLoginMiddleware;
use Yiisoft\Http\Method;
use Yiisoft\Router\Route;

// Module routes: collected automatically by config/common/routes.php.
// May contain Route or Group instances.
return [
    Route::get('/')
        ->middleware(RedirectGuestToLoginMiddleware::class)
        ->action(HomeIndexAction::class)
        ->name('home'),
    Route::get('/language/{locale:[a-z]{2}}')
        ->action(LanguageSwitchAction::class)
        ->name('language/switch'),
    Route::get('/access-denied')
        ->action(AccessDeniedHandler::class)
        ->name('error/access-denied'),
    Route::get('/too-many-requests')
        ->action(TooManyRequestsHandler::class)
        ->name('error/too-many-requests'),
    Route::get('/invalid-request')
        ->action(InvalidRequestHandler::class)
        ->name('error/invalid-request'),
    Route::get('/user')
        ->middleware(RedirectGuestToLoginMiddleware::class)
        ->action(UserIndexAction::class)
        ->name('user/index'),
    Route::get('/user/view/{id:\d+}')
        ->action(UserViewAction::class)
        ->name('user/view'),
    Route::methods([Method::GET, Method::POST], '/user/create')
        ->action(UserCreateAction::class)
        ->name('user/create'),
    Route::methods([Method::GET, Method::POST], '/user/update/{id:\d+}')
        ->action(UserUpdateAction::class)
        ->name('user/update'),
    Route::post('/user/delete/{id:\d+}')
        ->action(UserDeleteAction::class)
        ->name('user/delete'),
    Route::methods([Method::GET, Method::POST], '/profile')
        ->middleware(RedirectGuestToLoginMiddleware::class)
        ->action(ProfileAction::class)
        ->name('user/profile'),
    Route::post('/profile/delete')
        ->middleware(RedirectGuestToLoginMiddleware::class)
        ->action(DeleteProfileAction::class)
        ->name('user/profile-delete'),
    Route::get('/role')
        ->middleware(RedirectGuestToLoginMiddleware::class)
        ->action(RoleIndexAction::class)
        ->name('role/index'),
    Route::get('/role/view/{id:\d+}')
        ->action(RoleViewAction::class)
        ->name('role/view'),
    Route::methods([Method::GET, Method::POST], '/role/create')
        ->action(RoleCreateAction::class)
        ->name('role/create'),
    Route::methods([Method::GET, Method::POST], '/role/update/{id:\d+}')
        ->action(RoleUpdateAction::class)
        ->name('role/update'),
    Route::post('/role/delete/{id:\d+}')
        ->action(RoleDeleteAction::class)
        ->name('role/delete'),
    Route::get('/permission')
        ->middleware(RedirectGuestToLoginMiddleware::class)
        ->action(PermissionIndexAction::class)
        ->name('permission/index'),
    Route::get('/permission/view/{id:\d+}')
        ->action(PermissionViewAction::class)
        ->name('permission/view'),
    Route::methods([Method::GET, Method::POST], '/permission/create')
        ->action(PermissionCreateAction::class)
        ->name('permission/create'),
    Route::methods([Method::GET, Method::POST], '/permission/update/{id:\d+}')
        ->action(PermissionUpdateAction::class)
        ->name('permission/update'),
    Route::post('/permission/delete/{id:\d+}')
        ->action(PermissionDeleteAction::class)
        ->name('permission/delete'),
    Route::get('/permission-group')
        ->middleware(RedirectGuestToLoginMiddleware::class)
        ->action(PermissionGroupIndexAction::class)
        ->name('permission-group/index'),
    Route::get('/permission-group/view/{id:\d+}')
        ->action(PermissionGroupViewAction::class)
        ->name('permission-group/view'),
    Route::methods([Method::GET, Method::POST], '/permission-group/create')
        ->action(PermissionGroupCreateAction::class)
        ->name('permission-group/create'),
    Route::methods([Method::GET, Method::POST], '/permission-group/update/{id:\d+}')
        ->action(PermissionGroupUpdateAction::class)
        ->name('permission-group/update'),
    Route::post('/permission-group/delete/{id:\d+}')
        ->action(PermissionGroupDeleteAction::class)
        ->name('permission-group/delete'),
    Route::get('/notification')
        ->middleware(RedirectGuestToLoginMiddleware::class)
        ->action(NotificationIndexAction::class)
        ->name('notification/index'),
    Route::get('/notification/open/{id:\d+}')
        ->middleware(RedirectGuestToLoginMiddleware::class)
        ->action(NotificationOpenAction::class)
        ->name('notification/open'),
    Route::post('/notification/read-all')
        ->middleware(RedirectGuestToLoginMiddleware::class)
        ->action(NotificationReadAllAction::class)
        ->name('notification/read-all'),
    Route::methods([Method::GET, Method::POST], '/register')
        ->action(RegisterAction::class)
        ->name('auth/register'),
    Route::methods([Method::GET, Method::POST], '/login')
        ->action(LoginAction::class)
        ->name('auth/login'),
    Route::methods([Method::GET, Method::POST], '/forgot-password')
        ->action(ForgotPasswordAction::class)
        ->name('auth/forgot-password'),
    Route::get('/forgot-email')
        ->action(ForgotEmailAction::class)
        ->name('auth/forgot-email'),
    Route::methods([Method::GET, Method::POST], '/change-password')
        ->action(ChangePasswordAction::class)
        ->name('auth/change-password'),
    Route::post('/logout')
        ->action(LogoutAction::class)
        ->name('auth/logout'),
];
