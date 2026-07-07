<?php

declare(strict_types=1);

namespace App\Shared\Services;

use DateInterval;
use DateTimeImmutable;
use JsonException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Yiisoft\Cookies\Cookie;
use Yiisoft\User\Login\Cookie\CookieLoginIdentityInterface;

use function json_encode;

final readonly class RememberMeCookieService
{
    private const COOKIE_NAME = 'autoLogin';
    private const DURATION = 'P5D';

    /**
     * @throws JsonException
     */
    public function addCookie(
        CookieLoginIdentityInterface $identity,
        ServerRequestInterface $request,
        ResponseInterface $response,
    ): ResponseInterface {
        $expires = (new DateTimeImmutable())->add(new DateInterval(self::DURATION));
        $value = json_encode(
            [
                $identity->getId(),
                $identity->getCookieLoginKey(),
                $expires->getTimestamp(),
            ],
            JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE,
        );

        return $this->cookie($request, $value, $expires)->addToResponse($response);
    }

    public function expireCookie(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        return $this->cookie($request, '')
            ->expire()
            ->addToResponse($response);
    }

    public function cookieName(): string
    {
        return self::COOKIE_NAME;
    }

    private function cookie(
        ServerRequestInterface $request,
        string $value,
        ?DateTimeImmutable $expires = null,
    ): Cookie {
        return new Cookie(
            name: self::COOKIE_NAME,
            value: $value,
            expires: $expires,
            path: '/',
            secure: $request->getUri()->getScheme() === 'https',
            httpOnly: true,
            sameSite: Cookie::SAME_SITE_LAX,
        );
    }
}
