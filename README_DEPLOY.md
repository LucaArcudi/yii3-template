# Deploy del template Yii3

Il progetto usa Docker Compose, GitHub Actions e GHCR per distribuire
l'applicazione su un VPS Linux tramite SSH.

Questa guida descrive il comportamento corrente. I valori specifici
`/opt/yii3`, `main`, porta `22` e porta applicativa `8080` non sono ancora
parametrizzati: la relativa attività è tracciata in
[`PIANO_MIGLIORAMENTO_TEMPLATE.md`](PIANO_MIGLIORAMENTO_TEMPLATE.md).

## Flusso corrente

```text
merge su main
  → CI verde
  → build e push immagine GHCR con tag SHA
  → avvio automatico della CD
  → verifica del manifest
  → connessione SSH al VPS
  → checkout dello stesso SHA
  → backup database
  → pull immagine e migration
  → ricreazione container e health check
  → rollback applicativo in caso di errore
```

La CI non accede alla produzione. La CD parte soltanto dopo una CI riuscita
su `main` oppure tramite avvio manuale esplicito.

## Prerequisiti del VPS

Il VPS corrente deve avere già:

- Linux con accesso SSH;
- utente `deploy` autorizzato a usare Docker;
- Docker Engine e Docker Compose v2;
- repository clonato in `/opt/yii3`;
- rete Docker esterna `caddy_public` e proxy Caddy;
- `/opt/yii3/.env.prod` con i segreti runtime;
- `/opt/yii3/docker/prod/compose.local.yml`;
- directory `/opt/yii3/backups`;
- chiave pubblica del CD in `/home/deploy/.ssh/authorized_keys`.

Il provisioning completo di questi prerequisiti non è ancora automatizzato.

## File sul VPS

```text
/opt/yii3/
├── .env.prod                         # segreti runtime, fuori da Git
├── docker/prod/compose.yml           # versione dal repository
├── docker/prod/compose.local.yml     # configurazione locale, fuori da Git
├── docker/proxy/                     # proxy Caddy versionato
├── scripts/                          # backup e deploy versionati
└── backups/                          # dump pre-deploy e manuali
```

Il checkout viene portato in detached HEAD sullo stesso commit usato come tag
dell'immagine. I file locali ignorati da Git non vengono rimossi.

Il proxy in esecuzione usa `/home/deploy/caddy-proxy/`; i due file installati
in quella directory provengono dalla sorgente versionata `docker/proxy/`.

## Bootstrap manuale del proxy Caddy

Il reverse proxy è uno stack Docker Compose separato dall'applicazione, ma la
sua configurazione resta versionata in [`docker/proxy/`](docker/proxy/). Il
bootstrap si esegue una volta su un VPS nuovo e si ripete soltanto quando si
aggiornano intenzionalmente configurazione o immagine del proxy; la CD
ordinaria non lo riavvia.

Prima dell'avvio:

- il DNS di `PROD_HOST` deve puntare al VPS;
- le porte TCP `80` e `443` devono essere raggiungibili e non occupate;
- l'utente `deploy` deve poter eseguire Docker Compose.

Dal checkout sul VPS:

```bash
cd /opt/yii3
docker network inspect caddy_public >/dev/null 2>&1 || docker network create caddy_public
install -d -m 0755 /home/deploy/caddy-proxy
install -m 0644 docker/proxy/compose.yml docker/proxy/Caddyfile.base /home/deploy/caddy-proxy/
cd /home/deploy/caddy-proxy
docker compose pull
docker compose up -d
docker compose ps
```

La fonte di verità resta nel repository. Installare sempre insieme
`compose.yml` e `Caddyfile.base`: copiare soltanto il Compose lascia il mount
del Caddyfile senza sorgente valida.

Per controllare l'avvio:

```bash
cd /home/deploy/caddy-proxy
docker compose logs --tail=100 caddy
```

Dopo il primo deploy dell'applicazione, verificare il percorso pubblico:

```bash
curl -fsS "https://<PROD_HOST>/login" > /dev/null
```

