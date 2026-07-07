<?php

declare(strict_types=1);

namespace App\Shared\Services\Mail\Transport;

use App\Shared\Services\Mail\EmailMessage;
use App\Shared\Services\Mail\EmailMessageFormatter;
use RuntimeException;

use function mail;
use function sprintf;

final class NativeEmailTransport implements EmailTransportInterface
{
    private EmailMessageFormatter $formatter;

    public function __construct(?EmailMessageFormatter $formatter = null)
    {
        $this->formatter = $formatter ?? new EmailMessageFormatter();
    }

    public function send(EmailMessage $message): void
    {
        [$headers, $body] = $this->formatter->format($message, includeTo: false);

        $sent = mail(
            $this->formatter->formatAddress($message->toEmail, (string) $message->toName),
            $headers['Subject'],
            $body,
            implode("\r\n", $this->formatter->headerLines($headers)),
        );

        if (!$sent) {
            throw new RuntimeException(sprintf('Unable to send email to "%s".', $message->toEmail));
        }
    }
}
