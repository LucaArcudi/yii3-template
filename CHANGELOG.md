# Changelog

## Unreleased

- CD: deploy sul **tag SHA del commit** invece di `latest` (run riproducibili; su run manuale l'input `image_tag`) e **rollback automatico** — `deploy.sh` registra il digest dell'immagine in esecuzione e la ripristina se avvio, invariante immagine o health check falliscono (le migration non vengono annullate: restore dal backup pre-deploy, runbook §9.4).
- Notifiche degli alert su Telegram: contact point, notification policy e regola ponte provisionati in Grafana (`docker/monitoring/grafana/provisioning/alerting/`) — gli alert `firing` di Prometheus generano una notifica per coppia alertname/severity con rinotifica ogni 4h; bot token e chat ID in `docker/monitoring/.env`.
- Centralizzazione dei log (`docker/monitoring/`): Loki (storage filesystem, retention 14 giorni allineata ai backup) + Alloy (stdout/stderr di tutti i container via Docker service discovery più `runtime/logs/app.log` dal volume dell'app, read-only); datasource Loki provisionato in Grafana, config validate in CI (`loki -verify-config`, `alloy fmt`).
- Ristrutturato `src/` in moduli feature autocontenuti (vertical slice): `Core` (User, Role, Permission, PermissionGroup, Notification, Log, Home, Error, NotFound, Language — con `PermissionGroup` separato da `Permission` e `UserCreateCommand` nel dominio User) e `Mes` (Task) vivono in `src/<Modulo>/` con classi di dominio, `Actions/`, `views/`, `routes.php` e `di.php` per modulo, raccolti automaticamente da `config/common/routes.php` (ora puro aggregatore) e `config/common/di/modules.php`. I binding DI di dominio escono da `config/common/di/` (restano infrastruttura e il binding globale `ValidatorInterface` in `di/validator.php`). L'infrastruttura condivisa è consolidata in `src/Shared/` (Data primitives, Services, Middleware, Params, Commands, Widgets, Dashboard, Navigation, Helpers, Assets, resources) senza più il suffisso "Core" nei namespace; l'alias `@resources` punta a `src/Shared/resources`. In `src/` restano solo `Core/`, `Mes/`, `Shared/` e `Migrations/`.
- Adottato `yiisoft/db-migration`: gli snapshot SQL di release sono eseguiti da una catena di migration (`App\Migrations`) validata in CI sia per idempotenza su DB esistente sia per bootstrap da database vuoto.
- Aggiunto comando console `user:create` per il primo utente admin (password generata stampata una sola volta, ruolo di default `ADMIN`).
- CD: step `migrate:up` tra backup e avvio della nuova versione dell'app.
- Corretto l'ordine degli script initdb.d nei compose (root e prod): `release_1_0_2` è lo schema base completo e deve precedere le altre release, che ne referenziano le tabelle via FK.
- Inclusa `database/` nell'immagine prod (`.dockerignore` è una allowlist e la escludeva: il `migrate:up` del CD legge gli snapshot da `/app/database`); nuovo step CI che verifica l'artefatto prod (file richiesti dal deploy e bit di esecuzione).
- CI più severa: `composer audit` ora è bloccante (niente più `|| true`) e Psalm è uno step obbligatorio del job di test (con baseline committata per il debito storico). Aggiornate le dipendenze dev con advisory: guzzle 7.13.1, psr7 2.12.3, dom-crawler e yaml 8.1.1 — audit pulito.
- CD: il container app viene ricreato esplicitamente a ogni deploy e il run verifica che giri l'immagine appena pubblicata (due deploy avevano lasciato attiva l'immagine precedente nonostante il pull).
- Aggiunto stack di monitoring (`docker/monitoring/`): Prometheus, Grafana (esposta via Caddy con TLS), node-exporter, cAdvisor e mysqld-exporter; solo Grafana è pubblica, il resto su rete interna. Config in `.env` locale (modello committato).
- CD: la logica di backup e deploy è spostata in `scripts/backup-db.sh` e `scripts/deploy.sh`, eseguiti sul VPS dal checkout allineato. Corregge il bug per cui i deploy risultavano verdi ma si interrompevano dopo il `migrate:up`: lo script arrivava via heredoc/stdin e `docker compose run` ne divorava le righe restanti (app mai ricreata, invariante mai eseguito).
- Osservabilità completata: metriche HTTP di Caddy scrappate da Prometheus (endpoint interno `:9180` via `docker/proxy/Caddyfile.base`), regole di alert versionate in `prometheus/rules/alerts.yml` e validate in CI con promtool (CPU, RAM, disco, target down, MySQL down, upstream proxy), retention automatica dei backup (14 giorni, solo dump automatici).
- Alert di liveness dell'app basato sugli upstream del reverse proxy (`caddy_reverse_proxy_upstreams_healthy`) invece che su cAdvisor: con lo snapshotter containerd di Docker (driver `overlayfs`) cAdvisor non esporta serie per-container — limite upstream verificato fino alla v0.52 e documentato.
- Dashboard ripulita: guida progetto, backlog admin e prossimi step sono diventati documentazione (`docs/documentazione-progetto.md` §3.1 e `docs/roadmap-sviluppo.md`); al loro posto un unico componente con i riferimenti GitHub (repo, docs, roadmap, issue, Actions).
- Visibilità di menu e dashboard centralizzata sulle policy: le definizioni dichiarano una `policyClass` e il rendering usa `canAccess()`, rimuovendo le condizioni extra dalle view.
- Aggiunte linee guida operative `AGENTS.md` per Codex e convenzione `Co-Authored-By` per tracciare Codex tra i contributor AI.
- Aggiunta `docs/roadmap-ai-codex-claude-code.md`: integrazione di Codex e Claude Code nel workflow di sviluppo e nella pipeline, rivista e ampliata con stato reale del repo, centralizzazione log (Loki/Alloy), percorso alert→notifiche→incident e guardrail sui permessi GitHub degli agenti.
- Riorganizzata `docs/`: i temi infrastrutturali (centralizzazione log Loki/Alloy, canale di notifica degli alert, self-healing deterministico e rollback automatico) escono dalla roadmap AI e vivono nella nuova `docs/roadmap-infrastruttura.md`; la roadmap AI resta focalizzata sugli agenti e rimanda ai prerequisiti.

## 1.0.0 - 2026-05-02

- Aggiunto dominio notifiche con tabelle, repository, reader, centro notifiche, apertura con mark-read e dropdown ArchitectUI in header con badge non lette.
- Aggiunta notifica automatica su login utente.
- Rimossa la persistenza delle impostazioni globali: logo login, logo header e footer sono configurati via Param/env.
- Rimossi fallback e seed dei permessi `VIEW` generici; restano `VIEW_ALL` e `VIEW_OWN`.
- Aggiunti widget riusabili `Tabs`, `CardList` e `Pagination`.
- Aggiunta vista task a card con FilterBar e paginazione, affiancata alla GridView tramite tabs.
- Aggiunti script `database/migrations/release_1_0_0.sql` e `database/seeders/release_1_0_0.sql`.
- Aggiunti test unitari per widget tabs.
