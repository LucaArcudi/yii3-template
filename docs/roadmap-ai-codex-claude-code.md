# Roadmap AI per il template Yii3: Codex + Claude Code

Obiettivo: integrare l'AI nel progetto **Yii3 template** — che ha già CI/CD su
GitHub Actions, deploy su VPS con script versionati, monitoring
Prometheus/Grafana e alert — con un workflow di sviluppo strutturato.

Focus del documento:

```text
Codex
Claude Code
GitHub Issues
Pull Request
CI/CD
self-healing assistito da AI
automazione da pre-analisi tecnica
alerting → incident
```

I prerequisiti infrastrutturali (centralizzazione log con Loki, canale di
notifica degli alert, rollback automatico) NON sono materia di questo
documento: vivono in [roadmap-infrastruttura.md](roadmap-infrastruttura.md)
e qui vengono solo referenziati.

La logica corretta non è dare all'AI il controllo libero della produzione.

La logica corretta è:

```text
AI legge contesto → propone/modifica codice → apre PR → CI verifica → umano approva → CD deploya
```

---

## 0. Stato del repository (2026-07-05)

Prima della roadmap, la fotografia di ciò che ESISTE GIÀ e su cui l'AI può
appoggiarsi (deciso e verificato, non aspirazionale):

```text
✔ branch protection su main: solo PR, check CI richiesto, niente push diretti
✔ CI severa: build, Trivy, composer validate/audit (bloccante), Psalm con
  baseline, Codeception, doppia validazione migration (idempotenza +
  bootstrap da zero), verifica dell'artefatto prod, promtool sulle regole
✔ CD con logica versionata in scripts/backup-db.sh e scripts/deploy.sh:
  backup con retention, migrate:up, ricreazione esplicita dell'app,
  invariante immagine, health check
✔ migration del framework (yiisoft/db-migration) + comando user:create
✔ monitoring: Prometheus + node/cadvisor/mysqld exporter + metriche HTTP
  di Caddy; Grafana pubblica in TLS; 6 regole di alert versionate
✔ runbook operativi in docs/documentazione-progetto.md §9 (log, rollback,
  restore, diagnosi 500)
```

Le sezioni che seguono costruiscono SOPRA questa base.

---

## 1. Ruolo dei due strumenti

### Codex

Usalo come **agente di sviluppo e revisione tecnica**.

Ruoli principali:

```text
- implementare feature da issue tecniche
- correggere bug
- fare refactoring
- scrivere o aggiornare test
- analizzare errori CI
- proporre PR
- fare code review automatica sulle PR
- lavorare localmente da terminale o in ambiente cloud/sandbox
```

Codex CLI può leggere, modificare ed eseguire codice nella directory
selezionata. Supporta istruzioni di progetto tramite `AGENTS.md`, lette
prima di iniziare il lavoro.

Fonti ufficiali:

- https://developers.openai.com/codex/cli
- https://developers.openai.com/codex/guides/agents-md
- https://developers.openai.com/codex/integrations/github
- https://openai.com/codex/

### Claude Code

Usalo come **agente operativo sul repository e sui workflow GitHub**.

Ruoli principali:

```text
- leggere la codebase
- implementare task da issue/PR
- analizzare bug
- eseguire comandi
- modificare file
- lavorare su branch
- creare PR
- reagire a mention @claude su issue e PR
- automatizzare workflow GitHub
```

Fonti ufficiali:

- https://docs.anthropic.com/en/docs/claude-code/overview
- https://docs.anthropic.com/en/docs/claude-code/github-actions
- https://docs.anthropic.com/en/docs/claude-code/common-workflows

---

## 2. Differenza pratica tra Codex e Claude Code

Non serve sceglierne uno solo.

```text
Codex       → sviluppo, bugfix, refactoring, review tecnica, test
Claude Code → workflow GitHub, issue/PR, automazioni, fix da mention, task operativi
```

Possibile divisione:

```text
Pre-analisi → Claude Code o Codex
Analisi tecnica → Codex
Generazione issue → Claude Code
Implementazione → Codex o Claude Code
Fix CI → Codex
Review PR → Codex
Automazioni su GitHub → Claude Code
Incident diagnosis → Claude Code + Codex
```

Versione semplice:

```text
Codex = sviluppatore automatico
Claude Code = collega operativo integrato nel workflow GitHub
CI = giudice tecnico
tu = reviewer finale
```

---

## 3. Architettura consigliata

