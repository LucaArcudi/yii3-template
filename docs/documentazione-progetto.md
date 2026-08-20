# Yii3 Template — Documentazione di progetto

> Ultimo aggiornamento: 20 agosto 2026.
>
> Documenti correlati: [README.md](../README.md) (quick start e release),
> [README_DEPLOY.md](../README_DEPLOY.md) (runbook deploy passo-passo),
> [flusso e confini DevOps](DEVOPS_WORKFLOW.md),
> [CHANGELOG.md](../CHANGELOG.md).

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
| HTTP runtime | FrankenPHP 1.12.7 (Caddy embedded), immagine fissata a digest |
| Database | MySQL 8.4 (`yiisoft/db` + `yiisoft/db-mysql`, query builder senza ORM) |
| Frontend | Tema ArchitectUI (Bootstrap 5), asset precompilati in `src/Shared/resources/architectui/`, gestiti da `yiisoft/assets` |
| Test | Codeception 5 (suite Unit, Functional, Console, Web) + PHPUnit 11 |
| Analisi statica | Psalm 6, Rector 2, PHP CS Fixer 3, composer-dependency-analyser |
| Sicurezza supply chain | Trivy 0.74 (fs, config, secret, image scan) |
| CI/CD | GitHub Actions → GHCR → deploy SSH su VPS |
| Infrastruttura | Docker Compose, Caddy (caddy-docker-proxy) |

## 3. Struttura del repository

```
.
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
│   ├── prod/compose.yml   # Compose di produzione (VPS)
│   ├── proxy/             # Reverse proxy Caddy per il VPS (+ Caddyfile.base metriche)
│   └── monitoring/        # Stack Prometheus/Grafana/exporter (vedi §8.9)
├── docs/                  # Documentazione attiva, roadmap e runbook
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
| `SESSION_COOKIE_SECURE` | `true` in prod | Flag Secure del cookie di sessione (dietro il proxy funziona grazie a `TrustedProxyMiddleware`, vedi §4.4) |
| `TRUSTED_PROXY_IPS` | `private,localhost` | Proxy fidati per gli header `X-Forwarded-*`: IP, range CIDR o alias di `IpRanges` |
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
2. `TrustedProxyMiddleware` — se la connessione arriva da un proxy fidato
   (`TRUSTED_PROXY_IPS`, default reti private + loopback) risolve
   `X-Forwarded-Proto` nello scheme dell'URI e l'IP reale del client da
   `X-Forwarded-For` (dal fondo della catena, saltando i proxy fidati)
   nell'attributo `clientIp`; da connessioni non fidate gli header sono
   ignorati;
3. `SecurityHeadersMiddleware` — `X-Content-Type-Options: nosniff`,
   `X-Frame-Options: SAMEORIGIN`, `Referrer-Policy`, `Permissions-Policy`;
   HSTS (`max-age=31536000; includeSubDomains`) solo su richieste HTTPS;
4. `LocaleMiddleware` — risolve la lingua (it/en, vedi `AppLocales`);
5. `SessionMiddleware`, `CookieMiddleware`, `CookieLoginMiddleware` —
   sessione, cookie firmati/cifrati, auto-login *remember me*;
6. `PasswordExpiredMiddleware` — forza il cambio password scaduta;
7. `StatusPageMiddleware` — pagine di stato (access denied, too many
   requests, invalid request);
8. `SameOriginRequestMiddleware` + `CsrfTokenMiddleware` — difesa CSRF a due
   livelli;
9. `FormatDataResponse`, `RequestCatcherMiddleware`, `Router` — formattazione
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
`make psalm`, `make rector`, `make cs-fix`, `make validate-ops`,
`make trivy|trivy-fs|trivy-config|trivy-image|trivy-gate`, `make help`.

Tutti i target applicativi usano il `compose.yml` root (§6.2). `make test`
crea uno stack isolato (`yii3-template-test`), usa una porta MySQL distinta
(`33060`, sovrascrivibile con `TEST_DB_PORT`), applica le migration su un
volume nuovo ed elimina stack e volume al termine. I vecchi Compose dev/test
e i target Docker Swarm ereditati da `yiisoft/app` sono stati rimossi: build,
pubblicazione e deploy di produzione passano esclusivamente da CI/CD e dagli
script versionati descritti in §8.

## 7. Test e qualità del codice

### 7.1 Test (Codeception)

Suite in `tests/`: **Unit** (widget, servizi, input/validazione, SQL dei
reader), **Functional**, **Console**, **Web**. Configurazione in
`codeception.yml`, coverage abilitato via `codeception/c3` (`APP_C3=1`).

```bash
# flusso locale completo: DB isolato, migration, tutte le suite, cleanup
make test

