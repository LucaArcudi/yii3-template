# Infrastruttura e osservabilità

Stato consolidato al 20 agosto 2026. Il backlog normativo è nel
[piano di miglioramento](../PIANO_MIGLIORAMENTO_TEMPLATE.md); questo documento
riassume capacità e rischi dell'infrastruttura corrente.

## Capacità implementate

- CI su push e pull request con validazione di workflow, shell e Compose,
  Composer audit/dependency analysis, PHP CS Fixer, Psalm, Codeception,
  migration e gate Trivy.
- Build unica dell'immagine prod, verifica dell'artefatto e pubblicazione su
  GHCR con tag SHA e `latest` soltanto da `main`.
- CD versionata in `scripts/`: preflight, checkout dello stesso SHA, backup,
  migration, avvio con healthcheck e rollback dell'immagine su deploy fallito.
- Backup con retention di 14 giorni, directory `0700`, dump `0600` e guardia
  contro file vuoti.
- Drill completo dump/drop/restore su MySQL isolato in ogni CI e tramite
  schedule settimanale sulla branch predefinita.
- Prometheus, Grafana, node-exporter, cAdvisor e mysqld-exporter; metriche HTTP
  del proxy e sei regole di alert versionate.
- Loki e Alloy per log container/applicazione con retention di 14 giorni.
- Notifiche Telegram provisionate nello stack Grafana.
- Runbook separati per stato, deploy, rollback, database e incidenti.

Le configurazioni di Prometheus, Loki e Alloy sono validate in CI. Immagini
operative, basi Docker e GitHub Actions sono fissate rispettivamente a digest
o commit SHA e censite da Dependabot.

## Semantica di health e recovery

L'immagine prod contiene un `HEALTHCHECK`; Compose lo usa durante
`up --wait` e lo script di deploy aggiunge una verifica HTTP con retry. Se il
nuovo container non supera il deploy, viene ripristinata l'immagine precedente.

La policy `restart: unless-stopped` interviene quando il processo termina o il
daemon riparte. Docker non riavvia automaticamente un container che resta vivo
ma diventa `unhealthy`: in esercizio il problema viene rilevato da healthcheck,
metriche e alert e gestito con i runbook. Per questo il repository non dichiara
un generico “self-healing” a runtime.

Il restore sintetico della CI prova la procedura e le migration, ma non prova
la leggibilità dei backup reali del VPS. Un test periodico di quei dump richiede
un ambiente controllato e un'azione esplicita del proprietario.

## Rischi e attività esterne residue

- **GitHub:** configurare Environment `production`, Secrets, Variables,
  approval policy e ruleset; provare il rifiuto di un push diretto a `main`.
- **VPS:** verificare il primo deploy reale, il percorso pubblico, alert e
  restore di un dump reale senza modificare la produzione.
- **Accesso host:** valutare socket proxy o Docker rootless per proxy/Alloy e
  ridurre privilegi, device e mount di cAdvisor.
- **Supply chain:** rivalutare entro la scadenza le eccezioni `.trivyignore`
  legate a FrankenPHP, senza proroghe automatiche.
- **Metriche applicative:** aggiungere un endpoint di business soltanto dopo
  avere definito dati, cardinalità e retention.

## Possibile semplificazione del proxy

FrankenPHP incorpora già Caddy, ma oggi lo stack separato
`caddy-docker-proxy` gestisce TLS, discovery tramite label, pubblicazione di
Grafana e metriche HTTP. La sua eliminazione è una valutazione futura: prima
vanno ridisegnati tutti questi compiti e la persistenza dei certificati. Non è
necessaria per considerare completo il percorso Docker/CI/CD/monitoring del
template.
