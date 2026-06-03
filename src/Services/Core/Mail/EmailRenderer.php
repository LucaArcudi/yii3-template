<?php

declare(strict_types=1);

namespace App\Services\Core\Mail;

use App\Params\Core\ApplicationParams;
use InvalidArgumentException;
use RuntimeException;
use Throwable;
use Yiisoft\Aliases\Aliases;

use function extract;
use function is_file;
use function ob_get_clean;
use function ob_start;
use function rtrim;
use function str_contains;
use function str_replace;
use function trim;

use const EXTR_SKIP;

final readonly class EmailRenderer
{
    private string $emailsPath;

    public function __construct(
        private Aliases $aliases,
        private ApplicationParams $applicationParams,
        string $emailsPath = '@resources/emails',
    ) {
        $this->emailsPath = rtrim($this->aliases->get($emailsPath), '\\/');
    }

    public function render(string $view, array $parameters = [], ?string $layout = 'layout'): string
    {
        $content = $this->renderFile($this->resolveViewFile($view), $parameters);

        if ($layout === null) {
            return $content;
        }

        return $this->renderFile(
            $this->resolveViewFile($layout),
            [
                ...$parameters,
                'applicationName' => $parameters['applicationName'] ?? $this->applicationParams->name,
                'content' => $content,
            ],
        );
    }

    private function resolveViewFile(string $view): string
    {
        $normalized = trim(str_replace('\\', '/', $view), '/');

        if ($normalized === '' || str_contains($normalized, '..')) {
            throw new InvalidArgumentException('Invalid email view name.');
        }

        $file = $this->emailsPath . '/' . $normalized . '.php';

        if (!is_file($file)) {
            throw new RuntimeException(sprintf('Email view "%s" was not found.', $view));
        }

        return $file;
    }

    private function renderFile(string $file, array $parameters): string
    {
        ob_start();

        try {
            extract($parameters, EXTR_SKIP);
            require $file;

            return (string) ob_get_clean();
        } catch (Throwable $exception) {
            ob_get_clean();

            throw $exception;
        }
    }
}
