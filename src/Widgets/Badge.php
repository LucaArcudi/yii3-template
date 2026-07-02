<?php

declare(strict_types=1);

namespace App\Widgets;

use Yiisoft\Html\Html;

class Badge
{
    public static function render(string $label, string $variant = 'secondary'): string
    {
        $variant = self::normalizeVariant($variant);
        $tone = self::bootstrapTone($variant);

        return (string) Html::span(
            $label,
            [
                'class' => [
                    'badge',
                    'rounded-pill',
                    'app-status-badge',
                    'app-status-badge--' . $variant,
                    $tone,
                ],
            ],
        );
    }

    private static function bootstrapTone(string $variant): string
    {
        return match (self::normalizeVariant($variant)) {
            'primary' => 'text-bg-primary',
            'success' => 'text-bg-success',
            'info' => 'text-bg-info',
            'warning' => 'text-bg-warning',
            'danger' => 'text-bg-danger',
            'dark' => 'text-bg-dark',
            'light' => 'text-bg-light',
            default => 'text-bg-secondary',
        };
    }

    private static function normalizeVariant(string $variant): string
    {
        return match ($variant) {
            'primary', 'secondary', 'success', 'info', 'warning', 'danger', 'dark', 'light' => $variant,
            default => 'secondary',
        };
    }

    private function __construct() {}
}
