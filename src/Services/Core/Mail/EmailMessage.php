<?php

declare(strict_types=1);

namespace App\Services\Core\Mail;

final readonly class EmailMessage
{
    public function __construct(
        public string $toEmail,
        public ?string $toName,
        public string $subject,
        public string $htmlBody,
        public ?string $textBody = null,
        public ?string $fromEmail = null,
        public ?string $fromName = null,
    ) {}

    public function withDefaults(string $fromEmail, string $fromName): self
    {
        return new self(
            toEmail: $this->toEmail,
            toName: $this->toName,
            subject: $this->subject,
            htmlBody: $this->htmlBody,
            textBody: $this->textBody,
            fromEmail: $this->fromEmail ?? $fromEmail,
            fromName: $this->fromName ?? $fromName,
        );
    }
}
