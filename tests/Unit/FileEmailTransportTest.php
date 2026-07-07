<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\Shared\Params\MailParams;
use App\Shared\Services\Mail\EmailMessage;
use App\Shared\Services\Mail\Transport\FileEmailTransport;
use Codeception\Test\Unit;
use FilesystemIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Yiisoft\Aliases\Aliases;

use function glob;
use function is_dir;
use function rmdir;
use function sys_get_temp_dir;
use function unlink;

final class FileEmailTransportTest extends Unit
{
    private string $runtimePath;

    protected function _before(): void
    {
        $this->runtimePath = sys_get_temp_dir() . '/yii3-template-mail-' . bin2hex(random_bytes(6));
    }

    protected function _after(): void
    {
        $this->removeDirectory($this->runtimePath);
    }

    public function testWritesEmlFile(): void
    {
        $transport = new FileEmailTransport(
            new Aliases(['@runtime' => $this->runtimePath]),
            new MailParams(
                fromEmail: 'no-reply@example.test',
                fromName: 'Demo App',
                transport: 'file',
                filePath: '@runtime/emails',
                smtpHost: 'smtp.example.test',
                smtpPort: 587,
                smtpUsername: 'username@example.test',
                smtpPassword: 'change-me',
                smtpEncryption: 'tls',
                smtpTimeout: 15,
            ),
        );

        $transport->send(new EmailMessage(
            toEmail: 'user@example.test',
            toName: 'Example User',
            subject: 'Account creato',
            htmlBody: '<p>Ciao</p>',
            fromEmail: 'no-reply@example.test',
            fromName: 'Demo App',
        ));

        $files = glob($this->runtimePath . '/emails/*.eml');

        self::assertCount(1, $files);

        $content = file_get_contents((string) $files[0]);

        self::assertIsString($content);
        self::assertStringContainsString('From: "Demo App" <no-reply@example.test>', $content);
        self::assertStringContainsString('To: "Example User" <user@example.test>', $content);
        self::assertStringContainsString('Subject: Account creato', $content);
        self::assertStringContainsString('<p>Ciao</p>', $content);
    }

    private function removeDirectory(string $path): void
    {
        if (!is_dir($path)) {
            return;
        }

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST,
        );

        foreach ($iterator as $item) {
            $item->isDir() ? rmdir($item->getPathname()) : unlink($item->getPathname());
        }

        rmdir($path);
    }
}
