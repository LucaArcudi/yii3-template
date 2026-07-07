<?php

declare(strict_types=1);

namespace App\Shared\Middleware;

use App\Shared\Helpers\AppLocales;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Yiisoft\Translator\TranslatorInterface;

/**
 * Imposta la lingua del translator a partire dal cookie di preferenza,
 * con fallback sulla lingua predefinita dell'applicazione.
 */
final readonly class LocaleMiddleware implements MiddlewareInterface
{
    public const COOKIE_NAME = 'locale';

    public function __construct(
        private TranslatorInterface $translator,
    ) {}

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $requested = $request->getCookieParams()[self::COOKIE_NAME] ?? '';
        $locale = is_string($requested) && AppLocales::isSupported($requested)
            ? $requested
            : AppLocales::DEFAULT;

        $this->translator->setLocale($locale);

        return $handler->handle($request);
    }
}
