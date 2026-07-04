<?php

declare(strict_types=1);

namespace App\Migrations;

/**
 * Patch release 1.0.0: tabelle notifiche/rate-limit/log, rimozione tabelle
 * legacy (core_component, core_menu, core_setting) e pulizia permessi.
 */
final class M202607050002Release100Patch extends SqlSnapshotMigration
{
    protected function sqlFile(): string
    {
        return 'database/migrations/release_1_0_0.sql';
    }
}
