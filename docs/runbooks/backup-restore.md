# Backup, restore e patch del DB

Alias `$DC`: vedi [stato-e-log.md](stato-e-log.md).

## Backup

Automatico ad ogni deploy (step del CD, `scripts/backup-db.sh`), con
retention automatica di 14 giorni **basata sul nome file**: qualunque
`backups/db_<timestamp>.sql` più vecchio di 14 giorni viene eliminato,
**inclusi i dump manuali** creati col comando qui sotto. Un dump che deve
sopravvivere alla retention va **rinominato** (es.
`db_2026-07-28_10-00-00_pre-intervento.sql`: il suffisso lo esclude dal
glob di pulizia). Backup manuale:

```bash
cd /opt/yii3 && mkdir -p backups
$DC exec -T db sh -lc 'mysqldump --no-tablespaces -u"$MYSQL_USER" -p"$MYSQL_PASSWORD" "$MYSQL_DATABASE"' \
  > backups/db_$(date +%F_%H-%M-%S).sql
```

## Restore

```bash
$DC exec -T db sh -lc 'mysql -u"$MYSQL_USER" -p"$MYSQL_PASSWORD" "$MYSQL_DATABASE"' \
  < backups/db_<timestamp>.sql
```

Caso tipico: migration fallita a metà dopo un deploy — restore del backup
pre-deploy creato dal CD (vedi [deploy-failed.md](deploy-failed.md)).

## Applicare una migration su DB esistente

`initdb.d` gira solo alla prima inizializzazione del volume; su un DB
esistente lo schema si evolve con la catena di migration (il CD esegue
`migrate:up` ad ogni deploy). Per applicare a mano un singolo snapshot:

```bash
$DC exec -T db sh -lc 'mysql -u"$MYSQL_USER" -p"$MYSQL_PASSWORD" "$MYSQL_DATABASE"' \
  < database/migrations/release_X_Y_Z.sql
```

## Cosa NON fare

- Mai `DROP DATABASE` / ricreazioni in produzione.
- Mai cancellare il volume `db_data`.
- Non eliminare backup a mano fuori dalla retention automatica.

## Istruzioni per l'AI

- Restore e patch sono operazioni di produzione: solo su richiesta esplicita
  dell'utente, citando prima questo runbook.
- I dump non vanno mai copiati fuori dal VPS né letti nel loro contenuto.
