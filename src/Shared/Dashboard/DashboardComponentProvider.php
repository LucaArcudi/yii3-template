<?php

declare(strict_types=1);

namespace App\Shared\Dashboard;

use App\Shared\Dashboard\Links\GithubReferencesPolicy;

final readonly class DashboardComponentProvider
{
    public function __construct(
        private DashboardComponentVisibility $componentVisibility,
    ) {}

    /**
     * @return list<DashboardComponentDefinition>
     */
    public function findVisible(): array
    {
        $components = $this->componentVisibility->filter(self::definitions());

        usort(
            $components,
            static fn(DashboardComponentDefinition $left, DashboardComponentDefinition $right): int => $left->sortOrder <=> $right->sortOrder,
        );

        return $components;
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
                policyClass: GithubReferencesPolicy::class,
            ),
        ];
    }
}
