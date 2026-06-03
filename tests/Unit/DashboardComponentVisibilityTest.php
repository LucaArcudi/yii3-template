<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\Dashboard\DashboardComponentDefinition;
use App\Dashboard\DashboardComponentVisibility;
use Codeception\Test\Unit;

final class DashboardComponentVisibilityTest extends Unit
{
    public function testRuntimeVisibilityConditionsHideMatchingComponents(): void
    {
        $components = DashboardComponentVisibility::filter(
            [
                new DashboardComponentDefinition(code: 'admin-control'),
                new DashboardComponentDefinition(code: 'developer-workbench'),
            ],
            [
                'code:admin-control' => false,
            ],
        );

        self::assertCount(1, $components);
        self::assertSame('developer-workbench', $components[0]->code);
    }

    public function testRuntimeVisibilityConditionsCanUseCodeKeys(): void
    {
        $components = DashboardComponentVisibility::filter(
            [
                new DashboardComponentDefinition(code: 'admin-control'),
            ],
            [
                'admin-control' => false,
            ],
        );

        self::assertSame([], $components);
    }
}
