<?php

declare(strict_types=1);

$application = require __DIR__ . '/../../common/application.php';
$env = static function (string $name, string $default, bool $allowEmpty = false): string {
    $value = getenv($name);

    if ($value === false) {
        $value = $_ENV[$name] ?? null;
    }

    return $value === null || (!$allowEmpty && $value === '') ? $default : (string) $value;
};

return [
    'traceLink' => 'phpstorm://open?url=file://{file}&line={line}',

    'mail' => [
        'fromEmail' => $env('MAIL_FROM_EMAIL', 'no-reply@local.test'),
        'fromName' => $env('MAIL_FROM_NAME', (string) $application['name']),
        'transport' => $env('MAIL_TRANSPORT', 'smtp'),
        'filePath' => $env('MAIL_FILE_PATH', '@runtime/emails'),
        'smtpHost' => $env('MAIL_SMTP_HOST', '127.0.0.1'),
        'smtpPort' => (int) $env('MAIL_SMTP_PORT', '1025'),
        'smtpUsername' => $env('MAIL_SMTP_USERNAME', '', allowEmpty: true),
        'smtpPassword' => $env('MAIL_SMTP_PASSWORD', '', allowEmpty: true),
        'smtpEncryption' => strtolower($env('MAIL_SMTP_ENCRYPTION', 'none')),
        'smtpTimeout' => (int) $env('MAIL_SMTP_TIMEOUT', '15'),
    ],
];
