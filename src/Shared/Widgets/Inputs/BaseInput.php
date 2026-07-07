<?php

declare(strict_types=1);

namespace App\Shared\Widgets\Inputs;

use Yiisoft\Form\Field\Base\InputField;
use Yiisoft\Html\Html;

abstract class BaseInput
{
    protected static function decorate(
        InputField $field,
        string $variant,
        string $icon = '',
        ?string $hint = null,
        array $validationErrors = [],
    ): InputField {
        $decorated = $field
            ->template("{label}\n{input}\n{hint}")
            ->addContainerClass('app-form-field')
            ->addLabelClass('app-form-field__label')
            ->addHintClass('app-form-field__hint')
            ->addInputClass('app-form-input__control')
            ->inputContainerTag('div')
            ->inputContainerClass('input-group', 'app-form-input', 'app-form-input--' . $variant, 'has-validation');

        if ($hint !== null && $hint !== '') {
            $decorated = $decorated->hint($hint);
        }

        if ($icon !== '') {
            $decorated = $decorated->beforeInput(self::renderIcon($icon));
        }

        return $decorated->afterInput(self::renderFeedback($validationErrors));
    }

    private static function renderIcon(string $icon): string
    {
        return (string) Html::span(
            (string) Html::i('', ['class' => $icon]),
            ['class' => 'input-group-text'],
        )->encode(false);
    }

    private static function renderFeedback(array $validationErrors): string
    {
        return (string) Html::div(
            InputValidation::firstError($validationErrors),
            [
                'class' => 'invalid-feedback app-form-input__feedback',
                'data-validation-feedback' => 'true',
                'aria-live' => 'polite',
            ],
        );
    }
}
