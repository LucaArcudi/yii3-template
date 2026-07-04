<?php

declare(strict_types=1);

namespace App\Migrations;

/**
 * Schema base completo + dati di riferimento (release 1.0.2).
 * Nonostante il numero di release, è il bootstrap: crea tutte le tabelle
 * (inclusa core_user, referenziata dalle FK delle release precedenti),
 * quindi DEVE restare la prima migration della catena.
 */
final class M202607050001Release102BaseSchema extends SqlSnapshotMigration
{
    protected function sqlFile(): string
    {
        return 'database/migrations/release_1_0_2.sql';
    }
}
