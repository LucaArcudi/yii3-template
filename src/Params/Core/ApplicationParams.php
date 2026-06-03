<?php

declare(strict_types=1);

namespace App\Params\Core;

final readonly class ApplicationParams
{
    public function __construct(
        public string $name = 'My Project',
        public string $charset = 'UTF-8',
        public string $locale = 'en',
        public string $version = '0.0.0',
    ) {}
}
