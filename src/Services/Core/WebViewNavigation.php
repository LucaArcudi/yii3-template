<?php

declare(strict_types=1);

namespace App\Services\Core;

final readonly class WebViewNavigation
{
    public function __construct(
        public string $currentUrl,
        public string $backUrl,
    ) {}

    public function parameters(): array
    {
        return [
            'backUrl' => $this->backUrl,
            'currentUrl' => $this->currentUrl,
        ];
    }
}