```text
File pre-analisi
    ↓
AI genera analisi tecnica
    ↓
AI genera issue GitHub
    ↓
Codex / Claude Code implementa su branch
    ↓
Pull Request
    ↓
CI
    ↓
AI corregge eventuali errori
    ↓
Review umana
    ↓
Merge
    ↓
CD (backup → migrate → deploy → invariante → health check)
    ↓
Monitoring Grafana/Prometheus
    ↓
Incident issue se qualcosa va male
```

Regola fondamentale:

```text
AI può proporre codice.
AI può aprire PR.
AI può correggere PR.
AI può leggere log.
AI NON deve modificare liberamente la produzione.
```

Nota: in questo repo la regola è già IMPOSTA dalla piattaforma, non
affidata alla buona volontà — la branch protection blocca chiunque
(umano o AI) fuori dal flusso PR, e i deploy passano solo dal CD.

---

## 4. Struttura file da aggiungere al repository

```text
.github/
  ISSUE_TEMPLATE/
    feature.yml
    bug.yml
    incident.yml
    ai-task.yml
  PULL_REQUEST_TEMPLATE.md
  workflows/
    ci.yml            (esiste)
    cd.yml            (esiste)
    ai-triage.yml     (futuro)
    ai-fix-ci.yml     (futuro)
    ai-incident.yml   (futuro)

docs/
  ai/
    pre-analisi/
    analisi-tecnica/
    prompts/
      genera-analisi-tecnica.md
      genera-issues.md
      implementa-issue.md
      fix-ci-failure.md
      review-pr.md
      incident-diagnosis.md
  runbooks/           (oggi i runbook vivono in documentazione-progetto.md §9;
                       estrarli in file singoli quando l'AI dovrà citarli)

AGENTS.md
CLAUDE.md
```

---

## 5. AGENTS.md per Codex

Il repository contiene ora un file `AGENTS.md` alla root. Quello è il punto di
verità operativo da passare a Codex: regole di sviluppo, limiti di sicurezza,
comandi di verifica e convenzione di co-autorship.

Contenuto consigliato di base:

```md
# Project instructions for Codex

This is a Yii3 web application template.

## General rules

- Do not push directly to main (branch protection will reject it anyway).
- Always work through a branch and a pull request.
- Keep changes small and reviewable.
- Follow the existing Yii3 project structure.
- Do not rewrite large parts of the project unless explicitly requested.
- Do not modify production secrets.
- Do not edit deployment credentials.
- Do not run destructive database commands.
- Do not create destructive migrations without explicit approval.

## Development rules

- Prefer Yii3 conventions already used in this project.
- Schema changes go through yiisoft/db-migration (`./yii migrate:create`),
  never through new initdb.d files.
- Add or update tests for every functional change.
- If a bug is fixed, add a regression test when possible.
- If behavior changes, update documentation.
- Run the available test suite before completing the task
  (`make test`, `make psalm`, `make cs-fix`).

## CI rules

- If CI fails, inspect the failing job before changing code.
- Fix the root cause, not only the symptom.
- Do not disable tests to make CI pass.
- Do not weaken static analysis rules or grow psalm-baseline.xml to
  silence new findings.
- Do not remove checks from GitHub Actions unless explicitly requested.

## Output rules

When finishing a task, summarize:
- files changed
- reason for the change
- tests run
- risks
- manual verification steps
```

---

## 6. CLAUDE.md per Claude Code

Contenuto consigliato:

```md
# Claude Code instructions

This is a Yii3 web application template with CI/CD and VPS deployment.

## Role

You are an AI development assistant working on GitHub issues and pull requests.

## Mandatory workflow

- Read the issue carefully.
- Check existing project structure before editing.
- Work on a dedicated branch (never tracking origin/main).
- Open a pull request.
- Never push directly to main.
- Keep PRs small.
- Explain every non-trivial change.
- Run tests when possible.
- Do not bypass CI.

## Safety rules

- Never modify production secrets.
- Never expose environment variables.
- Never run destructive commands.
- Never delete production data.
- Never change deployment workflows unless explicitly requested.
- Never perform production operations unless the runbook allows it.
- Never put remote logic in heredocs that contain docker compose
  run/exec: they eat stdin and the script dies half-way while looking
  green. Deploy logic lives in scripts/*.sh, versioned.

## Yii3 rules

- Follow the current Yii3 template structure.
- Prefer existing patterns over inventing new architecture.
- Schema changes via yiisoft/db-migration.
- Add tests for new behavior.
- Update docs when commands, env variables or workflows change.

## Pull request summary

Every PR must include:
- what changed
- why it changed
- how it was tested
- possible risks
- rollback notes if relevant
```

---

## 7. Automazione da pre-analisi ad analisi tecnica

Input umano o semi-umano:

