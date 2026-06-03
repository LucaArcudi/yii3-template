<?php

declare(strict_types=1);

namespace App\Params\Core;

final readonly class MailParams
{
    public function __construct(
        public string $fromEmail,
        public string $fromName,
        public string $transport,
        public string $filePath,
        public string $smtpHost,
        public int $smtpPort,
        public string $smtpUsername,
        public string $smtpPassword,
        public string $smtpEncryption,
        public int $smtpTimeout,
    ) {
    }
}
