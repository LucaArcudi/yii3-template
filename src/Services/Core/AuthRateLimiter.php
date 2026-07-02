<?php

declare(strict_types=1);

namespace App\Services\Core;

use App\Params\Core\AuthParams;
use Psr\Http\Message\ServerRequestInterface;
use Yiisoft\Db\Connection\ConnectionInterface;
use Yiisoft\Db\Query\Query;

use function date;
use function hash;
use function is_string;
use function max;
use function mb_strtolower;
use function strtotime;
use function time;
use function trim;

final readonly class AuthRateLimiter
{
    private const TABLE = '{{%core_auth_rate_limit}}';

    public function __construct(
        private ConnectionInterface $db,
        private AuthParams $authParams,
    ) {}

    public function consumeLogin(ServerRequestInterface $request, ?string $email): AuthRateLimitResult
    {
        return $this->consume('login', $request, $this->normalizeIdentity($email), $this->authParams->loginMaxAttempts);
    }

    public function clearLoginIdentity(?string $email): void
    {
        $identity = $this->normalizeIdentity($email);

        if ($identity !== null) {
            $this->clearKey('login:identity', $identity);
        }
    }

    public function consumeRegistration(ServerRequestInterface $request, ?string $email): AuthRateLimitResult
    {
        return $this->consume(
            'registration',
            $request,
            $this->normalizeIdentity($email),
            $this->authParams->registrationMaxAttempts,
        );
    }

    public function consumePasswordReset(ServerRequestInterface $request, ?string $email): AuthRateLimitResult
    {
        return $this->consume(
            'password-reset',
            $request,
            $this->normalizeIdentity($email),
            $this->authParams->passwordResetMaxAttempts,
        );
    }

    public function consumePasswordChange(ServerRequestInterface $request, ?string $identity): AuthRateLimitResult
    {
        return $this->consume(
            'password-change',
            $request,
            $this->normalizeIdentity($identity),
            $this->authParams->passwordChangeMaxAttempts,
        );
    }

    public function clearPasswordChangeIdentity(?string $identity): void
    {
        $identity = $this->normalizeIdentity($identity);

        if ($identity !== null) {
            $this->clearKey('password-change:identity', $identity);
        }
    }

    private function consume(
        string $scope,
        ServerRequestInterface $request,
        ?string $identity,
        int $maxAttempts,
    ): AuthRateLimitResult {
        $maxAttempts = max(1, $maxAttempts);
        $windowSeconds = max(60, $this->authParams->rateLimitWindowSeconds);
        $blockSeconds = max(60, $this->authParams->rateLimitBlockSeconds);

        if ($identity !== null) {
            $result = $this->consumeKey($scope . ':identity', $identity, $maxAttempts, $windowSeconds, $blockSeconds);

            if (!$result->allowed) {
                return $result;
            }
        }

        return $this->consumeKey(
            $scope . ':ip',
            $this->clientIp($request),
            $maxAttempts * 4,
            $windowSeconds,
            $blockSeconds,
        );
    }

    private function consumeKey(
        string $scope,
        string $value,
        int $maxAttempts,
        int $windowSeconds,
        int $blockSeconds,
    ): AuthRateLimitResult {
        $now = time();
        $row = (new Query($this->db))
            ->from(self::TABLE)
            ->where(['rate_key' => $this->key($scope, $value)])
            ->one();

        $attempts = 0;
        $windowStartedAt = $now;

        if ($row !== null) {
            $blockedUntil = $this->timestamp($row['blocked_until'] ?? null);

            if ($blockedUntil !== null && $blockedUntil > $now) {
                return AuthRateLimitResult::blocked($blockedUntil - $now);
            }

            $windowStartedAt = $this->timestamp($row['window_started_at'] ?? null) ?? $now;
            $attempts = (int) ($row['attempts'] ?? 0);

            if (($blockedUntil !== null && $blockedUntil <= $now) || $windowStartedAt + $windowSeconds <= $now) {
                $attempts = 0;
                $windowStartedAt = $now;
            }
        }

        $attempts++;
        $blockedUntil = $attempts > $maxAttempts ? $now + $blockSeconds : null;

        $data = [
            'rate_key' => $this->key($scope, $value),
            'scope' => $scope,
            'attempts' => $attempts,
            'window_started_at' => $this->formatTime($windowStartedAt),
            'blocked_until' => $blockedUntil === null ? null : $this->formatTime($blockedUntil),
            'last_attempt_at' => $this->formatTime($now),
        ];

        $this->db->createCommand()
            ->upsert(self::TABLE, $data, [
                'scope' => $data['scope'],
                'attempts' => $data['attempts'],
                'window_started_at' => $data['window_started_at'],
                'blocked_until' => $data['blocked_until'],
                'last_attempt_at' => $data['last_attempt_at'],
            ])
            ->execute();

        return $blockedUntil === null
            ? AuthRateLimitResult::allowed()
            : AuthRateLimitResult::blocked($blockSeconds);
    }

    private function clearKey(string $scope, string $value): void
    {
        $this->db->createCommand()
            ->delete(self::TABLE, ['rate_key' => $this->key($scope, $value)])
            ->execute();
    }

    private function key(string $scope, string $value): string
    {
        return hash('sha256', $scope . "\0" . $value);
    }

    private function normalizeIdentity(?string $identity): ?string
    {
        $identity = trim((string) $identity);

        return $identity === '' ? null : mb_strtolower($identity);
    }

    private function clientIp(ServerRequestInterface $request): string
    {
        $ip = $request->getServerParams()['REMOTE_ADDR'] ?? null;

        return is_string($ip) && $ip !== '' ? $ip : 'unknown';
    }

    private function timestamp(mixed $value): ?int
    {
        if (!is_string($value) || $value === '') {
            return null;
        }

        $timestamp = strtotime($value);

        return $timestamp === false ? null : $timestamp;
    }

    private function formatTime(int $timestamp): string
    {
        return date('Y-m-d H:i:s', $timestamp);
    }
}
