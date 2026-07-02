<?php

declare(strict_types=1);

namespace App\Widgets;

use App\Helpers\Translate;
use Yiisoft\Html\Html;

final class BackButton
{
    public static function render(
        string $url,
        ?string $label = null,
        string $class = 'btn-shadow btn btn-outline-secondary',
    ): string {
        $label ??= Translate::t('Indietro');
        return (string) Html::a(
            (string) Html::i('', ['class' => 'fa-solid fa-arrow-left me-1'])
            . Html::encode($label),
            $url,
            ['class' => $class],
        )->encode(false);
    }

    private function __construct() {}
}