```text
docs/ai/pre-analisi/2026-07-04-nome-attivita.md
```

Esempio contenuto:

```md
# Pre-analisi

Il cliente vuole gestire documenti associati a un'entità.
Serve upload file.
Serve filtro per stato.
Serve export.
Attenzione ai permessi.
```

Output AI:

```text
docs/ai/analisi-tecnica/2026-07-04-nome-attivita.md
```

Prompt consigliato:

```md
# Task

Genera una analisi tecnica a partire da questa pre-analisi.

## Obiettivo

Trasforma il testo in una specifica tecnica implementabile per un progetto Yii3.

## Output richiesto

Usa questa struttura:

# Obiettivo
# Contesto funzionale
# Entità coinvolte
# Modifiche DB
# Migration previste
# Model/Repository/Service
# Controller/Action
# Rotte
# View/Form
# Validazioni
# Permessi
# Test da aggiungere
# Rischi
# Criteri di accettazione
# Piano implementativo

## Regole

- Non inventare requisiti non presenti.
- Se qualcosa non è chiaro, segnala un dubbio.
- Mantieni il piano implementabile in piccoli step.
- Ragiona come backend developer Yii3.
```

---

## 8. Generazione issue operative

Dalla analisi tecnica, l'AI deve generare issue piccole.

Esempio:

```text
[DB] Creare migration per tabella documenti
[BE] Creare model Documento
[BE] Creare service per upload documenti
[BE] Creare controller CRUD documenti
[TEST] Aggiungere test upload documento
[DOC] Aggiornare README modulo documenti
```

Ogni issue deve contenere:

```md
# Descrizione
# Contesto
# Scope
# File probabilmente coinvolti
# Acceptance criteria
# Test richiesti
# Vincoli
# Note per AI agent
```

Esempio nota per AI:

```md
## Note per AI agent

Implementare solo questa issue.
Non implementare funzionalità collegate non richieste.
Non modificare la pipeline.
Non modificare segreti o configurazioni produzione.
Aprire PR dedicata.
```

---

## 9. Workflow Codex per implementare issue

```text
Issue GitHub pronta
    ↓
Codex legge issue
    ↓
Codex crea branch
    ↓
Codex modifica codice
    ↓
Codex esegue test
    ↓
Codex apre PR
    ↓
CI verifica
```

Prompt esempio per Codex:

```md
Implement this GitHub issue in the Yii3 template project.

Rules:
- Follow AGENTS.md.
- Keep the PR small.
- Do not modify deployment secrets.
- Do not edit CI/CD workflows.
- Add or update tests.
- Run the relevant test commands.
- If DB changes are needed, create a yiisoft/db-migration migration.
- Open a pull request with a clear summary.
```

---

## 10. Workflow Claude Code per issue/PR

```text
Issue GitHub
    ↓
Commento @claude
    ↓
Claude Code analizza
    ↓
Claude Code implementa o propone piano
    ↓
PR
    ↓
CI
```

Esempio commento su issue:

```md
@claude implement this issue following CLAUDE.md.
Keep the change small.
Open a pull request.
Do not modify deployment secrets or CI/CD workflows.
```

Esempio commento su PR:

```md
@claude review this PR.
Focus on:
- Yii3 conventions
- missing tests
- possible regressions
- unsafe migrations
- deployment risks
```

---

## 11. Fix automatico degli errori CI

Workflow ideale:

```text
CI fallisce
    ↓
GitHub Action raccoglie log
    ↓
viene creata/aggiornata issue "CI failure"
    ↓
Codex o Claude Code analizza
    ↓
AI apre PR di fix
    ↓
CI riparte
```

Dati da includere nell'issue:

```text
- branch
- commit SHA
- workflow fallito
- job fallito
- step fallito
- log essenziale
- comando fallito
- link alla run
```

Nota per questo repo: i run sono leggibili senza autenticazione via API
pubblica (`/repos/<owner>/<repo>/actions/runs`), ma i LOG richiedono un
token: il workflow di raccolta deve girare con `GITHUB_TOKEN` in sola
lettura e incollare l'estratto nell'issue.

Prompt per fix CI:

```md
Analyze this CI failure.

Rules:
- Identify the root cause.
- Do not disable tests.
- Do not remove CI checks.
- Do not weaken quality rules.
- Propose the smallest safe fix.
- Add or update tests if needed.
- Open a PR.
```

Fix accettabili:

```text
- test rotto da modifica reale
- typo
- import mancante
- dependency lock non aggiornato
- migration test fallito
- comando composer errato
```

Fix NON accettabili:

