<?php

declare(strict_types=1);

// Each module may expose its DI definitions in src/<Module>/di.php:
// they are collected here into the `di` configuration group.
$definitions = [];

foreach (glob(dirname(__DIR__, 3) . '/src/*/di.php') ?: [] as $moduleDiFile) {
    /** @var array<string, mixed> $moduleDefinitions */
    $moduleDefinitions = require $moduleDiFile;

    foreach ($moduleDefinitions as $id => $definition) {
        if (array_key_exists($id, $definitions)) {
            throw new RuntimeException(
                sprintf('Duplicate DI definition "%s" in module config "%s".', $id, $moduleDiFile),
            );
        }

        $definitions[$id] = $definition;
    }
}

return $definitions;
