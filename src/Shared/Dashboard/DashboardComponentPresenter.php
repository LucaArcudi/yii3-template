<?php

declare(strict_types=1);

namespace App\Shared\Dashboard;

use function trim;

final readonly class DashboardComponentPresenter
{
    public function __construct(
        private DashboardComponentDefinition $component,
    ) {}

    public function code(): string
    {
        return $this->component->code;
    }

    public function viewName(): string
    {
        return $this->component->viewName;
    }

    public function width(): string
    {
        $width = trim($this->component->width);

        return $width !== '' ? $width : 'col-12 col-xl-6';
    }

    public function sortOrder(): int
    {
        return $this->component->sortOrder;
    }

    public function active(): bool
    {
        return $this->component->active;
    }
}
