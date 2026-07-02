<?php

declare(strict_types=1);

namespace App\Widgets\Inputs;

use Yiisoft\Form\PureField\Field;

final class TextareaInput extends BaseInput
{
    public static function render(
        string $name,
        string $label,
        ?string $value = null,
        ?string $placeholder = null,
        string $icon = '',
        ?string $hint = null,
        array $inputAttributes = [],
        array $validationRules = [],
        array $validationErrors = [],
        bool $validated = false,
    ): string {
        $field = Field::textarea($name)
            ->inputData(InputValidation::inputData($name, $value, $validationRules, $validationErrors, $validated))
            ->label($label)
            ->value($value);

        if ($placeholder !== null) {
            $field = $field->placeholder($placeholder);
        }

        $resolvedInputAttributes = InputValidation::inputAttributes('textarea', $inputAttributes, $validationRules);
        if ($resolvedInputAttributes !== []) {
            $field = $field->addInputAttributes($resolvedInputAttributes);
        }

        return (string) self::decorate($field, 'textarea', $icon, $hint, $validationErrors);
    }

    private function __construct() {}
}
