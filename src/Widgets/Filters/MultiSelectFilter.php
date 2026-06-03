<?php

declare(strict_types=1);

namespace App\Widgets\Filters;

use App\Widgets\Inputs\MultiSelectControl;
use Yiisoft\Html\Html;
use Yiisoft\Yii\DataView\Filter\Widget\Context;
use Yiisoft\Yii\DataView\Filter\Widget\FilterWidget;

use function array_key_exists;
use function array_values;
use function is_array;
use function is_scalar;
use function json_decode;
use function json_last_error;
use function trim;

use const JSON_ERROR_NONE;

final class MultiSelectFilter extends FilterWidget
{
    /**
     * @param array<array-key, string|int|float> $options
     */
    public function __construct(
        private readonly array $options = [],
        private readonly string $placeholder = 'Seleziona uno o piu elementi',
        private readonly string $icon = '',
        private readonly ?string $id = null,
        private readonly string $selectClass = 'form-select',
        private readonly array $inputAttributes = [],
        private readonly array $validationRules = [],
        private readonly bool $autoSubmit = true,
    ) {}

    public function renderFilter(Context $context): string
    {
        return self::renderStandalone(
            name: $context->property,
            values: self::valuesFromContext($context->value, $this->options),
            options: $this->options,
            placeholder: $this->placeholder,
            icon: $this->icon,
            id: $this->id ?? FilterControl::buildFieldId($context->formId, $context->property),
            formId: $context->formId,
            selectClass: $this->selectClass,
            inputAttributes: $this->inputAttributes,
            validationRules: $this->validationRules,
            autoSubmit: $this->autoSubmit,
        );
    }

    /**
     * @param array<array-key, string|int|float> $values
     * @param array<array-key, string|int|float> $options
     */
    public static function renderStandalone(
        string $name,
        array $values = [],
        array $options = [],
        string $placeholder = 'Seleziona uno o piu elementi',
        string $icon = '',
        ?string $id = null,
        ?string $formId = null,
        string $selectClass = 'form-select',
        array $inputAttributes = [],
        array $validationRules = [],
        bool $autoSubmit = true,
    ): string {
        $fieldId = $id ?? FilterControl::buildFieldId($formId ?? 'filter', $name);
        $labelId = $fieldId . '-label';
        $listId = $fieldId . '-listbox';
        $values = self::normalizeValues($values, $options);

        $nativeSelect = Html::select($name)
            ->multiple()
            ->addAttributes(FilterValidation::inputAttributes(
                type: 'select',
                inputAttributes: [
                    'id' => $fieldId,
                    'class' => trim($selectClass . ' app-form-input__control app-multi-select__native'),
                    'data-multi-select-native' => 'true',
                    ...$inputAttributes,
                    ...($autoSubmit
                        ? FilterControl::autoSubmitAttributes(
                            value: $values,
                            formId: $formId,
                            trigger: FilterControl::TRIGGER_OUTSIDE_CLICK,
                        )
                        : FilterControl::validationAttributes()),
                ],
                validationRules: $validationRules,
                allowedValues: FilterValidation::allowedValuesFromOptions($options),
            ))
            ->optionsData($options)
            ->values($values);

        if ($options === []) {
            $nativeSelect = $nativeSelect->disabled();
        }

        $control = (string) Html::tag(
            'div',
            MultiSelectControl::renderEnhanced(
                labelId: $labelId,
                listId: $listId,
                placeholder: $placeholder,
                empty: $options === [],
                emptyLabel: 'Nessuna opzione disponibile.',
                surfaceAttributes: ['aria-label' => $placeholder],
            )
            . (string) $nativeSelect,
            [
                'class' => 'app-multi-select',
                'data-multi-select' => 'true',
                'data-placeholder' => $placeholder,
                'data-empty-options-label' => 'Nessuna opzione disponibile.',
            ],
        )->encode(false);

        return FilterControl::renderInputGroup(
            control: $control,
            icon: $icon,
            wrapperClass: 'input-group app-filter-control app-filter-control--select app-form-input app-form-input--multiselect has-validation',
        );
    }

    /**
     * @param array<array-key, string|int|float> $options
     * @return string[]
     */
    private static function valuesFromContext(?string $value, array $options): array
    {
        if ($value === null || $value === '') {
            return [];
        }

        $decoded = json_decode($value, true);

        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            return self::normalizeValues($decoded, $options);
        }

        return self::normalizeValues([$value], $options);
    }

    /**
     * @param array<array-key, mixed> $values
     * @param array<array-key, string|int|float> $options
     * @return string[]
     */
    private static function normalizeValues(array $values, array $options): array
    {
        $selected = [];

        foreach ($values as $value) {
            if (!is_scalar($value)) {
                continue;
            }

            $value = trim((string) $value);

            if ($value === '') {
                continue;
            }

            $selected[$value] = $value;
        }

        if ($selected === []) {
            return [];
        }

        $ordered = [];

        foreach ($options as $value => $_label) {
            $value = (string) $value;

            if (array_key_exists($value, $selected)) {
                $ordered[] = $value;
                unset($selected[$value]);
            }
        }

        return [...$ordered, ...array_values($selected)];
    }
}
