# Infrastruttura e osservabilità

Stato consolidato al 21 agosto 2026. Il backlog normativo è nel
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
- Notifiche Telegram provisionate e verificate nello stack Grafana.
- Runbook separati per stato, deploy, rollback, database e incidenti.
- Repository Secrets/Variables configurati e `main` protetto da ruleset.

Le configurazioni di Prometheus, Loki e Alloy sono validate in CI. Immagini
operative, basi Docker e GitHub Actions sono fissate rispettivamente a digest
o commit SHA e censite da Dependabot.

## Verifica sul VPS

Il 21 agosto 2026, dopo la CD verde del commit `bb2a1b8`, è stato verificato
l'intero percorso operativo:

- Grafana 13.1.3 operativo con database `ok` e contact point Telegram testato
  con consegna reale;
- Prometheus `ready`, con i target `prometheus`, `node`, `cadvisor`, `mysql` e
  `caddy` tutti `UP`;
- `mysql_up == 1`, quindi exporter, rete interna e credenziali dedicate sono
  funzionanti;
- Loki `ready`, con etichette e log Docker ingeriti da Alloy consultabili in
  Grafana Explore.

Questa verifica chiude il percorso Docker, CI/CD e monitoring usato come
progetto dimostrativo. Non sono stati versionati token, password o altri valori
del file `docker/monitoring/.env` presente esclusivamente sul VPS.

## Semantica di health e recovery

L'immagine prod contiene un `HEALTHCHECK`; Compose lo usa durante
`up --wait` e lo script di deploy aggiunge una verifica HTTP con retry. Se il
nuovo container non supera il deploy, viene ripristinata l'immagine precedente.

La policy `restart: unless-stopped` interviene quando il processo termina o il
daemon riparte. Docker non riavvia automaticamente un container che resta vivo
ma diventa `unhealthy`: in esercizio il problema viene rilevato da healthcheck,
metriche e alert e gestito con i runbook. Per questo il repository non dichiara
un generico “self-healing” a runtime.

## Manutenzione ed estensioni non bloccanti

L'unica attività infrastrutturale calendarizzata è la decisione sulle due
eccezioni `.trivyignore` in scadenza il 31 agosto 2026. La rivalutazione
intermedia del 20 agosto ha confermato che FrankenPHP 1.12.7 contiene ancora i
moduli interessati; alla scadenza non è ammessa una proroga automatica.

Restano annotate come estensioni facoltative:

- GitHub Environment `production`, per introdurre in futuro eventuali
  approvazioni al deploy e Secrets/Variables limitati all'ambiente;
- restore periodico di un dump reale, oltre al drill isolato già eseguito in
  CI;
- socket proxy/Docker rootless e riduzione di capability e mount per proxy,
  Alloy e cAdvisor;
- dashboard Grafana community e metriche applicative di business;
- soluzione upstream alla visibilità per-container di cAdvisor con snapshotter
  `overlayfs`.

Il GitHub Environment non è necessario per l'assetto corrente, ma resta nel
backlog come possibile evoluzione. Ansible è stato invece rimosso
intenzionalmente. Le pull request Dependabot sono proposte di manutenzione
valutabili singolarmente, non criteri di completamento. La classificazione
completa è mantenuta nel §5 del
[piano di miglioramento](../PIANO_MIGLIORAMENTO_TEMPLATE.md).

## Possibile semplificazione del proxy

FrankenPHP incorpora già Caddy, ma oggi lo stack separato
`caddy-docker-proxy` gestisce TLS, discovery tramite label, pubblicazione di
Grafana e metriche HTTP. La sua eliminazione è una valutazione futura: prima
vanno ridisegnati tutti questi compiti e la persistenza dei certificati. Non è
necessaria per considerare completo il percorso Docker/CI/CD/monitoring del
template.