# in container (come in CI)
docker compose run --rm --no-deps app ./vendor/bin/codecept run --skip-group database

# gruppo database (serve MySQL attivo e migrato, come in CI)
docker compose run --rm app ./vendor/bin/codecept run -g database

# in locale
APP_ENV=test vendor/bin/codecept run Unit
APP_ENV=test vendor/bin/codecept run Functional
```

I test che richiedono il DB sono nel gruppo `database`: il run principale
della CI li salta (gira senza MySQL), poi un secondo step li esegue contro
il DB della CI ricostruito dalla catena di migration, con una guardia che
fallisce se il gruppo non esegue alcun test.

### 7.2 Analisi statica e stile

| Strumento | Config | Comando |
|---|---|---|
| Psalm | `psalm.xml` | `make psalm` / `vendor/bin/psalm` |
| Rector | `rector.php` | `make rector` / `vendor/bin/rector` |
| PHP CS Fixer | `.php-cs-fixer.php` | `make cs-fix` |
| Dependency analyser | `composer-dependency-analyser.php` | `make composer-dependency-analyser` |
| Trivy | `trivy.yaml` | `make trivy` (fs+config), `make trivy-image`, `make trivy-gate` |

Gli scan Trivy informativi sono **report-only** (`exit-code 0`) ed escludono
`.git`, `vendor`, `.local`, dump/backup e i file `.env*`. In CI due **gate
bloccanti** separati (`make trivy-gate` in locale) falliscono il job sulle
vulnerabilità HIGH/CRITICAL **con fix disponibile** (`--ignore-unfixed`),
sul filesystem e sull'immagine prod; eccezioni solo in `.trivyignore`,
ognuna con motivazione e scadenza `exp:YYYY-MM-DD` (alla scadenza la voce
si riattiva nel gate). Il lockfile documentale degli asset frontend
(`docs/assets/architectui-4.5.0-package-lock-cleanup.json`) è incluso nello
scan come manifest npm via `file-patterns`: le dipendenze dev sono escluse
dal default di Trivy, restano sorvegliate le dipendenze runtime del bundle.

## 8. DevOps

### 8.1 Pipeline end-to-end

```
push su main
   │
   ▼
CI (.github/workflows/ci.yml)
   │  job "test"
   ├─ actionlint, ShellCheck e render delle configurazioni Compose
   ├─ regressione degli script di deploy in ambienti temporanei
   ├─ Trivy fs/config/secret scan (report-only) + gate fs bloccante
   ├─ build immagine dev (--pull) + Trivy image scan
   ├─ composer validate / audit / dependency analysis
   ├─ PHP CS Fixer (dry-run) + Psalm
   ├─ codecept run --skip-group database
   ├─ validazione migration + codecept run -g database (su DB migrato)
   └─ drill dump/drop/restore su uno stack MySQL isolato
   │  (job "test" verde)
   ▼
image (ogni push e pull request)
   ├─ unica build docker/Dockerfile --target prod
   ├─ verifica contenuto artefatto + gate Trivy bloccante
   └─ solo su main: push della stessa immagine su GHCR con {<sha>, latest}
   │  (workflow CI concluso con successo)
   ▼
CD (.github/workflows/cd.yml — workflow_run su CI / manuale)
   ├─ risoluzione SHA + preflight manifest GHCR (deploy serializzati)
   ├─ SSH sul VPS come utente deploy
   ├─ checkout detached verificato dello stesso SHA nella directory configurata
   ├─ backup DB con permessi restrittivi
   ├─ docker compose pull && up -d --wait (timeout 120s)
   └─ health check HTTP su 127.0.0.1:8080/login
