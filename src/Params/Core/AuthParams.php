<?php

declare(strict_types=1);

namespace App\Params\Core;

use DateInterval;
use DateTimeImmutable;

final readonly class AuthParams
{
    public function __construct(
        public int $passwordMaxAgeDays,
        public int $passwordResetTokenTtlMinutes,
        public int $rateLimitWindowSeconds,
        public int $rateLimitBlockSeconds,
        public int $loginMaxAttempts,
        public int $registrationMaxAttempts,
        public int $passwordResetMaxAttempts,
        public int $passwordChangeMaxAttempts,
        public string $defaultRegistrationRoleCode,
    ) {}

    public function passwordExpiresAt(?string $from = null): ?string
    {
        if ($this->passwordMaxAgeDays <= 0) {
            return null;
        }

        $date = $from === null
            ? new DateTimeImmutable()
            : new DateTimeImmutable($from);

        return $date
            ->add(new DateInterval('P' . $this->passwordMaxAgeDays . 'D'))
            ->format('Y-m-d H:i:s');
    }

    public function passwordResetTokenExpiresAt(): string
    {
        return (new DateTimeImmutable())
            ->add(new DateInterval('PT' . max(1, $this->passwordResetTokenTtlMinutes) . 'M'))
            ->format('Y-m-d H:i:s');
    }
}
