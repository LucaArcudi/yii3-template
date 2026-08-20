# Backup, restore e patch del DB

Alias `$DC`: vedi [stato-e-log.md](stato-e-log.md). Restore e patch sono
operazioni di produzione: richiedono autorizzazione esplicita e una finestra
controllata.

## Backup

La CD esegue `scripts/backup-db.sh` prima di ogni deploy. Lo script:

- interpreta `.env.prod` tramite Docker Compose, senza parsing manuale dotenv;
- usa `umask 077`, directory `backups/` con modo `0700` e dump `0600`;
- esegue `mysqldump --single-transaction --no-tablespaces`;
- fallisce se il dump è vuoto;
- elimina dopo 14 giorni soltanto i file con nome automatico esatto
  `db_YYYY-MM-DD_HH-MM-SS.sql`.

Per un backup manuale usare lo stesso percorso versionato:

```bash
cd /opt/yii3
bash scripts/backup-db.sh
```

Un dump da conservare oltre la retention va rinominato aggiungendo un suffisso,
per esempio `db_2026-08-20_10-00-00_pre-intervento.sql`, e deve mantenere i
permessi `0600`.

## Verifiche prima del restore

1. identificare il dump esatto senza stamparne il contenuto;
2. verificare proprietario, modo `0600`, dimensione e spazio libero;
3. confermare database e ambiente di destinazione;
4. fermare le scritture applicative o concordare la finestra di manutenzione;
5. creare un ulteriore backup pre-restore.

## Restore

Con l'alias `$DC` definito dal runbook comune:

```bash
$DC exec -T db sh -lc 'mysql -u"$MYSQL_USER" -p"$MYSQL_PASSWORD" "$MYSQL_DATABASE"' \
  < backups/db_<timestamp>.sql
```

Dopo il restore verificare almeno connessione, migration history e health
dell'app. Il caso tipico è una migration fallita dopo il backup pre-deploy;
vedi [deploy-failed.md](deploy-failed.md).

## Drill automatico

`tests/Deploy/backup-restore.sh` crea uno stack Compose e un volume MySQL
dedicati, applica le migration, inserisce un dato sentinella, effettua dump,
svuotamento e restore e controlla dato e history. Il trap rimuove stack e
volume anche in caso di errore. La CI lo esegue a ogni run e tramite schedule
settimanale.

Il drill non usa `.env.prod`, non tocca i volumi dev/prod e non sostituisce la
prova periodica di un backup reale in un ambiente autorizzato.

## Evoluzione dello schema

`initdb.d` gira soltanto alla prima inizializzazione di un volume. Su un DB
esistente lo schema evolve con la catena `yiisoft/db-migration`; normalmente
la CD esegue `./yii migrate:up -y`. Non applicare a mano singoli snapshot SQL
salvo una procedura di recovery esplicitamente approvata.

## Cosa non fare

- non eseguire `DROP DATABASE` o ricreazioni in produzione;
- non cancellare il volume `db_data`;
- non leggere, loggare o copiare i dump fuori dal perimetro autorizzato;
- non eliminare backup a mano per aggirare la retention;
- non avviare un restore senza avere identificato ambiente e dump esatti.
