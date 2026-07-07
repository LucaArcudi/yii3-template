<?php

declare(strict_types=1);

namespace App\Shared\Widgets\Filters;

use Yiisoft\Html\Html;

final class FilterControl
{
    public const TRIGGER_OUTSIDE_CLICK = 'outside-click';
    public const TRIGGER_SELECT_MOUSELEAVE = 'select-mouseleave';

    public static function renderInputGroup(string $control, string $icon = '', string $wrapperClass = 'input-group'): string
    {
        $content = '';

        if ($icon !== '') {
            $content .= (string) Html::span(
                (string) Html::i('', ['class' => $icon]),
                ['class' => 'input-group-text'],
            )->encode(false);
        }

        $content .= $control;
        $content .= (string) Html::div('', [
            'class' => 'invalid-feedback app-form-input__feedback',
            'data-validation-feedback' => 'true',
            'aria-live' => 'polite',
        ]);

        return (string) Html::div($content, ['class' => $wrapperClass])->encode(false);
    }

    public static function autoSubmitAttributes(
        string|array $value = '',
        ?string $formId = null,
        string $trigger = self::TRIGGER_OUTSIDE_CLICK,
    ): array {
        return [
            'form' => $formId,
            'data-auto-filter-last-value' => self::autoSubmitValue($value),
            'data-auto-filter-trigger' => $trigger,
            ...self::validationAttributes(),
            'onkeydown' => self::submitOnEnterScript(),
        ];
    }

    public static function validationAttributes(): array
    {
        return [
            'data-filter-validation-field' => 'true',
        ];
    }

    public static function buildFieldId(string $widgetId, string $name): string
    {
        $normalized = preg_replace('/[^a-zA-Z0-9_-]/', '-', $name) ?? $name;
        return $widgetId . '-' . trim($normalized, '-');
    }

    private static function submitOnEnterScript(): string
    {
        return "if (event.key !== 'Enter') { return; }"
            . "event.preventDefault();"
            . "if (this.willValidate && !this.checkValidity()) { this.reportValidity(); return; }"
            . "this.dataset.autoFilterLastValue = this.multiple ? "
            . "JSON.stringify(Array.from(this.options).filter(function (option) { return option.selected; }).map(function (option) { return option.value; })) "
            . ": this.value;"
            . self::submitScript();
    }

    private static function autoSubmitValue(string|array $value): string
    {
        if (!is_array($value)) {
            return $value;
        }

        return json_encode(
            array_map(static fn(mixed $item): string => (string) $item, array_values($value)),
        ) ?: '[]';
    }

    private static function submitScript(): string
    {
        return "if (!this.form) { return; }"
            . "if (typeof this.form.requestSubmit === 'function') { this.form.requestSubmit(); return; }"
            . "this.form.submit();";
    }

    private function __construct() {}
}
