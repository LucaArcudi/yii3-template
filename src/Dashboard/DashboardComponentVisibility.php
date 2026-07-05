<?php

declare(strict_types=1);

namespace App\Dashboard;

use App\Services\Core\PolicyAccessResolver;

final readonly class DashboardComponentVisibility
{
    public function __construct(
        private PolicyAccessResolver $policyAccess,
    ) {}

    /**
     * @param list<DashboardComponentDefinition> $components
     * @return list<DashboardComponentDefinition>
     */
    public function filter(array $components): array
    {
        $visible = [];

        foreach ($components as $component) {
            if ($component->active && $this->policyAccess->canAccess($component->policyClass)) {
                $visible[] = $component;
            }
        }

        return $visible;
    }
}
