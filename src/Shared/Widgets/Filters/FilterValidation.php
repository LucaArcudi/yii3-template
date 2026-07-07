<?php

declare(strict_types=1);

namespace App\Shared\Widgets\Filters;

use App\Shared\Helpers\Translate;
use App\Shared\Widgets\Inputs\InputValidation;
use Yiisoft\Validator\Rule\In;
use Yiisoft\Validator\Rule\Integer;
use Yiisoft\Validator\Rule\Length;

use function array_key_exists;
use function array_values;
use function is_array;
use function is_scalar;
use function json_encode;

final class FilterValidation
{
    private const INVALID_VALUE_MESSAGE = 'Valore filtro non valido.';

    /**
     * @param array $inputAttributes
     * @param array $validationRules
     * @param array<array-key, mixed> $allowedValues
     * @return array
     */
    public static function inputAttributes(
        string $type,
        array $inputAttributes = [],
        array $validationRules = [],
        array $allowedValues = [],
    ): array {
        $attributes = array_merge(
            self::lengthAttributesFromRules($type, $validationRules),
            InputValidation::inputAttributes($type, $inputAttributes, self::nativeRules($validationRules)),
        );
        $allowedValues = self::mergeAllowedValues(
            self::allowedValuesFromRules($validationRules),
            $allowedValues,
        );

        if ($allowedValues !== []) {
            $attributes['data-filter-allowed-values'] = json_encode($allowedValues) ?: '[]';
            $attributes['data-filter-validation-message'] = Translate::t(self::INVALID_VALUE_MESSAGE);
        }

        return $attributes;
    }

    /**
     * @param array $validationRules
     */
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

    /**
     * @param array<array-key, string|int|float|array<array-key, string|int|float>> $options
     * @return string[]
     */
    public static function allowedValuesFromOptions(array $options): array
    {
        $values = [];

        foreach ($options as $value => $label) {
            if (is_array($label)) {
                foreach ($label as $groupValue => $_groupLabel) {
                    self::appendAllowedValue($values, $groupValue);
                }

                continue;
            }

            self::appendAllowedValue($values, $value);
        }

        return array_values($values);
    }

    /**
     * @param array $validationRules
     * @return array
     */
    private static function nativeRules(array $validationRules): array
    {
        $rules = [];

        foreach ($validationRules as $rule) {
            if ($rule instanceof Length) {
                continue;
            }

            $rules[] = $rule;
        }

        return $rules;
    }

    /**
     * @param array $validationRules
     * @return array
     */
    private static function lengthAttributesFromRules(string $type, array $validationRules): array
    {
        if ($type === 'select') {
            return [];
        }

        $attributes = [];

        foreach ($validationRules as $rule) {
            if (!$rule instanceof Length) {
                continue;
            }

            if ($rule->getExactly() !== null) {
                $attributes['data-filter-exact-length'] = $rule->getExactly();
                continue;
            }

            if ($rule->getMin() !== null) {
                $attributes['data-filter-min-length'] = $rule->getMin();
            }

            if ($rule->getMax() !== null) {
                $attributes['data-filter-max-length'] = $rule->getMax();
            }
        }

        return $attributes;
    }

    /**
     * @param array $validationRules
     * @return string[]
     */
    private static function allowedValuesFromRules(array $validationRules): array
    {
        $values = [];

        foreach ($validationRules as $rule) {
            if (!$rule instanceof In || $rule->isNot()) {
                continue;
            }

            foreach ($rule->getValues() as $value) {
                self::appendAllowedValue($values, $value);
            }
        }

        return array_values($values);
    }

    /**
     * @param string[] $baseValues
     * @param array<array-key, mixed> $extraValues
     * @return string[]
     */
    private static function mergeAllowedValues(array $baseValues, array $extraValues): array
    {
        $values = [];

        foreach ($baseValues as $value) {
            self::appendAllowedValue($values, $value);
        }

        foreach ($extraValues as $value) {
            self::appendAllowedValue($values, $value);
        }

        return array_values($values);
    }

    /**
     * @param array<string, string> $values
     */
    private static function appendAllowedValue(array &$values, mixed $value): void
    {
        if (!is_scalar($value)) {
            return;
        }

        $value = (string) $value;

        if (array_key_exists($value, $values)) {
            return;
        }

        $values[$value] = $value;
    }

    private function __construct() {}
}
