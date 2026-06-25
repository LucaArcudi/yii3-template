# Deploy Manuale

Questo progetto usa Docker Compose per un deploy manuale ripetibile. Non ci sono ancora automazioni GitHub Actions per il deploy, Terraform o Ansible.

## Prerequisiti

- Docker e Docker Compose plugin installati sul server.
- Accesso in pull a `ghcr.io/tuo-user/yii3-template`.
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

## Rollback Base

Per tornare a un tag precedente, impostare il tag noto come buono e riavviare il servizio `app`:

```bash
PROD_IMAGE_TAG=<tag-precedente> docker compose --env-file .env.prod -f docker/prod/compose.yml pull app
PROD_IMAGE_TAG=<tag-precedente> docker compose --env-file .env.prod -f docker/prod/compose.yml up -d app
PROD_IMAGE_TAG=<tag-precedente> docker compose --env-file .env.prod -f docker/prod/compose.yml logs -f --tail=200 app
```

Per rendere il rollback persistente, aggiornare `PROD_IMAGE_TAG` in `.env.prod` dopo la verifica.
