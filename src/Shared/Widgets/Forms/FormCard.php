<?php

declare(strict_types=1);

namespace App\Shared\Widgets\Forms;

use App\Shared\Widgets\Card;

class FormCard
{
    public static function render(
        string $title,
        string $formHtml,
        string $variant = 'primary',
        ?string $footer = null,
    ): string {
        return Card::render(
            title: $title,
            body: $formHtml,
            variant: $variant,
            footer: $footer,
        );
    }

    private function __construct() {}
}
