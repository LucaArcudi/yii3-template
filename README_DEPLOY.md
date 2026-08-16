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
├── scripts/                          # backup e deploy versionati
└── backups/                          # dump pre-deploy e manuali
```

Il checkout viene portato in detached HEAD sullo stesso commit usato come tag
dell'immagine. I file locali ignorati da Git non vengono rimossi.

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

## Ansible

Ansible non viene installato dal repository: chi amministra il server deve
installarlo separatamente sulla propria postazione di controllo e verificare:

```bash
ansible-playbook --version
```

Sul VPS normalmente non serve installare Ansible; sono sufficienti SSH e
Python. L'inventory reale parte da `ansible/inventory.example.ini`, viene
salvato come `ansible/inventory.ini` ed è ignorato da Git.

I playbook attuali sono ancora specifici dell'installazione esistente:

- `server_check.yml` verifica un VPS già preparato;
- `proxy.yml` gestisce rete e proxy Caddy;
- `app.yml` modifica configurazione e ricrea l'app, sovrapponendosi alla CD.

Non costituiscono ancora un bootstrap riutilizzabile per un VPS nuovo. Prima
di presentarli come percorso supportato dovranno essere parametrizzati,
completati e separati dal deploy applicativo.

## Responsabilità definitive

```text
Ansible  prepara e verifica la macchina, raramente
CI       testa e produce l'artefatto, a ogni PR/merge
CD       distribuisce la release, dopo CI verde
Docker   esegue i servizi in dev, CI e produzione
```

Ansible non deve essere eseguito a ogni merge e la CD non deve installare o
riconfigurare il sistema operativo.
