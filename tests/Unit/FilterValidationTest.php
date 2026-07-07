<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\Core\Permission\PermissionFilter;
use App\Widgets\Filters\DateFilter;
use App\Widgets\Filters\FilterField;
use App\Widgets\Filters\FilterModal;
use App\Widgets\Filters\MultiSelectFilter;
use App\Widgets\Filters\SelectFilter;
use App\Widgets\Filters\TextFilter;
use Codeception\Test\Unit;
use Yiisoft\Validator\Rule\Integer;
use Yiisoft\Validator\Validator;

final class FilterValidationTest extends Unit
{
    public function testTextFilterUsesSharedClientValidationMarkup(): void
    {
        $html = TextFilter::renderStandalone(
            name: 'email',
            value: 'arcudilu@gmail.com',
            formId: 'user-filter-form',
        );

        self::assertStringContainsString('app-form-input app-form-input--text has-validation', $html);
        self::assertStringContainsString('app-form-input__control', $html);
        self::assertStringContainsString('data-filter-validation-field="true"', $html);
    }

    public function testDateFilterUsesSharedClientValidationMarkup(): void
    {
        $html = DateFilter::renderStandalone(
            name: 'created_at',
            value: '2026-04-30',
            formId: 'task-filter-form',
        );

        self::assertStringContainsString('app-form-input app-form-input--date has-validation', $html);
        self::assertStringContainsString('app-form-input__control', $html);
        self::assertStringContainsString('app-date-input', $html);
        self::assertStringContainsString('data-date-picker="true"', $html);
        self::assertStringContainsString('data-filter-validation-field="true"', $html);
    }

    public function testSelectFilterUsesSharedClientValidationMarkup(): void
    {
        $html = SelectFilter::renderStandalone(
            name: 'status',
            value: '1',
            options: [1 => 'Attivo', 0 => 'Non attivo'],
            formId: 'menu-filter-form',
        );

        self::assertStringContainsString('app-form-input app-form-input--select has-validation', $html);
        self::assertStringContainsString('app-form-input__control', $html);
        self::assertStringContainsString('data-filter-validation-field="true"', $html);
    }

    public function testSelectFilterCanRenderPersistentDropdown(): void
    {
        $html = FilterField::render(
            [
                'name' => 'month',
                'label' => 'Mese',
                'widget' => 'selectFilter',
                'options' => [4 => 'Maggio'],
                'persistent' => true,
            ],
            ['month' => '4'],
            'task-filter-form',
        );

        self::assertStringContainsString('data-single-select-persistent="true"', $html);
    }

    public function testMultiSelectFilterUsesSharedClientValidationMarkup(): void
    {
        $html = MultiSelectFilter::renderStandalone(
            name: 'role_ids',
            values: ['1'],
            options: [1 => 'Admin'],
            formId: 'user-filter-form',
        );

        self::assertStringContainsString('app-form-input app-form-input--multiselect has-validation', $html);
        self::assertStringContainsString('app-form-input__control', $html);
        self::assertStringContainsString('data-filter-validation-field="true"', $html);
    }

    public function testIntegerInputRulesRenderNativeNumberValidation(): void
    {
        $html = TextFilter::renderStandalone(
            name: 'weight',
            value: '2',
            formId: 'permission-filter-form',
            validationRules: [new Integer(min: 1)],
        );

        self::assertStringContainsString('type="number"', $html);
        self::assertStringContainsString('min="1"', $html);
        self::assertStringContainsString('step="1"', $html);
    }

    public function testFilterRulesRenderCustomLengthValidationWithoutNativeTruncation(): void
    {
        $filterRules = (new PermissionFilter(new Validator()))->getFilterRules();

        $html = TextFilter::renderStandalone(
            name: 'name',
            value: 'Accesso dashboard',
            formId: 'permission-filter-form',
            validationRules: $filterRules['name'],
        );

        self::assertStringContainsString('data-filter-max-length="100"', $html);
        self::assertStringNotContainsString('maxlength="100"', $html);
        self::assertStringContainsString('data-validation-feedback="true"', $html);
    }

    public function testFilterFieldForwardsValidationRulesToWidget(): void
    {
        $html = FilterField::render(
            [
                'name' => 'weight',
                'label' => 'Peso',
                'widget' => 'textFilter',
                'validationRules' => [new Integer(min: 1)],
            ],
            ['weight' => '3'],
            'permission-filter-form',
        );

        self::assertStringContainsString('type="number"', $html);
        self::assertStringContainsString('min="1"', $html);
    }

    public function testFilterFieldCanDisableAutoSubmit(): void
    {
        $html = FilterField::render(
            [
                'name' => 'title',
                'label' => 'Titolo',
                'widget' => 'textFilter',
                'autoSubmit' => false,
            ],
            ['title' => 'Refactor'],
            'task-filter-modal-form',
        );

        self::assertStringContainsString('data-filter-validation-field="true"', $html);
        self::assertStringNotContainsString('data-auto-filter-trigger', $html);
        self::assertStringNotContainsString('onkeydown', $html);
    }

    public function testFilterModalRendersValidatedFormWithoutAutoSubmit(): void
    {
        $html = FilterModal::render(
            id: 'task-filter-modal',
            title: 'Filtri task',
            action: '/task',
            filters: ['title' => 'Refactor'],
            fields: [
                [
                    'name' => 'title',
                    'label' => 'Titolo',
                    'widget' => 'textFilter',
                ],
            ],
        );

        self::assertStringContainsString('app-validation-form', $html);
        self::assertStringContainsString('Applica filtri', $html);
        self::assertStringNotContainsString('data-auto-filter-trigger', $html);
        self::assertStringNotContainsString('Annulla', $html);
    }

    public function testFilterModalButtonShowsActiveCountBeforeButton(): void
    {
        $html = FilterModal::button('task-filter-modal', 2);

        self::assertStringContainsString('app-filter-modal-trigger__count', $html);
        self::assertStringContainsString('>2</span>', $html);
        self::assertStringContainsString('aria-label="2 filtri attivi"', $html);
        self::assertStringNotContainsString('2 attivi', $html);
        self::assertMatchesRegularExpression('/>2<\\/span>.*<button/s', $html);
    }
}
