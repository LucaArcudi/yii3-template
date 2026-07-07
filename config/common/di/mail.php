<?php

declare(strict_types=1);

use App\Shared\Params\MailParams;
use App\Shared\Services\Mail\Transport\EmailTransportInterface;
use App\Shared\Services\Mail\Transport\FileEmailTransport;
use App\Shared\Services\Mail\Transport\NativeEmailTransport;
use App\Shared\Services\Mail\Transport\SmtpEmailTransport;
use Yiisoft\Aliases\Aliases;

/** @var array $params */

return [
    MailParams::class => [
        '__construct()' => [
            'fromEmail' => (string) $params['mail']['fromEmail'],
            'fromName' => (string) $params['mail']['fromName'],
            'transport' => strtolower((string) $params['mail']['transport']),
            'filePath' => (string) $params['mail']['filePath'],
            'smtpHost' => (string) $params['mail']['smtpHost'],
            'smtpPort' => (int) $params['mail']['smtpPort'],
            'smtpUsername' => (string) $params['mail']['smtpUsername'],
            'smtpPassword' => (string) $params['mail']['smtpPassword'],
            'smtpEncryption' => strtolower((string) $params['mail']['smtpEncryption']),
            'smtpTimeout' => (int) $params['mail']['smtpTimeout'],
        ],
    ],

    EmailTransportInterface::class => static function (MailParams $mailParams, Aliases $aliases): EmailTransportInterface {
        return match ($mailParams->transport) {
            'file' => new FileEmailTransport($aliases, $mailParams),
            'native' => new NativeEmailTransport(),
            'smtp' => new SmtpEmailTransport($mailParams),
            default => throw new InvalidArgumentException(sprintf('Unsupported mail transport "%s".', $mailParams->transport)),
        };
    },
];
