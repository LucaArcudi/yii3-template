<?php

declare(strict_types=1);

namespace App\Widgets\Inputs;

use App\Helpers\Translate;
use Yiisoft\Form\PureField\Field;
use Yiisoft\Html\Html;

final class DateInput extends BaseInput
{
    public static function render(
        string $name,
        string $label,
        ?string $value = null,
        ?string $placeholder = 'YYYY-MM-DD',
        string $icon = 'fa-regular fa-calendar',
        ?string $hint = null,
        array $inputAttributes = [],
        array $validationRules = [],
        array $validationErrors = [],
        bool $validated = false,
    ): string {
        $field = Field::text($name)
            ->inputData(InputValidation::inputData($name, $value, $validationRules, $validationErrors, $validated))
            ->label($label)
            ->value($value)
            ->placeholder($placeholder ?? 'YYYY-MM-DD');

        $resolvedInputAttributes = InputValidation::inputAttributes(
            'text',
            [
                'autocomplete' => 'off',
                'data-date-picker' => 'true',
                'data-date-validation-message' => Translate::t('Inserisci una data valida nel formato YYYY-MM-DD.'),
                'inputmode' => 'numeric',
                ...($placeholder !== null ? ['title' => $placeholder] : []),
                ...$inputAttributes,
            ],
            $validationRules,
        );
        Html::addCssClass($resolvedInputAttributes, ['form-control', 'app-date-input']);

        if ($resolvedInputAttributes !== []) {
            $field = $field->addInputAttributes($resolvedInputAttributes);
        }

        return (string) self::decorate($field, 'date', $icon, $hint, $validationErrors)
            ->addInputContainerClass('app-filter-control', 'app-filter-control--date');
    }

    private function __construct() {}
}
