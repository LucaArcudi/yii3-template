<?php

declare(strict_types=1);

namespace App\Shared\Widgets\Inputs;

use App\Shared\Helpers\Translate;
use Yiisoft\Html\Html;

final class MultiSelectControl
{
    public static function renderEnhanced(
        string $labelId,
        string $listId,
        string $placeholder,
        bool $empty,
        string $emptyLabel,
        array $surfaceAttributes = [],
    ): string {
        return (string) Html::tag(
            'div',
            self::renderSurface($labelId, $listId, $placeholder, $surfaceAttributes)
            . self::renderDropdown($listId, $empty, $emptyLabel),
            [
                'class' => 'app-multi-select__enhanced',
                'data-multi-select-enhanced' => 'true',
                'hidden' => true,
            ],
        )->encode(false);
    }

    private static function renderSurface(
        string $labelId,
        string $listId,
        string $placeholder,
        array $surfaceAttributes,
    ): string {
        return (string) Html::tag(
            'div',
            (string) Html::tag(
                'div',
                (string) Html::tag('div', '', [
                    'class' => 'app-multi-select__tags',
                    'data-multi-select-tags' => 'true',
                ])
                . (string) Html::tag(
                    'span',
                    $placeholder,
                    [
                        'class' => 'app-multi-select__placeholder',
                        'data-multi-select-placeholder' => 'true',
                    ],
                ),
                ['class' => 'app-multi-select__value'],
            )->encode(false)
            . (string) Html::tag('span', '', [
                'class' => 'app-multi-select__summary',
                'data-multi-select-summary' => 'true',
            ])
            . (string) Html::span(
                (string) Html::i('', ['class' => 'fa-solid fa-chevron-down']),
                ['class' => 'app-multi-select__caret'],
            )->encode(false),
            [
                'class' => 'app-multi-select__surface',
                'data-multi-select-surface' => 'true',
                'tabindex' => 0,
                'role' => 'combobox',
                'aria-haspopup' => 'listbox',
                'aria-expanded' => 'false',
                'aria-controls' => $listId,
                'aria-labelledby' => $labelId,
                ...$surfaceAttributes,
            ],
        )->encode(false);
    }

    private static function renderDropdown(string $listId, bool $empty, string $emptyLabel): string
    {
        return (string) Html::tag(
            'div',
            (string) Html::tag(
                'div',
                (string) Html::tag(
                    'span',
                    $empty ? $emptyLabel : Translate::t('{selected} di {total} selezionati', ['selected' => 0, 'total' => 0]),
                    [
                        'class' => 'app-multi-select__counter',
                        'data-multi-select-counter' => 'true',
                    ],
                )
                . (string) Html::tag(
                    'div',
                    (string) Html::button(
                        Translate::t('Seleziona tutti'),
                        [
                            'type' => 'button',
                            'class' => 'btn btn-link btn-sm app-multi-select__action',
                            'data-multi-select-select-all' => 'true',
                        ],
                    )
                    . (string) Html::button(
                        'Svuota',
                        [
                            'type' => 'button',
                            'class' => 'btn btn-link btn-sm app-multi-select__action',
                            'data-multi-select-clear' => 'true',
                        ],
                    ),
                    ['class' => 'app-multi-select__actions'],
                )->encode(false),
                ['class' => 'app-multi-select__toolbar'],
            )->encode(false)
            . (string) Html::tag(
                'div',
                '',
                [
                    'class' => 'app-multi-select__list',
                    'id' => $listId,
                    'role' => 'listbox',
                    'aria-multiselectable' => 'true',
                    'data-multi-select-list' => 'true',
                ],
            ),
            [
                'class' => 'app-multi-select__dropdown',
                'data-multi-select-dropdown' => 'true',
                'hidden' => true,
            ],
        )->encode(false);
    }

    private function __construct() {}
}
