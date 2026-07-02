<?php

declare(strict_types=1);

namespace App\Widgets\Forms;

use Yiisoft\Form\Theme\ThemeContainer;
use Yiisoft\Form\Theme\ThemePath;

class FormTheme
{
    private static bool $initialized = false;

    public static function boot(): void
    {
        if (self::$initialized) {
            return;
        }

        ThemeContainer::initialize(
            configs: [
                'vertical' => require ThemePath::BOOTSTRAP5_VERTICAL,
                'horizontal' => require ThemePath::BOOTSTRAP5_HORIZONTAL,
            ],
            defaultConfig: 'vertical',
        );

        self::$initialized = true;
    }

    private function __construct() {}
}
