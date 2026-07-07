<?php

declare(strict_types=1);

namespace App\Shared\Services;

use RuntimeException;

use function bin2hex;
use function count;
use function ctype_xdigit;
use function explode;
use function password_hash;
use function password_verify;
use function random_bytes;
use function sprintf;
use function strlen;

final class AuthTokenService
{
    private const SELECTOR_BYTES = 16;
    private const VERIFIER_BYTES = 32;
    private const HASH_ALGORITHM = PASSWORD_ARGON2ID;

    public function generateRememberToken(): string
    {
        return bin2hex(random_bytes(self::VERIFIER_BYTES));
    }

    /**
     * @return array{selector: string, verifier: string, token: string}
     */
    public function generateResetToken(): array
    {
        $selector = bin2hex(random_bytes(self::SELECTOR_BYTES));
        $verifier = bin2hex(random_bytes(self::VERIFIER_BYTES));

        return [
            'selector' => $selector,
            'verifier' => $verifier,
            'token' => sprintf('%s.%s', $selector, $verifier),
        ];
    }

    /**
     * @return array{selector: string, verifier: string}|null
     */
    public function splitResetToken(string $token): ?array
    {
        $parts = explode('.', $token, 2);

        if (count($parts) !== 2) {
            return null;
        }

        [$selector, $verifier] = $parts;

        if (
            strlen($selector) !== self::SELECTOR_BYTES * 2 ||
            strlen($verifier) !== self::VERIFIER_BYTES * 2 ||
            !ctype_xdigit($selector) ||
            !ctype_xdigit($verifier)
        ) {
            return null;
        }

        return [
            'selector' => $selector,
            'verifier' => $verifier,
        ];
    }

    public function hash(string $token): string
    {
        $hash = password_hash($token, self::HASH_ALGORITHM);

        if ($hash === false) {
            throw new RuntimeException('Unable to hash authentication token.');
        }

        return $hash;
    }

    public function verify(string $token, ?string $hash): bool
    {
        return $hash !== null && $hash !== '' && password_verify($token, $hash);
    }
}
