<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\Shared\Middleware\TrustedProxyMiddleware;
use Codeception\Test\Unit;
use HttpSoft\Message\Response;
use HttpSoft\Message\ServerRequest;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class TrustedProxyMiddlewareTest extends Unit
{
    public function testUntrustedConnectionIgnoresForwardedHeaders(): void
    {
        $handler = new ForwardedCaptureHandler();

        $this->middleware()->process(
            $this->request('203.0.113.10')
                ->withHeader('X-Forwarded-Proto', 'https')
                ->withHeader('X-Forwarded-For', '198.51.100.1'),
            $handler,
        );

        self::assertSame('203.0.113.10', $handler->clientIp);
        self::assertSame('http', $handler->scheme);
    }

    public function testTrustedProxyResolvesSchemeAndClientIp(): void
    {
        $handler = new ForwardedCaptureHandler();

        $this->middleware()->process(
            $this->request('172.18.0.5')
                ->withHeader('X-Forwarded-Proto', 'https')
                ->withHeader('X-Forwarded-For', '203.0.113.10'),
            $handler,
        );

        self::assertSame('203.0.113.10', $handler->clientIp);
        self::assertSame('https', $handler->scheme);
    }

    public function testSpoofedChainEntryIsIgnored(): void
    {
        $handler = new ForwardedCaptureHandler();

        // Il client ha inviato un X-Forwarded-For falso ("6.6.6.6"); il proxy
        // ha accodato l'IP reale: dal fondo, il primo non fidato è il client.
        $this->middleware()->process(
            $this->request('172.18.0.5')
                ->withHeader('X-Forwarded-For', '6.6.6.6, 203.0.113.10'),
            $handler,
        );

        self::assertSame('203.0.113.10', $handler->clientIp);
    }

    public function testFullyTrustedChainFallsBackToOrigin(): void
    {
        $handler = new ForwardedCaptureHandler();

        // Richiesta locale passata dal proxy: tutta la catena è fidata.
        $this->middleware()->process(
            $this->request('172.18.0.5')->withHeader('X-Forwarded-For', '127.0.0.1'),
            $handler,
        );

        self::assertSame('127.0.0.1', $handler->clientIp);
    }

    public function testMalformedForwardedForFallsBackToRemoteAddr(): void
    {
        $handler = new ForwardedCaptureHandler();

        $this->middleware()->process(
            $this->request('172.18.0.5')->withHeader('X-Forwarded-For', 'not-an-ip, 203.0.113.10'),
            $handler,
        );

        self::assertSame('172.18.0.5', $handler->clientIp);
    }

    public function testInvalidForwardedProtoLeavesSchemeUntouched(): void
    {
        $handler = new ForwardedCaptureHandler();

        $this->middleware()->process(
            $this->request('172.18.0.5')->withHeader('X-Forwarded-Proto', 'gopher'),
            $handler,
        );

        self::assertSame('http', $handler->scheme);
    }

    public function testMissingHeadersKeepRemoteAddrAsClientIp(): void
    {
        $handler = new ForwardedCaptureHandler();

        $this->middleware()->process($this->request('127.0.0.1'), $handler);

        self::assertSame('127.0.0.1', $handler->clientIp);
        self::assertSame('http', $handler->scheme);
    }

    private function middleware(): TrustedProxyMiddleware
    {
        return new TrustedProxyMiddleware(['private', 'localhost']);
    }

    private function request(string $remoteAddr): ServerRequestInterface
    {
        return new ServerRequest(serverParams: ['REMOTE_ADDR' => $remoteAddr], uri: 'http://app.test/login');
    }
}

final class ForwardedCaptureHandler implements RequestHandlerInterface
{
    public ?string $clientIp = null;
    public string $scheme = '';

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        /** @var string|null $clientIp */
        $clientIp = $request->getAttribute(TrustedProxyMiddleware::ATTRIBUTE_CLIENT_IP);
        $this->clientIp = $clientIp;
        $this->scheme = $request->getUri()->getScheme();

        return new Response();
    }
}
