<?php

declare(strict_types=1);

namespace App\Widgets;

use Yiisoft\Html\Html;

class Card
{
    public static function render(
        string $title,
        string $body,
        string $variant = 'primary',
        bool $outline = true,
        ?string $tools = null,
        ?string $footer = null,
        ?string $subtitle = null,
        ?string $icon = null,
    ): string {
        $variant = self::normalizeVariant($variant);
        $cardAttributes = [
            'class' => [
                'main-card',
                'mb-3',
                'card',
                'app-admin-card',
                'app-admin-card--' . $variant,
            ],
        ];

        if (!$outline) {
            Html::addCssClass($cardAttributes, 'app-admin-card--solid');
        }

        $headerContent = self::renderHeading(
            title: $title,
            subtitle: $subtitle,
            icon: $icon,
        );

        if ($tools !== null && $tools !== '') {
            $headerContent .= (string) Html::div($tools, ['class' => 'app-admin-card__tools'])->encode(false);
        }

        $sections = [
            (string) Html::div($headerContent, ['class' => ['card-header', 'app-admin-card__header']])->encode(false),
            (string) Html::div($body, ['class' => ['card-body', 'app-admin-card__body']])->encode(false),
        ];

        if ($footer !== null && $footer !== '') {
            $sections[] = (string) Html::div($footer, ['class' => ['card-footer', 'app-admin-card__footer']])->encode(false);
        }

        return (string) Html::div(implode('', $sections), $cardAttributes)->encode(false);
    }

    protected static function normalizeVariant(string $variant): string
    {
        return match ($variant) {
            'primary', 'secondary', 'success', 'info', 'warning', 'danger', 'dark', 'light' => $variant,
            default => 'primary',
        };
    }

    private static function renderHeading(string $title, ?string $subtitle, ?string $icon): string
    {
        $content = '';

        if ($icon !== null && $icon !== '') {
            $content .= (string) Html::span(
                (string) Html::i('', ['class' => $icon]),
                ['class' => 'app-admin-card__icon'],
            )->encode(false);
        }

        $titleGroup = (string) Html::h3($title, ['class' => 'card-title']);

        if ($subtitle !== null && $subtitle !== '') {
            $titleGroup .= (string) Html::div($subtitle, ['class' => 'app-admin-card__subtitle']);
        }

        $content .= (string) Html::div($titleGroup, ['class' => 'app-admin-card__title-group'])->encode(false);

        return (string) Html::div($content, ['class' => 'app-admin-card__heading'])->encode(false);
    }

    private function __construct()
    {
    }
}
