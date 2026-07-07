<?php

declare(strict_types=1);

namespace App\Shared\Services;

use HttpSoft\Message\Response;
use HttpSoft\Response\RedirectResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Yiisoft\Http\Method;
use Yiisoft\Router\UrlGeneratorInterface;

use function array_filter;
use function array_merge;
use function is_string;

final readonly class WebActionService
{
    public function __construct(
        private UrlGeneratorInterface $urlGenerator,
        private RememberedUrlService $rememberedUrl,
    ) {}

    public function forbidden(string $message = 'Access denied.'): ResponseInterface
    {
        $response = new Response(403);
        $response->getBody()->write($message);

        return $response;
    }

    public function notFound(?string $message = null): ResponseInterface
    {
        $response = new Response(404);

        if ($message !== null && $message !== '') {
            $response->getBody()->write($message);
        }

        return $response;
    }

    public function redirect(string $url): ResponseInterface
    {
        return new RedirectResponse($url);
    }

    public function redirectToView(string $resourcePath, int $id): ResponseInterface
    {
        return $this->redirect('/' . $resourcePath . '/view/' . $id);
    }

    public function isPost(ServerRequestInterface $request): bool
    {
        return $request->getMethod() === Method::POST;
    }

    public function rememberCurrent(string $key, ServerRequestInterface $request): string
    {
        return $this->rememberedUrl->rememberCurrent($key, $request);
    }

    public function rememberFromRequest(string $key, ServerRequestInterface $request, string $fallback = '/'): string
    {
        return $this->rememberedUrl->rememberFromRequest($key, $request, $fallback);
    }

    public function remember(string $key, string $url): void
    {
        $this->rememberedUrl->remember($key, $url);
    }

    public function previous(string $key, string $default = '/'): string
    {
        return $this->rememberedUrl->previous($key, $default);
    }

    public function viewNavigation(
        string $resourceKey,
        int $id,
        ServerRequestInterface $request,
        string $indexFallback,
    ): WebViewNavigation {
        $currentUrl = $this->rememberCurrent($resourceKey . '.view.' . $id, $request);
        $backUrl = $this->rememberedUrl->fromReturnParameter($request)
            ?? $this->viewBackUrl($resourceKey, $id, $indexFallback);

        $this->rememberedUrl->remember($resourceKey . '.view.back.' . $id, $backUrl);

        return new WebViewNavigation($currentUrl, $backUrl);
    }

    public function viewBackUrl(string $resourceKey, int $id, string $indexFallback): string
    {
        return $this->previous(
            $resourceKey . '.view.back.' . $id,
            $this->previous($resourceKey . '.index', $indexFallback),
        );
    }

    public function createBackUrl(string $resourceKey, string $indexFallback): string
    {
        return $this->previous(
            $resourceKey . '.create.back',
            $this->previous($resourceKey . '.index', $indexFallback),
        );
    }

    public function rememberCreateBackUrl(
        string $resourceKey,
        ServerRequestInterface $request,
        string $indexFallback,
    ): string {
        return $this->rememberFromRequest(
            $resourceKey . '.create.back',
            $request,
            $this->previous($resourceKey . '.index', $indexFallback),
        );
    }

    public function updateBackUrl(string $resourceKey, int $id, string $indexFallback): string
    {
        return $this->previous(
            $resourceKey . '.update.back.' . $id,
            $this->viewBackUrl($resourceKey, $id, $indexFallback),
        );
    }

    public function rememberUpdateBackUrl(
        string $resourceKey,
        int $id,
        ServerRequestInterface $request,
        string $indexFallback,
    ): string {
        return $this->rememberFromRequest(
            $resourceKey . '.update.back.' . $id,
            $request,
            $this->viewBackUrl($resourceKey, $id, $indexFallback),
        );
    }

    public function sort(array $query, string $default): string
    {
        return is_string($query['sort'] ?? null) ? (string) $query['sort'] : $default;
    }

    public function gridUrlCreator(string $routeName, array $query, array $fixedQuery = []): callable
    {
        return function (array $arguments, array $queryParameters) use ($routeName, $query, $fixedQuery): string {
            return $this->urlGenerator->generate(
                $routeName,
                $arguments,
                $this->query(array_merge($query, $queryParameters, $fixedQuery)),
            );
        };
    }

    public function query(array $query): array
    {
        return array_filter(
            $query,
            static fn(mixed $value): bool => $value !== null && $value !== '',
        );
    }
}
