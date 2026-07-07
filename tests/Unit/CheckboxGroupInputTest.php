<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\Shared\Widgets\Inputs\CheckboxGroupInput;
use Codeception\Test\Unit;

final class CheckboxGroupInputTest extends Unit
{
    public function testRendersPermissionGroups(): void
    {
        $html = CheckboxGroupInput::render(
            name: 'permission_ids',
            label: 'Permessi associati',
            groups: [
                [
                    'label' => 'User',
                    'items' => [
                        [
                            'id' => 7,
                            'name' => 'User',
                            'code' => 'USER',
                        ],
                    ],
                ],
            ],
        );

        self::assertStringContainsString('app-permission-group__title">User</div>', $html);
        self::assertStringContainsString('USER', $html);
    }

    public function testSelectedValuesAreChecked(): void
    {
        $html = CheckboxGroupInput::render(
            name: 'permission_ids',
            label: 'Permessi associati',
            groups: [
                [
                    'label' => 'User',
                    'items' => [
                        [
                            'id' => 7,
                            'name' => 'User',
                            'code' => 'USER',
                        ],
                    ],
                ],
            ],
            selectedValues: [7],
        );

        self::assertStringContainsString('value="7"', $html);
        self::assertStringContainsString('checked', $html);
    }

    public function testEmptyStateIsRendered(): void
    {
        $html = CheckboxGroupInput::render(
            name: 'permission_ids',
            label: 'Permessi associati',
            groups: [],
        );

        self::assertStringContainsString('Nessun permesso disponibile', $html);
    }
}