```

Il CD si attiva **automaticamente** al termine con successo della CI su
`main`, oppure **manualmente** da GitHub → Actions → CD → *Run workflow*.

### 8.2 Immagine Docker (`docker/Dockerfile`, multi-stage)

| Stage | Base | Contenuto |
|---|---|---|
| `base` | `dunglas/frankenphp:1.12.7-php8.4-bookworm` + digest | `apt upgrade` dei pacchetti di sistema + estensioni PHP (opcache, intl, dom, pdo_mysql, …) |
| `dev` | `base` | + Xdebug, Composer; utente non-root `appuser` con UID/GID dell'host (arg `USER_ID`/`GROUP_ID`), `CAP_NET_BIND_SERVICE` per bind su 80/443 |
| `prod-builder` | `base` | `composer install --no-dev --classmap-authoritative`, poi rimuove `composer.json`/`composer.lock` |
| `prod` | `base` | Copia `/app` dal builder, `APP_ENV=prod`, `SERVER_ROOT=/app/public`, esegue come `www-data` |

FrankenPHP incorpora Caddy: il container serve HTTP direttamente
(`SERVER_NAME=:80`, TLS terminato dal proxy esterno, vedi §8.5).

### 8.3 CI (`.github/workflows/ci.yml`)

Trigger: ogni `push`, `pull_request` e schedule settimanale. Due job:

1. **test** — validazione di workflow, shell e configurazioni Compose;
   regressione dell'helper di checkout su repository Git
   temporanei (SHA vecchio/errato/estraneo, worktree sporco e file locali
   preservati) → Trivy fs/config/secret sul repo (report-only) + gate fs
   bloccante sulle HIGH/CRITICAL con fix disponibile → build dell'immagine
   dev via `compose.yml` root (`--pull` dei digest dichiarati) → Trivy image scan
   su `yii3-template-app:latest` → `composer install`,
   `composer validate`, `composer audit` (bloccante) →
   PHP CS Fixer in dry-run → dependency analysis → Psalm →
   `codecept run --skip-group database` → validazione migration
   (idempotenza + bootstrap da zero) → `codecept run -g database` sul DB
   migrato, con guardia sul numero di test eseguiti → prova completa di backup
   e restore su uno stack MySQL isolato. Eccezioni ai gate solo via
   `.trivyignore` (motivazione + scadenza `exp:`). Lo schedule mantiene attivo
   il drill di recovery anche in assenza di push.
2. **image** — dipende da `test` e gira su ogni push e pull request. Esegue
   l'unica build `docker/Dockerfile --target prod` del workflow, verifica
   nell'immagine i file richiesti dal deploy e applica il gate Trivy
   bloccante sulle HIGH/CRITICAL con fix disponibile. Solo sui push a `main`
   autentica la CI con `GITHUB_TOKEN` e pubblica su GHCR la stessa immagine
   locale con i tag `${GITHUB_SHA}` e `latest` (permessi `contents: read`,
   `packages: write`); il registry ne calcola il digest content-addressed.

Il tag `<sha>` per ogni release è ciò che rende possibile il rollback
([runbooks/rollback.md](runbooks/rollback.md)).

### 8.4 CD (`.github/workflows/cd.yml`)

Trigger: `workflow_run` (CI conclusa con successo su `main`, solo per run
innescati da `push`) o `workflow_dispatch`. Il job `deploy` usa il concurrency
group `production-deploy`: un solo deploy alla volta e al massimo uno pendente;
un run pendente più recente può sostituire il precedente, mentre quello attivo
non viene cancellato. La logica remota vive negli helper versionati
`scripts/checkout-deploy-commit.sh`, `scripts/backup-db.sh` e
`scripts/deploy.sh`; il workflow li invoca soltanto. **Mai** logica remota via
heredoc: `docker compose run`/`exec` leggono stdin e divorano il resto dello
script — il deploy risulterebbe verde ma interrotto a metà.

1. **Target e preflight** — `DEPLOY_SHA` è l'unica fonte di verità. Nei run
   automatici vale `workflow_run.head_sha`; nei run manuali l'input
   obbligatorio `image_tag`, che accetta solo uno SHA completo di 40
   caratteri. Lo stesso valore forma `APP_IMAGE`. Prima dell'SSH il CD accede
   a GHCR in sola lettura e verifica che il manifest del tag esista: `latest`,
   SHA abbreviati/malformati e immagini assenti falliscono senza modificare il
   VPS. Prima dell'SSH vengono inoltre verificati tutti i Secrets e validate le
   Variables opzionali `DEPLOY_DIR`, `DEPLOY_REMOTE`, `DEPLOY_BRANCH`,
   `VPS_SSH_PORT` e `HEALTH_URL`;
2. **Setup SSH** — chiave privata dal secret `VPS_SSH_KEY`; `known_hosts`
   popolato dal secret `VPS_KNOWN_HOSTS` (fingerprint pinnata: sostituisce
   l'`ssh-keyscan` a ogni deploy, che era trust-on-first-use ripetuto), con
   verifica immediata che il secret contenga una riga per `VPS_HOST` (nel
   formato `[host]:porta` quando la porta non è `22`);
3. **Allineamento repo sul VPS** — l'helper di checkout rifiuta modifiche a
   file tracciati, aggiorna remote/branch configurati, verifica che
   `DEPLOY_SHA` esista e
   appartenga alla sua storia, poi esegue un checkout detached senza
   `--force` e controlla `HEAD == DEPLOY_SHA`. I file locali ignorati
   (`.env.prod`, override compose e backup) non vengono toccati. La stessa
   invariante viene ricontrollata immediatamente prima di backup e deploy;
4. **Backup DB** — `mysqldump` dentro il container `db` nella directory
   `backups/` del deploy. Le credenziali vengono interpretate da Docker Compose
   a partire da `.env.prod`, evitando il parsing fragile del dotenv e valori
   host stantii; `umask 077`, directory `0700` e dump `0600` limitano la
   lettura. `--single-transaction` evita lock sull'app live e un dump vuoto fa
   fallire lo step. Retention automatica: i dump
   più vecchi di 14 giorni vengono eliminati (glob stretto sul timestamp:
   i backup rinominati a mano si salvano);
5. **Deploy** — il CD passa a `deploy.sh` l'immagine sullo stesso
   **tag SHA del checkout** via `APP_IMAGE`: non si deploya `latest`, ogni
   run è riproducibile. Lo script registra l'immagine in esecuzione (digest,
   per l'eventuale rollback), poi `docker compose
   pull`, le migration del framework con l'immagine nuova (`run --rm app
   ./yii migrate:up -y`, idempotenti: lo schema è pronto prima che parta
   il nuovo codice), quindi `up -d --wait --wait-timeout 120` con
   ricreazione esplicita dell'app, invariante immagine e health check:

   ```bash
   curl -fsS -m 10 --retry 12 --retry-delay 5 --retry-all-errors \
     -H 'X-Forwarded-Proto: https' \
     http://127.0.0.1:8080/login
   ```

   L'header `X-Forwarded-Proto: https` è **necessario**: simula il proxy TLS;
   senza, il cookie di sessione `Secure` fa rispondere 500 (vedi
   [runbooks/diagnosi-500.md](runbooks/diagnosi-500.md)).
   Se avvio, invariante o health check falliscono, lo script **ripristina
   automaticamente l'immagine precedente** (con health check di conferma;
   le migration non vengono annullate, vedi
   [runbooks/backup-restore.md](runbooks/backup-restore.md)) e il run
   fallisce comunque, perché il deploy non è avvenuto.

Il `HEALTHCHECK` dell'immagine rende lo stato disponibile a Docker e a
`compose up --wait`. `restart: unless-stopped` non riavvia da solo un processo
ancora vivo ma `unhealthy`: fuori dal deploy intervengono alert e runbook.

**Secrets richiesti** (repository secrets): `VPS_HOST`, `VPS_USER`,
`VPS_SSH_KEY` (chiave dedicata alla CD; la pubblica sta
nell'`authorized_keys` dell'utente sul VPS), `VPS_KNOWN_HOSTS` (righe complete
in formato `known_hosts` per lo stesso host/porta di `VPS_HOST`, con fingerprint
verificata tramite un canale fidato; **non** la sola stringa `SHA256:...`).

**Variables opzionali**: `DEPLOY_DIR` (`/opt/yii3`), `DEPLOY_REMOTE`
(`origin`), `DEPLOY_BRANCH` (`main`), `VPS_SSH_PORT` (`22`) e `HEALTH_URL`
(`http://127.0.0.1:8080/login`). L'installazione usa Repository Secrets e
Repository Variables; un Environment GitHub `production` è una possibilità
facoltativa che non è stata adottata e non è richiesta dal workflow.

