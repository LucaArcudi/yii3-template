<?php

declare(strict_types=1);

namespace App\Shared\Widgets\Filters;

use Yiisoft\Html\Html;
use Yiisoft\Yii\DataView\Filter\Widget\Context;
use Yiisoft\Yii\DataView\Filter\Widget\FilterWidget;

class TextFilter extends FilterWidget
{
    public function __construct(
        private readonly string $placeholder = '',
        private readonly string $icon = '',
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
        string $placeholder = '',
        string $icon = '',
        ?string $id = null,
        ?string $formId = null,
        string $inputClass = 'form-control',
        array $inputAttributes = [],
        array $validationRules = [],
        bool $autoSubmit = true,
    ): string {
        $inputType = FilterValidation::inputType('text', $validationRules);
        $attributes = FilterValidation::inputAttributes(
            type: $inputType,
            inputAttributes: [
                'id' => $id,
                'class' => trim($inputClass . ' app-form-input__control'),
                'placeholder' => $placeholder,
                ...$inputAttributes,
                ...($autoSubmit
                    ? FilterControl::autoSubmitAttributes($value, $formId)
                    : FilterControl::validationAttributes()),
            ],
            validationRules: $validationRules,
        );

        $input = Html::input($inputType, $name, $value, $attributes);

        return FilterControl::renderInputGroup(
            control: (string) $input,
            icon: $icon,
            wrapperClass: 'input-group app-filter-control app-filter-control--text app-form-input app-form-input--text has-validation',
        );
    }
}
