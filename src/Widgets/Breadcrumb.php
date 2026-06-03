<?php

declare(strict_types=1);

namespace App\Widgets;

use Yiisoft\Html\Html;

final class Breadcrumb
{
    /**
     * @param array<int, string|array{label:string, url?:string|null, icon?:string|null}> $items
     */
    public static function render(array $items): string
    {
        $normalizedItems = self::normalizeItems($items);

        if ($normalizedItems === []) {
            return '';
        }

        $breadcrumbs = [];
        $lastIndex = count($normalizedItems) - 1;

        foreach ($normalizedItems as $index => $item) {
            $isActive = $index === $lastIndex;
            $content = self::renderItemContent($item['label'], $item['icon']);

            if (!$isActive && $item['url'] !== null && $item['url'] !== '') {
                $content = (string) Html::a(
                    $content,
                    $item['url'],
                    ['class' => 'app-page-breadcrumb__link'],
                )->encode(false);
            } else {
                $content = (string) Html::span(
                    $content,
                    ['class' => 'app-page-breadcrumb__current'],
                )->encode(false);
            }

            $attributes = [
                'class' => ['breadcrumb-item', 'app-page-breadcrumb__item'],
            ];

            if ($isActive) {
                $attributes['class'][] = 'active';
                $attributes['aria-current'] = 'page';
            }

            $breadcrumbs[] = (string) Html::tag('li', $content, $attributes)->encode(false);
        }

        $list = (string) Html::tag(
            'ol',
            implode('', $breadcrumbs),
            ['class' => ['breadcrumb', 'app-page-breadcrumb__list', 'mb-0']],
        )->encode(false);

        return (string) Html::tag(
            'nav',
            $list,
            [
                'class' => 'app-page-breadcrumb',
                'aria-label' => 'breadcrumb',
            ],
        )->encode(false);
    }

    /**
     * @param array<int, string|array{label:string, url?:string|null, icon?:string|null}> $items
     *
     * @return array<int, array{label:string, url:?string, icon:string}>
     */
    private static function normalizeItems(array $items): array
    {
        $normalized = [];

        foreach ($items as $item) {
            if (is_string($item)) {
                $label = trim($item);
                if ($label === '') {
                    continue;
                }

                $normalized[] = [
                    'label' => $label,
                    'url' => null,
                    'icon' => '',
                ];

                continue;
            }

            $label = trim((string) ($item['label'] ?? ''));
            if ($label === '') {
                continue;
            }

            $normalized[] = [
                'label' => $label,
                'url' => isset($item['url']) ? (string) $item['url'] : null,
                'icon' => trim((string) ($item['icon'] ?? '')),
            ];
        }

        return $normalized;
    }

    private static function renderItemContent(string $label, string $icon = ''): string
    {
        $content = '';

        if ($icon !== '') {
            $content .= (string) Html::i('', ['class' => $icon . ' app-page-breadcrumb__icon']);
        }

        $content .= (string) Html::span($label, ['class' => 'app-page-breadcrumb__label']);

        return $content;
    }

    private function __construct()
    {
    }
}
