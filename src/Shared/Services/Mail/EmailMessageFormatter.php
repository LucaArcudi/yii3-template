<?php

declare(strict_types=1);

namespace App\Shared\Services\Mail;

use DateTimeImmutable;

use function addcslashes;
use function bin2hex;
use function implode;
use function random_bytes;
use function sprintf;
use function str_replace;
use function trim;

use const DATE_RFC2822;

final class EmailMessageFormatter
{
    /**
     * @return array{array<string, string>, string}
     */
    public function format(EmailMessage $message, bool $includeTo = true): array
    {
        [$contentType, $body] = $this->buildBody($message);

        $headers = [
            'Date' => (new DateTimeImmutable())->format(DATE_RFC2822),
            'From' => $this->formatAddress((string) $message->fromEmail, (string) $message->fromName),
            'Subject' => $this->sanitizeHeader($message->subject),
            'MIME-Version' => '1.0',
            'Content-Type' => $contentType,
        ];

        if ($includeTo) {
            $headers = [
                'Date' => $headers['Date'],
                'From' => $headers['From'],
                'To' => $this->formatAddress($message->toEmail, (string) $message->toName),
                'Subject' => $headers['Subject'],
                'MIME-Version' => $headers['MIME-Version'],
                'Content-Type' => $headers['Content-Type'],
            ];
        }

        return [$headers, $body];
    }

    public function message(EmailMessage $message): string
    {
        [$headers, $body] = $this->format($message);

        return implode("\r\n", $this->headerLines($headers)) . "\r\n\r\n" . $body;
    }

    /**
     * @param array<string, string> $headers
     *
     * @return list<string>
     */
    public function headerLines(array $headers): array
    {
        $lines = [];

        foreach ($headers as $name => $value) {
            $lines[] = $name . ': ' . $value;
        }

        return $lines;
    }

    public function formatAddress(string $email, string $name): string
    {
        $email = $this->sanitizeHeader($email);
        $name = $this->sanitizeHeader($name);

        if ($name === '') {
            return $email;
        }

        return sprintf('"%s" <%s>', addcslashes($name, '"\\'), $email);
    }

    private function sanitizeHeader(string $value): string
    {
        return trim(str_replace(["\r", "\n"], ' ', $value));
    }

    /**
     * @return array{string, string}
     */
    private function buildBody(EmailMessage $message): array
    {
        if ($message->textBody === null) {
            return ['text/html; charset=UTF-8', $message->htmlBody];
        }

        $boundary = '=_yii3_' . bin2hex(random_bytes(16));

        return [
            sprintf('multipart/alternative; boundary="%s"', $boundary),
            '--' . $boundary
            . "\r\nContent-Type: text/plain; charset=UTF-8"
            . "\r\nContent-Transfer-Encoding: 8bit\r\n\r\n"
            . $message->textBody
            . "\r\n--" . $boundary
            . "\r\nContent-Type: text/html; charset=UTF-8"
            . "\r\nContent-Transfer-Encoding: 8bit\r\n\r\n"
            . $message->htmlBody
            . "\r\n--" . $boundary . "--\r\n",
        ];
    }
}
