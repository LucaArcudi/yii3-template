# Yii3 Template

> Questo template è fornito "così com'è".
> Hardening e verifiche di sicurezza restano a carico di chi lo installa.

Template applicativo [Yii3](https://www.yiiframework.com/) con tema
ArchitectUI e domini admin pronti all'uso: utenti, ruoli e permessi, menu,
task e notifiche. Include una pipeline completa: test e scansioni in CI,
immagine pubblicata su GHCR, deploy automatico su VPS via SSH e monitoring con
Prometheus, Grafana, Loki, Alloy e notifiche Telegram.

Licenza [BSD-3-Clause](LICENSE) — contributi benvenuti, vedi
[CONTRIBUTING.md](CONTRIBUTING.md).

## Requisiti

- Docker con Compose v2
- GNU make per i target di qualità (su Windows: WSL2)

## Avvio rapido

Il compose di riferimento è `compose.yml` alla radice (lo stesso usato dalla
CI): servizio `app` (FrankenPHP, build target `dev` con Xdebug) + servizio
`db` (MySQL 8.4).

```bash
git clone https://github.com/LucaArcudi/yii3-template.git
cd yii3-template
cp .env.example .env
docker compose up -d
docker compose run --rm app composer install
```

App su <http://localhost:8080> (porta: `DEV_PORT` in `.env`), MySQL esposto
su `localhost:3306` (`DB_PORT`). Il codice è bind-montato in `/app`: le
modifiche sono attive subito. Xdebug si abilita con `XDEBUG_MODE=debug` in
`.env`.

Alla **prima** inizializzazione del volume, MySQL carica gli snapshot SQL
di `database/` via initdb.d. Lo schema è comunque gestito dalle **migration
del framework** (`yiisoft/db-migration`), che eseguono gli stessi snapshot
idempotenti: su un DB già inizializzato registrano solo la history, su un
DB vuoto fanno il bootstrap completo (la CI valida entrambi gli scenari).

```bash
docker compose run --rm app ./yii migrate:up -y      # applica le migration
docker compose run --rm app ./yii migrate:history    # stato
docker compose run --rm app ./yii migrate:create ... # nuova migration
```

Primo utente admin (il seed, per scelta, non crea utenti):

```bash
docker compose run --rm app ./yii user:create admin@example.com "Admin"
```

Stampa una password generata, mostrata una sola volta; opzioni `--password`
e `--role` (default `ADMIN`). Reset totale del DB:
`docker compose down -v && docker compose up -d`.

## Test e qualità

I target Make usano lo stesso `compose.yml` della root e gli stessi strumenti
della CI. Falli passare prima di aprire una PR.

| Comando | Cosa fa |
|---|---|
| `make test` | suite Codeception con MySQL in uno stack isolato e temporaneo |
| `make psalm` | analisi statica |
| `make cs-fix` | PHP CS Fixer |
| `make rector` | refactoring automatici |
| `make composer-dependency-analyser` | igiene delle dipendenze |
| `make validate-ops` | actionlint, ShellCheck e render di tutti i Compose operativi |
| `make trivy-gate` | gate HIGH/CRITICAL con fix su filesystem e immagine prod |
| `make help` | elenco completo dei target |

`make test` usa il progetto Compose `yii3-template-test`, applica le migration
su un volume DB nuovo e rimuove container e volumi al termine anche in caso di
errore. La porta MySQL di test predefinita è `33060`; può essere cambiata con
`make test TEST_DB_PORT=<porta>`. I deploy di produzione non sono target Make:
passano esclusivamente dalla pipeline e dagli script versionati descritti in
[`README_DEPLOY.md`](README_DEPLOY.md).

Le scansioni Trivy locali usano l'immagine ufficiale `aquasec/trivy`
(nessuna installazione richiesta), in modalità report-only con
`exit-code 0`; esclusioni in `trivy.yaml`:

```bash
make trivy        # filesystem + configurazioni
docker compose -f compose.yml build app
make trivy-image  # scansione dell'immagine app
```

## CI/CD

- **CI** (`.github/workflows/ci.yml`): validazione dei file operativi, build,
  scansioni Trivy, Composer validate/audit/dependency analysis, PHP CS Fixer,
  Psalm, suite Codeception e drill backup/restore isolato. Dopo i test
  costruisce una sola immagine prod, ne
  verifica anche il `HEALTHCHECK` e la sottopone al gate Trivy; su push a
  `main` pubblica su GHCR quella stessa immagine
  (`ghcr.io/lucaarcudi/yii3-template`, tag `latest` e SHA del commit). Uno
  schedule settimanale mantiene esercitata la procedura di recovery.
- **CD** (`.github/workflows/cd.yml`): al successo della CI su `main`
  allinea i file sul VPS via SSH, esegue il backup del DB, applica le
  migration (`migrate:up`) e fa `docker compose pull` + `up` con health
  check finale.
- **Monitoring** (`docker/monitoring/`): metriche, alert, log centralizzati e
  notifiche Telegram in uno stack Compose separato dal ciclo di deploy
  applicativo.

Dettagli operativi: [README_DEPLOY.md](README_DEPLOY.md) e
[indice della documentazione](docs/README.md). Mappa dei confini:
[flusso DevOps](docs/DEVOPS_WORKFLOW.md).
Roadmap: [piano di miglioramento](PIANO_MIGLIORAMENTO_TEMPLATE.md),
[sviluppo funzionale](docs/roadmap-sviluppo.md) e
[infrastruttura e osservabilità](docs/roadmap-infrastruttura.md).
Note di release: [CHANGELOG.md](CHANGELOG.md).
