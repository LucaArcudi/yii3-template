# Deploy Manuale

Questo progetto usa Docker Compose per un deploy manuale ripetibile. Non ci sono ancora automazioni GitHub Actions per il deploy, Terraform o Ansible.

## Prerequisiti

- Docker e Docker Compose plugin installati sul server.
- Accesso in pull a `ghcr.io/lucaarcudi/yii3-template`.
- Rete Docker esterna `caddy_public` gia presente se si usano le label Caddy:

```bash
docker network inspect caddy_public >/dev/null 2>&1 || docker network create caddy_public
```

## Configurazione

Preparare il file runtime dei valori di produzione partendo dall'esempio, poi sostituire host e password:

```bash
cp .env.prod.example .env.prod
editor .env.prod
```

Il file `.env.prod` non va committato.

## Login GHCR

Usare un token con permesso di lettura dei package, oppure un login gia configurato sul server:

```bash
docker login ghcr.io
```

## Pull e Avvio

```bash
docker compose --env-file .env.prod -f docker/prod/compose.yml pull
docker compose --env-file .env.prod -f docker/prod/compose.yml up -d
docker compose --env-file .env.prod -f docker/prod/compose.yml logs -f --tail=200
```

## Verifica

```bash
docker compose --env-file .env.prod -f docker/prod/compose.yml ps
docker compose --env-file .env.prod -f docker/prod/compose.yml logs --tail=200 app
docker compose --env-file .env.prod -f docker/prod/compose.yml logs --tail=200 db
```

## Aggiornamento Database

Su un database gia inizializzato, gli script in `/docker-entrypoint-initdb.d` non vengono rieseguiti. Applicare le patch idempotenti manualmente:

```bash
docker compose --env-file .env.prod -f docker/prod/compose.yml exec -T db sh -lc 'mysql -u"$MYSQL_USER" -p"$MYSQL_PASSWORD" "$MYSQL_DATABASE"' < database/migrations/release_1_0_2.sql
```

Se si usa un override locale, aggiungere anche `-f docker/prod/compose.local.yml`.

## Accesso Database

Per accedere al DB di produzione con HeidiSQL, non esporre MySQL pubblicamente. Creare un override locale sul server:

```bash
cp docker/prod/compose.local.example.yml docker/prod/compose.local.yml
docker compose --env-file .env.prod -f docker/prod/compose.yml -f docker/prod/compose.local.yml up -d db
```

Poi dal PC aprire un tunnel SSH:

```bash
ssh -N -L 3307:127.0.0.1:3307 root@173.212.248.57
```

In HeidiSQL usare `127.0.0.1`, porta `3307`, utente `DB_USERNAME`, password `DB_PASSWORD`, database `DB_DATABASE` dal file `.env.prod`.

Per il DB Docker locale usare `127.0.0.1`, porta `${DB_PORT:-3306}`, utente `root`, password vuota, database `${DB_DATABASE:-yii3_template}`.

## Rollback Base

Per tornare a un tag precedente, impostare l'immagine nota come buona e riavviare il servizio `app`:

```bash
APP_IMAGE=ghcr.io/lucaarcudi/yii3-template:<tag-precedente> docker compose --env-file .env.prod -f docker/prod/compose.yml pull app
APP_IMAGE=ghcr.io/lucaarcudi/yii3-template:<tag-precedente> docker compose --env-file .env.prod -f docker/prod/compose.yml up -d app
APP_IMAGE=ghcr.io/lucaarcudi/yii3-template:<tag-precedente> docker compose --env-file .env.prod -f docker/prod/compose.yml logs -f --tail=200 app
```

Per rendere il rollback persistente, aggiornare `APP_IMAGE` in `.env.prod` dopo la verifica.
