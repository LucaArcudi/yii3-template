<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\Shared\Middleware\SameOriginRequestMiddleware;
use App\Shared\Middleware\TrustedProxyMiddleware;
use Codeception\Test\Unit;
use HttpSoft\Message\Response;
use HttpSoft\Message\ServerRequest;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Yiisoft\Http\Method;
use Yiisoft\Http\Status;

final class SameOriginRequestMiddlewareTest extends Unit
{
    public function testPostFromForwardedHttpsOriginUsesPublicHostHeader(): void
    {
        $request = (new ServerRequest(
            serverParams: ['REMOTE_ADDR' => '172.19.0.3'],
            method: Method::POST,
            uri: 'http://yii3-template.duckdns.org:80/login',
        ))
            ->withHeader('Host', 'yii3-template.duckdns.org')
            ->withHeader('Origin', 'https://yii3-template.duckdns.org')
            ->withHeader('X-Forwarded-Proto', 'https');

        $response = (new TrustedProxyMiddleware(['private']))->process(
            $request,
            new SameOriginPipelineHandler(),
        );

        self::assertSame(Status::OK, $response->getStatusCode());
        self::assertSame('accepted', (string) $response->getBody());
    }

    public function testDefaultPortIsNormalized(): void
    {
        $response = $this->process(
            (new ServerRequest(method: Method::POST, uri: 'https://app.example.test/login'))
                ->withHeader('Host', 'app.example.test:443')
                ->withHeader('Origin', 'https://app.example.test'),
        );

        self::assertSame(Status::OK, $response->getStatusCode());
    }

    public function testUriAuthorityIsUsedWhenHostHeaderIsMissing(): void
    {
        $response = $this->process(
            (new ServerRequest(method: Method::POST, uri: 'https://app.example.test/login'))
                ->withoutHeader('Host')
                ->withHeader('Referer', 'https://app.example.test/form'),
        );

        self::assertSame(Status::OK, $response->getStatusCode());
    }

    public function testPostFromDifferentHostIsForbidden(): void
    {
        $response = $this->process(
            (new ServerRequest(method: Method::POST, uri: 'https://app.example.test/login'))
                ->withHeader('Host', 'app.example.test')
                ->withHeader('Origin', 'https://evil.example'),
        );

        self::assertSame(Status::FORBIDDEN, $response->getStatusCode());
        self::assertSame('Forbidden.', (string) $response->getBody());
    }

    public function testPostFromDifferentSchemeIsForbidden(): void
    {
        $response = $this->process(
            (new ServerRequest(method: Method::POST, uri: 'https://app.example.test/login'))
                ->withHeader('Host', 'app.example.test')
                ->withHeader('Origin', 'http://app.example.test'),
        );

        self::assertSame(Status::FORBIDDEN, $response->getStatusCode());
    }

    public function testPostFromDifferentNonDefaultPortIsForbidden(): void
    {
        $response = $this->process(
            (new ServerRequest(method: Method::POST, uri: 'https://app.example.test/login'))
                ->withHeader('Host', 'app.example.test:8443')
                ->withHeader('Origin', 'https://app.example.test'),
        );

        self::assertSame(Status::FORBIDDEN, $response->getStatusCode());
    }

    public function testSafeMethodDoesNotRequireMatchingOrigin(): void
    {
        $response = $this->process(
            (new ServerRequest(method: Method::GET, uri: 'https://app.example.test/login'))
                ->withHeader('Origin', 'https://evil.example'),
        );

        self::assertSame(Status::OK, $response->getStatusCode());
    }

    private function process(ServerRequestInterface $request): ResponseInterface
    {
        return (new SameOriginRequestMiddleware())->process($request, new SameOriginAcceptedHandler());
    }
}

final readonly class SameOriginAcceptedHandler implements RequestHandlerInterface
{
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $response = new Response();
        $response->getBody()->write('accepted');

        return $response;
    }
}

final readonly class SameOriginPipelineHandler implements RequestHandlerInterface
{
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        return (new SameOriginRequestMiddleware())->process($request, new SameOriginAcceptedHandler());
    }
}
