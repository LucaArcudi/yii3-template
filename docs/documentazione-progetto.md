# Yii3 Template — Documentazione di progetto

> Ultimo aggiornamento: 7 luglio 2026.
>
> Documenti correlati: [README.md](../README.md) (quick start e release),
> [README_DEPLOY.md](../README_DEPLOY.md) (runbook deploy passo-passo),
> [CHANGELOG.md](../CHANGELOG.md),
> [analisi-sicurezza-e-migliorie-2026-07-02.md](analisi-sicurezza-e-migliorie-2026-07-02.md) (audit di sicurezza).

## Indice

1. [Panoramica](#1-panoramica)
2. [Stack tecnologico](#2-stack-tecnologico)
3. [Struttura del repository](#3-struttura-del-repository)
4. [Architettura applicativa](#4-architettura-applicativa)
5. [Database](#5-database)
6. [Sviluppo locale](#6-sviluppo-locale)
7. [Test e qualità del codice](#7-test-e-qualità-del-codice)
8. [DevOps](#8-devops)
9. [Runbook operativi](#9-runbook-operativi)
10. [Limiti noti e lavori futuri](#10-limiti-noti-e-lavori-futuri)

---

## 1. Panoramica

Applicazione web **Yii3** basata sul template ufficiale `yiisoft/app`, con tema
**ArchitectUI** e un'area amministrativa completa: utenti, ruoli, permessi,
gruppi di permessi, menu di navigazione, task e centro notifiche.

Il progetto funge da **template di partenza** per applicazioni gestionali: il
modulo `Core` fornisce autenticazione, RBAC, audit log, notifiche e la
componentistica UI; il modulo `Mes` (dominio `Task`) è l'esempio di riferimento
per aggiungere nuovi domini CRUD.

L'interfaccia è in italiano (locale di default), con inglese come seconda
lingua selezionabile.

In produzione l'app gira su un VPS in container Docker (immagine FrankenPHP
pubblicata su GHCR), dietro reverse proxy Caddy con TLS automatico, con
pipeline CI/CD su GitHub Actions. Vedi la [sezione DevOps](#8-devops).

## 2. Stack tecnologico

| Livello | Tecnologia |
|---|---|
| Linguaggio | PHP 8.2 – 8.5 (immagine Docker: PHP 8.4) |
| Framework | Yii3 (pacchetti `yiisoft/*`: DI, router FastRoute, middleware dispatcher, view renderer, validator, translator, session, CSRF, user/auth, data) |
| HTTP runtime | FrankenPHP 1 (Caddy embedded) — `dunglas/frankenphp:1-php8.4-bookworm` |
| Database | MySQL 8.4 (`yiisoft/db` + `yiisoft/db-mysql`, query builder senza ORM) |
| Frontend | Tema ArchitectUI (Bootstrap 5), asset precompilati in `src/Shared/resources/architectui/`, gestiti da `yiisoft/assets` |
| Test | Codeception 5 (suite Unit, Functional, Console, Web) + PHPUnit 11 |
| Analisi statica | Psalm 6, Rector 2, PHP CS Fixer 3, composer-dependency-analyser |
| Sicurezza supply chain | Trivy 0.71 (fs, config, secret, image scan) |
| CI/CD | GitHub Actions → GHCR → deploy SSH su VPS |
| Infrastruttura | Docker Compose, Caddy (caddy-docker-proxy), Ansible |

## 3. Struttura del repository

```
.
├── ansible/               # Inventory e playbook per il VPS (proxy, app, check)
├── assets/                # Asset sorgente dell'app (main/site.css)
├── config/                # Configurazione yiisoft/config (vedi §4.2)
│   ├── common/            #   DI, params, routes condivisi web+console
│   ├── console/           #   Comandi console
│   ├── web/               #   Pipeline middleware, PSR-17, auth web
│   └── environments/      #   Override per dev / test / prod
├── database/
│   ├── migrations/        # Script SQL idempotenti per release (release_X_Y_Z.sql)
│   └── seeders/           # Dati iniziali (gruppi permessi, permessi, ruoli…)
├── docker/
│   ├── Dockerfile         # Multi-stage: base → dev / prod-builder → prod
│   ├── compose.yml        # Frammento comune (volumi caddy) usato dal Makefile
│   ├── dev/, test/        # Override compose ereditati dal template upstream (vedi §6.4)
│   ├── prod/compose.yml   # Compose di produzione (VPS)
│   ├── proxy/             # Reverse proxy Caddy per il VPS (+ Caddyfile.base metriche)
│   └── monitoring/        # Stack Prometheus/Grafana/exporter (vedi §8.9)
├── docs/                  # Documentazione di progetto (questo file, roadmap, audit…)
├── public/                # Docroot: index.php, favicon, robots.txt, asset pubblicati
├── scripts/               # Script operativi eseguiti sul VPS dal CD (backup, deploy)
├── src/                   # Codice applicativo (namespace App\, vedi §4)
├── tests/                 # Suite Codeception
├── compose.yml            # Compose di sviluppo/CI principale (app + MySQL)
├── .env / .env.example    # Variabili per il compose di sviluppo
├── .env.prod.example      # Modello del file segreti di produzione
├── Makefile               # Target dev/test/analisi/trivy (vedi §6.4)
├── trivy.yaml             # Configurazione scansioni Trivy
├── codeception.yml        # Configurazione test + coverage
└── yii                    # Entrypoint console (./yii serve, ./yii hello…)
```

### 3.1 Guida rapida alle cartelle di `src/`

Dove mettere le mani per ogni tipo di modifica (contenuto estratto dal
componente "Guida progetto" della dashboard):

| Percorso | Cosa contiene | File di riferimento |
|---|---|---|
| `config/` | Alias, container DI, route, parametri e configurazioni separate per web, console e ambienti. | `config/common/di/db.php`, `config/common/routes.php`, `config/environments/dev/params.php` |
| `src/<Modulo>/` (es. `src/Mes/`) | Moduli feature autocontenuti (vertical slice): per ogni dominio entità, input, repository, policy, action e view nella stessa cartella, più `routes.php` e `di.php` del modulo, raccolti automaticamente dalla config. | `src/Mes/Task/TaskPolicy.php`, `src/Mes/Task/Actions/IndexAction.php`, `src/Mes/routes.php` |
| `src/Shared/Data/` | Primitive dati condivise tra i moduli: entità base, wrapper input, interfaccia policy, scope di ownership. | `src/Shared/Data/BaseEntity.php`, `src/Shared/Data/Scope/OwnershipScope.php`, `src/Shared/Data/AccessPolicyInterface.php` |
| `src/Shared/Middleware/` | Middleware HTTP condivisi della pipeline applicativa. | `src/Shared/Middleware/RedirectGuestToLoginMiddleware.php`, `src/Shared/Middleware/LocaleMiddleware.php` |
| `src/Shared/Services/` | Logica riusabile tra handler e moduli: autorizzazione, autenticazione, mail, supporto alle viste. | `src/Shared/Services/AuthorizationService.php`, `src/Shared/Services/Mail/Mailer.php` |
| `src/Migrations/` | Migration del framework (`yiisoft/db-migration`) che eseguono gli snapshot SQL di release (vedi §5.2). | `src/Migrations/SqlSnapshotMigration.php` |
| `src/Shared/Dashboard/` | Definizione, visibilità e rendering dei blocchi mostrati nella home autenticata. | `src/Shared/Dashboard/DashboardComponentProvider.php`, `src/Shared/resources/components/core/` |
| `src/Shared/resources/` | Layout, componenti dashboard, template email, cataloghi traduzioni e risorse ArchitectUI (le view dei domini vivono nei moduli). | `src/Shared/resources/layouts/main.php`, `src/Shared/resources/messages/en/app.php` |
| `src/Shared/Widgets/` | Widget UI riusabili: form, input, CRUD, liste, badge, menu, modali, viste dettaglio. | `src/Shared/Widgets/Card.php`, `src/Shared/Widgets/Crud/CrudActions.php`, `src/Shared/Widgets/Forms` |
| `src/Shared/Assets/` e `assets/` | Bundle PHP che pubblicano gli asset e file statici sorgente (CSS custom). | `src/Shared/Assets/ArchitectUi/ArchitectUiAsset.php`, `assets/main/site.css` |
| `database/` | Snapshot SQL idempotenti di release e seed, eseguiti da initdb.d e dalle migration del framework. | `database/migrations/release_1_0_2.sql`, `database/seeders/release_1_0_0.sql` |
| `public/` | Document root: entry point web, favicon, robots, asset pubblicati. | `public/index.php`, `public/assets` |
| `tests/` | Suite Codeception: unit, functional, console. | `tests/Unit`, `tests/Functional`, `codeception.yml` |
| `runtime/`, `vendor/` | File generati (cache, log, sessioni) e dipendenze Composer: non contengono codice applicativo da modificare. | `runtime/logs/app.log`, `composer.json` |

## 4. Architettura applicativa

### 4.1 Bootstrap e ambienti

- Entrypoint web: `public/index.php` → `HttpApplicationRunner` di
  `yiisoft/yii-runner-http`. Entrypoint console: `./yii`
  (`yiisoft/yii-runner-console`).
- `src/Environment.php` valida `APP_ENV` (valori ammessi: `dev`, `test`,
  `prod`) e normalizza `APP_DEBUG`, `APP_C3` (coverage), `APP_HOST_PATH`.
- `public/index.php` imposta `APP_ENV=dev` + `APP_DEBUG=1` **solo se
  `APP_ENV` non è già definito** (uso locale con `php yii serve`); in
  container la variabile arriva dal compose e non viene mai sovrascritta.

### 4.2 Configurazione (yiisoft/config)

La configurazione è assemblata da `yiisoft/config` secondo
`config/configuration.php` e il merge plan:

- `config/common/` — definizioni DI (`di/*.php`), `params.php`, `routes.php`,
  validi per web e console;
- `config/web/` — pipeline middleware (`di/application.php`), factory PSR-17,
  auth web;
- `config/console/` — registrazione comandi (`commands.php`);
- `config/environments/{dev,test,prod}/params.php` — override per ambiente
  (es. in `dev` il mail transport punta a un SMTP locale su porta 1025).

I parametri sono esposti al codice tramite value object dedicati in
`src/Shared/Params/` (`ApplicationParams`, `AuthParams`, `LayoutParams`,
`MailParams`, `EntityLogParams`), popolati nel DI: le classi applicative non
leggono mai `$params` o variabili d'ambiente direttamente.

### 4.3 Variabili d'ambiente

Lette in `config/common/params.php` e nei compose. Le principali:

| Variabile | Default | Descrizione |
|---|---|---|
| `APP_ENV` | — (obbligatoria; `dev` se assente in locale) | Ambiente: `dev` / `test` / `prod` |
| `APP_DEBUG` | `false` | Debug + pagine errore dettagliate |
| `DB_DSN` | — | es. `mysql:host=db;port=3306;dbname=yii3_template` |
| `DB_USERNAME` / `DB_PASSWORD` | — | Credenziali DB |
| `AUTH_COOKIE_SECRET_KEY` | chiave d'esempio | Cifra il cookie *remember me*. **In `prod` l'app rifiuta di partire con la chiave di default** (generarla: `openssl rand -hex 32`) |
| `AUTH_PASSWORD_MAX_AGE_DAYS` | `90` | Scadenza password (0 = disattivata) |
| `AUTH_PASSWORD_RESET_TOKEN_TTL_MINUTES` | `60` | TTL token reset password |
| `AUTH_RATE_LIMIT_WINDOW_SECONDS` / `AUTH_RATE_LIMIT_BLOCK_SECONDS` | `300` / `900` | Finestra e blocco del rate limiter auth |
| `AUTH_LOGIN_MAX_ATTEMPTS` | `5` | Tentativi login per finestra (registrazione: 3, reset: 3, cambio password: 5) |
| `AUTH_DEFAULT_REGISTRATION_ROLE_CODE` | `UTENTE_ESTERNO` | Ruolo assegnato ai nuovi registrati |
| `SESSION_SAVE_PATH` | `runtime/sessions` | Path sessioni su file |
| `SESSION_COOKIE_SECURE` | `true` in prod | Flag Secure del cookie di sessione (vedi §10 per il caso dietro proxy) |
| `SESSION_COOKIE_SAMESITE` | `Lax` | SameSite del cookie di sessione |
| `MAIL_TRANSPORT` | `file` (`smtp` in dev) | `file` / `smtp` / `native` |
| `MAIL_FROM_EMAIL`, `MAIL_SMTP_*` | vedi `params.php` | Mittente e parametri SMTP |
| `ENTITY_LOG_ENABLED` (+ `_WEB` / `_CONSOLE` / `_SYSTEM`) | `true`/`true`/`false`/`true` | Audit log per canale |
| `APP_LOGO`, `APP_LOGO_SMALL`, `APP_FOOTER_LEFT`, `APP_FOOTER_RIGHT` | tema | Branding del layout |

Variabili solo compose: `APP_IMAGE`, `PROD_HOST`, `SERVER_NAME`, `APP_PORT`,
`DB_DATABASE`, `MYSQL_ROOT_PASSWORD`, `DB_FORWARD_HOST/PORT` (prod);
`DEV_PORT`, `DB_PORT`, `LOCAL_UID/LOCAL_GID`, `XDEBUG_MODE` (dev).

### 4.4 Pipeline middleware (web)

Definita in `config/web/di/application.php`, in ordine di esecuzione:

1. `ErrorCatcher` — cattura eccezioni e rende le pagine di errore;
2. `SecurityHeadersMiddleware` — `X-Content-Type-Options: nosniff`,
   `X-Frame-Options: SAMEORIGIN`, `Referrer-Policy`, `Permissions-Policy`;
   HSTS (`max-age=31536000; includeSubDomains`) solo su richieste HTTPS;
3. `LocaleMiddleware` — risolve la lingua (it/en, vedi `AppLocales`);
4. `SessionMiddleware`, `CookieMiddleware`, `CookieLoginMiddleware` —
   sessione, cookie firmati/cifrati, auto-login *remember me*;
5. `PasswordExpiredMiddleware` — forza il cambio password scaduta;
6. `StatusPageMiddleware` — pagine di stato (access denied, too many
   requests, invalid request);
7. `SameOriginRequestMiddleware` + `CsrfTokenMiddleware` — difesa CSRF a due
   livelli;
8. `FormatDataResponse`, `RequestCatcherMiddleware`, `Router` — formattazione
   risposta, request provider, dispatch della rotta.

Fallback per rotte inesistenti: `NotFoundHandler` (404 custom).

### 4.5 Routing e handler

Le rotte sono dichiarative in `src/<Modulo>/routes.php` (array di
`Route`/`Group`); `config/common/routes.php` è il solo aggregatore che le
raccoglie automaticamente da tutti i moduli. Gli handler sono **action class
invocabili singole** (niente controller multi-azione) in
`src/<Modulo>/<Dominio>/Actions/`, es. `App\Core\User\Actions\CreateAction`,
`App\Mes\Task\Actions\CreateAction`.

Convenzione URL per i CRUD: `/{dominio}`, `/{dominio}/view/{id}`,
`/{dominio}/create`, `/{dominio}/update/{id}`, `/{dominio}/delete/{id}`
(delete solo `POST`). Domini registrati: `task`, `user`, `role`,
`permission`, `permission-group`, `notification` (index/open/read-all), più
`/profile`, le rotte auth (`/login`, `/logout`, `/register`,
`/forgot-password`, `/forgot-email`, `/change-password`), lo switch lingua
`/language/{locale}` e le pagine di errore.

Le rotte riservate usano `RedirectGuestToLoginMiddleware`; l'URL richiesto
viene ricordato (`RememberedUrlService`) e ripristinato dopo il login.

### 4.6 Livello dati: pattern per dominio

Ogni dominio vive nella cartella del suo modulo, `src/<Modulo>/<Dominio>/`
(esempi completi: `src/Core/User/`, `src/Mes/Task/`), con un set di classi a
responsabilità fissa:

| Classe | Responsabilità |
|---|---|
| `<D>Entity` | Record immutabile mappato sulla tabella (estende `BaseEntity`) |
| `<D>Input` | Form model con regole `yiisoft/validator` (hydration via `yiisoft/input-http`) |
| `<D>Repository` | Scritture (insert/update/delete) via `yiisoft/db` |
| `<D>Reader` | Letture: query, filtri, sort, paginazione (`yiisoft/data`, `data-db`) |
| `<D>Filter` | Stato dei filtri della lista (da query string) |
| `<D>Policy` | Autorizzazione: cosa può fare l'utente corrente sul dominio |
| `<D>Presenter` | Formattazione dei valori per la UI |
| `<D>Scope` | Restrizione delle query in base alla visibilità (vedi sotto) |

La distinzione **`*_VIEW_ALL` vs `*_VIEW_OWN`** è implementata da
`OwnershipScope` (`src/Shared/Data/Scope/`): chi ha solo `VIEW_OWN` vede solo i
record di cui è owner; lo scope viene applicato a livello di query dal Reader.

I moduli attuali: `Core` (User, Role, Permission, PermissionGroup,
Notification, Log, più i domini web Home/Error/NotFound/Language) e `Mes`
(Task), entrambi a fette verticali autocontenute in `src/<Modulo>/`. Le
action CRUD seguono lo stesso schema:
policy check → hydration dell'Input → validazione → Repository → flash +
redirect, con audit log automatico; `WebActionService`
(`src/Shared/Services/`) fornisce le primitive comuni (risposte
forbidden/not-found, redirect, gestione degli URL "ricordati" per tornare
alla lista dopo il salvataggio).

### 4.7 Autenticazione, autorizzazione, sicurezza applicativa

- **Password**: hash Argon2id (`PasswordHasher`); scadenza configurabile con
  forzatura del cambio (`PasswordExpiredMiddleware`).
- **Login/registrazione/reset**: rate limiting persistito su
  `core_auth_rate_limit` (`AuthRateLimiter`), captcha matematico
  (`MathCaptchaService`), token di reset con schema selector/verifier hashati
  Argon2id e TTL (`AuthTokenService`); l'ID di sessione viene rigenerato al
  login (anti session-fixation); la verifica password avviene anche per email
  inesistenti (anti user-enumeration via timing).
- **Remember me**: cookie `autoLogin` cifrato con `AUTH_COOKIE_SECRET_KEY`,
  HttpOnly, SameSite (`RememberMeCookieService` + `CookieLoginMiddleware`).
- **RBAC su database**: utenti → ruoli (`core_user_role`) → permessi
  (`core_role_permission`); i permessi sono raggruppati in
  `core_permission_group` e gestibili da UI. `AuthorizationService` +
  `CurrentActorProvider` espongono i check; le Policy per dominio li
  incapsulano.
- **Audit log**: ogni modifica alle entità è tracciata su `core_log`
  (`EntityLogRepository`, widget `EntityLogList` nelle view di dettaglio),
  attivabile per canale (web/console/system).
- **Notifiche**: `core_notification` + `core_notification_user`; dropdown in
  header con badge non lette, centro notifiche, mark-read singolo e massivo.

### 4.8 UI: layout, tema, widget

- Layout in `src/Shared/resources/layouts/` (`main.php` autenticato, `guest.php`
  pubblico); le view dei domini vivono nei moduli, in
  `src/<Modulo>/<Dominio>/views/`.
- Tema ArchitectUI: asset precompilati in `src/Shared/resources/architectui/`,
  registrati da `ArchitectUiAsset` e pubblicati in `public/assets/`.
- Widget riusabili in `src/Shared/Widgets/`: input form (`Inputs/*` con validazione
  client-side coerente col validator), filtri lista (`Filters/*` con
  FilterBar e modale), data view (`Grid`, `CardList`, `Detail`,
  `Pagination`), `CrudActions`, `FlashMessages`, `Menu`, `Tabs`, `Modal`,
  `Breadcrumb`, `NotificationDropdown`, `EntityLogList`.
- La navigazione laterale è definita da `NavigationProvider`
  (`src/Shared/Navigation/`) e filtrata tramite le `policyClass` dichiarate sulle
  voci: la visibilità passa da `canAccess()`. La dashboard della home è
  composta da componenti dichiarativi (`src/Shared/Dashboard/`) con lo stesso pattern
  policy-based.
- Traduzioni: sorgenti in italiano usate come message ID; catalogo inglese in
  `src/Shared/resources/messages/en/app.php`, messaggi del validator in italiano in
  `it/yii-validator.php`. Helper `Translate` per le stringhe.

### 4.9 Email

Servizio in `src/Shared/Services/Mail/`: `Mailer` + `EmailRenderer` (template
PHP in `src/Shared/resources/emails/`, layout dedicato) con transport intercambiabile:

- `file` (default) — scrive le mail in `runtime/emails/` (comodo in dev/test);
- `smtp` — SMTP nativo con TLS/None (`SmtpEmailTransport`);
- `native` — `mail()` di PHP.

Email attuali: benvenuto alla registrazione e reset password.

### 4.10 Console

`./yii` espone i comandi Yii3 standard (`serve`, cache, assets…) più i comandi
applicativi registrati in `config/console/commands.php` (es. `hello`,
`App\Shared\Commands\HelloCommand`, da usare come scheletro).

## 5. Database

### 5.1 Schema

Tabelle (prefisso `core_` per il modulo Core, `mes_` per il modulo Mes):

| Tabella | Contenuto |
|---|---|
| `core_user` | Utenti (credenziali Argon2id, stato, scadenza password, token reset) |
| `core_role`, `core_user_role` | Ruoli e assegnazione utente→ruolo |
| `core_permission`, `core_permission_group`, `core_role_permission` | Permessi granulari, raggruppamento, assegnazione ruolo→permesso |
| `core_auth_rate_limit` | Contatori del rate limiter auth |
| `core_log` | Audit log delle entità |
| `core_notification`, `core_notification_user` | Notifiche e stato di lettura per utente |
| `mes_task` | Task (dominio d'esempio) |

### 5.2 Migrazioni e seed

Lo schema è gestito da **`yiisoft/db-migration`**: la catena in
`src/Migrations/` (namespace `App\Migrations`, classe base
`SqlSnapshotMigration`) esegue gli **script SQL idempotenti per release**
di `database/migrations/` e `database/seeders/`, che restano la fonte di
verità unica.

Regole e comandi:

- `./yii migrate:up -y` applica le migration mancanti; `migrate:history`
  mostra lo stato; `migrate:create` genera una nuova classe in
  `src/Migrations/` (nome `M<yyyymmddHHMM><Nome>`);
- la prima migration è `release_1_0_2`, lo **schema base completo** (crea
  anche `core_user`, referenziata dalle FK delle altre release); seguono
  `1_0_0`, `1_0_1` e il seed — stesso ordine nei mount initdb.d dei compose;
- ogni script resta rieseguibile senza errori (`CREATE TABLE IF NOT EXISTS`,
  `INSERT ... ON DUPLICATE KEY` ecc.): su un DB già inizializzato la catena
  registra solo la history, su un DB vuoto fa il bootstrap completo — la CI
  valida entrambi gli scenari;
- initdb.d resta come fast-path del primo `up` (MySQL lo esegue **solo alla
  prima inizializzazione del volume**); in produzione le migration girano
  nel CD prima dell'avvio della nuova versione (§8.4);
- primo utente: `./yii user:create <email> "<nome>"` (ruolo di default
  `ADMIN`, password generata e stampata una sola volta).

## 6. Sviluppo locale

### 6.1 Requisiti

- Docker + Docker Compose (percorso consigliato), oppure PHP ≥ 8.2 locale con
  estensioni `ctype`, `filter`, `mbstring` (+ `dom`, `pdo_mysql` per test
  completi e DB) e Composer.

### 6.2 Avvio con Docker (percorso principale)

Il compose di riferimento è **`compose.yml` alla radice** (lo stesso usato
dalla CI): servizio `app` (build target `dev` del `docker/Dockerfile`, con
Xdebug e Composer) + servizio `db` (MySQL 8.4 con migration e seed montati in
initdb.d).

```bash
cp .env.example .env          # DEV_PORT, DB_PORT, credenziali DB, XDEBUG_MODE
docker compose up -d          # app su http://localhost:8080, MySQL su :3306
docker compose run --rm app composer install
```

Il codice è montato in bind mount su `/app`: le modifiche sono immediate.
`XDEBUG_MODE=debug` in `.env` attiva Xdebug (host: `host.docker.internal`).

### 6.3 Avvio senza Docker

```bash
composer install
APP_ENV=dev php yii serve --port=8088   # server PHP built-in su localhost:8088
```

Senza `APP_ENV`, `public/index.php` assume `dev` con debug attivo. Il DB va
fornito a parte via `DB_DSN`/`DB_USERNAME`/`DB_PASSWORD`.

### 6.4 Makefile

Il `Makefile` incapsula i flussi Docker: `make up|down|stop|clear`,
`make shell`, `make yii <cmd>`, `make composer <cmd>`, `make test`,
`make psalm`, `make rector`, `make cs-fix`, `make trivy|trivy-fs|trivy-config|trivy-image`,
`make help`.

> **Nota (eredità upstream):** i target dev/test del Makefile usano
> `docker/compose.yml` + `docker/dev|test/compose.yml`, che si aspettano file
> `docker/dev/.env` e `docker/test/.env` non presenti nel repo, e
> `prod-deploy` è pensato per Docker Swarm. Sono residui del template
> `yiisoft/app`: il flusso effettivo di sviluppo è il `compose.yml` root
> (§6.2) e quello di produzione è descritto in §8. Restano pienamente
> operativi i target di analisi e i target Trivy.

## 7. Test e qualità del codice

### 7.1 Test (Codeception)

Suite in `tests/`: **Unit** (widget, servizi, input/validazione, SQL dei
reader), **Functional**, **Console**, **Web**. Configurazione in
`codeception.yml`, coverage abilitato via `codeception/c3` (`APP_C3=1`).

```bash
# in container (come in CI)
docker compose run --rm --no-deps app ./vendor/bin/codecept run --skip-group database

# in locale
APP_ENV=test vendor/bin/codecept run Unit
APP_ENV=test vendor/bin/codecept run Functional
```

I test che richiedono il DB sono nel gruppo `database` (la CI li salta perché
esegue le suite senza il servizio MySQL).

### 7.2 Analisi statica e stile

| Strumento | Config | Comando |
|---|---|---|
| Psalm | `psalm.xml` | `make psalm` / `vendor/bin/psalm` |
| Rector | `rector.php` | `make rector` / `vendor/bin/rector` |
| PHP CS Fixer | `.php-cs-fixer.php` | `make cs-fix` |
| Dependency analyser | `composer-dependency-analyser.php` | `make composer-dependency-analyser` |
| Trivy | `trivy.yaml` | `make trivy` (fs+config), `make trivy-image` |

Trivy è in modalità **report-only** (`exit-code 0`) sia in locale sia in CI;
esclude `.git`, `vendor`, `.local`, dump/backup e i file `.env*`.

## 8. DevOps

### 8.1 Pipeline end-to-end

```
push su main
   │
   ▼
CI (.github/workflows/ci.yml)
   ├─ Trivy fs/config/secret scan (report-only)
   ├─ build immagine dev + Trivy image scan
   ├─ composer install / validate / audit
   └─ codecept run --skip-group database
   │  (job "test" verde)
   ▼
publish-image (solo push su main)
   ├─ build docker/Dockerfile --target prod
   └─ push su GHCR: ghcr.io/lucaarcudi/yii3-template:{<sha>, latest}
   │  (workflow CI concluso con successo)
   ▼
CD (.github/workflows/cd.yml — workflow_run su CI / manuale)
   ├─ SSH sul VPS come utente deploy
   ├─ git fetch + merge --ff-only origin/main in /opt/yii3
   ├─ backup DB (mysqldump → /opt/yii3/backups/)
   ├─ docker compose pull && up -d --wait (timeout 120s)
   └─ health check HTTP su 127.0.0.1:8080/login
```

Il CD si attiva **automaticamente** al termine con successo della CI su
`main`, oppure **manualmente** da GitHub → Actions → CD → *Run workflow*.

### 8.2 Immagine Docker (`docker/Dockerfile`, multi-stage)

| Stage | Base | Contenuto |
|---|---|---|
| `base` | `dunglas/frankenphp:1-php8.4-bookworm` | Estensioni PHP (opcache, intl, dom, pdo_mysql, …) |
| `dev` | `base` | + Xdebug, Composer; utente non-root `appuser` con UID/GID dell'host (arg `USER_ID`/`GROUP_ID`), `CAP_NET_BIND_SERVICE` per bind su 80/443 |
| `prod-builder` | `base` | `composer install --no-dev --classmap-authoritative`, poi rimuove `composer.json`/`composer.lock` |
| `prod` | `base` | Copia `/app` dal builder, `APP_ENV=prod`, `SERVER_ROOT=/app/public`, esegue come `www-data` |

FrankenPHP incorpora Caddy: il container serve HTTP direttamente
(`SERVER_NAME=:80`, TLS terminato dal proxy esterno, vedi §8.5).

### 8.3 CI (`.github/workflows/ci.yml`)

Trigger: ogni `push` e `pull_request`. Due job:

1. **test** — Trivy fs/config/secret sul repo → build dell'immagine dev via
   `compose.yml` root → Trivy image scan su `yii3-template-app:latest` →
   `composer install`, `composer validate`, `composer audit` (non bloccante)
   → `codecept run --skip-group database`.
2. **publish-image** — dipende da `test`, gira **solo su push a `main`**.
   Builda `docker/Dockerfile --target prod` e pubblica su GHCR i tag
   `${GITHUB_SHA}` e `latest`, autenticandosi con `GITHUB_TOKEN`
   (permessi `contents: read`, `packages: write`).

Il tag `<sha>` per ogni release è ciò che rende possibile il rollback (§9.3).

### 8.4 CD (`.github/workflows/cd.yml`)

Trigger: `workflow_run` (CI conclusa con successo su `main`, solo per run
innescati da `push`) o `workflow_dispatch`. Un job `deploy` in quattro step,
tutti via SSH. La logica di backup e deploy vive in `scripts/backup-db.sh`
e `scripts/deploy.sh`, versionati nel repo ed eseguiti dal checkout
allineato: il workflow li invoca soltanto. **Mai** logica remota via
heredoc: `docker compose run`/`exec` leggono stdin e divorano il resto
dello script — il deploy risulterebbe verde ma interrotto a metà:

1. **Setup SSH** — chiave privata dal secret `VPS_SSH_KEY`; `known_hosts`
   popolato dal secret `VPS_KNOWN_HOSTS` (fingerprint pinnata: sostituisce
   l'`ssh-keyscan` a ogni deploy, che era trust-on-first-use ripetuto), con
   verifica immediata che il secret contenga una riga per `VPS_HOST`;
2. **Allineamento repo sul VPS** — `git fetch` + `merge --ff-only
   origin/main` in `/opt/yii3`: senza questo passo il deploy aggiornerebbe
   solo l'immagine, lasciando compose/migration/config alla versione vecchia;
3. **Backup DB** — `mysqldump` dentro il container `db` →
   `/opt/yii3/backups/db_<timestamp>.sql`. Le credenziali sono lette da
   `.env.prod` sul VPS (non dall'env del container, che riflette `.env.prod`
   solo al momento della *creazione* del container: dopo una rotazione
   password sarebbe stantio); `--single-transaction` evita lock sull'app
   live e un dump vuoto fa fallire lo step. Retention automatica: i dump
   più vecchi di 14 giorni vengono eliminati (glob stretto sul timestamp:
   i backup rinominati a mano si salvano);
4. **Deploy** — `docker compose pull`, poi le migration del framework con
   l'immagine nuova (`run --rm app ./yii migrate:up -y`, idempotenti: lo
   schema è pronto prima che parta il nuovo codice), quindi
   `up -d --wait --wait-timeout 120` e health check:

   ```bash
   curl -fsS -m 10 --retry 12 --retry-delay 5 --retry-all-errors \
     -H 'X-Forwarded-Proto: https' \
     http://127.0.0.1:8080/login
   ```

   L'header `X-Forwarded-Proto: https` è **necessario**: simula il proxy TLS;
   senza, il cookie di sessione `Secure` fa rispondere 500 (vedi §9.5).

**Secrets richiesti** (repository secrets): `VPS_HOST`, `VPS_USER`,
`VPS_SSH_KEY` (chiave dedicata `yii3_github_actions_cd`; la pubblica sta in
`/home/deploy/.ssh/authorized_keys` sul VPS), `VPS_KNOWN_HOSTS` (righe
complete in formato `known_hosts` per lo stesso host di `VPS_HOST`, generate
con `ssh-keyscan -t ed25519,ecdsa,rsa <VPS_IP>`; **non** la sola
fingerprint `SHA256:...`).

### 8.5 Infrastruttura di produzione

**Server**: VPS Contabo, IP `<VPS_IP>`, host pubblico
`yii3-template.duckdns.org`. Utente operativo `deploy` (gruppo `docker`, sudo
con password); `root` solo per emergenze. Il DB **non è esposto
pubblicamente** (solo loopback + tunnel SSH).

Layout sul server:

| Percorso | Contenuto |
|---|---|
| `/opt/yii3` | Clone del repo (allineato dal CD con merge ff-only) |
| `/opt/yii3/.env.prod` | Segreti reali di produzione — **fuori git** |
| `/opt/yii3/docker/prod/compose.local.yml` | Override locale del VPS — **fuori git** (generato da Ansible, vedi §8.7) |
| `/opt/yii3/backups/` | Dump DB pre-deploy e manuali |
| `/home/deploy/caddy-proxy/` | Compose del reverse proxy Caddy |

Tutti i comandi compose in produzione usano la tripletta:

```bash
cd /opt/yii3
docker compose --env-file .env.prod \
  -f docker/prod/compose.yml \
  -f docker/prod/compose.local.yml \
  <comando>
```

**Stack applicativo** (`docker/prod/compose.yml`):

- `app` — immagine `ghcr.io/lucaarcudi/yii3-template:latest` (override con
  `APP_IMAGE`); porta bindata **solo su loopback**
  (`127.0.0.1:8080:80`, per health check e debug dal VPS); reti
  `app_internal` (verso il DB) e `caddy_public` (verso il proxy); label
  `caddy: ${PROD_HOST}` + `caddy.reverse_proxy: {{upstreams 80}}` per la
  pubblicazione automatica; `restart: unless-stopped`; il compose **rifiuta di
  partire** se `DB_PASSWORD` o `AUTH_COOKIE_SECRET_KEY` mancano (`:?` in yaml).
- `db` — MySQL 8.4 su rete interna, volume `db_data`, migration/seed montati
  in initdb.d (solo primo avvio del volume).

**Override locale** (`compose.local.yml`, esempio versionato in
`compose.local.example.yml`): espone il DB su `127.0.0.1:3307` per il tunnel
SSH, imposta `SESSION_COOKIE_SECURE=false` (vedi §10) e aggiunge la label
HSTS al proxy.

**Reverse proxy** (`docker/proxy/compose.yml`):
`lucaslorentz/caddy-docker-proxy:2.13-alpine` in ascolto su 80/443, legge le
label `caddy.*` dei container sulla rete esterna `caddy_public` e genera la
configurazione con **HTTPS automatico Let's Encrypt**. Gira in
`/home/deploy/caddy-proxy`, con il socket Docker montato in sola lettura.

### 8.6 File di ambiente di produzione

`.env.prod` (da `.env.prod.example`) definisce: `COMPOSE_PROJECT_NAME`,
`APP_IMAGE`, `PROD_HOST`, `SERVER_NAME`, `APP_PORT`,
`AUTH_COOKIE_SECRET_KEY`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`,
`MYSQL_ROOT_PASSWORD`, `DB_FORWARD_HOST/PORT`. Va creato/aggiornato **a mano
sul VPS**; non transita mai da git né dalla CI.

### 8.7 Ansible (`ansible/`)

Automazione idempotente lato server (eseguita dalla postazione di lavoro; non
richiede root: l'utente `deploy` è nel gruppo `docker`):

| Playbook | Scopo |
|---|---|
| `playbooks/server_check.yml` | Diagnosi/assert: Docker presente, `/opt/yii3`, `.env.prod`, compose, backups, servizi up, health 200 |
| `playbooks/proxy.yml` | Crea la rete `caddy_public`, installa e avvia il proxy Caddy in `/home/deploy/caddy-proxy` |
| `playbooks/app.yml` | Imposta `PROD_HOST` in `.env.prod`, scrive `compose.local.yml`, ricrea il container app, verifica health su loopback e via HTTPS pubblico |

```bash
cd ansible
ansible-playbook playbooks/server_check.yml
```

Inventory: `inventory.ini` (host `yii3-vps`, utente `deploy`, chiave
`~/.ssh/yii3_github_actions_cd`). Provisioning completo del server (install
Docker, utente deploy, firewall, hardening) è previsto ma non ancora
automatizzato (vedi §10).

### 8.8 Scansioni di sicurezza

- **Trivy** in CI a ogni push (fs + config + secret sul repo, image scan
  sull'immagine buildata) e in locale via `make trivy` / `make trivy-image`
  (usa l'immagine `aquasec/trivy:0.71.2`, nessuna installazione richiesta).
  Attualmente report-only; per renderlo bloccante alzare `exit-code` in
  `trivy.yaml`/workflow.
- **`composer audit`** in CI, **bloccante**: una advisory nuova ferma il
  run. **Psalm** è anch'esso uno step obbligatorio, con baseline committata
  (`psalm-baseline.xml`) per il debito storico.
- Audit manuale completo: vedi
  [analisi-sicurezza-e-migliorie-2026-07-02.md](analisi-sicurezza-e-migliorie-2026-07-02.md).

### 8.9 Monitoring (Prometheus + Grafana)

Stack separato in `docker/monitoring/compose.yml`, con ciclo di vita
indipendente dai deploy (il CD ricrea l'app, non il monitoring):
Prometheus (retention 15 giorni), Grafana, node-exporter (metriche host),
cAdvisor (metriche container), mysqld-exporter (utente MySQL dedicato
`exporter` con soli grant di monitoraggio). Config locale in
`docker/monitoring/.env`, fuori git (modello in `.env.example`).

- Esposto **solo Grafana**: `https://<GRAFANA_HOST>` via caddy-docker-proxy
  con TLS automatico (con DuckDNS i sottodomini wildcard del proprio nome
  risolvono già). Signup disabilitato, admin con password generata.
- Prometheus e gli exporter restano su rete interna; la UI di Prometheus
  ascolta solo sul loopback del VPS (`127.0.0.1:9090`), raggiungibile con
  `ssh -L 9090:127.0.0.1:9090 deploy@<VPS_IP>` e poi
  `http://localhost:9090` in locale.
- Avvio/aggiornamento sul VPS, da `/opt/yii3`:
  `docker compose -f docker/monitoring/compose.yml up -d --wait`.
- Dashboard consigliate (import per ID da grafana.com): **1860** (Node
  Exporter Full), **14282** (cAdvisor), **14057** (MySQL).
- Il job `caddy` scrappa le metriche HTTP del reverse proxy
  (`caddy-proxy:9180`, abilitate da `docker/proxy/Caddyfile.base`; porta
  mai pubblicata sull'host): latenze e status code del traffico pubblico.
- Alert in `prometheus/rules/alerts.yml` (CPU, memoria, disco, target
  down, MySQL down, upstream del proxy non sano), validati in CI con
  `promtool check config`; visibili in Prometheus `/alerts` e in Grafana
  (metrica `ALERTS`). Le notifiche push si aggiungono collegando un
  contact point Grafana o un Alertmanager.
- Limite noto: con Docker che usa lo snapshotter containerd (storage
  driver `overlayfs`, com'è sul VPS attuale) cAdvisor esporta solo il
  cgroup root e niente serie per-container (anche in v0.52): la dashboard
  14282 resta vuota e la liveness dell'app si misura dagli upstream di
  Caddy. Su host con overlay2 classico cAdvisor funziona per intero.
- Estensione futura: endpoint `/metrics` applicativo (richiede la scelta
  delle metriche di business e uno storage per i contatori).

## 9. Runbook operativi

### 9.1 Stato e log in produzione

```bash
ssh deploy@<VPS_IP>
cd /opt/yii3

# alias della tripletta compose usata in tutti i comandi seguenti
DC='docker compose --env-file .env.prod -f docker/prod/compose.yml -f docker/prod/compose.local.yml'

$DC ps                      # stato container
$DC logs app --tail=100     # log applicazione
$DC logs db --tail=100      # log MySQL
```

### 9.2 Deploy manuale dal VPS

```bash
cd /opt/yii3
$DC pull
$DC up -d
curl -fsS -H 'X-Forwarded-Proto: https' http://127.0.0.1:8080/login >/dev/null && echo OK
```

(In alternativa: GitHub → Actions → CD → *Run workflow*.)

### 9.3 Rollback

Il deploy usa `latest`, ma ogni build è taggata anche con lo SHA del commit:

```bash
cd /opt/yii3
APP_IMAGE=ghcr.io/lucaarcudi/yii3-template:<sha-precedente> $DC pull app
APP_IMAGE=ghcr.io/lucaarcudi/yii3-template:<sha-precedente> $DC up -d app
```

Per renderlo persistente, fissare `APP_IMAGE` in `.env.prod`. Se la release
conteneva una migration, valutare il restore del backup pre-deploy (§9.4).

### 9.4 Backup, restore e patch DB

Backup automatico ad ogni deploy (step del CD). Backup manuale:

```bash
cd /opt/yii3 && mkdir -p backups
$DC exec -T db sh -lc 'mysqldump --no-tablespaces -u"$MYSQL_USER" -p"$MYSQL_PASSWORD" "$MYSQL_DATABASE"' \
  > backups/db_$(date +%F_%H-%M-%S).sql

# retention consigliata (7 giorni)
find /opt/yii3/backups -type f -name "db_*.sql" -mtime +7 -delete
```

Applicare una migration su DB esistente (initdb.d gira solo al primo avvio):

```bash
$DC exec -T db sh -lc 'mysql -u"$MYSQL_USER" -p"$MYSQL_PASSWORD" "$MYSQL_DATABASE"' \
  < database/migrations/release_X_Y_Z.sql
```

Restore da backup: stesso comando `mysql` con in input il file di dump.

### 9.5 Diagnosi di un 500 dopo il deploy

1. `$DC logs app --tail=200` — l'error handler logga su stdout/`runtime/logs`;
2. ricordare che il curl di health **deve** includere
   `-H 'X-Forwarded-Proto: https'`: senza header la richiesta appare HTTP e
   il cookie di sessione `Secure` genera un 500 fuorviante;
3. verificare che `.env.prod` contenga `AUTH_COOKIE_SECRET_KEY` non di
   default e `DB_PASSWORD` corretti (il compose fallisce fast se mancano);
4. `ansible-playbook playbooks/server_check.yml` per un check completo.

### 9.6 Accesso al DB dal PC locale (tunnel SSH)

```bash
ssh -N -L 3307:127.0.0.1:3307 deploy@<VPS_IP>
```

Poi collegarsi con il client SQL a `127.0.0.1:3307` usando le credenziali di
`.env.prod`. Il DB non è mai raggiungibile direttamente da internet.

### 9.7 Aggiungere un nuovo dominio CRUD (checklist)

1. Migration SQL idempotente in `database/migrations/` (+ eventuale seed dei
   permessi `<DOMINIO>_VIEW_ALL/VIEW_OWN/CREATE/UPDATE/DELETE`);
2. classi in `src/<Modulo>/<Dominio>/` sul modello di
   `src/Mes/Task/` (Entity, Input, Repository, Reader, Filter, Policy,
   Presenter, Scope);
3. action in `src/<Modulo>/<Dominio>/Actions/` (Index/View/Create/
   Update/Delete, con `withViewPath('@src/<Modulo>/<Dominio>/views')` nel
   costruttore) e view in `src/<Modulo>/<Dominio>/views/`;
4. rotte in `src/<Modulo>/routes.php` e DI in `src/<Modulo>/di.php`,
   raccolti automaticamente dalla config (per un modulo nuovo basta creare
   i due file);
5. voce di menu in `src/Shared/Navigation/NavigationProvider.php` con la
   `policyClass` del dominio;
6. montare la nuova migration nei compose (`compose.yml` root e
   `docker/prod/compose.yml`) e applicarla a mano sul DB di produzione;
7. test unit per Input/Reader e aggiornamento del CHANGELOG.

## 10. Limiti noti e lavori futuri

Dall'audit del 2 luglio 2026 e dallo stato attuale dell'infrastruttura:

- **Cookie di sessione senza flag `Secure` dietro il proxy**: FrankenPHP non
  traduce `X-Forwarded-Proto` per PHP, quindi sul VPS
  `SESSION_COOKIE_SECURE=false` (override in `compose.local.yml`).
  Mitigazione attiva: HSTS iniettato dal proxy. Fix definitivo previsto:
  `yiisoft/proxy-middleware` per risolvere gli header forwarded dal proxy
  fidato.
- **Rate limiter dietro proxy**: usa `REMOTE_ADDR`, che dietro Caddy è l'IP
  del proxy → bucket unico condiviso. Stessa soluzione del punto precedente.
- **Trivy non bloccante** in CI (report-only, scelta esplicita in questa
  fase); `composer audit` è invece bloccante e senza advisory aperte.
- **Provisioning server non automatizzato**: Ansible copre proxy, app config
  e check; install Docker/utenti/firewall/hardening sono ancora manuali.
- **Deploy su `latest`**: il rollback è manuale via tag SHA; un possibile
  passo successivo è deployare direttamente il tag SHA dal CD.
- **Target Makefile ereditati dal template upstream** parzialmente non
  funzionanti (vedi §6.4).
