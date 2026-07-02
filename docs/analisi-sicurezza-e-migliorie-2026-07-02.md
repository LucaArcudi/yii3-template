# Analisi di sicurezza e migliorie — 2 luglio 2026

Audit del progetto (sicurezza, qualità del codice, scaffolding) e interventi applicati
nel commit `33185ff` ("Security hardening, code quality pass and visual refresh").

## 1. Falle di sicurezza individuate

### Critiche (risolte)

| # | Problema | Dettaglio | Fix applicato |
|---|----------|-----------|---------------|
| 1 | Debug forzato in produzione | `public/index.php` eseguiva `putenv('APP_ENV=dev'); putenv('APP_DEBUG=1')` incondizionatamente: `Environment::getRawValue()` legge i valori di `putenv`, quindi sovrascriveva `APP_ENV=prod` / `APP_DEBUG=false` del container. L'error handler in prod avrebbe esposto stack trace, variabili e percorsi. Presente dal primo commit. | Il default a `dev` viene applicato **solo** quando `APP_ENV` non è definito (uso locale con `php yii serve`). |
| 2 | Session fixation | Nessuna rigenerazione dell'ID di sessione dopo il login (`yiisoft/user` non lo fa da solo). Un session ID fissato prima del login restava valido da autenticato. | `LoginAction` chiama `Session::regenerateId()` subito dopo il login riuscito. |
| 3 | Chiave segreta cookie di default | Se `AUTH_COOKIE_SECRET_KEY` mancava, l'app partiva con la chiave d'esempio hardcoded nel repo (usata per cifrare il cookie `autoLogin`), anche in prod. | In prod l'app **rifiuta di partire** se la chiave è quella di default; il compose prod richiede la variabile (`:?`) e `.env.prod.example` documenta come generarla (`openssl rand -hex 32`). |

### Medie

| # | Problema | Stato |
|---|----------|-------|
| 4 | App prod esposta anche su `0.0.0.0:8080`, bypassando Caddy/TLS | **Risolto**: porta bindata su `127.0.0.1` (resta per healthcheck e debug dal VPS). |
| 5 | Nessun security header (nosniff, X-Frame-Options, Referrer-Policy, HSTS…) | **Risolto**: nuovo `SecurityHeadersMiddleware` in cima allo stack. HSTS solo su https. |
| 6 | Enumerazione utenti via timing sul login: se l'email non esisteva, `password_verify` (Argon2id, decine di ms) non veniva chiamato → il tempo di risposta rivelava le email registrate | **Risolto**: verifica sempre eseguita, contro un hash Argon2id fittizio quando l'utente non esiste. |
| 7 | Rate limiter per-IP inefficace dietro reverse proxy: `AuthRateLimiter::clientIp()` usa solo `REMOTE_ADDR`, che dietro Caddy è l'IP del proxy → bucket condiviso da tutti i client (lockout collettivo facile, attaccante non identificato) | **Aperto** (scelta infrastrutturale): serve gestire `X-Forwarded-For` accettandolo solo dal proxy fidato. |
| 8 | CD: `ssh-keyscan` a ogni deploy = trust-on-first-use ripetuto; un MITM sulla rete del runner può impersonare il VPS | **Aperto**: consigliato un secret `VPS_KNOWN_HOSTS` con la fingerprint pinnata. |
| 9 | 9 advisory (`composer audit`) su 4 pacchetti **solo dev**: guzzlehttp/guzzle, guzzlehttp/psr7, symfony/yaml, symfony/dom-crawler — nessuno esposto a runtime | **Aperto**: eseguire `composer update` per le dipendenze dev nel container quando comodo. |

### Punti forti riscontrati

- Argon2id per password **e** token; reset token con schema selector/verifier.
- CSRF middleware + `SameOriginRequestMiddleware`; cookie `autoLogin` cifrato, HttpOnly, SameSite.
- Sessioni con `use_strict_mode`; protezione open-redirect corretta in `RememberedUrlService`
  (verifica host, neutralizza URL protocol-relative `//evil.com`).
- Query sempre parametrizzate (nessuna concatenazione SQL trovata in `src/`).
- Encoding HTML coerente nei widget (`Html::` di default, `encode(false)` solo su frammenti già codificati).
- Messaggi di login non enumeranti; captcha con `hash_equals`; rate limiting multi-scope (identità + IP).
- `.env`/`.env.prod` fuori dal VCS; container non-root; Trivy in CI.

## 2. Migliorie di qualità applicate

- **CI sbloccata**: `composer.lock` riallineato a `composer.json` (solo content-hash e piattaforma,
  nessuna versione cambiata) → `composer validate` passa.
- **php-cs-fixer** applicato con il ruleset del progetto (PER-CS 2.0): ~165 file riformattati,
  nessun cambio semantico.
- `ArchitectUiAsset`: rimosso `scripts/scrollbar.js` duplicato.
- `LoginAction`: eliminata la doppia iniezione di `CurrentUser` (costruttore + parametro del metodo).
- Accenti corretti nei testi utente ("La password è scaduta", "Si è verificato un problema", ecc.).
- Rimosso `maximum-scale=1` dal meta viewport (bloccava lo zoom: problema di accessibilità).

## 3. Parere sullo scaffolding

**Promosso.** Il pattern `Data/<Modulo>/<Entità>/{Entity, Reader, Repository, Policy, Presenter,
Filter, Input, Scope}` + handler ADR (una classe per azione) + `Params` tipizzati + widget riusabili
è applicato con coerenza totale; la separazione Core/Mes rende chiaro il confine tra framework
applicativo e dominio. Sopra la media dei template Yii3.

Contropartite da tenere d'occhio:

1. ~8-9 classi di boilerplate per ogni nuova entità CRUD → valutare un generatore di codice.
2. `config/common/routes.php` monolitico → valutare un file di route per modulo.

## 4. Verifiche eseguite

- Smoke test locale (PHP built-in server, senza DB): `/login`, `/register`, `/forgot-password`
  → 200 con i nuovi security header presenti.
- `php -l` pulito su tutti i file toccati.
- Test Unit: 81/82 pass — l'unico errore (`could not find driver` in `NotificationReaderSqlTest`)
  è ambientale (manca `pdo_mysql` sul PHP locale), non legato alle modifiche.
- Psalm non eseguibile in locale (mancano ext `dom`/`simplexml`); gira in CI nel container.

## 5. Nota operativa per il deploy

Il push su `main` innesca CI → CD → deploy sul VPS. Prima del prossimo push aggiungere nel
`.env.prod` reale sul VPS:

```bash
AUTH_COOKIE_SECRET_KEY=$(openssl rand -hex 32)
```

Senza, il deploy fallisce all'avvio del container (fail-fast voluto).