`caddy-docker-proxy` legge le label dei container tramite il socket Docker.
Questo accesso è necessario al funzionamento corrente: non pubblicare il
socket in rete e non montarlo in altri container senza una motivazione
esplicita.

## Configurazione GitHub

Il workflow corrente legge quattro repository secrets:

| Secret | Contenuto |
|---|---|
| `VPS_HOST` | IP o hostname usato per SSH |
| `VPS_USER` | utente operativo, normalmente `deploy` |
| `VPS_SSH_KEY` | chiave privata dedicata alla CD |
| `VPS_KNOWN_HOSTS` | righe complete `known_hosts` verificate per `VPS_HOST` |

La chiave pubblica corrispondente a `VPS_SSH_KEY` deve essere presente in
`authorized_keys` sul VPS. Non riutilizzare chiavi personali o credenziali di
Codex.

`VPS_KNOWN_HOSTS` deve contenere la chiave host effettivamente verificata. Non
disabilitare `StrictHostKeyChecking` e non sostituire il secret con un
`ssh-keyscan` eseguito a ogni deploy.

L'evoluzione prevista sposterà questa configurazione in un GitHub Environment
`production`, separando Secrets e Variables non sensibili.

## Configurazione runtime

Partire da `.env.prod.example` e creare manualmente `/opt/yii3/.env.prod` sul
VPS. Il file reale non deve transitare nel repository o nei log della CI.

Contiene almeno:

- immagine applicativa e host pubblico;
- chiave dei cookie;
- nome, utente e password del database;
- password root MySQL;
- eventuali porte e proxy fidati.

Le modifiche allo schema passano sempre da `./yii migrate:up`. Gli script
`initdb.d` servono soltanto al primo avvio di un volume MySQL vuoto e non sono
una procedura di aggiornamento della produzione.

## GHCR

La CI pubblica:

```text
ghcr.io/<owner>/<repository>:<sha-completo>
ghcr.io/<owner>/<repository>:latest
```

La CD distribuisce il tag SHA immutabile, non `latest`.

Se il package GHCR è pubblico, il VPS può effettuare il pull senza login. Se
è privato, il VPS necessita di una credenziale separata con il solo permesso
`read:packages`. Non usare token personali con permessi amministrativi.

## Deploy automatico

Il workflow [`.github/workflows/cd.yml`](.github/workflows/cd.yml) riceve lo
SHA dalla CI completata, verifica che l'immagine esista e poi esegue:

1. `scripts/checkout-deploy-commit.sh`;
2. `scripts/backup-db.sh`;
3. `scripts/deploy.sh`.

Il deploy è serializzato dal concurrency group `production-deploy`: non
possono essere eseguiti due deploy contemporaneamente.

## Deploy manuale da GitHub

Percorso:

```text
GitHub → Actions → CD → Run workflow
```

L'input `image_tag` deve essere lo SHA Git completo di 40 caratteri di una
release pubblicata. SHA abbreviati, `latest` e manifest inesistenti vengono
rifiutati prima dell'accesso SSH.

## Operazioni manuali e incidenti

Le procedure operative vivono sotto [`docs/runbooks/`](docs/runbooks/):

- [stato e log](docs/runbooks/stato-e-log.md);
- [deploy manuale](docs/runbooks/deploy-manuale.md);
- [deploy fallito](docs/runbooks/deploy-failed.md);
- [rollback](docs/runbooks/rollback.md);
- [backup e restore](docs/runbooks/backup-restore.md);
- [accesso DB tramite tunnel](docs/runbooks/accesso-db-tunnel.md).

Non improvvisare comandi di cancellazione, reset del repository, restore o
ricreazione dei volumi fuori dai runbook.

## Responsabilità operative

```text
Bootstrap VPS  prepara manualmente macchina e proxy, una volta
CI             testa e produce l'artefatto, a ogni PR/merge
CD             distribuisce la release applicativa, dopo CI verde
Docker         esegue i servizi in sviluppo, CI e produzione
```

La CD non installa Docker, non configura utenti, firewall, DNS o proxy e non
riconfigura il sistema operativo. Questi prerequisiti vengono preparati e
verificati manualmente prima del primo deploy.
