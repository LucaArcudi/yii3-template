<?php

declare(strict_types=1);

namespace App\Shared\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Yiisoft\NetworkUtilities\IpHelper;
use Yiisoft\NetworkUtilities\IpRanges;

/**
 * Risolve gli header X-Forwarded-* quando la connessione arriva da un proxy
 * fidato (il reverse proxy Caddy, sulle reti private Docker):
 *
 * - X-Forwarded-Proto aggiorna lo scheme dell'URI: dietro TLS terminato dal
 *   proxy l'app vede `https`, quindi il cookie di sessione può esigere il
 *   flag Secure e {@see SecurityHeadersMiddleware} emette HSTS;
 * - X-Forwarded-For fornisce l'IP reale del client
 *   ({@see ATTRIBUTE_CLIENT_IP}), risolto dal fondo della catena saltando i
 *   proxy fidati: il primo IP non fidato è il client. Un client non può
 *   falsificarlo: il proxy accoda sempre l'IP della connessione reale, i
 *   valori iniettati restano più a sinistra e vengono ignorati.
 *
 * Se la connessione NON proviene da un proxy fidato gli header vengono
 * ignorati e l'attributo vale REMOTE_ADDR.
 */
final readonly class TrustedProxyMiddleware implements MiddlewareInterface
{
    public const ATTRIBUTE_CLIENT_IP = 'clientIp';

    private IpRanges $trustedRanges;

    /**
     * @param string[] $trustedIps IP o range CIDR dei proxy fidati; ammessi
     * gli alias di {@see IpRanges} come `private` e `localhost`.
     */
    public function __construct(array $trustedIps)
    {
        $this->trustedRanges = new IpRanges($trustedIps);
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $remoteAddr = $this->remoteAddr($request);

        if ($remoteAddr === null || !$this->isTrusted($remoteAddr)) {
            return $handler->handle($request->withAttribute(self::ATTRIBUTE_CLIENT_IP, $remoteAddr));
        }

        $scheme = $this->forwardedScheme($request);

        if ($scheme !== null && $scheme !== $request->getUri()->getScheme()) {
            $request = $request->withUri($request->getUri()->withScheme($scheme));
        }

        return $handler->handle(
            $request->withAttribute(self::ATTRIBUTE_CLIENT_IP, $this->forwardedClientIp($request, $remoteAddr)),
        );
    }

    private function remoteAddr(ServerRequestInterface $request): ?string
    {
        $ip = $request->getServerParams()['REMOTE_ADDR'] ?? null;

        return is_string($ip) && $this->isIp($ip) ? $ip : null;
    }

    private function forwardedScheme(ServerRequestInterface $request): ?string
    {
        $proto = strtolower(trim($request->getHeaderLine('X-Forwarded-Proto')));

        return in_array($proto, ['http', 'https'], true) ? $proto : null;
    }

    /**
     * IP del client dalla catena X-Forwarded-For, letta dal fondo: il primo
     * IP non fidato è il client; se sono tutti fidati vale il primo della
     * catena (l'origine). Header assente o malformato → REMOTE_ADDR.
     */
    private function forwardedClientIp(ServerRequestInterface $request, string $remoteAddr): string
    {
        $header = trim($request->getHeaderLine('X-Forwarded-For'));

        if ($header === '') {
            return $remoteAddr;
        }

        $chain = array_map(static fn(string $part): string => trim($part), explode(',', $header));

        foreach ($chain as $ip) {
            if (!$this->isIp($ip)) {
                return $remoteAddr;
            }
        }

        foreach (array_reverse($chain) as $ip) {
            if (!$this->isTrusted($ip)) {
                return $ip;
            }
        }

        return $chain[0];
    }

    private function isTrusted(string $ip): bool
    {
        return $this->trustedRanges->isAllowed($ip);
    }

    private function isIp(string $value): bool
    {
        return preg_match(IpHelper::IPV4_REGEXP, $value) === 1
            || preg_match(IpHelper::IPV6_REGEXP, $value) === 1;
    }
}
