<?php

declare(strict_types=1);

use App\Handlers\Web\Core\Permission\CreateAction as PermissionCreateAction;
use App\Handlers\Web\Core\Permission\DeleteAction as PermissionDeleteAction;
use App\Handlers\Web\Core\Permission\IndexAction as PermissionIndexAction;
use App\Handlers\Web\Core\Permission\UpdateAction as PermissionUpdateAction;
use App\Handlers\Web\Core\Permission\ViewAction as PermissionViewAction;
use App\Handlers\Web\Core\Error\AccessDeniedHandler;
use App\Handlers\Web\Core\Error\InvalidRequestHandler;
use App\Handlers\Web\Core\Error\TooManyRequestsHandler;
use App\Handlers\Web\Core\PermissionGroup\CreateAction as PermissionGroupCreateAction;
use App\Handlers\Web\Core\PermissionGroup\DeleteAction as PermissionGroupDeleteAction;
use App\Handlers\Web\Core\PermissionGroup\IndexAction as PermissionGroupIndexAction;
use App\Handlers\Web\Core\PermissionGroup\UpdateAction as PermissionGroupUpdateAction;
use App\Handlers\Web\Core\PermissionGroup\ViewAction as PermissionGroupViewAction;
use App\Handlers\Web\Core\Notification\IndexAction as NotificationIndexAction;
use App\Handlers\Web\Core\Notification\OpenAction as NotificationOpenAction;
use App\Handlers\Web\Core\Notification\ReadAllAction as NotificationReadAllAction;
use App\Handlers\Web\Core\Role\CreateAction as RoleCreateAction;
use App\Handlers\Web\Core\Role\DeleteAction as RoleDeleteAction;
use App\Handlers\Web\Core\Role\IndexAction as RoleIndexAction;
use App\Handlers\Web\Core\Role\UpdateAction as RoleUpdateAction;
use App\Handlers\Web\Core\Role\ViewAction as RoleViewAction;
use App\Handlers\Web\Mes\Task\CreateAction;
use App\Handlers\Web\Mes\Task\DeleteAction;
use App\Handlers\Web\Mes\Task\IndexAction;
use App\Handlers\Web\Mes\Task\UpdateAction;
use App\Handlers\Web\Mes\Task\ViewAction;
use App\Handlers\Web\Core\User\CreateAction as UserCreateAction;
use App\Handlers\Web\Core\User\DeleteAction as UserDeleteAction;
use App\Handlers\Web\Core\User\DeleteProfileAction;
use App\Handlers\Web\Core\User\ChangePasswordAction;
use App\Handlers\Web\Core\User\ForgotEmailAction;
use App\Handlers\Web\Core\User\ForgotPasswordAction;
use App\Handlers\Web\Core\User\IndexAction as UserIndexAction;
use App\Handlers\Web\Core\User\LoginAction;
use App\Handlers\Web\Core\User\LogoutAction;
use App\Handlers\Web\Core\User\ProfileAction;
use App\Handlers\Web\Core\User\RegisterAction;
use App\Handlers\Web\Core\User\UpdateAction as UserUpdateAction;
use App\Handlers\Web\Core\User\ViewAction as UserViewAction;
use App\Handlers\Web\Core\Home\IndexAction as HomeIndexAction;
use App\Handlers\Middleware\Core\RedirectGuestToLoginMiddleware;
use Yiisoft\Http\Method;
use Yiisoft\Router\Group;
use Yiisoft\Router\Route;

return [
    Group::create()
        ->routes(
            Route::get('/')
                ->middleware(RedirectGuestToLoginMiddleware::class)
                ->action(HomeIndexAction::class)
                ->name('home'),
            Route::get('/access-denied')
                ->action(AccessDeniedHandler::class)
                ->name('error/access-denied'),
            Route::get('/too-many-requests')
                ->action(TooManyRequestsHandler::class)
                ->name('error/too-many-requests'),
            Route::get('/invalid-request')
                ->action(InvalidRequestHandler::class)
                ->name('error/invalid-request'),
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
        ),
];
