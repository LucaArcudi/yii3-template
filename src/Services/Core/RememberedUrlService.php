<?php

declare(strict_types=1);

namespace App\Services\Core;

use Psr\Http\Message\ServerRequestInterface;
use Yiisoft\Session\SessionInterface;

use function is_array;
use function is_string;
use function parse_url;
use function strcasecmp;
use function str_starts_with;
use function trim;

final readonly class RememberedUrlService
{
    private const SESSION_KEY = '__app_remembered_urls';

    public function __construct(
        private SessionInterface $session,
    ) {}

    public function remember(string $key, string $url): void
    {
        $normalized = $this->normalizeUrl($url);

        if ($normalized === null) {
            return;
        }

        $this->session->open();

        $urls = $this->session->get(self::SESSION_KEY, []);
        $urls = is_array($urls) ? $urls : [];
        $urls[$key] = $normalized;

        $this->session->set(self::SESSION_KEY, $urls);
    }

    public function rememberCurrent(string $key, ServerRequestInterface $request): string
    {
        $url = $this->currentUrl($request);
        $this->remember($key, $url);

        return $url;
    }

    public function rememberFromRequest(string $key, ServerRequestInterface $request, string $fallback = '/'): string
    {
        $url = $this->fromReturnParameter($request)
            ?? $this->fromReferer($request)
            ?? $fallback;

        $this->remember($key, $url);

        return $this->previous($key, $fallback);
    }

    public function previous(string $key, string $default = '/'): string
    {
        $this->session->open();

        $urls = $this->session->get(self::SESSION_KEY, []);
        $url = is_array($urls) ? ($urls[$key] ?? null) : null;

        return is_string($url) && $url !== '' ? $url : $default;
    }

    public function pull(string $key, string $default = '/'): string
    {
        $this->session->open();

        $urls = $this->session->get(self::SESSION_KEY, []);
        $urls = is_array($urls) ? $urls : [];

        $url = $urls[$key] ?? null;
        unset($urls[$key]);

        $this->session->set(self::SESSION_KEY, $urls);

        return is_string($url) && $url !== '' ? $url : $default;
    }

    public function fromReferer(ServerRequestInterface $request): ?string
    {
        return $this->normalizeUrl($request->getHeaderLine('Referer'), $request);
    }

    public function fromReturnParameter(ServerRequestInterface $request, string $parameter = '_return'): ?string
    {
        $query = $request->getQueryParams();
        $value = $query[$parameter] ?? null;

        return is_string($value) ? $this->normalizeUrl($value, $request) : null;
    }

    private function currentUrl(ServerRequestInterface $request): string
    {
        $uri = $request->getUri();
        $url = $uri->getPath() !== '' ? $uri->getPath() : '/';

        if ($uri->getQuery() !== '') {
            $url .= '?' . $uri->getQuery();
        }

        if ($uri->getFragment() !== '') {
            $url .= '#' . $uri->getFragment();
        }

        return $url;
    }

    private function normalizeUrl(?string $url, ?ServerRequestInterface $request = null): ?string
    {
        if (!is_string($url)) {
            return null;
        }

        $url = trim($url);
        if ($url === '') {
            return null;
        }

        if (str_starts_with($url, '/')) {
            return $this->composeRelativeUrl(parse_url($url));
        }

        $parts = parse_url($url);
        if ($parts === false) {
            return null;
        }

        $path = (string) ($parts['path'] ?? '');
        if ($path === '' || !str_starts_with($path, '/')) {
            return null;
        }

        if (isset($parts['host'])) {
            if ($request === null) {
                return null;
            }

            $currentUri = $request->getUri();
            $currentHost = $currentUri->getHost();

            if ($currentHost !== '' && strcasecmp((string) $parts['host'], $currentHost) !== 0) {
                return null;
            }

            if (isset($parts['port']) && $currentUri->getPort() !== null && (int) $parts['port'] !== $currentUri->getPort()) {
                return null;
            }
        }

        return $this->composeRelativeUrl($parts);
    }

    private function composeRelativeUrl(array|false $parts): ?string
    {
        if ($parts === false) {
            return null;
        }

        $path = (string) ($parts['path'] ?? '');
        if ($path === '' || !str_starts_with($path, '/')) {
            return null;
        }

        $url = $path;

        if (($parts['query'] ?? '') !== '') {
            $url .= '?' . $parts['query'];
        }

        if (($parts['fragment'] ?? '') !== '') {
            $url .= '#' . $parts['fragment'];
        }

        return $url;
    }
}
