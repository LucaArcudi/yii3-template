<?php

declare(strict_types=1);

namespace App\Assets\ArchitectUi;

use Yiisoft\Assets\AssetBundle;

final class ArchitectUiAsset extends AssetBundle
{
    public ?string $sourcePath = '@resources/architectui/assets';
    public ?string $basePath = '@assets';
    public ?string $baseUrl = '@assetsUrl';

    public array $css = [
        'styles/main.css',
        'styles/app-overrides.css',
        'styles/app-theme.css',
    ];

    public array $js = [
        'scripts/main.js',
        'scripts/scrollbar.js',
        'scripts/chart_js.js',
        'scripts/demo.js',
        'scripts/fullcalendar.js',
        'scripts/toastr.js',
        'scripts/app.js',
    ];
}
