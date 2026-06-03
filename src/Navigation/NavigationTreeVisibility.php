<?php

declare(strict_types=1);

namespace App\Navigation;

final class NavigationTreeVisibility
{
    /**
     * @param array<array-key, mixed> $items
     * @param callable(array): bool $isAllowed
     * @return array<array-key, mixed>
     */
    public static function filter(array $items, callable $isAllowed): array
    {
        $visible = [];

        foreach ($items as $item) {
            if (!is_array($item) || !$isAllowed($item)) {
                continue;
            }

            $type = NavigationItemType::fromArray($item);
            $item['_children'] = self::filter($item['_children'] ?? [], $isAllowed);

            if (NavigationItemType::canContainChildren($type)) {
                if ($item['_children'] !== []) {
                    $visible[] = $item;
                }

                continue;
            }

            if ($type === NavigationItemType::SEPARATOR) {
                $visible[] = $item;
                continue;
            }

            if (trim((string) ($item['url'] ?? '')) !== '') {
                $item['_children'] = [];
                $visible[] = $item;
            }
        }

        return self::pruneSeparators($visible);
    }

    /**
     * @param array<array-key, mixed> $items
     * @return array<array-key, mixed>
     */
    private static function pruneSeparators(array $items): array
    {
        $pruned = [];

        foreach ($items as $item) {
            $type = NavigationItemType::fromArray($item);

            if ($type === NavigationItemType::SEPARATOR) {
                if ($pruned === [] || NavigationItemType::fromArray($pruned[array_key_last($pruned)]) === NavigationItemType::SEPARATOR) {
                    continue;
                }

                $pruned[] = $item;
                continue;
            }

            $item['_children'] = self::pruneSeparators($item['_children'] ?? []);
            $pruned[] = $item;
        }

        while ($pruned !== [] && NavigationItemType::fromArray($pruned[array_key_last($pruned)]) === NavigationItemType::SEPARATOR) {
            array_pop($pruned);
        }

        return $pruned;
    }

    private function __construct()
    {
    }
}
