<?php

declare(strict_types=1);

namespace App\Widgets\Inputs;

use App\Helpers\Translate;
use Yiisoft\Html\Html;

use function array_key_exists;
use function count;
use function preg_replace;
use function trim;

final class MultiSelectInput
{
    /**
     * @param array<array-key, string|int|float> $options
     * @param array<array-key, string|int|float> $values
     */
    public static function render(
        string $name,
        string $label,
        array $values = [],
        array $options = [],
        string $icon = '',
        ?string $hint = null,
        ?string $placeholder = null,
        array $inputAttributes = [],
        array $validationRules = [],
        array $validationErrors = [],
        bool $validated = false,
    ): string {
        $fieldId = self::buildFieldId($name);
        $labelId = $fieldId . '-label';
        $listId = $fieldId . '-listbox';
        $placeholder ??= Translate::t('Seleziona uno o piu elementi');

        $resolvedInputAttributes = InputValidation::inputAttributes('select', $inputAttributes, $validationRules);
        unset($resolvedInputAttributes['multiple'], $resolvedInputAttributes['name'], $resolvedInputAttributes['id']);

        if (!array_key_exists('size', $resolvedInputAttributes)) {
            $resolvedInputAttributes['size'] = max(6, min(10, max(1, count($options))));
        }

        if ($options === []) {
            $resolvedInputAttributes['disabled'] = true;
        }

        $nativeSelectClass = ['form-select', 'app-form-input__control', 'app-multi-select__native'];

        if ($validated) {
            $nativeSelectClass[] = $validationErrors !== [] ? 'is-invalid' : 'is-valid';
        }

        $nativeSelect = Html::select($name)
            ->multiple()
            ->addAttributes([
                'id' => $fieldId,
                'class' => $nativeSelectClass,
                'data-multi-select-native' => 'true',
                ...$resolvedInputAttributes,
            ])
            ->optionsData($options)
            ->values($values);

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
            'class' => ['input-group', 'app-form-input', 'app-form-input--multiselect', 'has-validation'],
        ]);

        if ($icon !== '') {
            $html .= self::renderIcon($icon);
        }

        $html .= (string) Html::tag(
            'div',
            MultiSelectControl::renderEnhanced(
                labelId: $labelId,
                listId: $listId,
                placeholder: $placeholder,
                empty: $options === [],
                emptyLabel: Translate::t('Nessun ruolo disponibile.'),
            )
            . (string) $nativeSelect,
            [
                'class' => 'app-multi-select',
                'data-multi-select' => 'true',
                'data-placeholder' => $placeholder,
                'data-empty-options-label' => Translate::t('Nessun ruolo disponibile.'),
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

        return $normalized !== '' ? $normalized : 'multi-select';
    }

    private function __construct() {}
}
