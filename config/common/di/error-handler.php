<?php

declare(strict_types=1);

use App\Environment;
use App\Shared\Middleware\StatusPageMiddleware;
use App\Core\Error\Actions\AccessDeniedHandler;
use App\Core\Error\Actions\InvalidRequestHandler;
use App\Core\Error\Actions\TooManyRequestsHandler;
use Yiisoft\Definitions\Reference;
use Yiisoft\ErrorHandler\Renderer\HtmlRenderer;

/**
 * @var array $params
 */

return [
    StatusPageMiddleware::class => [
        '__construct()' => [
            'accessDeniedHandler' => Reference::to(AccessDeniedHandler::class),
            'tooManyRequestsHandler' => Reference::to(TooManyRequestsHandler::class),
            'invalidRequestHandler' => Reference::to(InvalidRequestHandler::class),
        ],
    ],

    HtmlRenderer::class => [
        '__construct()' => [
            'traceLink' => static function (string $file, int|null $line) use ($params): string|null {
                if (!isset($params['traceLink'])) {
                    return null;
                }

                try {
                    $hostPath = Environment::appHostPath();
                    if ($hostPath !== null) {
                        /** @var string $file */
                        $file = preg_replace('~^(/app/)~', rtrim($hostPath, '\\/') . '/', $file);
                    }
                    return str_replace(
                        ['{file}', '{line}'],
                        [$file, (string) $line],
                        $params['traceLink'],
                    );
                } catch (Throwable) {
                    return null;
                }
            },
        ],
    ],
];
