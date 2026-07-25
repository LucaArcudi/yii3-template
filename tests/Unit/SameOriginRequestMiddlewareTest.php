<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\Shared\Middleware\SameOriginRequestMiddleware;
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
        $response = (new SameOriginRequestMiddleware())->process(
            (new ServerRequest(method: Method::POST, uri: 'https://yii3-template.duckdns.org:80/login'))
                ->withHeader('Host', 'yii3-template.duckdns.org')
                ->withHeader('Origin', 'https://yii3-template.duckdns.org'),
            new SameOriginAcceptedHandler(),
        );

        self::assertSame(Status::OK, $response->getStatusCode());
        self::assertSame('accepted', (string) $response->getBody());
    }

    public function testPostFromDifferentOriginIsForbidden(): void
    {
        $response = (new SameOriginRequestMiddleware())->process(
            (new ServerRequest(method: Method::POST, uri: 'https://yii3-template.duckdns.org/login'))
                ->withHeader('Host', 'yii3-template.duckdns.org')
                ->withHeader('Origin', 'https://evil.example'),
            new SameOriginAcceptedHandler(),
        );

        self::assertSame(Status::FORBIDDEN, $response->getStatusCode());
        self::assertSame('Forbidden.', (string) $response->getBody());
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
