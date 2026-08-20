# Piano di miglioramento del template Yii3

> Roadmap attiva dal 16 agosto 2026, consolidata il 21 agosto 2026.
>
> Questo è il backlog unico del repository. I documenti storici non sono
> fonti operative parallele e restano recuperabili nello storico Git.
> Il percorso dimostrativo Docker, CI/CD e monitoring è concluso; ciò che
> rimane è classificato sotto senza riaprirlo implicitamente.

## 1. Obiettivo

Il repository deve restare un template Yii3 pubblico, riutilizzabile e sicuro
per applicazioni gestionali. Il lavoro futuro ha cinque obiettivi:

1. rendere chiari e configurabili sviluppo, CI/CD e provisioning;
2. chiudere i rilievi residui di sicurezza e affidabilità;
3. completare il backlog funzionale della release 1.1;
4. ridurre il debito tecnico e frontend;
5. fornire a Codex un contesto preciso per produrre modifiche focalizzate e
   verificabili.

Il primo obiettivo infrastrutturale è stato completato e verificato sul VPS il
21 agosto 2026. P2-P5 restano manutenzione del template e della supply chain;
P6 e P7 sono un backlog applicativo Yii3 separato e non sono criteri di
completamento del percorso DevOps.

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
- monitoring con Prometheus, Grafana, Loki e Alloy, verificato sul VPS insieme
  alle notifiche Telegram;
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
- [x] Riordinare `docs/` rimuovendo dalle fonti attive audit, prompt e piani
  superati, che restano recuperabili nello storico Git.
- [x] Semplificare i form GitHub del precedente flusso interno ed eliminare i
  riferimenti ai prompt non più operativi.
- [x] Allineare `AGENTS.md`, `CONTRIBUTING.md`, README e template PR al flusso
  richiesta diretta → branch → PR.
- [x] Creare un indice della documentazione attiva.
- [x] Documentare visivamente i confini tra Codex, GitHub, Docker, CI/CD,
  proxy Caddy e VPS.
- [x] Proteggere `main` con ruleset e merge tramite pull request; configurazione
  confermata dal proprietario il 20 agosto 2026.

### P1 — Docker, CI/CD e bootstrap riutilizzabili

Le modifiche devono restare focalizzate e retrocompatibili con il VPS attuale.

1. [x] **Parametrizzare gli script di deploy.** Introdurre variabili per
   directory di deploy, remote, branch, porta SSH e health URL mantenendo gli
   attuali valori come default. Aggiungere test per default e override.
2. [x] **Configurare il target GitHub.** Credenziali e valori di deploy usano
   rispettivamente Repository Secrets e Repository Variables. Il workflow ne
   valida i valori e fallisce prima dell'SSH se manca un Secret obbligatorio.
   Un Environment GitHub `production` resta tracciato come evoluzione futura
   facoltativa e non fa parte dell'assetto attuale.
3. [x] **Separare bootstrap e CD.** Rimossi i playbook Ansible specifici del
   VPS; il proxy Caddy resta versionato come stack Docker con bootstrap
   manuale, mentre la CD distribuisce soltanto le release applicative.
4. [x] **Pubblicare l'artefatto verificato.** Il job `image` costruisce una
   sola immagine di produzione, ne verifica il contenuto, applica il gate
   Trivy e sui push a `main` pubblica quella stessa immagine con tag SHA e
   `latest`; GHCR le assegna il digest content-addressed.
5. [x] **Validare i file operativi.** Aggiungere controlli per workflow GitHub,
   shell e Docker Compose senza indebolire i gate esistenti.
6. [x] **Riscrivere la guida di installazione production-ready.** Separare
   chiaramente bootstrap del VPS, configurazione GitHub, primo deploy e ciclo
   ordinario delle release.
