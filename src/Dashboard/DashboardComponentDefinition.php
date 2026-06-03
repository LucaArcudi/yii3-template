<?php

declare(strict_types=1);

namespace App\Dashboard;

final readonly class DashboardComponentDefinition
{
    /**
     * @param list<string> $roleCodes
     */
    public function __construct(
        public string $code,
        public string $viewName = 'default',
        public string $width = 'col-12 col-xl-6',
        public int $sortOrder = 0,
        public bool $active = true,
        public array $roleCodes = [],
    ) {
    }
}
