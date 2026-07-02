<?php

declare(strict_types=1);

namespace App\Dashboard;

use function array_key_exists;
use function trim;

final class DashboardComponentVisibility
{
    /**
     * @param list<DashboardComponentDefinition> $components
     * @param array<int|string, bool> $visibility
     * @return list<DashboardComponentDefinition>
     */
    public static function filter(array $components, array $visibility): array
    {
        $visible = [];

        foreach ($components as $component) {
            if (self::isVisibleByCondition($component, $visibility)) {
                $visible[] = $component;
            }
        }

        return $visible;
    }

    /**
     * @param array<int|string, bool> $visibility
     */
    private static function isVisibleByCondition(DashboardComponentDefinition $component, array $visibility): bool
    {
        foreach (self::visibilityKeys($component) as $key) {
            if (array_key_exists($key, $visibility) && !(bool) $visibility[$key]) {
                return false;
            }
        }

        return true;
    }

    /**
     * @return list<string>
     */
    private static function visibilityKeys(DashboardComponentDefinition $component): array
    {
        $code = trim($component->code);

        return $code !== '' ? [$code, 'code:' . $code] : [];
    }

    private function __construct() {}
}
