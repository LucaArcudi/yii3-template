<?php

declare(strict_types=1);

namespace App\Services\Core;

use function ceil;
use function max;

final readonly class AuthRateLimitResult
{
    private function __construct(
        public bool $allowed,
        public int $retryAfterSeconds = 0,
    ) {
    }

    public static function allowed(): self
    {
        return new self(true);
    }

    public static function blocked(int $retryAfterSeconds): self
    {
        return new self(false, max(1, $retryAfterSeconds));
    }

    public function message(): string
    {
        $minutes = max(1, (int) ceil($this->retryAfterSeconds / 60));

        return $minutes === 1
            ? 'Troppi tentativi. Riprova tra 1 minuto.'
            : 'Troppi tentativi. Riprova tra ' . $minutes . ' minuti.';
    }
}
