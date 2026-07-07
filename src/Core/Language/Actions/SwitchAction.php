<?php

declare(strict_types=1);

namespace App\Core\Language\Actions;

use App\Handlers\Middleware\Core\LocaleMiddleware;
use App\Helpers\AppLocales;
use App\Services\Core\RememberedUrlService;
use DateInterval;
use DateTimeImmutable;
use HttpSoft\Message\Response;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Yiisoft\Cookies\Cookie;
use Yiisoft\Router\CurrentRoute;

/**
 * Cambia la lingua dell'interfaccia salvando la preferenza in un cookie
 * e torna alla pagina di provenienza (solo same-origin).
 */
final readonly class SwitchAction
{
    private const COOKIE_DURATION = 'P1Y';

    public function __construct(
        private RememberedUrlService $rememberedUrl,
    ) {}

    public function __invoke(ServerRequestInterface $request, CurrentRoute $currentRoute): ResponseInterface
    {
        $locale = (string) $currentRoute->getArgument('locale');
        $response = (new Response(302))
            ->withHeader('Location', $this->rememberedUrl->fromReferer($request) ?? '/');

        if (!AppLocales::isSupported($locale)) {
            return $response;
        }

        $cookie = new Cookie(
            name: LocaleMiddleware::COOKIE_NAME,
            value: $locale,
            expires: (new DateTimeImmutable())->add(new DateInterval(self::COOKIE_DURATION)),
            path: '/',
            secure: $request->getUri()->getScheme() === 'https',
            httpOnly: true,
            sameSite: Cookie::SAME_SITE_LAX,
        );

        return $cookie->addToResponse($response);
    }
}
