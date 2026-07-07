<?php

declare(strict_types=1);

namespace App\Shared\Widgets;

use Yiisoft\Html\Html;

final class Tabs
{
    /**
     * @param array<int, array{id:string,label:string,content:string,icon?:string,active?:bool,url?:string}> $items
     */
    public static function render(array $items, string $id = 'app-tabs'): string
    {
        if ($items === []) {
            return '';
        }

        $activeIndex = 0;

        foreach ($items as $index => $item) {
            if (($item['active'] ?? false) === true) {
                $activeIndex = $index;
                break;
            }
        }

        $nav = '';
        $panels = '';

        foreach ($items as $index => $item) {
            $itemId = self::normalizeId($id . '-' . $item['id']);
            $tabId = $itemId . '-tab';
            $active = $index === $activeIndex;
            $label = ($item['icon'] ?? null) !== null && $item['icon'] !== ''
                ? (string) Html::i('', ['class' => $item['icon'] . ' me-1'])
                    . Html::encode($item['label'])
                : Html::encode($item['label']);
            $buttonAttributes = [
                'id' => $tabId,
                'type' => 'button',
                'class' => ['nav-link', $active ? 'active' : null],
                'data-bs-toggle' => 'tab',
                'data-bs-target' => '#' . $itemId,
                'data-tab-id' => (string) $item['id'],
                'role' => 'tab',
                'aria-controls' => $itemId,
                'aria-selected' => $active ? 'true' : 'false',
            ];

            if (($item['url'] ?? '') !== '') {
                $buttonAttributes['data-tab-url'] = (string) $item['url'];
            }

            $nav .= (string) Html::tag(
                'li',
                (string) Html::button($label, $buttonAttributes)->encode(false),
                ['class' => 'nav-item', 'role' => 'presentation'],
            )->encode(false);

            $panels .= (string) Html::div(
                $item['content'],
                [
                    'id' => $itemId,
                    'class' => ['tab-pane', 'fade', $active ? 'show active' : null],
                    'role' => 'tabpanel',
                    'aria-labelledby' => $tabId,
                    'tabindex' => '0',
                ],
            )->encode(false);
        }

        return (string) Html::div(
            (string) Html::tag('ul', $nav, ['class' => ['nav', 'nav-tabs', 'app-tabs__nav'], 'role' => 'tablist'])->encode(false)
            . (string) Html::div($panels, ['class' => ['tab-content', 'app-tabs__content']])->encode(false),
            ['id' => self::normalizeId($id), 'class' => 'app-tabs'],
        )->encode(false);
    }

    private static function normalizeId(string $id): string
    {
        $id = preg_replace('/[^a-zA-Z0-9_-]+/', '-', $id) ?? 'app-tab';
        $id = trim($id, '-');

        return $id === '' ? 'app-tab' : $id;
    }

    private function __construct() {}
}
