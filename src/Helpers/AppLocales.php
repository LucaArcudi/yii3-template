<?php

declare(strict_types=1);

namespace App\Helpers;

/**
 * Lingue supportate dall'applicazione: la lista guida middleware, switcher e translator.
 */
final class AppLocales
{
    public const DEFAULT = 'it';

    /**
     * Mappa locale => nome nella lingua stessa (usato dallo switcher, non va tradotto).
     */
    public const SUPPORTED = [
        'it' => 'Italiano',
        'en' => 'English',
    ];

    public static function isSupported(string $locale): bool
    {
        return isset(self::SUPPORTED[$locale]);
    }
}
