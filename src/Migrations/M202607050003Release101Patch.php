<?php

declare(strict_types=1);

namespace App\Migrations;

/**
 * Patch release 1.0.1: allineamento produzione (rate-limit e log).
 * Interamente coperta dallo schema base: mantenuta per fedeltà 1:1
 * con gli snapshot SQL di release.
 */
final class M202607050003Release101Patch extends SqlSnapshotMigration
{
    protected function sqlFile(): string
    {
        return 'database/migrations/release_1_0_1.sql';
    }
}