```text
- rimuovere test
- commentare assertion
- disabilitare job
- ignorare errori con `|| true`
- saltare static analysis
- gonfiare psalm-baseline.xml per zittire errori nuovi
```

---

## 12. Self-healing assistito da AI

Il primo livello di self-healing è quello deterministico (restart su
healthcheck, invariante immagine, rollback automatico) e non richiede AI:
vive in [roadmap-infrastruttura.md](roadmap-infrastruttura.md) §3.

Il livello assistito da AI si appoggia sopra, e l'AI non tocca la
produzione:

```text
alert Prometheus
    ↓
webhook (vedi §16)
    ↓
issue incident GitHub
    ↓
raccolta log/metriche
    ↓
Claude Code/Codex analizza
    ↓
diagnosi
    ↓
eventuale PR correttiva
```

Dati da allegare all'issue incident:

```text
- alert name + severity + timestamp
- commit deployato (git -C /opt/yii3 log -1)
- ultimo workflow CD
- docker ps / stato container
- docker compose logs --tail=200
- stato DB
- health endpoint
- metriche CPU/RAM/disk (query Prometheus)
- ultimi errori applicativi (runtime/logs/app.log)
```

Prompt incident:

```md
Analyze this production incident.

Rules:
- Do not perform production changes.
- Do not request secrets.
- Do not suggest destructive DB operations.
- Identify probable cause.
- Separate immediate mitigation from code fix.
- If code change is needed, open a PR.
- If runbook action is enough, reference the correct runbook.
```

---

## 13. Runbook prima dell'AI

Prima di far ragionare l'AI sugli incident servono runbook chiari. Oggi
vivono in `docs/documentazione-progetto.md` §9 (stato e log, rollback,
restore backup, diagnosi 500); quando l'AI dovrà citarli in automatico
conviene estrarli in file singoli:

```text
docs/runbooks/app-down.md
docs/runbooks/deploy-failed.md
docs/runbooks/db-down.md
docs/runbooks/rollback.md
docs/runbooks/backup-restore.md
docs/runbooks/disk-full.md
```

Ogni runbook: Symptoms, Immediate checks, Safe actions, AI instructions
(cosa può e cosa NON può fare).

---

## 14. Review automatica PR

Checklist review:

```text
- bug evidenti
- regressioni
- test mancanti
- migration rischiose
- codice non coerente con Yii3
- documentazione mancante
- possibili problemi di deploy
```

Prompt review:

```md
Review this pull request.

Focus on serious issues:
- correctness
- regressions
- missing tests
- unsafe migrations
- Yii3 architecture violations
- deployment risks

Do not nitpick style unless it affects maintainability.
```

La review AI non sostituisce quella umana: è un secondo paio di occhi.

---

## 15. Strategia branch e permessi

```text
main ← solo merge da PR (GIÀ imposto dal ruleset)

feature/*
bugfix/*
incident/*
ai/*
```

Per task AI:

```text
ai/issue-123-document-upload
ai/fix-ci-456
ai/incident-789-app-down
```

Regola pratica (in questo repo già garantita dalla piattaforma):

```text
AI può scrivere su branch dedicato.
AI non può scrivere su main.
AI non può deployare direttamente.
```

---

## 16. Dall'alert all'incident issue

Presupposti infrastrutturali (vedi
[roadmap-infrastruttura.md](roadmap-infrastruttura.md)):

```text
- log centralizzati in Loki (§1): un incident senza log allegabili
  costringe a SSH manuale, che è esattamente ciò che non si vuole dare
  all'AI. Con Loki l'issue include il LINK alla query dell'intervallo
  dell'alert e l'AI riceve estratti selezionati via query, non SSH.
- canale di notifica degli alert (§2): prima le notifiche umane, poi
  l'automazione.
```

Con questi in piedi, l'incident automation:

```text
Alertmanager webhook → GitHub API repository_dispatch
    ↓
workflow ai-incident.yml crea l'issue incident precompilata
(alert, severity, timestamp, commit deployato, link Grafana/Loki)
    ↓
mention @claude per la diagnosi (vedi §12)
```

Regole:

```text
- il webhook NON esegue azioni sul VPS: crea solo issue
- il token usato dal webhook ha permesso SOLO issues:write
- deduplicazione: un alert che rientra chiude/aggiorna l'issue, non ne
  apre una nuova a ogni valutazione
```

---

## 17. Guardrail e permessi GitHub per gli agenti

Prima di dare agli agenti un ruolo nei workflow GitHub:

