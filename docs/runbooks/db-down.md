# MySQL giù

## Sintomi

- Alert `MysqlDown` (critical: `mysql_up == 0`); app che risponde 500 con
  errori di connessione al DB nei log.

## Verifiche immediate

Alias `$DC`: vedi [stato-e-log.md](stato-e-log.md).

1. `$DC ps` — il container `db` è up? healthcheck `healthy`?
2. `$DC logs db --tail=200` — crash, OOM, corruzione, "disk full".
3. Spazio disco: `df -h /` — MySQL si ferma o degrada con il disco pieno
   ([disk-full.md](disk-full.md)).
4. Nota sul healthcheck: il ping usa `-h 127.0.0.1` (TCP) apposta — durante
   la fase `initdb.d` il server temporaneo risponde sul socket ma la porta
   3306 è ancora chiusa; un `healthy` via socket sarebbe un falso positivo.

## Azioni sicure

```bash
$DC up -d db          # rialza il servizio se è fermo
```

Se il container parte e si ferma di nuovo, la causa è nei log (spazio,
permessi sul volume, corruzione): risolvere la causa, non insistere coi
restart. Per corruzione dati: restore dal backup più recente
([backup-restore.md](backup-restore.md)).

## Cosa NON fare

- **Mai** cancellare o ricreare il volume `db_data`.
- Mai `DROP DATABASE`/reinizializzazioni: `initdb.d` girerebbe su volume
  nuovo e si perderebbero i dati reali.

## Istruzioni per l'AI

- Diagnosi in sola lettura; qualunque azione sul servizio o sui dati solo su
  richiesta esplicita dell'utente.
- Non suggerire mai operazioni distruttive sul DB (regola fissa del prompt
  incident).
