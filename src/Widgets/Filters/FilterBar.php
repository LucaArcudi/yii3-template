<?php

declare(strict_types=1);

namespace App\Widgets\Filters;

use Yiisoft\Html\Html;
use Yiisoft\View\WebView;

use function is_scalar;

class FilterBar
{
    public static function render(
        WebView $view,
        string $action,
        array $filters,
        array $fields,
        string $formClass = 'w-100',
        bool $toggleable = true,
        ?bool $collapsed = null,
        string $title = 'Filtri',
        string $icon = 'fa-solid fa-filter',
    ): string {
        $widgetId = self::buildWidgetId();
        $formId = $widgetId . '-form';
        $collapseId = $widgetId . '-collapse';
        $toggleIconId = $widgetId . '-toggle-icon';
        $storageKey = self::buildStorageKey($action, $title, $fields);
        $collapsed ??= false;

        $headerContent = (string) Html::div(
            (string) Html::span(
                (string) Html::i('', ['class' => $icon]),
                ['class' => 'app-admin-filter__icon'],
            )->encode(false)
            . (string) Html::div(
                (string) Html::h3($title, ['class' => 'card-title']),
                ['class' => 'app-admin-filter__title-group'],
            )->encode(false),
            ['class' => 'app-admin-filter__heading'],
        )->encode(false);

        if ($toggleable) {
            $headerContent .= (string) Html::div(
                self::renderToggleButton(
                    collapseId: $collapseId,
                    collapsed: $collapsed,
                    iconId: $toggleIconId,
                    buttonClass: 'btn btn-sm btn-link text-decoration-none app-admin-filter__toggle',
                ),
                ['class' => 'app-admin-filter__actions'],
            )->encode(false);
        }

        $formContent = implode(
            '',
            [
                ...self::renderHiddenFields($filters, $fields),
                (string) Html::div(
                    implode('', self::renderFields($formId, $filters, $fields, 'form-label')),
                    ['class' => 'row g-3'],
                )->encode(false),
            ],
        );

        $formAttributes = [
            'id' => $formId,
            'class' => ['app-admin-filter__form', 'app-validation-form'],
            'data-validated' => '0',
        ];
        Html::addCssClass($formAttributes, $formClass);

        $form = Html::form($action, 'get', $formAttributes)
            ->content($formContent)
            ->encode(false);

        $collapseAttributes = [
            'id' => $collapseId,
            'class' => ['collapse', 'w-100'],
        ];

        if (!$collapsed) {
            Html::addCssClass($collapseAttributes, 'show');
        }

        $content = (string) Html::div($headerContent, ['class' => ['card-header', 'app-admin-filter__header']])->encode(false)
            . (string) Html::div(
                (string) Html::div((string) $form, ['class' => ['card-body', 'app-admin-filter__body', 'w-100']])->encode(false),
                $collapseAttributes,
            )->encode(false);

        self::registerToggleJs($view, $toggleable, $collapseId, $toggleIconId, $storageKey, $collapsed);

        return (string) Html::div($content, ['class' => ['card', 'app-admin-filter', 'mb-3', 'w-100']])->encode(false);
    }

    private static function renderFields(
        string $formId,
        array $filters,
        array $fields,
        string $labelClass,
    ): array
    {
        $html = [];

        foreach ($fields as $field) {
            $html[] = FilterField::render($field, $filters, $formId, $labelClass);
        }

        return $html;
    }

    private static function renderHiddenFields(array $filters, array $fields): array
    {
        $fieldNames = [];
        $excludedNames = ['page' => true, 'previous-page' => true];

        foreach ($fields as $field) {
            $name = (string) ($field['name'] ?? '');

            if ($name !== '') {
                $fieldNames[$name] = true;
            }
        }

        $html = [];

        foreach ($filters as $name => $value) {
            if (isset($fieldNames[$name]) || isset($excludedNames[$name]) || !is_scalar($value) || $value === '') {
                continue;
            }

            $html[] = (string) Html::hiddenInput((string) $name, (string) $value);
        }

        return $html;
    }

    private static function renderToggleButton(
        string $collapseId,
        bool $collapsed,
        string $iconId,
        string $buttonClass,
    ): string {
        return (string) Html::button(
            (string) Html::span('Filtri', ['class' => 'small text-uppercase fw-semibold'])
            . (string) Html::i('', ['id' => $iconId, 'class' => ['fa-solid', $collapsed ? 'fa-chevron-down' : 'fa-chevron-up']]),
            [
                'type' => 'button',
                'class' => $buttonClass,
                'data-bs-toggle' => 'collapse',
                'data-bs-target' => '#' . $collapseId,
                'aria-expanded' => $collapsed ? 'false' : 'true',
                'aria-controls' => $collapseId,
            ],
        )->encode(false);
    }

    private static function registerToggleJs(
        WebView $view,
        bool $toggleable,
        string $collapseId,
        string $toggleIconId,
        string $storageKey,
        bool $collapsed,
    ): void {
        if (!$toggleable) {
            return;
        }

        $defaultState = $collapsed ? 'collapsed' : 'expanded';

        $view->registerJs(
            <<<JS
                (function () {
                    const collapseElement = document.getElementById('{$collapseId}');
                    const toggleIcon = document.getElementById('{$toggleIconId}');
                    const toggleButton = toggleIcon?.closest('button');

                    if (!collapseElement || !toggleIcon) {
                        return;
                    }

                    const storageKey = '{$storageKey}';
                    const defaultState = '{$defaultState}';
                    const readState = () => {
                        try {
                            return window.localStorage.getItem(storageKey);
                        } catch (error) {
                            return null;
                        }
                    };
                    const writeState = (state) => {
                        try {
                            window.localStorage.setItem(storageKey, state);
                        } catch (error) {
                        }
                    };
                    const syncIcon = (isExpanded) => {
                        toggleIcon.classList.toggle('fa-chevron-up', isExpanded);
                        toggleIcon.classList.toggle('fa-chevron-down', !isExpanded);

                        if (toggleButton) {
                            toggleButton.setAttribute('aria-expanded', isExpanded ? 'true' : 'false');
                        }
                    };
                    const applyState = (state) => {
                        const isExpanded = state !== 'collapsed';
                        collapseElement.classList.toggle('show', isExpanded);
                        syncIcon(isExpanded);
                    };

                    const savedState = readState();
                    const initialState = savedState ?? defaultState;
                    applyState(initialState);

                    collapseElement.addEventListener('shown.bs.collapse', function () {
                        syncIcon(true);
                        writeState('expanded');
                    });

                    collapseElement.addEventListener('hidden.bs.collapse', function () {
                        syncIcon(false);
                        writeState('collapsed');
                    });
                })();
            JS
        );
    }

    private static function buildWidgetId(): string
    {
        return 'admin-filter-bar-' . bin2hex(random_bytes(4));
    }

    private static function buildStorageKey(string $action, string $title, array $fields): string
    {
        $fieldNames = [];

        foreach ($fields as $field) {
            $name = (string) ($field['name'] ?? '');

            if ($name !== '') {
                $fieldNames[] = $name;
            }
        }

        return 'admin-filter-bar:' . sha1($action . '|' . $title . '|' . implode('|', $fieldNames));
    }

    private function __construct()
    {
    }
}
