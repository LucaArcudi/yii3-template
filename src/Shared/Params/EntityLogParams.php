<?php

declare(strict_types=1);

namespace App\Shared\Params;

final readonly class EntityLogParams
{
    public function __construct(
        public bool $enabled = true,
        public bool $webEnabled = true,
        public bool $consoleEnabled = false,
        public bool $systemEnabled = true,
    ) {}

    public function isEnabledFor(string $source): bool
    {
        if (!$this->enabled) {
            return false;
        }

        return match ($source) {
            'web' => $this->webEnabled,
            'console' => $this->consoleEnabled,
            'system' => $this->systemEnabled,
            default => true,
        };
    }
}