7. [x] **Verificare deploy e osservabilità reali.** Dopo il merge della PR #45
   la CD ha distribuito il commit `bb2a1b8`; sul VPS sono stati verificati
   tutti i target Prometheus `UP`, `mysql_up == 1`, Grafana 13.1.3, Loki
   `ready`, ingestione Alloy e consegna di una notifica Telegram di prova.

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
- [x] **Aggiungere `HEALTHCHECK` al Dockerfile.** Deve essere coerente con i
  controlli già usati da Compose e CD.
- [ ] **Documentare il comportamento same-origin.** Chiarire che, senza
  `Origin` e `Referer`, la barriera CSRF effettiva resta il token.

### P3 — Integrità dei dati e sicurezza operativa

- [ ] Rendere atomico il rate limiter con incremento lato MySQL o transazione
  con lock e test concorrente sul DB reale.
- [x] Proteggere i backup locali con `umask 077`, directory `0700` e dump
  `0600`; evitare parsing fragile di `.env.prod`.
- [x] Automatizzare periodicamente un restore in ambiente isolato.
- [ ] Definire retention e cleanup dell'audit log.
- [ ] Ridurre l'enumerazione account residua.
- [ ] Definire chiavi esterne e indici di Core tramite migration.
- [x] Portare progressivamente GitHub Actions a SHA completi e immagini
  critiche a digest.
- [x] Eseguire una rivalutazione intermedia delle eccezioni Trivy. Il 20 agosto
  2026 la release FrankenPHP 1.12.7 contiene ancora i due moduli vulnerabili
  censiti.
- [ ] Alla scadenza del 31 agosto 2026, rieseguire il gate e prendere una
  decisione esplicita: aggiornare l'upstream, rimuovere le eccezioni oppure
  accettare nuovamente il rischio con una nuova scadenza motivata.

La riduzione dell'accesso host tramite socket proxy, Docker rootless e minori
capability/mount resta un hardening facoltativo del VPS. Non blocca la chiusura
del percorso Docker, CI/CD e monitoring.

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
- [x] Mantenere documentazione e `CHANGELOG.md` nello stesso cambiamento che
  modifica comportamento o workflow.

### P5 — Debito frontend

Ogni modifica ai bundle passa da `docs/assets/REBUILD.md`; i file minificati
non vengono modificati a mano.

- [x] Rigenerare il lockfile di cleanup senza la dipendenza rimossa per
  licenza e aggiornare SHA-256, notice e procedura; verificare il pattern
  Trivy, rimasto valido perché il nome del file non cambia.
- [ ] Valutare l'allineamento tra Bootstrap CSS e Bootstrap JS.
- [ ] Eliminare la duplicazione di jQuery tra `main.js` e `demo.js`.
- [x] Rimuovere la dipendenza `wnumb` non emessa dagli artefatti.
- [x] Chiudere le incertezze di attribuzione per Hamburgers e RFS.
- [ ] Correggere la sidebar su viewport molto basse con verifica responsive.
- [ ] Valutare una build Bootstrap selettiva solo dopo un audit dedicato.

### P6 — Backlog applicativo Yii3 1.1

Questo backlog è separato dal percorso DevOps ed è riepilogato anche nella
[roadmap di sviluppo](docs/roadmap-sviluppo.md).

- [ ] **Utente super.** Aggiungere `is_super` a `core_user` ed escludere gli
  utenti super dalle liste previste, con migration, policy e test.
- [ ] **Notifiche.** Migliorare centro notifiche e canali mantenendo il dominio
  `Core/Notification` come punto di estensione.
- [ ] **Select dipendenti.** Implementare un esempio nei form e nei filtri
  statici senza autosubmit preservando `FilterBar`.

### P7 — Evoluzioni Yii3 successive

- [ ] **Multitenancy.** Introdurre un modello semplice basato su `tenant_id`,
  definendo scope, indici, ownership e migrazione dei dati esistenti.
- [ ] **Pagamenti Stripe.** Definire confini del dominio, webhook,
  idempotenza, errori, dati sensibili e test.

