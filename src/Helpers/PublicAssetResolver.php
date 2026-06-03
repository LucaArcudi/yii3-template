<?php

declare(strict_types=1);

namespace App\Helpers;

final class PublicAssetResolver
{
    public static function url(?string $value): ?string
    {
        $value = trim((string) $value);

        if ($value === '') {
            return null;
        }

        if (filter_var($value, FILTER_VALIDATE_URL) !== false) {
            return $value;
        }

        $relative = ltrim($value, '/');

        if (preg_match('/^[a-zA-Z0-9_\\.\\/-]+$/', $relative) !== 1) {
            return null;
        }

        if (str_contains('/' . $relative . '/', '/../')) {
            return null;
        }

        $publicPath = dirname(__DIR__, 2) . '/public/' . $relative;

        return is_file($publicPath) ? '/' . $relative : null;
    }

    private function __construct()
    {
    }
}
