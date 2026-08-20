# Deploy del template Yii3

Il progetto distribuisce un'immagine FrankenPHP/Caddy con Docker Compose,
GitHub Actions e GHCR su un VPS Linux tramite SSH. Il provisioning del server
è intenzionalmente separato dal ciclo ordinario delle release.

## Flusso di una release

```text
branch → pull request → CI
  → validazione file operativi, test, audit e scansioni Trivy
  → build e verifica dell'unica immagine prod
merge su main
  → nuova CI e pubblicazione su GHCR con tag SHA + latest
  → CD dopo CI verde
  → preflight configurazione e manifest
  → checkout dello stesso SHA sul VPS
  → backup DB → migration → avvio con healthcheck
  → rollback dell'immagine se il deploy non supera i controlli
```

La CI non possiede accesso SSH alla produzione. La CD parte automaticamente
soltanto dopo una CI riuscita, avviata da un push su `main`; può anche essere
eseguita manualmente indicando uno SHA completo già pubblicato.

## 1. Bootstrap del VPS

Questa fase si esegue una volta per server e richiede intervento manuale. Il
repository non installa il sistema operativo e non modifica utenti, firewall,
DNS o daemon Docker.

Prerequisiti:

- Linux aggiornato con Docker Engine e Docker Compose v2;
- utente operativo, per esempio `deploy`, autorizzato a usare Docker;
- accesso SSH a chiave e firewall aperto soltanto sulle porte necessarie;
- DNS dell'applicazione diretto al VPS;
- repository clonato nella directory di deploy (default `/opt/yii3`);
- chiave pubblica dedicata alla CD in `authorized_keys`;
- accesso in sola lettura a GHCR sul VPS, se il package è privato.

Il checkout sul VPS deve avere un remote e un branch coerenti con le GitHub
Variables `DEPLOY_REMOTE` e `DEPLOY_BRANCH`. La CD usa un detached HEAD, ma
accetta soltanto commit appartenenti alla storia del branch configurato.

### Runtime locale di produzione

Nella directory di deploy:

```bash
cp .env.prod.example .env.prod
cp docker/prod/compose.local.example.yml docker/prod/compose.local.yml
chmod 600 .env.prod
```

Compilare `.env.prod` con valori reali. Il file contiene i segreti runtime e
deve rimanere esclusivamente sul VPS: non va committato, copiato nei log o
salvato nei GitHub Secrets della pipeline.

Le impostazioni principali sono:

- `APP_IMAGE`, sostituita dalla CD con il tag SHA della release;
- `PROD_HOST`, `SERVER_NAME` e `APP_PORT`;
- `AUTH_COOKIE_SECRET_KEY`;
- `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`, `MYSQL_ROOT_PASSWORD`;
- l'eventuale forward loopback del DB per il tunnel SSH.

### Proxy Caddy esterno

L'assetto corrente usa uno stack separato `caddy-docker-proxy`: termina TLS,
legge le label Compose e pubblica sia l'app sia Grafana. La configurazione è
versionata in [`docker/proxy/`](docker/proxy/), ma il bootstrap è manuale.

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

Adattare `/opt/yii3` e `/home/deploy` se l'installazione usa percorsi o utenti
diversi. Installare sempre insieme `compose.yml` e `Caddyfile.base`. La CD
ordinaria non aggiorna né riavvia questo stack.

Il proxy monta il socket Docker in sola lettura. È comunque un accesso
privilegiato all'host: non esporre il socket in rete. La riduzione di questo
accesso tramite socket proxy/rootless resta un'attività di hardening del VPS.

L'eliminazione del proxy esterno è una possibile semplificazione futura, non
un difetto da correggere ora. Richiede prima di ridisegnare terminazione TLS,
routing di Grafana, persistenza dei certificati e metriche Caddy.

## 2. Configurazione GitHub

La configurazione effettiva nel repository GitHub richiede il proprietario.
Il workflow esegue un preflight e fallisce prima dell'SSH se manca un secret
obbligatorio o un valore non supera la validazione.

### Secrets obbligatori

| Nome | Contenuto |
|---|---|
| `VPS_HOST` | Hostname o IPv4 usato per SSH |
| `VPS_USER` | Utente operativo sul VPS |
| `VPS_SSH_KEY` | Chiave privata dedicata esclusivamente alla CD |
| `VPS_KNOWN_HOSTS` | Riga/e complete e verificate in formato `known_hosts` |

Verificare la fingerprint della chiave host tramite un canale fidato. Non
disabilitare `StrictHostKeyChecking` e non sostituire il secret con un
`ssh-keyscan` eseguito durante ogni deploy.

Con una porta SSH diversa da `22`, `VPS_KNOWN_HOSTS` deve contenere l'host nel
formato `[host]:porta`. La chiave pubblica corrispondente a `VPS_SSH_KEY` deve
essere presente nell'`authorized_keys` dell'utente configurato.

### Variables opzionali