Queste evoluzioni iniziano solo dopo la stabilizzazione della release 1.1.
Se il perimetro non è chiaro, Codex chiede conferma nella conversazione prima
di modificare il codice.

## 5. Stato dei perimetri

| Perimetro | Stato | Significato |
|---|---|---|
| P0-P1 — Docker, CI/CD e monitoring | **Completato** | Implementazione, CI/CD e verifica reale sul VPS concluse. |
| P2 — Hardening applicativo | Aperto | Manutenzione PHP/Yii, separata dal percorso DevOps. |
| P3 — Integrità e sicurezza operativa | Aperto | Attività applicative/dati più la sola scadenza Trivy esplicitata sotto. |
| P4-P5 — Qualità e frontend | Aperto | Debito tecnico del template, da affrontare con PR dedicate. |
| P6 — Funzionalità Yii3 1.1 | Backlog separato | Utente super, notifiche e select dipendenti. |
| P7 — Evoluzioni Yii3 | Backlog separato | Multitenancy e pagamenti Stripe, solo dopo P6. |

### Attività infrastrutturali residue e decisioni di perimetro

| Voce | Classificazione | Impatto sulla chiusura DevOps |
|---|---|---|
| Decisione sulle eccezioni Trivy entro il 31 agosto 2026 | Manutenzione con scadenza | Unica azione infrastrutturale calendarizzata; non è un nuovo componente. |
| Restore periodico di un dump reale del VPS | Facoltativo | Il drill isolato è già in CI; il test reale non è richiesto. |
| Socket proxy, Docker rootless e riduzione di capability/mount | Hardening facoltativo | Rischio noto dell'accesso host, non bloccante. |
| Metriche per-container di cAdvisor con snapshotter `overlayfs` | Limite upstream mitigato | La liveness usa le metriche degli upstream Caddy. |
| Dashboard community Grafana | Visualizzazione facoltativa | Metriche, log e alert funzionano anche senza importarle. |
| Endpoint applicativo `/metrics` e metriche di business | Evoluzione facoltativa | Richiede requisiti applicativi prima dell'implementazione. |
| Eliminazione del proxy Caddy esterno | Semplificazione futura | Richiede un nuovo disegno per TLS, routing, certificati e metriche. |
| GitHub Environment `production` | Evoluzione futura facoltativa | Potrà introdurre approvazioni al deploy e Secrets/Variables limitati all'ambiente; l'assetto attuale usa Repository Secrets/Variables. |
| Ansible per il bootstrap | Decisione chiusa: rimosso | Il bootstrap del VPS resta manuale e documentato. |
| Pull request automatiche Dependabot | Manutenzione ordinaria | Ogni proposta può essere valutata o ignorata; non è un requisito di chiusura. |

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
- `docs/README.md`
- `docs/DEVOPS_WORKFLOW.md`
- `docs/documentazione-progetto.md`
- `docs/roadmap-sviluppo.md`
- `docs/roadmap-infrastruttura.md`
- `docs/assets/`
- `docs/runbooks/`

## 8. Verifiche esterne e interventi del proprietario

Completati il 21 agosto 2026:

- merge tramite pull request e CD verde sul commit `bb2a1b8`;
- ruleset di `main` e configurazione tramite Repository Secrets/Variables;
- bot Telegram configurato nel file locale del VPS e notifica di prova
  ricevuta;
- target Prometheus tutti `UP`, `mysql_up == 1`, Grafana operativo, Loki
  `ready` e log Docker consultabili tramite Alloy.

Resta una sola azione calendarizzata: decidere entro il 31 agosto 2026 come
gestire le due eccezioni Trivy in scadenza. Le altre voci della tabella in
§5 sono possibilità future o decisioni già chiuse, non attività necessarie.

P6 e P7 restano esclusivamente nel backlog applicativo Yii3 e non bloccano la
chiusura del percorso Docker, CI/CD e monitoring usato come progetto
dimostrativo.
