<?php

declare(strict_types=1);

namespace App\Widgets\Inputs;

use Yiisoft\Form\PureField\InputData;
use Yiisoft\Validator\Rule\Integer;
use Yiisoft\Validator\Rule\Length;
use Yiisoft\Validator\Rule\Required;

final class InputValidation
{
    public static function inputData(
        string $name,
        mixed $value,
        array $validationRules = [],
        array $validationErrors = [],
        bool $validated = false,
    ): InputData {
        return new InputData(
            name: $name,
            value: $value,
            validationRules: $validationRules,
            validationErrors: self::resolveValidationErrors($value, $validationErrors, $validated),
        );
    }

    public static function inputAttributes(string $type, array $inputAttributes = [], array $validationRules = []): array
    {
        return array_merge(self::attributesFromRules($type, $validationRules), $inputAttributes);
    }

    public static function inputType(string $defaultType, array $validationRules = []): string
    {
        if ($defaultType !== 'text') {
            return $defaultType;
        }

        foreach ($validationRules as $rule) {
            if ($rule instanceof Integer) {
                return 'number';
            }
        }

        return $defaultType;
    }

    public static function firstError(array $validationErrors): string
    {
        return (string) ($validationErrors[0] ?? '');
    }

    private static function resolveValidationErrors(
        mixed $value,
        array $validationErrors,
        bool $validated,
    ): ?array {
        if (!$validated) {
            return null;
        }

        if ($validationErrors !== []) {
            return array_values($validationErrors);
        }

        return self::hasRenderableValue($value) ? [] : null;
    }

    private static function attributesFromRules(string $type, array $validationRules): array
    {
        $attributes = [];

        foreach ($validationRules as $rule) {
            if ($rule instanceof Required) {
                $attributes['required'] = true;
                continue;
            }

            if ($rule instanceof Length && $type !== 'select') {
                if ($rule->getExactly() !== null) {
                    $attributes['data-input-exact-length'] = $rule->getExactly();
                    continue;
                }

                if ($rule->getMin() !== null) {
                    $attributes['data-input-min-length'] = $rule->getMin();
                }

                if ($rule->getMax() !== null) {
                    $attributes['data-input-max-length'] = $rule->getMax();
                }

                continue;
            }

            if ($rule instanceof Integer && ($type === 'text' || $type === 'number')) {
                if ($type === 'text') {
                    $attributes['inputmode'] = 'numeric';
                    $attributes['pattern'] = '[+-]?\\d+';
                } else {
                    $attributes['step'] = 1;
                }

                if ($rule->getMin() !== null) {
                    $attributes['min'] = $rule->getMin();
                }

                if ($rule->getMax() !== null) {
                    $attributes['max'] = $rule->getMax();
                }
            }
        }

        return $attributes;
    }

    private static function hasRenderableValue(mixed $value): bool
    {
        return $value !== null && $value !== '';
    }

    private function __construct() {}
}
