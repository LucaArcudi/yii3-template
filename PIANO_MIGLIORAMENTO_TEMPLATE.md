# Piano di miglioramento del template Yii3

> Roadmap attiva dal 16 agosto 2026.
>
> Questo è il backlog consolidato del repository. La pulizia dei documenti
> precedenti è rinviata a una modifica separata: nel frattempo non
> costituiscono fonti operative parallele.

## 1. Obiettivo

Il repository deve restare un template Yii3 pubblico, riutilizzabile e sicuro
per applicazioni gestionali. Il lavoro futuro ha cinque obiettivi:

1. rendere chiari e configurabili sviluppo, CI/CD e provisioning;
2. chiudere i rilievi residui di sicurezza e affidabilità;
3. completare il backlog funzionale della release 1.1;
4. ridurre il debito tecnico e frontend;
5. fornire a Codex un contesto preciso per produrre modifiche focalizzate e
   verificabili.

## 2. Base già disponibile

Il template parte da una base matura che non va riprogettata:

- architettura a moduli verticali: `Core`, `Mes` e infrastruttura condivisa
  in `src/Shared/`;
- autenticazione, RBAC, audit log, notifiche, utenti, ruoli e permessi;
- action invocabili, policy di dominio, reader/repository, ownership scope,
  input validati e widget condivisi;
- migration `yiisoft/db-migration`, bootstrap idempotente e comando
  `user:create`;
- ambienti Docker per sviluppo, test e produzione;
- CI con Codeception, database reale, Psalm, PHP CS Fixer, Composer audit e
  gate Trivy;
- immagine di produzione su GHCR e CD su VPS con backup, migration, health
  check e rollback applicativo;
- monitoring con Prometheus, Grafana, Loki e Alloy;
- procedure di rebuild frontend, licenze e runbook operativi;
- regole Codex in `AGENTS.md`.

Prima si riusano servizi e pattern esistenti; nuova architettura viene
introdotta solo quando il codice corrente non offre un'estensione adatta.

## 3. Modalità di sviluppo

Il proprietario formula la richiesta direttamente nella conversazione Codex.
Non sono richieste una issue preventiva o una fase documentale separata.

```text
richiesta diretta
  → fetch di origin/main
  → branch dedicato
  → implementazione e test
  → review del diff
  → commit e push della branch
  → pull request
  → CI verde
  → review e merge manuali
  → CD automatica, quando prevista
```

Regole:

- Codex è responsabile dell'intera modifica e non delega salvo richiesta
  esplicita del proprietario.
- L'obiettivo, i limiti e i criteri della richiesta vengono chiariti nella
  conversazione quando necessario.
- Codex non amplia il perimetro: le migliorie scoperte vengono riportate a
  parte.
- Codex può pushare soltanto la branch dedicata e aprire o aggiornare la PR.
- Codex non pusha su `main`, non fa merge e non opera in produzione.
- CI, test e controlli statici sono evidenze; non si indeboliscono test,
  scansioni o baseline per ottenere un esito verde.

La configurazione condivisa di Codex potrà vivere in `.codex/config.toml`, ma
non dovrà contenere credenziali o impostazioni personali. Login OpenAI e
GitHub restano nel credential store della postazione di sviluppo.

## 4. Priorità

### P0 — Consolidamento documentale e flusso Codex

- [x] Creare una sola roadmap attiva per il template.
- [ ] Riordinare `docs/` e valutare l'archiviazione di audit, prompt, recap e
  piani non più attivi in una modifica documentale separata.
- [ ] Semplificare i form GitHub del precedente flusso interno ed eliminare i
  riferimenti ai prompt non più operativi.
- [x] Allineare `AGENTS.md`, `CONTRIBUTING.md`, README e template PR al flusso
  richiesta diretta → branch → PR.
- [ ] Creare un indice della documentazione attiva.
- [ ] Documentare visivamente i confini tra Codex, GitHub, Docker, CI/CD,
  proxy Caddy e VPS.
