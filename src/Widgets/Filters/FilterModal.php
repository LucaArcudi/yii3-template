<?php

declare(strict_types=1);

namespace App\Widgets\Filters;

use App\Widgets\Modal;
use Yiisoft\Html\Html;

use function array_key_exists;
use function implode;
use function is_array;
use function is_scalar;

final class FilterModal
{
    public static function button(
        string $modalId,
        int $activeCount = 0,
        string $label = 'Filtri',
        string $buttonClass = 'btn btn-outline-primary btn-shadow btn-sm',
    ): string {
        $count = $activeCount > 0
            ? (string) Html::span(
                (string) $activeCount,
                [
                    'class' => 'app-filter-modal-trigger__count',
                    'aria-label' => $activeCount === 1 ? '1 filtro attivo' : $activeCount . ' filtri attivi',
                    'title' => $activeCount === 1 ? '1 filtro attivo' : $activeCount . ' filtri attivi',
                ],
            )
            : '';

        $button = (string) Html::button(
            (string) Html::i('', ['class' => 'fa-solid fa-sliders me-1']) . Html::encode($label),
            [
                'type' => 'button',
                'class' => $buttonClass,
                'data-bs-toggle' => 'modal',
                'data-bs-target' => '#' . $modalId,
            ],
        )->encode(false);

        return (string) Html::div(
            $count . $button,
            ['class' => 'app-filter-modal-trigger'],
        )->encode(false);
    }

    public static function render(
        string $id,
        string $title,
        string $action,
        array $filters,
        array $fields,
        string $variant = 'primary',
        string $icon = 'fa-solid fa-sliders',
        string $size = 'lg',
    ): string {
        $formId = $id . '-form';
        $form = self::form(
            action: $action,
            filters: $filters,
            fields: self::modalFields($fields),
            formId: $formId,
        );

        return Modal::render(
            id: $id,
            title: $title,
            body: $form,
            footer: self::footer($action, $formId),
            variant: $variant,
            icon: $icon,
            size: $size,
        );
    }

    public static function activeCount(array $filters, array $fields): int
    {
        $count = 0;

        foreach ($fields as $field) {
            $name = (string) ($field['name'] ?? '');

            if ($name === '' || !array_key_exists($name, $filters)) {
                continue;
            }

            if (self::hasActiveValue($filters[$name])) {
                $count++;
            }
        }

        return $count;
    }

    private static function form(string $action, array $filters, array $fields, string $formId): string
    {
        $fieldNames = [];
        $hiddenFields = [];
        $inputs = [];

        foreach ($fields as $field) {
            $name = (string) ($field['name'] ?? '');

            if ($name === '') {
                continue;
            }

            $fieldNames[$name] = true;
            $inputs[] = FilterField::render($field, $filters, $formId, 'form-label');
        }

        foreach ($filters as $name => $value) {
            if (
                isset($fieldNames[$name])
                || isset(['page' => true, 'previous-page' => true][$name])
                || !is_scalar($value)
                || $value === ''
            ) {
                continue;
            }

            $hiddenFields[] = (string) Html::hiddenInput((string) $name, (string) $value);
        }

        return (string) Html::form($action, 'get', [
            'id' => $formId,
            'class' => ['app-admin-filter__form', 'app-filter-modal__form', 'app-validation-form'],
            'data-validated' => '0',
        ])
            ->content(
                implode('', $hiddenFields)
                . (string) Html::div(implode('', $inputs), ['class' => 'row g-3'])->encode(false),
            )
            ->encode(false);
    }

    private static function footer(string $action, string $formId): string
    {
        $footer = (string) Html::a(
            (string) Html::i('', ['class' => 'fa-solid fa-rotate-left me-1']) . 'Svuota',
            $action,
            ['class' => ['btn', 'btn-outline-secondary']],
        )->encode(false);

        $footer .= (string) Html::button(
            (string) Html::i('', ['class' => 'fa-solid fa-check me-1']) . 'Applica filtri',
            [
                'type' => 'submit',
                'form' => $formId,
                'class' => ['btn', 'btn-primary', 'btn-shadow'],
            ],
        )->encode(false);

        return $footer;
    }

    private static function modalFields(array $fields): array
    {
        foreach ($fields as &$field) {
            $field['autoSubmit'] = false;
        }
        unset($field);

        return $fields;
    }

    private static function hasActiveValue(mixed $value): bool
    {
        if (is_array($value)) {
            return $value !== [];
        }

        return is_scalar($value) && (string) $value !== '';
    }

    private function __construct() {}
}
