<?php

declare(strict_types=1);

namespace App\Data\Core\Log;

use App\Data\Core\BaseEntity;
use App\Params\Core\EntityLogParams;
use Psr\Http\Message\ServerRequestInterface;
use Throwable;
use Yiisoft\Db\Connection\ConnectionInterface;
use Yiisoft\RequestProvider\RequestProviderInterface;

use function array_map;
use function date;
use function explode;
use function implode;
use function is_array;
use function is_object;
use function is_scalar;
use function json_encode;
use function mb_substr;
use function str_contains;
use function strtolower;
use function trim;

use const JSON_THROW_ON_ERROR;
use const JSON_UNESCAPED_SLASHES;
use const JSON_UNESCAPED_UNICODE;
use const PHP_SAPI;

final readonly class EntityLogRepository
{
    public const ACTION_CREATE = 'create';
    public const ACTION_UPDATE = 'update';
    public const ACTION_DELETE = 'delete';

    public function __construct(
        private ConnectionInterface $db,
        private ?RequestProviderInterface $requestProvider = null,
        private EntityLogParams $params = new EntityLogParams(),
    ) {
    }

    public function record(
        string $entityType,
        int|string|null $entityId,
        string $action,
        BaseEntity $entity,
        string $query,
        array $params = [],
        ?int $actorId = null,
    ): void {
        $request = $this->request();
        $source = $this->source($request);

        if (!$this->params->isEnabledFor($source)) {
            return;
        }

        $resolvedActorId = $actorId ?? $entity->updatedBy ?? $entity->createdBy;

        $this->db->createCommand()->insert('{{%core_log}}', [
            'entity_type' => $entityType,
            'entity_id' => $entityId !== null ? (string) $entityId : null,
            'action' => $action,
            'source' => $source,
            'actor_id' => $resolvedActorId,
            'entity_created_at' => $entity->createdAt,
            'entity_updated_at' => $entity->updatedAt,
            'sql_query' => $query,
            'sql_params' => $this->json($this->redact($params)),
            'url' => $request === null ? null : mb_substr((string) $request->getUri(), 0, 2048),
            'method' => $request?->getMethod(),
            'request_query' => $this->requestQuery($request),
            'request_body' => $this->requestBody($request),
            'console_command' => $request === null ? $this->consoleCommand() : null,
            'ip_address' => $request === null ? null : $this->ipAddress($request),
            'user_agent' => $request === null ? null : mb_substr($request->getHeaderLine('User-Agent'), 0, 512),
            'created_at' => date('Y-m-d H:i:s'),
        ])->execute();
    }

    private function source(?ServerRequestInterface $request): string
    {
        return $request !== null ? 'web' : (PHP_SAPI === 'cli' ? 'console' : 'system');
    }

    private function request(): ?ServerRequestInterface
    {
        if ($this->requestProvider === null) {
            return null;
        }

        try {
            return $this->requestProvider->get();
        } catch (Throwable) {
            return null;
        }
    }

    private function requestQuery(?ServerRequestInterface $request): ?string
    {
        if ($request === null) {
            return null;
        }

        $queryParams = $request->getQueryParams();

        return $queryParams === [] ? null : $this->json($this->redact($queryParams));
    }

    private function requestBody(?ServerRequestInterface $request): ?string
    {
        if ($request === null) {
            return null;
        }

        $parsedBody = $request->getParsedBody();

        if ($parsedBody === null) {
            return null;
        }

        if (is_array($parsedBody)) {
            return $this->json($this->redact($parsedBody));
        }

        if (is_object($parsedBody)) {
            return $this->json($this->redact((array) $parsedBody));
        }

        return $this->json(['value' => is_scalar($parsedBody) ? (string) $parsedBody : '[unavailable]']);
    }

    private function consoleCommand(): ?string
    {
        $argv = $_SERVER['argv'] ?? null;
        if (!is_array($argv) || $argv === []) {
            return null;
        }

        return mb_substr(implode(' ', array_map(static fn (mixed $part): string => (string) $part, $argv)), 0, 512);
    }

    private function ipAddress(ServerRequestInterface $request): ?string
    {
        $forwardedFor = $request->getHeaderLine('X-Forwarded-For');
        if ($forwardedFor !== '') {
            return mb_substr(trim(explode(',', $forwardedFor)[0]), 0, 45);
        }

        $serverParams = $request->getServerParams();
        $remoteAddress = $serverParams['REMOTE_ADDR'] ?? null;

        return $remoteAddress === null ? null : mb_substr((string) $remoteAddress, 0, 45);
    }

    private function json(array $value): string
    {
        return json_encode($value, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }

    private function redact(array $params): array
    {
        $redacted = [];

        foreach ($params as $key => $value) {
            $normalizedKey = strtolower((string) $key);

            if (
                str_contains($normalizedKey, 'password')
                || str_contains($normalizedKey, 'token')
                || str_contains($normalizedKey, 'csrf')
                || str_contains($normalizedKey, 'secret')
            ) {
                $redacted[$key] = '[redacted]';
                continue;
            }

            $redacted[$key] = is_array($value) ? $this->redact($value) : $value;
        }

        return $redacted;
    }
}
