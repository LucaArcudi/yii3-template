<?php

declare(strict_types=1);

namespace App\Handlers\Middleware\Core;

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

        if ($this->hostPortFromUrl($source) === $this->hostPortFromRequest($request)) {
            return $handler->handle($request);
        }

        $response = new Response(403);
        $response->getBody()->write('Forbidden.');

        return $response;
    }

    private function hostPortFromRequest(ServerRequestInterface $request): string
    {
        $uri = $request->getUri();
        $host = $this->hostPortFromUri($uri);

        if ($host !== '') {
            return $host;
        }

        return strtolower($request->getHeaderLine('Host'));
    }

    private function hostPortFromUri(UriInterface $uri): string
    {
        $host = strtolower($uri->getHost());

        if ($host === '') {
            return '';
        }

        $port = $uri->getPort();

        return $port === null ? $host : $host . ':' . $port;
    }

    private function hostPortFromUrl(string $url): ?string
    {
        $host = parse_url($url, PHP_URL_HOST);

        if (!is_string($host) || $host === '') {
            return null;
        }

        $port = parse_url($url, PHP_URL_PORT);

        return is_int($port)
            ? strtolower($host) . ':' . $port
            : strtolower($host);
    }
}
