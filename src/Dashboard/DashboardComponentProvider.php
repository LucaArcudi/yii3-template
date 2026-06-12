<?php

declare(strict_types=1);

namespace App\Dashboard;

use App\Services\Core\AuthorizationService;
use Yiisoft\User\CurrentUser;

final readonly class DashboardComponentProvider
{
    public function __construct(
        private CurrentUser $currentUser,
        private AuthorizationService $authorizationService,
    ) {
    }

    /**
     * @return list<DashboardComponentDefinition>
     */
    public function findVisible(): array
    {
        $components = array_values(array_filter(
            self::definitions(),
            fn (DashboardComponentDefinition $component): bool => $component->active && $this->hasAccess($component),
        ));

        usort(
            $components,
            static fn (DashboardComponentDefinition $left, DashboardComponentDefinition $right): int => $left->sortOrder <=> $right->sortOrder,
        );

        return $components;
    }

    private function hasAccess(DashboardComponentDefinition $component): bool
    {
        if ($component->roleCodes === []) {
            return true;
        }

        if ($this->currentUser->isGuest()) {
            return false;
        }

        return $this->authorizationService->userHasAnyRole($this->currentUser->getId(), $component->roleCodes);
    }

    /**
     * @return list<DashboardComponentDefinition>
     */
    private static function definitions(): array
    {
        return [
            new DashboardComponentDefinition(
                code: 'project-guide',
                viewName: 'guide/project-structure',
                width: 'col-12',
                sortOrder: 5,
            ),
            new DashboardComponentDefinition(
                code: 'admin-control',
                viewName: 'metric/admin-control',
                width: 'col-12 col-xl-4',
                sortOrder: 10,
                roleCodes: ['SUPER_ADMIN', 'ADMIN'],
            ),
            new DashboardComponentDefinition(
                code: 'developer-workbench',
                viewName: 'notice/next-steps',
                width: 'col-12 col-xl-4',
                sortOrder: 20,
                roleCodes: ['SUPER_ADMIN','ADMIN', 'SVILUPPATORE'],
            ),
        ];
    }
}
