<?php

declare(strict_types=1);

namespace App\Shared\Widgets\Inputs;

use App\Shared\Helpers\Translate;
use Yiisoft\Html\Html;

use function preg_replace;
use function trim;

final class SelectInput
{
    /**
     * @param array<array-key, string|int|float|array<array-key, string|int|float>> $options
     */
    public static function render(
        string $name,
        string $label,
        mixed $value = null,
        array $options = [],
        ?string $prompt = null,
        string $icon = '',
        ?string $hint = null,
        array $inputAttributes = [],
        array $validationRules = [],
        array $validationErrors = [],
        bool $validated = false,
    ): string {
        $fieldId = self::buildFieldId($name);
        $labelId = $fieldId . '-label';
        $listId = $fieldId . '-listbox';
        $placeholder = $prompt ?? Translate::t('Seleziona un elemento');

        $resolvedInputAttributes = InputValidation::inputAttributes('select', $inputAttributes, $validationRules);
        unset($resolvedInputAttributes['multiple'], $resolvedInputAttributes['name'], $resolvedInputAttributes['id']);

        if ($options === []) {
            $resolvedInputAttributes['disabled'] = true;
        }

        $nativeSelectClass = [
            'form-select',
            'app-form-input__control',
            'app-multi-select__native',
            'app-single-select__native',
        ];

        if ($validated) {
            $nativeSelectClass[] = $validationErrors !== [] ? 'is-invalid' : 'is-valid';
        }

        $nativeSelect = Html::select($name)
            ->addAttributes([
                'id' => $fieldId,
                'class' => $nativeSelectClass,
                'data-single-select-native' => 'true',
                ...$resolvedInputAttributes,
            ])
            ->optionsData($options)
            ->value($value);

        if ($prompt !== null) {
            $nativeSelect = $nativeSelect->prompt($prompt);
        }

        $html = '<div class="app-form-field">';
        $html .= (string) Html::tag(
            'div',
            $label,
            [
                'class' => 'app-form-field__label',
                'id' => $labelId,
            ],
        );
        $html .= Html::openTag('div', [
            'class' => ['input-group', 'app-form-input', 'app-form-input--select', 'has-validation'],
        ]);

        if ($icon !== '') {
            $html .= self::renderIcon($icon);
        }

        $html .= (string) Html::tag(
            'div',
            self::renderEnhanced($labelId, $listId, $placeholder, $options === [])
            . (string) $nativeSelect,
            [
                'class' => ['app-single-select', 'app-multi-select'],
                'data-single-select' => 'true',
                'data-placeholder' => $placeholder,
                'data-empty-options-label' => Translate::t('Nessuna opzione disponibile.'),
                'data-has-prompt' => $prompt !== null ? 'true' : 'false',
            ],
        )->encode(false);
        $html .= self::renderFeedback($validationErrors);
        $html .= Html::closeTag('div');

        if ($hint !== null && $hint !== '') {
            $html .= (string) Html::tag(
                'div',
                $hint,
                ['class' => 'app-form-field__hint'],
            );
        }

        $html .= '</div>';

        return $html;
    }

    private static function renderEnhanced(
        string $labelId,
        string $listId,
        string $placeholder,
        bool $empty,
    ): string {
        return (string) Html::tag(
            'div',
            self::renderSurface($labelId, $listId, $placeholder)
            . self::renderDropdown($listId, $empty),
            [
                'class' => 'app-single-select__enhanced',
                'data-single-select-enhanced' => 'true',
                'hidden' => true,
            ],
        )->encode(false);
    }

    private static function renderSurface(string $labelId, string $listId, string $placeholder): string
    {
        return (string) Html::tag(
            'div',
            (string) Html::tag(
                'div',
                (string) Html::tag('div', '', [
                    'class' => 'app-multi-select__tags',
                    'data-single-select-tags' => 'true',
                ])
                . (string) Html::tag(
                    'span',
                    $placeholder,
                    [
                        'class' => 'app-multi-select__placeholder',
                        'data-single-select-placeholder' => 'true',
                    ],
                ),
                ['class' => 'app-multi-select__value'],
            )->encode(false)
            . (string) Html::tag('span', '', [
                'class' => 'app-multi-select__summary',
                'data-single-select-summary' => 'true',
            ])
            . (string) Html::span(
                (string) Html::i('', ['class' => 'fa-solid fa-chevron-down']),
                ['class' => 'app-multi-select__caret'],
            )->encode(false),
            [
                'class' => 'app-multi-select__surface',
                'data-single-select-surface' => 'true',
                'tabindex' => 0,
                'role' => 'combobox',
                'aria-haspopup' => 'listbox',
                'aria-expanded' => 'false',
                'aria-controls' => $listId,
                'aria-labelledby' => $labelId,
            ],
        )->encode(false);
    }

    private static function renderDropdown(string $listId, bool $empty): string
    {
        return (string) Html::tag(
            'div',
            (string) Html::tag(
                'div',
                (string) Html::tag(
                    'span',
                    $empty ? Translate::t('Nessuna opzione disponibile.') : Translate::t('{selected} di {total} selezionati', ['selected' => 0, 'total' => 0]),
                    [
                        'class' => 'app-multi-select__counter',
                        'data-single-select-counter' => 'true',
                    ],
                )
                . (string) Html::tag(
                    'div',
                    (string) Html::button(
                        'Svuota',
                        [
                            'type' => 'button',
                            'class' => 'btn btn-link btn-sm app-multi-select__action',
                            'data-single-select-clear' => 'true',
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
                    'data-single-select-list' => 'true',
                ],
            ),
            [
                'class' => 'app-multi-select__dropdown',
                'data-single-select-dropdown' => 'true',
                'hidden' => true,
            ],
        )->encode(false);
    }

    private static function renderIcon(string $icon): string
    {
        return (string) Html::span(
            (string) Html::i('', ['class' => $icon]),
            ['class' => 'input-group-text'],
        )->encode(false);
    }

    private static function renderFeedback(array $validationErrors): string
    {
        return (string) Html::tag(
            'div',
            InputValidation::firstError($validationErrors),
            [
                'class' => 'invalid-feedback app-form-input__feedback',
                'data-validation-feedback' => 'true',
                'aria-live' => 'polite',
            ],
        );
    }

    private static function buildFieldId(string $name): string
    {
        $normalized = preg_replace('/[\[\]]+/', '-', $name);
        $normalized = preg_replace('/[^a-zA-Z0-9_-]+/', '-', (string) $normalized);

        $normalized = trim((string) $normalized, '-');

        return $normalized !== '' ? $normalized : 'select';
    }

    private function __construct() {}
}
