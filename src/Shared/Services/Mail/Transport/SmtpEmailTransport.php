<?php

declare(strict_types=1);

namespace App\Shared\Services\Mail\Transport;

use App\Shared\Params\MailParams;
use App\Shared\Services\Mail\EmailMessage;
use App\Shared\Services\Mail\EmailMessageFormatter;
use RuntimeException;

use function base64_encode;
use function defined;
use function fclose;
use function fgets;
use function fwrite;
use function implode;
use function in_array;
use function preg_match;
use function preg_replace;
use function sprintf;
use function stream_context_create;
use function stream_set_timeout;
use function stream_socket_client;
use function stream_socket_enable_crypto;
use function trim;

use const STREAM_CLIENT_CONNECT;
use const STREAM_CRYPTO_METHOD_TLS_CLIENT;

final class SmtpEmailTransport implements EmailTransportInterface
{
    private EmailMessageFormatter $formatter;

    public function __construct(
        private readonly MailParams $mailParams,
        ?EmailMessageFormatter $formatter = null,
    ) {
        $this->formatter = $formatter ?? new EmailMessageFormatter();
    }

    public function send(EmailMessage $message): void
    {
        if (!in_array($this->mailParams->smtpEncryption, ['tls', 'ssl', 'none'], true)) {
            throw new RuntimeException(sprintf(
                'Unsupported SMTP encryption "%s". Use "tls", "ssl", or "none".',
                $this->mailParams->smtpEncryption,
            ));
        }

        $socket = $this->connect();

        try {
            $this->expect($socket, [220], 'SMTP greeting');
            $this->sendCommand($socket, 'EHLO localhost', [250]);

            if ($this->mailParams->smtpEncryption === 'tls') {
                $this->sendCommand($socket, 'STARTTLS', [220]);
                $this->enableTls($socket);
                $this->sendCommand($socket, 'EHLO localhost', [250]);
            }

            $this->authenticate($socket);
            $this->sendCommand($socket, sprintf('MAIL FROM:<%s>', (string) $message->fromEmail), [250]);
            $this->sendCommand($socket, sprintf('RCPT TO:<%s>', $message->toEmail), [250, 251]);
            $this->sendCommand($socket, 'DATA', [354]);
            $this->write($socket, $this->dotStuff($this->formatter->message($message)) . "\r\n.\r\n");
            $this->expect($socket, [250], 'message body');
            $this->sendCommand($socket, 'QUIT', [221]);
        } finally {
            fclose($socket);
        }
    }

    /**
     * @return resource
     */
    private function connect()
    {
        $scheme = $this->mailParams->smtpEncryption === 'ssl' ? 'ssl' : 'tcp';
        $remote = sprintf('%s://%s:%d', $scheme, $this->mailParams->smtpHost, $this->mailParams->smtpPort);
        $context = stream_context_create([
            'ssl' => [
                'verify_peer' => true,
                'verify_peer_name' => true,
                'peer_name' => $this->mailParams->smtpHost,
            ],
        ]);

        $socket = @stream_socket_client(
            $remote,
            $errorCode,
            $errorMessage,
            $this->mailParams->smtpTimeout,
            STREAM_CLIENT_CONNECT,
            $context,
        );

        if ($socket === false) {
            throw new RuntimeException(sprintf(
                'Unable to connect to SMTP server "%s": %s (%d).',
                $remote,
                $errorMessage,
                $errorCode,
            ));
        }

        stream_set_timeout($socket, $this->mailParams->smtpTimeout);

        return $socket;
    }

    /**
     * @param resource $socket
     */
    private function authenticate($socket): void
    {
        if ($this->mailParams->smtpUsername === '') {
            return;
        }

        $this->sendCommand($socket, 'AUTH LOGIN', [334]);
        $this->sendCommand($socket, base64_encode($this->mailParams->smtpUsername), [334]);
        $this->sendCommand($socket, base64_encode($this->mailParams->smtpPassword), [235]);
    }

    /**
     * @param resource $socket
     */
    private function enableTls($socket): void
    {
        $method = STREAM_CRYPTO_METHOD_TLS_CLIENT;

        if (defined('STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT') && defined('STREAM_CRYPTO_METHOD_TLSv1_3_CLIENT')) {
            $method = STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT | STREAM_CRYPTO_METHOD_TLSv1_3_CLIENT;
        }

        if (@stream_socket_enable_crypto($socket, true, $method) !== true) {
            throw new RuntimeException('Unable to enable TLS for SMTP connection.');
        }
    }

    /**
     * @param resource $socket
     * @param list<int> $expectedCodes
     */
    private function sendCommand($socket, string $command, array $expectedCodes): string
    {
        $this->write($socket, $command . "\r\n");

        return $this->expect($socket, $expectedCodes, $command);
    }

    /**
     * @param resource $socket
     */
    private function write($socket, string $data): void
    {
        if (fwrite($socket, $data) === false) {
            throw new RuntimeException('Unable to write to SMTP connection.');
        }
    }

    /**
     * @param resource $socket
     * @param list<int> $expectedCodes
     */
    private function expect($socket, array $expectedCodes, string $context): string
    {
        [$code, $response] = $this->readResponse($socket);

        if (!in_array($code, $expectedCodes, true)) {
            throw new RuntimeException(sprintf(
                'Unexpected SMTP response for "%s". Expected %s, got %d: %s',
                $context,
                implode(', ', $expectedCodes),
                $code,
                trim($response),
            ));
        }

        return $response;
    }

    /**
     * @param resource $socket
     *
     * @return array{int, string}
     */
    private function readResponse($socket): array
    {
        $response = '';

        while (($line = fgets($socket)) !== false) {
            $response .= $line;

            if (preg_match('/^(\d{3})([ -])/', $line, $matches) === 1 && $matches[2] === ' ') {
                return [(int) $matches[1], $response];
            }
        }

        throw new RuntimeException('Unable to read SMTP response.');
    }

    private function dotStuff(string $message): string
    {
        $message = (string) preg_replace("/\r\n|\r|\n/", "\r\n", $message);

        return (string) preg_replace('/^\./m', '..', $message);
    }
}
