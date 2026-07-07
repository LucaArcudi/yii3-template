<?php

declare(strict_types=1);

use App\Core\NotFound\Actions\NotFoundHandler;
use App\Handlers\Middleware\Core\LocaleMiddleware;
use App\Handlers\Middleware\Core\PasswordExpiredMiddleware;
use App\Handlers\Middleware\Core\SameOriginRequestMiddleware;
use App\Handlers\Middleware\Core\SecurityHeadersMiddleware;
use App\Handlers\Middleware\Core\StatusPageMiddleware;
use Yiisoft\Cookies\CookieMiddleware;
use Yiisoft\Csrf\CsrfTokenMiddleware;
use Yiisoft\DataResponse\Middleware\FormatDataResponse;
use Yiisoft\Definitions\DynamicReference;
use Yiisoft\Definitions\Reference;
use Yiisoft\ErrorHandler\Middleware\ErrorCatcher;
use Yiisoft\Input\Http\HydratorAttributeParametersResolver;
use Yiisoft\Input\Http\RequestInputParametersResolver;
use Yiisoft\Middleware\Dispatcher\CompositeParametersResolver;
use Yiisoft\Middleware\Dispatcher\MiddlewareDispatcher;
use Yiisoft\Middleware\Dispatcher\ParametersResolverInterface;
use Yiisoft\RequestProvider\RequestCatcherMiddleware;
use Yiisoft\Router\Middleware\Router;
use Yiisoft\Session\SessionMiddleware;
use Yiisoft\User\Login\Cookie\CookieLoginMiddleware;
use Yiisoft\Yii\Http\Application;

/** @var array $params */

return [
    Application::class => [
        '__construct()' => [
            'dispatcher' => DynamicReference::to([
                'class' => MiddlewareDispatcher::class,
                'withMiddlewares()' => [
                    [
                        ErrorCatcher::class,
                        SecurityHeadersMiddleware::class,
                        LocaleMiddleware::class,
                        SessionMiddleware::class,
                        CookieMiddleware::class,
                        CookieLoginMiddleware::class,
                        PasswordExpiredMiddleware::class,
                        StatusPageMiddleware::class,
                        SameOriginRequestMiddleware::class,
                        CsrfTokenMiddleware::class,
                        FormatDataResponse::class,
                        RequestCatcherMiddleware::class,
                        Router::class,
                    ],
                ],
            ]),
            'fallbackHandler' => Reference::to(NotFoundHandler::class),
        ],
    ],

    ParametersResolverInterface::class => [
        'class' => CompositeParametersResolver::class,
        '__construct()' => [
            Reference::to(HydratorAttributeParametersResolver::class),
            Reference::to(RequestInputParametersResolver::class),
        ],
    ],
];
