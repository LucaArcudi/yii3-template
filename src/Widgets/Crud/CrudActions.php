<?php

declare(strict_types=1);

namespace App\Widgets\Crud;

use App\Helpers\Translate;
use App\Widgets\Modal;
use Yiisoft\Html\Html;
use Yiisoft\Yii\View\Renderer\Csrf;

use function array_filter;
use function implode;
use function is_string;

final class CrudActions
{
    public static function viewLink(
        string $url,
        string $icon = 'fa-solid fa-eye',
        ?string $label = null,
        array $attributes = [],
    ): string {
        return self::link($url, $label ?? Translate::t('Apri'), $icon, 'primary', $attributes);
    }

    public static function updateLink(
        string $url,
        string $icon = 'fa-solid fa-pen-to-square',
        ?string $label = null,
        array $attributes = [],
    ): string {
        return self::link($url, $label ?? Translate::t('Modifica'), $icon, 'warning', $attributes);
    }

    public static function updatePageLink(
        string $url,
        string $icon = 'fa-solid fa-pen-to-square',
        ?string $label = null,
        array $attributes = [],
    ): string {
        return self::pageLink($url, $label ?? Translate::t('Modifica'), $icon, 'warning', $attributes);
    }

    public static function deleteTrigger(
        string $modalId,
        string $icon = 'fa-solid fa-trash',
        ?string $label = null,
        array $attributes = [],
    ): string {
        $label ??= Translate::t('Elimina');
        $attributes = self::buttonAttributes($label, 'danger', $attributes);
        $attributes['type'] ??= 'button';
        $attributes['data-bs-toggle'] ??= 'modal';
        $attributes['data-bs-target'] ??= '#' . $modalId;

        return (string) Html::button(self::iconLabel($icon, $label), $attributes)->encode(false);
    }

    public static function deletePageTrigger(
        string $modalId,
        string $icon = 'fa-solid fa-trash',
        ?string $label = null,
        array $attributes = [],
    ): string {
        $label ??= Translate::t('Elimina');
        Html::addCssClass($attributes, ['btn', 'btn-danger', 'btn-shadow']);
        $attributes['type'] ??= 'button';
        $attributes['title'] ??= $label;
        $attributes['aria-label'] ??= $label;
        $attributes['data-bs-toggle'] ??= 'modal';
        $attributes['data-bs-target'] ??= '#' . $modalId;

        return (string) Html::button(self::visibleIconLabel($icon, $label), $attributes)->encode(false);
    }

    public static function group(array $buttons, string $ariaLabel, string $empty = '-'): string
    {
        $buttons = array_filter($buttons, static fn(mixed $button): bool => is_string($button) && $button !== '');

        if ($buttons === []) {
            return (string) Html::span($empty, ['class' => 'text-muted']);
        }

        return (string) Html::div(
            implode('', $buttons),
            [
                'class' => 'app-task-actions',
                'role' => 'group',
                'aria-label' => $ariaLabel,
            ],
        )->encode(false);
    }

    public static function deleteBody(string $message, array $meta = []): string
    {
        $body = (string) Html::p(
            $message,
            ['class' => [$meta === [] ? 'mb-0' : 'mb-3', 'text-muted']],
        )->encode(false);

        if ($meta !== []) {
            $body .= self::metaGrid($meta);
        }

        return $body;
    }

    /**
     * @param array<string, string> $items Map of label to already escaped or trusted HTML value.
     */
    public static function metaGrid(array $items): string
    {
        $content = '';

        foreach ($items as $label => $value) {
            $content .= (string) Html::div($label, ['class' => 'app-task-view__meta-label']);
            $content .= (string) Html::div($value, ['class' => 'app-task-view__meta-value'])->encode(false);
        }

        return (string) Html::div($content, ['class' => 'app-task-view__meta-grid'])->encode(false);
    }

    public static function deleteModal(
        string $id,
        string $title,
        string $action,
        string $body,
        Csrf $csrf,
        string $icon = 'fa-solid fa-triangle-exclamation',
        string $submitIcon = 'fa-solid fa-trash',
        ?string $submitLabel = null,
    ): string {
        return Modal::render(
            id: $id,
            title: $title,
            body: $body,
            footer: self::deleteFooter($action, $csrf, $submitIcon, $submitLabel ?? Translate::t('Elimina definitivamente')),
            variant: 'danger',
            icon: $icon,
        );
    }

    private static function link(
        string $url,
        string $label,
        string $icon,
        string $variant,
        array $attributes,
    ): string {
        return (string) Html::a(
            self::iconLabel($icon, $label),
            $url,
            self::buttonAttributes($label, $variant, $attributes),
        )->encode(false);
    }

    private static function pageLink(
        string $url,
        string $label,
        string $icon,
        string $variant,
        array $attributes,
    ): string {
        Html::addCssClass($attributes, ['btn-shadow', 'btn', 'btn-' . $variant]);
        $attributes['title'] ??= $label;
        $attributes['aria-label'] ??= $label;

        return (string) Html::a(
            self::visibleIconLabel($icon, $label),
            $url,
            $attributes,
        )->encode(false);
    }

    private static function buttonAttributes(string $label, string $variant, array $attributes): array
    {
        Html::addCssClass(
            $attributes,
            ['btn', 'btn-sm', 'btn-outline-' . $variant, 'app-task-action-btn', 'pt-2'],
        );

        $attributes['title'] ??= $label;
        $attributes['aria-label'] ??= $label;

        return $attributes;
    }

    private static function iconLabel(string $icon, string $label): string
    {
        return (string) Html::i('', ['class' => $icon])
            . (string) Html::span($label, ['class' => 'visually-hidden']);
    }

    private static function visibleIconLabel(string $icon, string $label): string
    {
        return (string) Html::i('', ['class' => $icon . ' me-1']) . Html::encode($label);
    }

    private static function deleteFooter(
        string $action,
        Csrf $csrf,
        string $submitIcon,
        string $submitLabel,
    ): string {
        $footer = '<form method="post" action="' . Html::encode($action) . '" class="d-flex flex-wrap justify-content-end gap-2 w-100">';
        $footer .= $csrf->hiddenInput();
        $footer .= (string) Html::button(Translate::t('Annulla'), [
            'type' => 'button',
            'class' => ['btn', 'btn-outline-secondary'],
            'data-bs-dismiss' => 'modal',
        ]);
        $footer .= (string) Html::button(
            (string) Html::i('', ['class' => $submitIcon . ' me-1']) . $submitLabel,
            [
                'type' => 'submit',
                'class' => ['btn', 'btn-danger', 'btn-shadow'],
            ],
        )->encode(false);
        $footer .= '</form>';

        return $footer;
    }

    private function __construct() {}
}
