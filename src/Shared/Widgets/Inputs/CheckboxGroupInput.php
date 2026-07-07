<?php

declare(strict_types=1);

namespace App\Shared\Widgets\Inputs;

use App\Shared\Helpers\Translate;
use Yiisoft\Html\Html;

final class CheckboxGroupInput
{
    public static function render(
        string $name,
        string $label,
        array $groups,
        array $selectedValues = [],
        ?string $hint = null,
        array $validationErrors = [],
        bool $validated = false,
    ): string {
        $selectedMap = [];

        foreach ($selectedValues as $value) {
            $selectedMap[(string) $value] = true;
        }

        $pickerClasses = ['app-permission-picker'];

        if ($validated) {
            $pickerClasses[] = $validationErrors !== [] ? 'is-invalid' : 'is-valid';
        }

        $html = '<div class="app-form-field">';
        $html .= '<div class="app-form-field__label">' . Html::encode($label) . '</div>';
        $html .= Html::openTag('div', ['class' => $pickerClasses]);

        if ($groups === []) {
            $html .= (string) Html::div(
                Translate::t('Nessun permesso disponibile. Crea prima almeno un permesso associabile.'),
                ['class' => 'alert alert-light mb-0'],
            );
        } else {
            $html .= '<div class="app-permission-groups">';

            foreach ($groups as $group) {
                $items = $group['items'] ?? [];
                $groupLabel = (string) ($group['label'] ?? 'Generale');

                $html .= '<section class="app-permission-group">';
                $html .= '<div class="app-permission-group__header">';
                $html .= '<div class="app-permission-group__title">' . Html::encode($groupLabel) . '</div>';
                $html .= (string) Html::span(
                    (string) count($items),
                    ['class' => ['badge', 'rounded-pill', 'text-bg-light', 'app-permission-group__count']],
                );
                $html .= '</div>';
                $html .= '<div class="app-permission-options">';

                foreach ($items as $item) {
                    $id = (int) ($item['id'] ?? 0);
                    $optionId = $name . '-' . $id;
                    $checked = isset($selectedMap[(string) $id]);

                    $attributes = [
                        'class' => ['form-check-input', 'app-permission-option__checkbox'],
                        'id' => $optionId,
                    ];

                    if ($checked) {
                        $attributes['checked'] = true;
                    }

                    $html .= '<label class="app-permission-option" for="' . Html::encode($optionId) . '">';
                    $html .= (string) Html::input('checkbox', $name . '[]', (string) $id, $attributes);
                    $html .= '<span class="app-permission-option__content">';
                    $html .= '<span class="app-permission-option__name">' . Html::encode((string) ($item['name'] ?? '')) . '</span>';
                    $html .= '<code class="app-permission-option__code">' . Html::encode((string) ($item['code'] ?? '')) . '</code>';
                    $html .= '<span class="app-permission-option__code">Peso ' . Html::encode((string) ($item['weight'] ?? 1)) . '</span>';
                    $html .= '</span>';
                    $html .= '</label>';
                }

                $html .= '</div>';
                $html .= '</section>';
            }

            $html .= '</div>';
        }

        $html .= '</div>';

        if ($hint !== null && $hint !== '') {
            $html .= '<div class="app-form-field__hint">' . Html::encode($hint) . '</div>';
        }

        if ($validated && $validationErrors !== []) {
            $html .= (string) Html::div(
                InputValidation::firstError($validationErrors),
                [
                    'class' => 'invalid-feedback d-block app-permission-picker__feedback',
                    'aria-live' => 'polite',
                ],
            );
        }

        $html .= '</div>';

        return $html;
    }

    private function __construct() {}
}
