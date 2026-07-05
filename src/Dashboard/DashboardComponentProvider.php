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
    ) {}

    /**
     * @return list<DashboardComponentDefinition>
     */
    public function findVisible(): array
    {
        $components = array_values(array_filter(
            self::definitions(),
            fn(DashboardComponentDefinition $component): bool => $component->active && $this->hasAccess($component),
        ));

        usort(
            $components,
            static fn(DashboardComponentDefinition $left, DashboardComponentDefinition $right): int => $left->sortOrder <=> $right->sortOrder,
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
            // Guida progetto, backlog e roadmap sono stati spostati in docs/
            // (documentazione-progetto.md §3.1, roadmap-sviluppo.md,
            // roadmap-ai-codex-claude-code.md): la dashboard rimanda al repo.
            new DashboardComponentDefinition(
                code: 'github-references',
                viewName: 'links/github-references',
                width: 'col-12 col-xl-6',
                sortOrder: 10,
            ),
        ];
    }
}
