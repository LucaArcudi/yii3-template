<?php

declare(strict_types=1);

namespace App\Widgets;

use App\Navigation\NavigationItemType;
use App\Navigation\NavigationTreeVisibility;
use Yiisoft\Html\Html;

final class Menu
{
    /**
     * @param array<array-key, mixed> $items
     */
    public static function render(array $items, string $currentPath): string
    {
        $items = NavigationTreeVisibility::filter(
            $items,
            static fn(array $item): bool => true,
        );

        if ($items === []) {
            return '';
        }

        return self::renderItems($items, $currentPath);
    }

    private static function renderItems(array $items, string $currentPath): string
    {
        $html = '';

        foreach ($items as $item) {
            if ((bool) ($item['header'] ?? false)) {
                $html .= '<li class="app-sidebar__heading">' . Html::encode((string) $item['name']) . '</li>';
                $html .= self::renderItems($item['_children'] ?? [], $currentPath);
                continue;
            }

            if ((bool) ($item['separator'] ?? false)) {
                $html .= '<li class="app-sidebar__separator"></li>';
                continue;
            }

            $type = NavigationItemType::fromArray($item);
            $children = NavigationItemType::canContainChildren($type) ? ($item['_children'] ?? []) : [];
            $hasChildren = $children !== [];
            $url = (string) ($item['url'] ?? '');
            $active = self::isActive($url, $currentPath) || self::hasActiveChild($children, $currentPath);
            $icon = (string) ($item['icon'] ?? 'pe-7s-angle-right');
            $linkUrl = $type === NavigationItemType::TOGGLE ? '#' : ($url !== '' ? $url : '#');
            $liAttributes = [
                'class' => $hasChildren && $active ? 'mm-active' : null,
            ];
            $linkAttributes = [
                'class' => $active ? 'mm-active' : null,
            ];

            if ($hasChildren) {
                $linkAttributes['aria-expanded'] = $active ? 'true' : 'false';
            }

            if ((bool) ($item['external'] ?? false)) {
                $linkAttributes['target'] = '_blank';
                $linkAttributes['rel'] = 'noopener noreferrer';
            }

            $html .= (string) Html::openTag('li', $liAttributes);
            $html .= (string) Html::a(
                (string) Html::i('', ['class' => 'metismenu-icon ' . $icon])
                . Html::encode((string) $item['name'])
                . ($hasChildren ? (string) Html::i('', ['class' => 'metismenu-state-icon pe-7s-angle-down caret-left']) : ''),
                $linkUrl,
                $linkAttributes,
            )->encode(false);

            if ($hasChildren) {
                $html .= '<ul class="' . ($active ? 'mm-collapse mm-show' : 'mm-collapse') . '">';
                $html .= self::renderItems($children, $currentPath);
                $html .= '</ul>';
            }

            $html .= '</li>';
        }

        return $html;
    }

    private static function hasActiveChild(array $items, string $currentPath): bool
    {
        foreach ($items as $item) {
            if (self::isActive((string) ($item['url'] ?? ''), $currentPath)) {
                return true;
            }

            if (
                NavigationItemType::canContainChildren(NavigationItemType::fromArray($item))
                && self::hasActiveChild($item['_children'] ?? [], $currentPath)
            ) {
                return true;
            }
        }

        return false;
    }

    private static function isActive(string $url, string $currentPath): bool
    {
        if ($url === '') {
            return false;
        }

        if ($url === '/') {
            return $currentPath === '/';
        }

        return $currentPath === $url || str_starts_with($currentPath, $url . '/');
    }

    private function __construct() {}
}