### 8.5 Infrastruttura di produzione

**Server**: VPS Linux già predisposto, con utente operativo dedicato autorizzato
a usare Docker; `root` resta riservato alle attività di sistema. Il DB **non è
esposto pubblicamente** (solo loopback + tunnel SSH).

Layout sul server:

| Percorso | Contenuto |
|---|---|
| `/opt/yii3` | Clone del repo in detached HEAD sullo SHA dell'immagine deployata |
| `/opt/yii3/.env.prod` | Segreti reali di produzione — **fuori git** |
| `/opt/yii3/docker/prod/compose.local.yml` | Override locale del VPS — **fuori git** (creato dall'esempio versionato) |
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
  pubblicazione automatica; `HEALTHCHECK` ereditato dall'immagine prod e
  `restart: unless-stopped`; il compose **rifiuta di partire** se `DB_PASSWORD`
  o `AUTH_COOKIE_SECRET_KEY` mancano (`:?` in yaml).
- `db` — MySQL 8.4 su rete interna, volume `db_data`, migration/seed montati
  in initdb.d (solo primo avvio del volume).

**Override locale** (`compose.local.yml`, creato manualmente dall'esempio
versionato `compose.local.example.yml`): espone il DB su
`127.0.0.1:3307` per il tunnel SSH. Nessun override per l'app: cookie
Secure e HSTS sono emessi dall'app grazie a `TrustedProxyMiddleware`
(niente label HSTS sul proxy: per RFC 6797 conta solo il primo header
STS e la label oscurava quello completo dell'app).

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

### 8.7 Bootstrap manuale del VPS e del proxy

Il repository non automatizza il provisioning del sistema operativo. Docker,
utente `deploy`, SSH, firewall, DNS, directory e file locali vengono preparati
manualmente prima del primo deploy.

La configurazione del proxy resta versionata in `docker/proxy/`. Sul VPS i
file `compose.yml` e `Caddyfile.base` vengono installati insieme in
`/home/deploy/caddy-proxy/`, dopo avere creato la rete esterna
`caddy_public`. Comandi, prerequisiti e verifiche sono definiti in
[`README_DEPLOY.md`](../README_DEPLOY.md#proxy-caddy-esterno).

La CD distribuisce soltanto le release applicative: non installa Docker, non
configura il server e non riavvia il proxy.

### 8.8 Scansioni di sicurezza

- **Trivy** in CI a ogni push, pull request e schedule (fs + config + secret
  sul repo, image scan sull'immagine buildata) e in locale via `make trivy` /
  `make trivy-image`
  (usa l'immagine `aquasec/trivy:0.74.0` fissata a digest, nessuna installazione
  richiesta). I report completi sono informativi; `make trivy-gate` e i due
  gate CI bloccano le HIGH/CRITICAL con fix disponibile sul filesystem e
  sull'immagine prod. Le sole eccezioni ammesse sono motivate e datate in
  `.trivyignore`.
- **`composer audit`** in CI, **bloccante**: una advisory nuova ferma il
  run. **Psalm** è anch'esso uno step obbligatorio, con baseline committata
  (`psalm-baseline.xml`) per il debito storico.
- GitHub Actions e immagini operative sono fissate a commit SHA o digest;
  Dependabot propone gli aggiornamenti delle fonti Docker censite.

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
  `docker compose --env-file docker/monitoring/.env -f
  docker/monitoring/compose.yml up -d --wait`.
- Dashboard consigliate (import per ID da grafana.com): **1860** (Node
  Exporter Full), **14282** (cAdvisor), **14057** (MySQL).
- Il job `caddy` scrappa le metriche HTTP del reverse proxy
  (`caddy-proxy:9180`, abilitate da `docker/proxy/Caddyfile.base`; porta
  mai pubblicata sull'host): latenze e status code del traffico pubblico.
- Alert in `prometheus/rules/alerts.yml` (CPU, memoria, disco, target
  down, MySQL down, upstream del proxy non sano), validati in CI con
  `promtool check config`; visibili in Prometheus `/alerts` e in Grafana
  (metrica `ALERTS`).
- **Notifiche degli alert su Telegram**, provisionate in
  `grafana/provisioning/alerting/`: una regola ponte in Grafana rilancia
  gli alert `firing` di Prometheus (una notifica per coppia
  alertname/severity, rinotifica ogni 4 ore finché attivo) verso il
  contact point Telegram. Bot token e chat ID vanno in
  `docker/monitoring/.env` (obbligatori, vedi `.env.example`); le soglie
  restano solo in `prometheus/rules/alerts.yml`, qui non si duplicano.
- **Log centralizzati (Loki + Alloy)**: Alloy raccoglie stdout/stderr di
  tutti i container via Docker service discovery (etichette `container`,
  `compose_service`, `compose_project`) più il log applicativo
  `runtime/logs/app.log` (volume dell'app montato read-only) e li spedisce
  a Loki (storage filesystem nel volume `loki_data`, retention 14 giorni
  come i backup). Solo rete interna. Datasource Loki provisionato in
  Grafana: query LogQL da *Explore*, un'unica UI per metriche e log; i log
  dei container sopravvivono ai ricreate dei deploy. Config validate in CI
  (`loki -verify-config`, `alloy fmt`).
- Limite noto: con Docker che usa lo snapshotter containerd (storage
  driver `overlayfs`, com'è sul VPS attuale) cAdvisor esporta solo il
  cgroup root e niente serie per-container (anche in v0.52): la dashboard
  14282 resta vuota e la liveness dell'app si misura dagli upstream di
  Caddy. Su host con overlay2 classico cAdvisor funziona per intero.
- Estensione futura: endpoint `/metrics` applicativo (richiede la scelta
  delle metriche di business e uno storage per i contatori).

Il 21 agosto 2026 lo stack è stato aggiornato e verificato sul VPS: Grafana
13.1.3 con database `ok`, Prometheus e tutti i target `UP`, `mysql_up == 1`,
Loki `ready`, log Docker presenti tramite Alloy e notifica Telegram di prova
ricevuta. Il dettaglio operativo è nel
[runbook monitoring](runbooks/monitoring.md).

## 9. Runbook operativi

I runbook vivono in file singoli sotto [`docs/runbooks/`](runbooks/) — uno
per scenario, citabile dalla conversazione o da una issue. La base comune è
[stato-e-log.md](runbooks/stato-e-log.md): definisce accesso SSH e alias
`$DC` usati da tutti gli altri.

| Scenario | Runbook |
|---|---|
| Stato, log e health in produzione | [stato-e-log.md](runbooks/stato-e-log.md) |
| Deploy manuale dal VPS | [deploy-manuale.md](runbooks/deploy-manuale.md) |
| Deploy fallito (run CD rosso) | [deploy-failed.md](runbooks/deploy-failed.md) |
| Rollback di una release | [rollback.md](runbooks/rollback.md) |
| Backup, restore e patch del DB | [backup-restore.md](runbooks/backup-restore.md) |
| 500 dopo un deploy | [diagnosi-500.md](runbooks/diagnosi-500.md) |
| App giù (`UpstreamProxyDown`/`TargetDown`) | [app-down.md](runbooks/app-down.md) |
| MySQL giù (`MysqlDown`) | [db-down.md](runbooks/db-down.md) |
| Disco quasi pieno (`DiskAlmostFull`) | [disk-full.md](runbooks/disk-full.md) |
| Accesso al DB dal PC locale (tunnel SSH) | [accesso-db-tunnel.md](runbooks/accesso-db-tunnel.md) |
| Monitoring e notifiche Telegram | [monitoring.md](runbooks/monitoring.md) |
| Nuovo dominio CRUD (checklist) | [nuovo-dominio-crud.md](runbooks/nuovo-dominio-crud.md) |

Ogni runbook di incident segue lo stesso schema: sintomi (con l'alert
Prometheus corrispondente), verifiche immediate, azioni sicure, cosa NON
fare, istruzioni per l'AI.

## 10. Limiti noti e lavori futuri

Il percorso Docker, CI/CD e monitoring è concluso. Lo stato residuo è
deliberatamente separato tra manutenzione con scadenza, estensioni facoltative
e backlog applicativo:

- **Trivy**: gate bloccante in CI sulle sole HIGH/CRITICAL **con fix
  disponibile** (eccezioni in `.trivyignore`, con scadenza); il resto dello
  scan resta report-only. `composer audit` è bloccante e senza advisory
  aperte.
- **Provisioning server non automatizzato**: installazione di Docker,
  utenti, SSH, firewall, hardening e bootstrap iniziale restano manuali e
  sono separati dalla CD applicativa.
- **GitHub Settings**: Repository Secrets/Variables e ruleset sono configurati;
  l'Environment `production` non è stato adottato ed è facoltativo.
- **Accesso all'host**: proxy e Alloy leggono il socket Docker; cAdvisor usa
  mount e privilegi estesi. Socket proxy/rootless e riduzione dei privilegi
  restano hardening facoltativo.
- **Proxy esterno**: può essere eliminato in futuro, ma oggi gestisce TLS,
  routing di app/Grafana e metriche. La semplificazione richiede un disegno
  sostitutivo esplicito e non blocca la chiusura del progetto DevOps.

Restore di un dump reale, dashboard community e metriche di business sono
altre estensioni non bloccanti. P2-P5 restano manutenzione del template e della
supply chain; P6 e P7 costituiscono il
[backlog applicativo Yii3](roadmap-sviluppo.md), separato dal progetto DevOps.
La classificazione normativa completa è nel
[piano di miglioramento](../PIANO_MIGLIORAMENTO_TEMPLATE.md) §5.
