<?php

declare(strict_types=1);

namespace App\Shared\Navigation;

final class NavigationItemType
{
    public const LINK = 'link';
    public const HEADER = 'header';
    public const TOGGLE = 'toggle';
    public const SEPARATOR = 'separator';

    public static function fromArray(array $item): string
    {
        if ((bool) ($item['separator'] ?? $item['is_separator'] ?? false)) {
            return self::SEPARATOR;
        }

        if ((bool) ($item['header'] ?? $item['is_header'] ?? false)) {
            return self::HEADER;
        }

        if ((bool) ($item['toggle'] ?? $item['is_toggle'] ?? false)) {
            return self::TOGGLE;
        }

        return self::LINK;
    }

    public static function canContainChildren(string $type): bool
    {
        return $type === self::HEADER || $type === self::TOGGLE;
    }

    private function __construct() {}
}