| Nome | Default | Scopo |
|---|---|---|
| `DEPLOY_DIR` | `/opt/yii3` | Directory assoluta del checkout sul VPS |
| `DEPLOY_REMOTE` | `origin` | Remote Git autorizzato per la release |
| `DEPLOY_BRANCH` | `main` | Branch di cui lo SHA deve fare parte |
| `VPS_SSH_PORT` | `22` | Porta SSH |
| `HEALTH_URL` | `http://127.0.0.1:8080/login` | Endpoint HTTP(S) verificato dalla CD |

L'assetto scelto usa Repository Secrets e Repository Variables. Un Environment
GitHub con regole di approvazione è facoltativo e non è necessario al workflow.

## 3. Primo deploy

Prima del merge che attiverà la prima CD:

1. verificare `docker version` e `docker compose version` sul VPS;
2. verificare il checkout, `.env.prod` e `compose.local.yml`;
3. avviare il proxy e controllarne i log;
4. assicurarsi che il VPS possa eseguire il pull dell'immagine GHCR;
5. configurare Secrets e, se servono, Variables su GitHub;
6. validare le configurazioni dal repository con `make validate-ops`;
7. aprire una PR e attendere la CI verde prima del merge.

Il primo merge su `main` pubblica l'immagine e avvia la CD. A deploy concluso:

```bash
curl -fsS "https://<PROD_HOST>/login" > /dev/null
cd /opt/yii3
docker compose --env-file .env.prod \
  -f docker/prod/compose.yml \
  -f docker/prod/compose.local.yml ps
```

La verifica pubblica e l'ispezione del run CD sono controlli manuali: il
repository non può attestare lo stato reale di una VPS a cui non accede.

## 4. Ciclo ordinario delle release

1. creare una branch da `origin/main` aggiornato;
2. implementare e verificare la modifica;
3. aprire la PR e attendere la CI;
4. fare review e merge manuale;
5. controllare il run CI su `main` e il successivo run CD;
6. verificare gli alert e il percorso pubblico dopo modifiche ad alto rischio.

Il job CD usa il concurrency group `production-deploy`: GitHub mantiene al
massimo un run attivo e uno pendente per il gruppo. Un run pendente più recente
può sostituirne uno precedente; nessun deploy viene eseguito in parallelo.

La CD esegue nell'ordine:

1. validazione SHA, Secrets, Variables e manifest GHCR;
2. `scripts/checkout-deploy-commit.sh`;
3. `scripts/backup-db.sh`;
4. `scripts/deploy.sh`.

Directory, remote, branch, porta SSH e health URL hanno default
retrocompatibili ma sono tutti parametrizzabili. Lo SHA è la fonte unica per
checkout e immagine; `latest` non viene usato per il deploy.

## 5. Healthcheck, rollback e recovery

L'immagine prod include un `HEALTHCHECK` sulla pagina `/login`. Docker Compose
usa quello stato durante `up --wait`; `deploy.sh` esegue inoltre un controllo
HTTP con retry e ripristina l'immagine precedente se avvio, invariante o health
check falliscono. Le migration non vengono annullate automaticamente.

`restart: unless-stopped` riavvia un processo terminato o un container dopo il
riavvio del daemon, ma non riavvia da solo un container che resta in stato
`unhealthy`. In quel caso healthcheck e monitoring rendono il guasto visibile;
l'azione correttiva segue i runbook.

I backup pre-deploy sono creati con directory `0700`, dump `0600`, `umask 077`
e retention di 14 giorni sui soli nomi automatici. La CI prova realmente dump,
svuotamento e restore su uno stack MySQL isolato a ogni run e, tramite schedule,
anche ogni settimana sulla branch predefinita. Un drill su un dump reale del VPS
resta una valutazione facoltativa e non blocca la chiusura del progetto.

## 6. Deploy manuale e incidenti

Da GitHub: **Actions → CD → Run workflow**, passando come `image_tag` lo SHA
Git completo di 40 caratteri di un'immagine già pubblicata. SHA abbreviati,
`latest` e manifest inesistenti vengono rifiutati prima dell'SSH.

Le procedure operative sono in [`docs/runbooks/`](docs/runbooks/):

- [stato e log](docs/runbooks/stato-e-log.md);
- [deploy manuale](docs/runbooks/deploy-manuale.md);
- [deploy fallito](docs/runbooks/deploy-failed.md);
- [rollback](docs/runbooks/rollback.md);
- [backup e restore](docs/runbooks/backup-restore.md);
- [monitoring e Telegram](docs/runbooks/monitoring.md);
- [accesso DB tramite tunnel](docs/runbooks/accesso-db-tunnel.md).

Non improvvisare reset Git, restore, cancellazioni di volumi o modifiche
dirette agli script sul VPS. La fonte di verità è sempre il repository.

## Confini di responsabilità

| Ambito | Responsabilità |
|---|---|
| Repository | Compose, Dockerfile, workflow, script, test e runbook versionati |
| CI | Verifica codice/configurazioni e produce l'artefatto |
| CD | Distribuisce una release già verificata su un server già predisposto |
| GitHub Settings | Repository Secrets, Repository Variables e ruleset: intervento owner |
| VPS | OS, Docker, SSH, firewall, DNS, file segreti e bootstrap: intervento owner |
| Produzione | Deploy e monitoring reali: solo operazioni esplicitamente autorizzate |
