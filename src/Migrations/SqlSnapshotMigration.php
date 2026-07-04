<?php

declare(strict_types=1);

namespace App\Migrations;

use RuntimeException;
use Yiisoft\Db\Migration\MigrationBuilder;
use Yiisoft\Db\Migration\MigrationInterface;

use function array_filter;
use function dirname;
use function explode;
use function file_get_contents;
use function preg_split;
use function str_starts_with;
use function trim;

/**
 * Esegue uno snapshot SQL di release (file in database/) come migration del
 * framework. Gli snapshot sono idempotenti per contratto ("safe to run more
 * than once"): su un DB già inizializzato da initdb.d la prima esecuzione è
 * un no-op che registra solo la history; su un DB vuoto fanno il bootstrap.
 */
abstract class SqlSnapshotMigration implements MigrationInterface
{
    /**
     * Percorso del file SQL relativo alla radice del progetto.
     */
    abstract protected function sqlFile(): string;

    public function up(MigrationBuilder $b): void
    {
        $path = dirname(__DIR__, 2) . '/' . $this->sqlFile();
        $sql = @file_get_contents($path);

        if ($sql === false) {
            throw new RuntimeException("Snapshot SQL non leggibile: $path");
        }

        foreach ($this->splitStatements($sql) as $statement) {
            $b->getDb()->createCommand($statement)->execute();
        }
    }

    /**
     * Divide lo script sugli `;` a fine riga. Sufficiente per questi file:
     * nessun `;` compare a metà riga (niente trigger, procedure o valori
     * stringa con punto e virgola).
     *
     * @return string[]
     */
    private function splitStatements(string $sql): array
    {
        $statements = [];

        foreach (preg_split('/;\s*$/m', $sql) ?: [] as $chunk) {
            $chunk = trim($chunk);

            $meaningful = array_filter(
                explode("\n", $chunk),
                static function (string $line): bool {
                    $line = trim($line);

                    return $line !== '' && !str_starts_with($line, '--');
                },
            );

            if ($meaningful !== []) {
                $statements[] = $chunk;
            }
        }

        return $statements;
    }
}
