<?php

declare(strict_types=1);

namespace App\Shared\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Yiisoft\Http\Method;
use Yiisoft\Http\Status;

use function ctype_digit;
use function in_array;
use function str_contains;
use function strtolower;
use function trim;

final readonly class StatusPageMiddleware implements MiddlewareInterface
{
    public const RETRY_AFTER_ATTRIBUTE = 'statusPageRetryAfterSeconds';

    public function __construct(
        private RequestHandlerInterface $accessDeniedHandler,
        private RequestHandlerInterface $tooManyRequestsHandler,
        private RequestHandlerInterface $invalidRequestHandler,
    ) {}

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $response = $handler->handle($request);
        $status = $response->getStatusCode();

        if (!$this->shouldRenderStatusPage($request, $response, $status)) {
            return $response;
        }

        $statusHandler = match ($status) {
            Status::FORBIDDEN => $this->accessDeniedHandler,
            Status::TOO_MANY_REQUESTS => $this->tooManyRequestsHandler,
            Status::UNPROCESSABLE_ENTITY => $this->invalidRequestHandler,
            default => null,
        };

        if ($statusHandler === null) {
            return $response;
        }

        $pageRequest = $status === Status::TOO_MANY_REQUESTS
            ? $this->withRetryAfterAttribute($request, $response)
            : $request;

        return $this->copyHeaders(
            $statusHandler->handle($pageRequest)->withStatus($status),
            $response,
        );
    }

    private function shouldRenderStatusPage(
        ServerRequestInterface $request,
        ResponseInterface $response,
        int $status,
    ): bool {
        if (!in_array(
            $status,
            [Status::FORBIDDEN, Status::TOO_MANY_REQUESTS, Status::UNPROCESSABLE_ENTITY],
            true,
        )) {
            return false;
        }

        if ($request->getMethod() === Method::HEAD) {
            return false;
        }

        return $this->requestAcceptsHtml($request) && $this->responseCanBeReplaced($response);
    }

    private function requestAcceptsHtml(ServerRequestInterface $request): bool
    {
        $accept = strtolower($request->getHeaderLine('Accept'));

        return $accept === ''
            || str_contains($accept, 'text/html')
            || str_contains($accept, 'application/xhtml+xml')
            || str_contains($accept, '*/*');
    }

    private function responseCanBeReplaced(ResponseInterface $response): bool
    {
        $contentType = strtolower($response->getHeaderLine('Content-Type'));

        return $contentType === ''
            || str_contains($contentType, 'text/html')
            || str_contains($contentType, 'text/plain');
    }

    private function withRetryAfterAttribute(
        ServerRequestInterface $request,
        ResponseInterface $response,
    ): ServerRequestInterface {
        $retryAfter = trim($response->getHeaderLine('Retry-After'));

        if ($retryAfter === '' || !ctype_digit($retryAfter)) {
            return $request;
        }

        return $request->withAttribute(self::RETRY_AFTER_ATTRIBUTE, (int) $retryAfter);
    }

    private function copyHeaders(ResponseInterface $target, ResponseInterface $source): ResponseInterface
    {
        foreach ($source->getHeaders() as $name => $values) {
            if (in_array(strtolower($name), ['content-length', 'content-type'], true)) {
                continue;
            }

            $target = $target->withHeader($name, $values);
        }

        return $target;
    }
}
