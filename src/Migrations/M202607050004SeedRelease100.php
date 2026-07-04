<?php

declare(strict_types=1);

namespace App\Migrations;

/**
 * Seed release 1.0.0: pulizia permessi legacy e dati del centro notifiche.
 */
final class M202607050004SeedRelease100 extends SqlSnapshotMigration
{
    protected function sqlFile(): string
    {
        return 'database/seeders/release_1_0_0.sql';
    }
}
