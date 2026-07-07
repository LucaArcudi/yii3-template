<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\Shared\Widgets\Filters\FilterField;
use App\Shared\Widgets\Filters\MultiSelectFilter;
use Codeception\Test\Unit;

final class MultiSelectFilterTest extends Unit
{
    public function testStandaloneRenderKeepsSelectedZeroValue(): void
    {
        $html = MultiSelectFilter::renderStandalone(
            name: 'status',
            values: ['0', '1'],
            options: [
                0 => 'To do',
                1 => 'In progress',
                2 => 'Done',
            ],
            formId: 'task-filter-form',
        );

        self::assertStringContainsString('name="status[]"', $html);
        self::assertStringContainsString('value="[&quot;0&quot;,&quot;1&quot;]"', $html);
        self::assertStringContainsString('value="0" selected', $html);
        self::assertStringContainsString('value="1" selected', $html);
        self::assertStringContainsString('data-auto-filter-trigger="outside-click"', $html);
    }

    public function testFilterFieldAcceptsArrayValues(): void
    {
        $html = FilterField::render(
            [
                'name' => 'role_ids',
                'label' => 'Ruoli',
                'widget' => 'multiSelectFilter',
                'options' => [
                    1 => 'Admin',
                    2 => 'Editor',
                ],
            ],
            [
                'role_ids' => ['2', '1'],
            ],
            'user-filter-form',
        );

        self::assertStringContainsString('name="role_ids[]"', $html);
        self::assertStringContainsString('value="[&quot;1&quot;,&quot;2&quot;]"', $html);
        self::assertStringContainsString('value="1" selected', $html);
        self::assertStringContainsString('value="2" selected', $html);
    }
}
