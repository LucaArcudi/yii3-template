<?php

declare(strict_types=1);

namespace App\Services\Core;

use RuntimeException;

final class PasswordHasher
{
    private const ALGORITHM = PASSWORD_ARGON2ID;

    private const OPTIONS = [
        'memory_cost' => PASSWORD_ARGON2_DEFAULT_MEMORY_COST,
        'time_cost' => PASSWORD_ARGON2_DEFAULT_TIME_COST,
        'threads' => PASSWORD_ARGON2_DEFAULT_THREADS,
    ];

    public function hash(string $password): string
    {
        $hash = password_hash($password, self::ALGORITHM, self::OPTIONS);

        if ($hash === false) {
            throw new RuntimeException('Unable to hash password with Argon2id.');
        }

        return $hash;
    }

    public function verify(string $password, string $hash): bool
    {
        return password_verify($password, $hash);
    }
}
