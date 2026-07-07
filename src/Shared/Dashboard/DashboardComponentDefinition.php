<?php

declare(strict_types=1);

namespace App\Shared\Dashboard;

use App\Shared\Data\AccessPolicyInterface;

final readonly class DashboardComponentDefinition
{
    /**
     * @param class-string<AccessPolicyInterface>|null $policyClass
     */
    public function __construct(
        public string $code,
        public string $viewName = 'default',
        public string $width = 'col-12 col-xl-6',
        public int $sortOrder = 0,
        public bool $active = true,
        public ?string $policyClass = null,
    ) {}
}
