<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\Shared\Dashboard\DashboardComponentDefinition;
use App\Shared\Dashboard\DashboardComponentVisibility;
use App\Shared\Services\PolicyAccessResolver;
use App\Tests\Unit\Support\AllowAccessPolicy;
use App\Tests\Unit\Support\ArrayContainer;
use App\Tests\Unit\Support\DenyAccessPolicy;
use Codeception\Test\Unit;

final class DashboardComponentVisibilityTest extends Unit
{
    public function testPolicyClassControlsComponentVisibility(): void
    {
        $visibility = new DashboardComponentVisibility(new PolicyAccessResolver(new ArrayContainer([
            AllowAccessPolicy::class => new AllowAccessPolicy(),
            DenyAccessPolicy::class => new DenyAccessPolicy(),
        ])));

        $components = $visibility->filter(
            [
                new DashboardComponentDefinition(code: 'admin-control', policyClass: DenyAccessPolicy::class),
                new DashboardComponentDefinition(code: 'developer-workbench', policyClass: AllowAccessPolicy::class),
                new DashboardComponentDefinition(code: 'public-reference'),
            ],
        );

        self::assertSame(['developer-workbench', 'public-reference'], array_map(
            static fn(DashboardComponentDefinition $component): string => $component->code,
            $components,
        ));
    }

    public function testInactiveComponentsAreHidden(): void
    {
        $visibility = new DashboardComponentVisibility(new PolicyAccessResolver(new ArrayContainer([])));

        $components = $visibility->filter(
            [
                new DashboardComponentDefinition(code: 'active'),
                new DashboardComponentDefinition(code: 'inactive', active: false),
            ],
        );

        self::assertCount(1, $components);
        self::assertSame('active', $components[0]->code);
    }
}
