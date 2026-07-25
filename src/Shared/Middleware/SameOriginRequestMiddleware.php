<?php

declare(strict_types=1);

namespace App\Shared\Middleware;

use HttpSoft\Message\Response;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UriInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Yiisoft\Http\Method;

use function in_array;
use function is_int;
use function is_string;
use function parse_url;
use function strtolower;
use function trim;

use const PHP_URL_SCHEME;
use const PHP_URL_HOST;
use const PHP_URL_PORT;

final readonly class SameOriginRequestMiddleware implements MiddlewareInterface
{
    private const SAFE_METHODS = [
        Method::GET,
        Method::HEAD,
        Method::OPTIONS,
    ];

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if (in_array($request->getMethod(), self::SAFE_METHODS, true)) {
            return $handler->handle($request);
        }

        $source = $request->getHeaderLine('Origin') ?: $request->getHeaderLine('Referer');

        if ($source === '') {
            return $handler->handle($request);
        }

        if ($this->originFromUrl($source) === $this->originFromRequest($request)) {
            return $handler->handle($request);
        }

        $response = new Response(403);
        $response->getBody()->write('Forbidden.');

        return $response;
    }

    private function originFromRequest(ServerRequestInterface $request): string
    {
        $uri = $request->getUri();
        $scheme = strtolower($uri->getScheme());
        $host = $this->hostPortFromHeader($request->getHeaderLine('Host'), $scheme);

        if ($host !== '') {
            return $this->origin($scheme, $host);
        }

        return $this->origin($scheme, $this->hostPortFromUri($uri));
    }

    private function hostPortFromUri(UriInterface $uri): string
    {
        $host = strtolower($uri->getHost());

        if ($host === '') {
            return '';
        }

        $port = $uri->getPort();

        return $this->hostPort($host, $port, strtolower($uri->getScheme()));
    }

    private function originFromUrl(string $url): ?string
    {
        $scheme = parse_url($url, PHP_URL_SCHEME);
        $host = parse_url($url, PHP_URL_HOST);

        if (!is_string($host) || $host === '') {
            return null;
        }

        $port = parse_url($url, PHP_URL_PORT);
        $normalizedScheme = is_string($scheme) ? strtolower($scheme) : '';

        return $this->origin(
            $normalizedScheme,
            $this->hostPort(strtolower($host), is_int($port) ? $port : null, $normalizedScheme),
        );
    }

    private function hostPortFromHeader(string $hostHeader, string $scheme): string
    {
        $hostHeader = trim(strtolower($hostHeader));

        if ($hostHeader === '') {
            return '';
        }

        $host = parse_url('//' . $hostHeader, PHP_URL_HOST);

        if (!is_string($host) || $host === '') {
            return $hostHeader;
        }

        $port = parse_url('//' . $hostHeader, PHP_URL_PORT);

        return $this->hostPort($host, is_int($port) ? $port : null, $scheme);
    }

    private function hostPort(string $host, ?int $port, string $scheme): string
    {
        if ($port === null || ($scheme === 'http' && $port === 80) || ($scheme === 'https' && $port === 443)) {
            return $host;
        }

        return $host . ':' . $port;
    }

    private function origin(string $scheme, string $hostPort): string
    {
        return $scheme === '' ? $hostPort : $scheme . '://' . $hostPort;
    }
}
