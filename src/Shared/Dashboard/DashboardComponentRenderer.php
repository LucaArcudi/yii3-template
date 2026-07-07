<?php

declare(strict_types=1);

namespace App\Shared\Dashboard;

use RuntimeException;
use Throwable;
use Yiisoft\Aliases\Aliases;
use Yiisoft\User\CurrentUser;

use function is_file;
use function ob_get_clean;
use function ob_start;
use function preg_match;
use function realpath;
use function rtrim;
use function sprintf;
use function str_replace;
use function str_starts_with;
use function strlen;
use function trim;

final readonly class DashboardComponentRenderer
{
    private string $basePath;

    public function __construct(
        Aliases $aliases,
        string $basePath = '@resources/components/core',
    ) {
        $this->basePath = rtrim($aliases->get($basePath), '\\/');
    }

    public function render(
        DashboardComponentDefinition $componentDefinition,
        CurrentUser $currentUser,
    ): string {
        $viewName = $this->normalizeViewName($componentDefinition->viewName);
        $viewFile = $this->resolveViewFile($viewName) ?? $this->resolveViewFile('default');

        if ($viewFile === null) {
            throw new RuntimeException(sprintf('Dashboard component view "%s" was not found.', $viewName));
        }

        $component = new DashboardComponentPresenter($componentDefinition);
        $identity = $currentUser->getIdentity();

        ob_start();

        try {
            require $viewFile;

            return (string) ob_get_clean();
        } catch (Throwable $exception) {
            ob_get_clean();

            throw $exception;
        }
    }

    private function resolveViewFile(string $viewName): ?string
    {
        $file = $this->basePath . '/' . str_replace('/', DIRECTORY_SEPARATOR, $viewName) . '.php';
        $basePath = realpath($this->basePath);
        $realFile = realpath($file);

        if ($basePath === false || $realFile === false || !is_file($realFile)) {
            return null;
        }

        return str_starts_with($realFile, $basePath . DIRECTORY_SEPARATOR) ? $realFile : null;
    }

    private function normalizeViewName(string $viewName): string
    {
        $normalized = trim(str_replace('\\', '/', $viewName), '/');

        if (
            $normalized === ''
            || strlen($normalized) > 120
            || preg_match('~^[A-Za-z0-9][A-Za-z0-9_-]*(/[A-Za-z0-9][A-Za-z0-9_-]*)*$~', $normalized) !== 1
        ) {
            return 'default';
        }

        return $normalized;
    }
}
