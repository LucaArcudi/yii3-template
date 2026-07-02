<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\Handlers\Middleware\Core\StatusPageMiddleware;
use Codeception\Test\Unit;
use HttpSoft\Message\Response;
use HttpSoft\Message\ServerRequest;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Yiisoft\Http\Status;

final class StatusPageMiddlewareTest extends Unit
{
    public function testForbiddenResponseIsReplacedWithAccessDeniedPage(): void
    {
        $middleware = new StatusPageMiddleware(
            new StaticResponseHandler('access denied page'),
            new StaticResponseHandler('rate limit page'),
            new StaticResponseHandler('invalid request page'),
        );

        $response = $middleware->process(
            (new ServerRequest(uri: '/restricted'))->withHeader('Accept', 'text/html'),
            new StaticResponseHandler('raw forbidden', Status::FORBIDDEN, ['X-Test' => 'kept']),
        );

        self::assertSame(Status::FORBIDDEN, $response->getStatusCode());
        self::assertSame('access denied page', (string) $response->getBody());
        self::assertSame('kept', $response->getHeaderLine('X-Test'));
    }

    public function testTooManyRequestsResponsePreservesRetryAfter(): void
    {
        $middleware = new StatusPageMiddleware(
            new StaticResponseHandler('access denied page'),
            new RetryAfterResponseHandler(),
            new StaticResponseHandler('invalid request page'),
        );

        $response = $middleware->process(
            (new ServerRequest(uri: '/login'))->withHeader('Accept', 'text/html'),
            new StaticResponseHandler('raw rate limit', Status::TOO_MANY_REQUESTS, ['Retry-After' => '120']),
        );

        self::assertSame(Status::TOO_MANY_REQUESTS, $response->getStatusCode());
        self::assertSame('retry-after=120', (string) $response->getBody());
        self::assertSame('120', $response->getHeaderLine('Retry-After'));
    }

    public function testJsonRequestKeepsOriginalResponse(): void
    {
        $middleware = new StatusPageMiddleware(
            new StaticResponseHandler('access denied page'),
            new StaticResponseHandler('rate limit page'),
            new StaticResponseHandler('invalid request page'),
        );

        $response = $middleware->process(
            (new ServerRequest(uri: '/api/restricted'))->withHeader('Accept', 'application/json'),
            new StaticResponseHandler('raw forbidden', Status::FORBIDDEN),
        );

        self::assertSame(Status::FORBIDDEN, $response->getStatusCode());
        self::assertSame('raw forbidden', (string) $response->getBody());
    }

    public function testUnprocessableEntityResponseIsReplacedWithInvalidRequestPage(): void
    {
        $middleware = new StatusPageMiddleware(
            new StaticResponseHandler('access denied page'),
            new StaticResponseHandler('rate limit page'),
            new StaticResponseHandler('invalid request page'),
        );

        $response = $middleware->process(
            (new ServerRequest(uri: '/login'))->withHeader('Accept', 'text/html'),
            new StaticResponseHandler('Unprocessable Entity', Status::UNPROCESSABLE_ENTITY),
        );

        self::assertSame(Status::UNPROCESSABLE_ENTITY, $response->getStatusCode());
        self::assertSame('invalid request page', (string) $response->getBody());
    }
}

final readonly class StaticResponseHandler implements RequestHandlerInterface
{
    public function __construct(
        private string $body,
        private int $status = Status::OK,
        private array $headers = [],
    ) {}

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $response = new Response($this->status);

        foreach ($this->headers as $name => $value) {
            $response = $response->withHeader((string) $name, (string) $value);
        }

        $response->getBody()->write($this->body);

        return $response;
    }
}

final readonly class RetryAfterResponseHandler implements RequestHandlerInterface
{
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $retryAfter = $request->getAttribute(StatusPageMiddleware::RETRY_AFTER_ATTRIBUTE);
        $response = new Response();
        $response->getBody()->write('retry-after=' . (is_int($retryAfter) ? (string) $retryAfter : 'none'));

        return $response;
    }
}
