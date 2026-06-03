<?php

declare(strict_types=1);

namespace App\Tests\Support\Extension;

use Codeception\Events;
use Codeception\Extension;
use RuntimeException;

use function fclose;
use function fsockopen;
use function is_resource;
use function proc_close;
use function proc_get_status;
use function proc_open;
use function proc_terminate;
use function putenv;
use function sleep;
use function stream_set_timeout;
use function usleep;

final class PhpBuiltInServerExtension extends Extension
{
    private const HOST = '127.0.0.1';
    private const PORT = 8080;

    protected static array $events = [
        Events::SUITE_BEFORE => 'startServer',
        Events::SUITE_AFTER => 'stopServer',
    ];

    /** @var resource|null */
    private $process = null;

    public function startServer(): void
    {
        if ($this->isListening()) {
            return;
        }

        $command = [
            'php',
            '-S',
            self::HOST . ':' . self::PORT,
            '-t',
            'public',
            'public/index.php',
        ];
        $appEnv = (string) ($_ENV['APP_ENV'] ?? $_SERVER['APP_ENV'] ?? 'test');
        $_ENV['APP_ENV'] = $appEnv;
        $_SERVER['APP_ENV'] = $appEnv;
        putenv('APP_ENV=' . $appEnv);

        $this->process = proc_open(
            $command,
            [
                0 => ['file', self::nullDevice(), 'r'],
                1 => ['file', self::nullDevice(), 'w'],
                2 => ['file', self::nullDevice(), 'w'],
            ],
            $pipes,
            $this->getRootDir(),
        );

        if (!is_resource($this->process)) {
            throw new RuntimeException('Unable to start PHP built-in server for Web suite.');
        }

        for ($attempt = 0; $attempt < 20; $attempt++) {
            if ($this->isListening()) {
                return;
            }

            usleep(250_000);
        }

        $this->stopServer();
        throw new RuntimeException('PHP built-in server did not start on 127.0.0.1:8080.');
    }

    public function stopServer(): void
    {
        if (!is_resource($this->process)) {
            return;
        }

        $status = proc_get_status($this->process);

        if (($status['running'] ?? false) === true) {
            proc_terminate($this->process);
            sleep(1);
        }

        proc_close($this->process);
        $this->process = null;
    }

    private function isListening(): bool
    {
        $connection = @fsockopen(self::HOST, self::PORT, $errorCode, $errorMessage, 0.2);

        if ($connection === false) {
            return false;
        }

        stream_set_timeout($connection, 1);
        fclose($connection);

        return true;
    }

    private static function nullDevice(): string
    {
        return PHP_OS_FAMILY === 'Windows' ? 'NUL' : '/dev/null';
    }
}
