<?php

declare(strict_types=1);

namespace App\Params\Core;

final readonly class LayoutParams
{
    public function __construct(
        public string $logo = 'images/logo.png',
        public string $logoSmall = 'images/logo_small.png',
        public string $footerLeft = 'Yii3 + ArchitectUI template',
        public string $footerRight = 'ver 0.1.0',
    ) {
    }
}
