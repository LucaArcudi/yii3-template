<?php

declare(strict_types=1);

namespace App\Shared\Widgets\Filters;

use App\Shared\Helpers\Translate;
use Yiisoft\Html\Html;
use Yiisoft\Yii\DataView\Filter\Widget\Context;
use Yiisoft\Yii\DataView\Filter\Widget\FilterWidget;

final class SelectFilter extends FilterWidget
{
    /**
     * @param array<array-key, string|int|float|array<array-key, string|int|float>> $options
     */
    public function __construct(
        private readonly array $options = [],
        private readonly ?string $prompt = null,
        private readonly string $icon = '',
        private readonly ?string $id = null,
        private readonly string $selectClass = 'form-select form-select-sm',
        private readonly array $inputAttributes = [],
        private readonly array $validationRules = [],
        private readonly bool $autoSubmit = true,
        private readonly bool $persistent = false,
    ) {}

    public function renderFilter(Context $context): string
    {
        return self::renderStandalone(
            name: $context->property,
            value: (string) ($context->value ?? ''),
            options: $this->options,
            prompt: $this->prompt,
            icon: $this->icon,
            id: $this->id ?? FilterControl::buildFieldId($context->formId, $context->property),
            formId: $context->formId,
            selectClass: $this->selectClass,
            inputAttributes: $this->inputAttributes,
            validationRules: $this->validationRules,
            autoSubmit: $this->autoSubmit,
            persistent: $this->persistent,
        );
    }

    /**
     * @param array<array-key, string|int|float|array<array-key, string|int|float>> $options
     */
    public static function renderStandalone(
        string $name,
        string $value = '',
        array $options = [],
        ?string $prompt = null,
        string $icon = '',
        ?string $id = null,
        ?string $formId = null,
        string $selectClass = 'form-select',
        array $inputAttributes = [],
        array $validationRules = [],
        bool $autoSubmit = true,
        bool $persistent = false,
    ): string {
        $fieldId = $id ?? FilterControl::buildFieldId($formId ?? 'filter', $name);
        $labelId = $fieldId . '-label';
        $listId = $fieldId . '-listbox';
        $placeholder = $prompt ?? Translate::t('Seleziona un elemento');

        $nativeSelect = Html::select($name)
            ->addAttributes(FilterValidation::inputAttributes(
                type: 'select',
                inputAttributes: [
                    'id' => $fieldId,
                    'class' => trim($selectClass . ' app-form-input__control app-multi-select__native app-single-select__native'),
                    'data-single-select-native' => 'true',
                    ...$inputAttributes,
                    ...($autoSubmit
                        ? FilterControl::autoSubmitAttributes(
                            value: $value,
                            formId: $formId,
                            trigger: FilterControl::TRIGGER_SELECT_MOUSELEAVE,
                        )
                        : FilterControl::validationAttributes()),
                ],
                validationRules: $validationRules,
                allowedValues: FilterValidation::allowedValuesFromOptions($options),
            ))
            ->optionsData($options)
            ->value($value);

        if ($prompt !== null) {
            $nativeSelect = $nativeSelect->prompt($prompt);
        }

        if ($options === []) {
            $nativeSelect = $nativeSelect->disabled();
        }

        $controlAttributes = [
            'class' => ['app-single-select', 'app-multi-select'],
            'data-single-select' => 'true',
            'data-placeholder' => $placeholder,
            'data-empty-options-label' => Translate::t('Nessuna opzione disponibile.'),
            'data-has-prompt' => $prompt !== null ? 'true' : 'false',
        ];

        if ($persistent) {
            $controlAttributes['data-single-select-persistent'] = 'true';
        }

        if ($autoSubmit) {
            $controlAttributes['data-auto-filter-select-wrapper'] = 'true';
        }

        $control = (string) Html::tag(
            'div',
            self::renderEnhanced($labelId, $listId, $placeholder, $options === [])
            . (string) $nativeSelect,
            $controlAttributes,
        )->encode(false);

        return FilterControl::renderInputGroup(
            control: $control,
            icon: $icon,
            wrapperClass: 'input-group app-filter-control app-filter-control--select app-form-input app-form-input--select has-validation',
        );
    }

    private static function renderEnhanced(
        string $labelId,
        string $listId,
        string $placeholder,
        bool $empty,
    ): string {
        return (string) Html::tag(
            'div',
            self::renderSurface($labelId, $listId, $placeholder)
            . self::renderDropdown($listId, $empty),
            [
                'class' => 'app-single-select__enhanced',
                'data-single-select-enhanced' => 'true',
                'hidden' => true,
            ],
        )->encode(false);
    }

    private static function renderSurface(string $labelId, string $listId, string $placeholder): string
    {
        return (string) Html::tag(
            'div',
            (string) Html::tag(
                'div',
                (string) Html::tag('div', '', [
                    'class' => 'app-multi-select__tags',
                    'data-single-select-tags' => 'true',
                ])
                . (string) Html::tag(
                    'span',
                    $placeholder,
                    [
                        'class' => 'app-multi-select__placeholder',
                        'data-single-select-placeholder' => 'true',
                    ],
                ),
                ['class' => 'app-multi-select__value'],
            )->encode(false)
            . (string) Html::tag('span', '', [
                'class' => 'app-multi-select__summary',
                'data-single-select-summary' => 'true',
            ])
            . (string) Html::span(
                (string) Html::i('', ['class' => 'fa-solid fa-chevron-down']),
                ['class' => 'app-multi-select__caret'],
            )->encode(false),
            [
                'class' => 'app-multi-select__surface',
                'data-single-select-surface' => 'true',
                'tabindex' => 0,
                'role' => 'combobox',
                'aria-haspopup' => 'listbox',
                'aria-expanded' => 'false',
                'aria-controls' => $listId,
                'aria-labelledby' => $labelId,
                'aria-label' => $placeholder,
            ],
        )->encode(false);
    }

    private static function renderDropdown(string $listId, bool $empty): string
    {
        return (string) Html::tag(
            'div',
            (string) Html::tag(
                'div',
                (string) Html::tag(
                    'span',
                    $empty ? Translate::t('Nessuna opzione disponibile.') : Translate::t('{selected} di {total} selezionati', ['selected' => 0, 'total' => 0]),
                    [
                        'class' => 'app-multi-select__counter',
                        'data-single-select-counter' => 'true',
                    ],
                )
                . (string) Html::tag(
                    'div',
                    (string) Html::button(
                        'Svuota',
                        [
                            'type' => 'button',
                            'class' => 'btn btn-link btn-sm app-multi-select__action',
                            'data-single-select-clear' => 'true',
                        ],
                    ),
                    ['class' => 'app-multi-select__actions'],
                )->encode(false),
                ['class' => 'app-multi-select__toolbar'],
            )->encode(false)
            . (string) Html::tag(
                'div',
                '',
                [
                    'class' => 'app-multi-select__list',
                    'id' => $listId,
                    'role' => 'listbox',
                    'data-single-select-list' => 'true',
                ],
            ),
            [
                'class' => 'app-multi-select__dropdown',
                'data-single-select-dropdown' => 'true',
                'hidden' => true,
            ],
        )->encode(false);
    }
}