```text
- GITHUB_TOKEN dei workflow: permessi minimi espliciti per job
  (già così in ci.yml/cd.yml: contents: read, packages: write solo dove serve)
- workflow AI: MAI accesso ai secrets di deploy (VPS_*): girano in job
  separati senza quegli scope
- fork e contributor esterni: approvazione manuale dei run (già attiva)
- azioni di terze parti pinnate (tag o SHA)
- environment protection su "production" con required reviewer: da
  attivare PRIMA di dare a un agente la possibilità di innescare deploy
- ogni commit AI porta il Co-Authored-By dell'agente: tracciabilità
  (convenzione già in uso in questo repo)
- trailer Codex: `Co-Authored-By: Codex <noreply@openai.com>`
- label dedicate: ai:proposed, ai:approved, incident
```

---

## 18. Roadmap pratica di implementazione

### Fase A — Preparare il repo per l'AI

```text
AGENTS.md, CLAUDE.md, docs/ai/prompts/, issue/PR template,
estrazione runbook in docs/runbooks/
```

Output: Codex e Claude Code capiscono come lavorare nel progetto.

### Fase B — Analisi tecnica e issue automatiche

```text
pre-analisi.md → analisi-tecnica.md → issue GitHub piccole
```

Anche manualmente all'inizio, da terminale.

### Fase C — Implementazione AI controllata

```text
issue → Codex/Claude Code → branch ai/* → PR → CI → review umana → merge
```

### Fase D — Prerequisiti infrastrutturali

```text
centralizzazione log (Loki + Alloy) e canale di notifica degli alert:
vedi roadmap-infrastruttura.md §1 e §2
```

### Fase E — Incident issue automatiche

```text
webhook → issue incident precompilata (vedi §16)
```

### Fase F — Fix CI e incident diagnosis assistiti

```text
CI failed → issue con log → AI → PR fix
alert → issue incident → AI diagnosis → eventuale PR
```

### Fase G — Self-healing limitato

Solo azioni sicure e deterministiche (il grosso vive in
roadmap-infrastruttura.md §3):

```text
- restart container (già attivo via healthcheck)
- rollback deploy su smoke test fallito
- re-run job
- alert escalation
```

---

## 19. Cosa NON fare

```text
- AI con SSH libero alla VPS
- AI con accesso ai secret
- AI che modifica main
- AI che deploya codice non revisionato
- AI che esegue migration distruttive
- AI che cancella file o dati
- AI che disabilita test per far passare la CI
- AI che gonfia la baseline Psalm per zittire errori nuovi
- AI che modifica workflow di deploy senza review
```

---

## 20. Setup minimo consigliato

```text
Codex CLI in locale
Claude Code in locale
AGENTS.md
CLAUDE.md
issue template AI
PR template
docs/ai/prompts
runbook estratti in docs/runbooks/
CI robusta            (✔ già presente)
CD con script versionati (✔ già presente; rollback automatico da aggiungere)
alert Prometheus      (✔ già presenti; manca il canale di notifica,
                       roadmap-infrastruttura.md §2)
centralizzazione log  (da fare, roadmap-infrastruttura.md §1)
incident issue manuale o semi-automatica
```

Solo dopo:

```text
Claude Code GitHub Actions (@claude su issue/PR)
Codex code review su GitHub
AI fix CI automatico
AI incident diagnosis
```

---

## 21. Esempio workflow completo

```text
1. Scrivi o ricevi una pre-analisi
2. Codex genera analisi tecnica
3. Claude Code genera issue GitHub operative
4. Codex implementa la prima issue su branch ai/*
5. Codex apre PR
6. CI fallisce
7. Claude Code analizza il fallimento
8. Codex aggiorna la PR
9. CI passa
10. Tu fai review
11. Merge
12. CD: backup → migrate → deploy → invariante → health check
13. Prometheus/Grafana monitorano, Loki conserva i log
14. Se un alert scatta, nasce una issue incident con link a log e metriche
15. Claude Code/Codex fa diagnosis
16. Se serve codice, l'AI apre una nuova PR
```

---

## 22. Sintesi finale

```text
Codex e Claude Code non devono comandare la pipeline.
Devono lavorare dentro la pipeline.
```

Formula pratica:

```text
Codex = sviluppatore/reviewer AI
Claude Code = operatore AI su issue, PR e workflow
GitHub Actions = orchestratore
CI = giudice
CD = esecutore controllato
Prometheus/Grafana = osservabilità (metriche)
Loki = osservabilità (log)
Runbook = guardrail operativo
Branch protection = guardrail di piattaforma
Tu = approvatore finale
```

Il risultato:

```text
Yii3 template
+ CI/CD verificato
+ monitoring + alerting
+ log centralizzati
+ runbook
+ Codex
+ Claude Code
= piattaforma web moderna AI-assisted
```
