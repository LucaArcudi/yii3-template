<?php

declare(strict_types=1);

namespace App\Widgets\Filters;

use InvalidArgumentException;
use Yiisoft\Html\Html;
use Yiisoft\Yii\DataView\Filter\Widget\Context;
use Yiisoft\Yii\DataView\Filter\Widget\FilterWidget;

use function array_key_exists;
use function array_values;
use function is_array;
use function is_scalar;
use function json_encode;

class FilterField
{
    public static function render(
        array $field,
        array $filters,
        string $formId,
        string $labelClass = 'form-label',
    ): string {
        $name = (string) ($field['name'] ?? '');
        $label = (string) ($field['label'] ?? ucfirst($name));
        $columnClass = (string) ($field['columnClass'] ?? 'col-12 col-md-4');
        $id = (string) ($field['id'] ?? FilterControl::buildFieldId($formId, $name));
        $value = array_key_exists($name, $filters)
            ? self::contextValue($filters[$name])
            : self::contextValue($field['value'] ?? '');

        if ($name === '') {
            throw new InvalidArgumentException('Filter field "name" is required.');
        }

        $widget = self::resolveWidget($field, $name, $id);

        $control = $widget
            ->withContext(new Context(
                property: $name,
                value: $value !== '' ? $value : null,
                formId: $formId,
            ))
            ->render();

        $content = '';

        if ($label !== '') {
            $content .= (string) Html::label($label, $id)->addAttributes([
                'class' => $labelClass,
                'id' => $id . '-label',
            ]);
        }

        $content .= $control;

        return (string) Html::div($content, ['class' => $columnClass])->encode(false);
    }

    private static function resolveWidget(array $field, string $name, string $id): FilterWidget
    {
        $widget = strtolower((string) ($field['widget'] ?? ''));
        $widgetOptions = is_array($field['widgetOptions'] ?? null) ? $field['widgetOptions'] : [];
        $icon = (string) ($widgetOptions['icon'] ?? ($field['icon'] ?? ''));
        $placeholder = (string) ($widgetOptions['placeholder'] ?? ($field['placeholder'] ?? ''));
        $inputClass = (string) ($widgetOptions['inputClass'] ?? ($field['inputClass'] ?? ''));
        $inputAttributes = self::arrayOption($field, $widgetOptions, 'inputAttributes');
        $validationRules = self::arrayOption($field, $widgetOptions, 'validationRules');
        $autoSubmit = self::boolOption($field, $widgetOptions, 'autoSubmit', true);
        $persistent = self::boolOption($field, $widgetOptions, 'persistent', false);

        return match ($widget) {
            'textfield', 'textfilter' => new TextFilter(
                placeholder: $placeholder,
                icon: $icon,
                id: $id,
                inputClass: $inputClass !== '' ? $inputClass : 'form-control',
                inputAttributes: $inputAttributes,
                validationRules: $validationRules,
                autoSubmit: $autoSubmit,
            ),
            'selectfield', 'selectfilter' => new SelectFilter(
                options: is_array($widgetOptions['options'] ?? null)
                    ? $widgetOptions['options']
                    : (is_array($field['options'] ?? null) ? $field['options'] : []),
                prompt: isset($widgetOptions['prompt']) ? (string) $widgetOptions['prompt'] : (isset($field['prompt']) ? (string) $field['prompt'] : null),
                icon: $icon,
                id: $id,
                selectClass: $inputClass !== '' ? $inputClass : 'form-select',
                inputAttributes: $inputAttributes,
                validationRules: $validationRules,
                autoSubmit: $autoSubmit,
                persistent: $persistent,
            ),
            'multiselectfield', 'multiselectfilter' => new MultiSelectFilter(
                options: is_array($widgetOptions['options'] ?? null)
                    ? $widgetOptions['options']
                    : (is_array($field['options'] ?? null) ? $field['options'] : []),
                placeholder: $placeholder !== '' ? $placeholder : 'Seleziona uno o piu elementi',
                icon: $icon,
                id: $id,
                selectClass: $inputClass !== '' ? $inputClass : 'form-select',
                inputAttributes: $inputAttributes,
                validationRules: $validationRules,
                autoSubmit: $autoSubmit,
            ),
            'datefield', 'datefilter' => new DateFilter(
                placeholder: $placeholder !== '' ? $placeholder : 'YYYY-MM-DD',
                icon: $icon !== '' ? $icon : 'fa-regular fa-calendar',
                id: $id,
                inputClass: $inputClass !== '' ? $inputClass : 'form-control',
                inputAttributes: $inputAttributes,
                validationRules: $validationRules,
                autoSubmit: $autoSubmit,
            ),
            default => throw new InvalidArgumentException(
                sprintf(
                    'Filter field "%s" has unknown widget "%s". Use "textFilter", "selectFilter", "multiSelectFilter", or "dateFilter".',
                    $name,
                    (string) ($field['widget'] ?? ''),
                ),
            ),
        };
    }

    private static function arrayOption(array $field, array $widgetOptions, string $name): array
    {
        if (is_array($widgetOptions[$name] ?? null)) {
            return $widgetOptions[$name];
        }

        return is_array($field[$name] ?? null) ? $field[$name] : [];
    }

    private static function boolOption(array $field, array $widgetOptions, string $name, bool $default): bool
    {
        if (is_bool($widgetOptions[$name] ?? null)) {
            return $widgetOptions[$name];
        }

        return is_bool($field[$name] ?? null) ? $field[$name] : $default;
    }

    private static function contextValue(mixed $value): string
    {
        if (is_array($value)) {
            $values = [];

            foreach ($value as $item) {
                if (is_scalar($item) && (string) $item !== '') {
                    $values[] = (string) $item;
                }
            }

            return json_encode(array_values($values)) ?: '[]';
        }

        return is_scalar($value) ? (string) $value : '';
    }

    private function __construct() {}
}
