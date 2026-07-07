<?php

declare(strict_types=1);

// Each module exposes its routes in src/<Module>/routes.php (Route or Group
// instances): they are collected here into the `routes` configuration group.
$routes = [];

foreach (glob(dirname(__DIR__, 2) . '/src/*/routes.php') ?: [] as $moduleRoutesFile) {
    foreach (require $moduleRoutesFile as $route) {
        $routes[] = $route;
    }
}

return $routes;
