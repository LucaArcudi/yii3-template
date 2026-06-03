<?php

declare(strict_types=1);

namespace App\Services\Core\Mail\Transport;

use App\Params\Core\MailParams;
use App\Services\Core\Mail\EmailMessage;
use App\Services\Core\Mail\EmailMessageFormatter;
use DateTimeImmutable;
use RuntimeException;
use Yiisoft\Aliases\Aliases;
use Yiisoft\Files\FileHelper;

use function file_put_contents;
use function preg_replace;
use function sprintf;

use const LOCK_EX;

final class FileEmailTransport implements EmailTransportInterface
{
    private EmailMessageFormatter $formatter;

    public function __construct(
        private Aliases $aliases,
        private MailParams $mailParams,
        ?EmailMessageFormatter $formatter = null,
    ) {
        $this->formatter = $formatter ?? new EmailMessageFormatter();
    }

    public function send(EmailMessage $message): void
    {
        $directory = $this->aliases->get($this->mailParams->filePath);
        FileHelper::ensureDirectory($directory);

        $file = sprintf(
            '%s/%s-%s.eml',
            $directory,
            (new DateTimeImmutable())->format('Ymd-His-u'),
            preg_replace('/[^a-zA-Z0-9._-]+/', '-', $message->toEmail) ?: 'mail',
        );

        $result = file_put_contents($file, $this->formatter->message($message), LOCK_EX);

        if ($result === false) {
            throw new RuntimeException(sprintf('Unable to write email file "%s".', $file));
        }
    }
}
