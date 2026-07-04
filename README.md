# Yii3 Template

Template applicativo [Yii3](https://www.yiiframework.com/) con tema
ArchitectUI e domini admin pronti all'uso: utenti, ruoli e permessi, menu,
task e notifiche. Include una pipeline completa: test e scansioni in CI,
immagine pubblicata su GHCR, deploy automatico su VPS via SSH.

Licenza [MIT](LICENSE) — contributi benvenuti, vedi
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

Sono gli stessi check eseguiti dalla CI: falli passare prima di aprire
una PR.

| Comando | Cosa fa |
|---|---|
| `make test` | suite Codeception (ambiente test dedicato) |
| `make psalm` | analisi statica |
| `make cs-fix` | PHP CS Fixer |
| `make rector` | refactoring automatici |
| `make composer-dependency-analyser` | igiene delle dipendenze |
| `make help` | elenco completo dei target |

Le scansioni Trivy locali usano l'immagine ufficiale `aquasec/trivy`
(nessuna installazione richiesta), in modalità report-only con
`exit-code 0`; esclusioni in `trivy.yaml`:

```bash
make trivy        # filesystem + configurazioni
docker compose -f compose.yml build app
make trivy-image  # scansione dell'immagine app
```

## CI/CD

- **CI** (`.github/workflows/ci.yml`): build, scansioni Trivy, `composer
  validate`/`audit` e suite Codeception nel compose di root, su ogni push e
  PR; su push a `main` pubblica l'immagine su GHCR
  (`ghcr.io/lucaarcudi/yii3-template`, tag `latest` e SHA del commit).
- **CD** (`.github/workflows/cd.yml`): al successo della CI su `main`
  allinea i file sul VPS via SSH, esegue il backup del DB, applica le
  migration (`migrate:up`) e fa `docker compose pull` + `up` con health
  check finale.

Dettagli operativi: [README_DEPLOY.md](README_DEPLOY.md) e
[docs/documentazione-progetto.md](docs/documentazione-progetto.md).
Note di release: [CHANGELOG.md](CHANGELOG.md).