- [ ] Verificare empiricamente che il ruleset di `main` respinga un push
  diretto del proprietario.

### P1 — Docker, CI/CD e bootstrap riutilizzabili

Le modifiche vanno eseguite in PR separate e retrocompatibili con il VPS
attuale.

1. [ ] **Parametrizzare gli script di deploy.** Introdurre variabili per
   directory di deploy, remote, branch, porta SSH e health URL mantenendo gli
   attuali valori come default. Aggiungere test per default e override.
2. [ ] **Configurare il target GitHub.** Usare un Environment `production`,
   GitHub Secrets per le credenziali e GitHub Variables per i valori non
   sensibili. Fallire prima dell'SSH se manca una configurazione obbligatoria.
3. [x] **Separare bootstrap e CD.** Rimossi i playbook Ansible specifici del
   VPS; il proxy Caddy resta versionato come stack Docker con bootstrap
   manuale, mentre la CD distribuisce soltanto le release applicative.
4. [x] **Pubblicare l'artefatto verificato.** Il job `image` costruisce una
   sola immagine di produzione, ne verifica il contenuto, applica il gate
   Trivy e sui push a `main` pubblica quella stessa immagine con tag SHA e
   `latest`; GHCR le assegna il digest content-addressed.
5. [ ] **Validare i file operativi.** Aggiungere controlli per workflow GitHub,
   shell e Docker Compose senza indebolire i gate esistenti.
6. [ ] **Riscrivere la guida di installazione production-ready.** Separare
   chiaramente bootstrap del VPS, configurazione GitHub, primo deploy e ciclo
   ordinario delle release.

### P2 — Hardening applicativo rapido

- [ ] **Usare l'IP fidato nell'audit log.** `EntityLogRepository` deve leggere
  l'attributo risolto da `TrustedProxyMiddleware`, con `REMOTE_ADDR` come
  fallback. Aggiungere un test di regressione.
- [ ] **Proteggere tutte le rotte non pubbliche.** Raggruppare le rotte
  riservate con middleware comune e aggiungere un test strutturale. Le policy
  di dominio restano la fonte della decisione autorizzativa.
- [ ] **Definire `APP_PUBLIC_URL`.** Renderla obbligatoria in produzione e
  usarla per i link sensibili, in particolare il reset password.
- [ ] **Aggiungere una CSP progressiva.** Partire da
  `Content-Security-Policy-Report-Only` e passare all'enforcement dopo la
  verifica delle pagine principali.
- [ ] **Aggiungere `HEALTHCHECK` al Dockerfile.** Deve essere coerente con i
  controlli già usati da Compose e CD.
- [ ] **Documentare il comportamento same-origin.** Chiarire che, senza
  `Origin` e `Referer`, la barriera CSRF effettiva resta il token.

### P3 — Integrità dei dati e sicurezza operativa

- [ ] Rendere atomico il rate limiter con incremento lato MySQL o transazione
  con lock e test concorrente sul DB reale.
- [ ] Proteggere i backup locali con `umask 077`, directory `0700` e dump
  `0600`; evitare parsing fragile di `.env.prod`.
- [ ] Automatizzare periodicamente un restore in ambiente isolato.
- [ ] Definire retention e cleanup dell'audit log.
- [ ] Ridurre l'enumerazione account residua.
- [ ] Definire chiavi esterne e indici di Core tramite migration.
- [ ] Ridurre l'accesso all'host di proxy e monitoring valutando socket proxy,
  Docker rootless e riduzione di capability/mount.
- [ ] Portare progressivamente GitHub Actions a SHA completi e immagini
  critiche a digest.
- [ ] Rivalutare le eccezioni Trivy alla relativa scadenza, senza proroghe
  automatiche.

### P4 — Qualità e debito tecnico

- [ ] Ridurre gradualmente `psalm-baseline.xml`, senza nuove soppressioni per
  nascondere errori freschi.
- [ ] Applicare le modernizzazioni Rector approvate separando cambi meccanici
  e funzionali.
