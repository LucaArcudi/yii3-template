<?php

declare(strict_types=1);

namespace App\Widgets\Filters;

use Yiisoft\Html\Html;
use Yiisoft\Yii\DataView\Filter\Widget\Context;
use Yiisoft\Yii\DataView\Filter\Widget\FilterWidget;

class DateFilter extends FilterWidget
{
    public function __construct(
        private readonly string $placeholder = 'YYYY-MM-DD',
        private readonly string $icon = 'fa-regular fa-calendar',
        private readonly ?string $id = null,
        private readonly string $inputClass = 'form-control form-control-sm',
        private readonly array $inputAttributes = [],
        private readonly array $validationRules = [],
        private readonly bool $autoSubmit = true,
    ) {}

    public function renderFilter(Context $context): string
    {
        return self::renderStandalone(
            name: $context->property,
            value: (string) ($context->value ?? ''),
            placeholder: $this->placeholder,
            icon: $this->icon,
            id: $this->id ?? FilterControl::buildFieldId($context->formId, $context->property),
            formId: $context->formId,
            inputClass: $this->inputClass,
            inputAttributes: $this->inputAttributes,
            validationRules: $this->validationRules,
            autoSubmit: $this->autoSubmit,
        );
    }

    public static function renderStandalone(
        string $name,
        string $value = '',
        string $placeholder = 'YYYY-MM-DD',
        string $icon = 'fa-regular fa-calendar',
        ?string $id = null,
        ?string $formId = null,
        string $inputClass = 'form-control',
        array $inputAttributes = [],
        array $validationRules = [],
        bool $autoSubmit = true,
    ): string {
        $attributes = FilterValidation::inputAttributes(
            type: 'text',
            inputAttributes: [
                'autocomplete' => 'off',
                'data-date-picker' => 'true',
                'data-date-validation-message' => 'Inserisci una data valida nel formato YYYY-MM-DD.',
                'id' => $id,
                'inputmode' => 'numeric',
                'class' => trim($inputClass . ' app-form-input__control app-date-input'),
                'placeholder' => $placeholder,
                'title' => $placeholder,
                ...$inputAttributes,
                ...($autoSubmit
                    ? FilterControl::autoSubmitAttributes($value, $formId)
                    : FilterControl::validationAttributes()),
            ],
            validationRules: $validationRules,
        );

        Html::addCssClass($attributes, 'app-date-input');

        $input = Html::input('text', $name, $value, $attributes);

        return FilterControl::renderInputGroup(
            control: (string) $input,
            icon: $icon,
            wrapperClass: 'input-group app-filter-control app-filter-control--date app-form-input app-form-input--date has-validation',
        );
    }
}