- [ ] Definire una soglia di coverage progressiva sui domini critici.
- [ ] Aggiornare singolarmente le dipendenze major con note e suite completa.
- [x] Correggere i target Makefile ereditati e non coerenti con il compose di
  root: tutti i target locali usano ora il Compose canonico, i test hanno uno
  stack MySQL isolato e i residui Docker Swarm sono stati rimossi.
- [ ] Mantenere documentazione e `CHANGELOG.md` nello stesso cambiamento che
  modifica comportamento o workflow.

### P5 — Debito frontend

Ogni modifica ai bundle passa da `docs/assets/REBUILD.md`; i file minificati
non vengono modificati a mano.

- [ ] Rigenerare il lockfile di cleanup senza la dipendenza rimossa per
  licenza e aggiornare SHA-256, notice, procedura e pattern Trivy.
- [ ] Valutare l'allineamento tra Bootstrap CSS e Bootstrap JS.
- [ ] Eliminare la duplicazione di jQuery tra `main.js` e `demo.js`.
- [ ] Rimuovere la dipendenza `wnumb` non emessa dagli artefatti.
- [ ] Chiudere le incertezze di attribuzione per Hamburgers e RFS.
- [ ] Correggere la sidebar su viewport molto basse con verifica responsive.
- [ ] Valutare una build Bootstrap selettiva solo dopo un audit dedicato.

### P6 — Backlog funzionale 1.1

- [ ] **Utente super.** Aggiungere `is_super` a `core_user` ed escludere gli
  utenti super dalle liste previste, con migration, policy e test.
- [ ] **Notifiche.** Migliorare centro notifiche e canali mantenendo il dominio
  `Core/Notification` come punto di estensione.
- [ ] **Select dipendenti.** Implementare un esempio nei form e nei filtri
  statici senza autosubmit preservando `FilterBar`.

### P7 — Evoluzioni successive

- [ ] **Multitenancy.** Introdurre un modello semplice basato su `tenant_id`,
  definendo scope, indici, ownership e migrazione dei dati esistenti.
- [ ] **Pagamenti Stripe.** Definire confini del dominio, webhook,
  idempotenza, errori, dati sensibili e test.

Queste evoluzioni iniziano solo dopo la stabilizzazione della release 1.1.
Se il perimetro non è chiaro, Codex chiede conferma nella conversazione prima
di modificare il codice.

## 5. Ordine di esecuzione

1. Completare P0 con una modifica documentale dedicata, decidendo prima quali
   file storici versionare.
2. Eseguire P1 nell'ordine indicato, una PR per responsabilità.
3. Chiudere le attività rapide di P2.
4. Procedere con sicurezza operativa e qualità P3-P5.
5. Completare la release 1.1 con P6.
6. Valutare P7 dopo la stabilizzazione.

## 6. Definition of Done

Ogni modifica viene chiusa solo quando:

- vive su una branch dedicata creata da `origin/main` aggiornato;
- contiene test funzionali o di regressione proporzionati al cambiamento;
- passa i controlli mirati e la CI completa;
- non indebolisce Psalm, test, scansioni o branch protection;
- usa migration del framework per ogni modifica di schema;
- aggiorna documentazione e `CHANGELOG.md` quando cambia comportamento,
  comando, workflow o funzionalità visibile;
- non modifica manualmente i bundle frontend;
- non contiene segreti, configurazioni personali o file locali;
- mantiene un diff focalizzato;
- dichiara rischi residui e verifiche manuali;
- viene mergiata manualmente dal proprietario.

## 7. Fonti attive

- `AGENTS.md`
- `README.md`
- `README_DEPLOY.md`
- `CHANGELOG.md`
- `docs/documentazione-progetto.md`
- `docs/roadmap-sviluppo.md`
- `docs/roadmap-infrastruttura.md`
- `docs/assets/`
- `docs/runbooks/`

I rapporti, i prompt e i piani precedenti restano per ora nel repository, ma
non sono fonti operative. Saranno riordinati in una modifica separata.
