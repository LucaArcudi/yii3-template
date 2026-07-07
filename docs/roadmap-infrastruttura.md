# Roadmap infrastruttura e osservabilità

Evoluzioni dell'infrastruttura di produzione e dello stack di osservabilità.
Sono prerequisiti tecnici indipendenti dall'AI; la roadmap che li usa come
base (incident automation, fix CI assistiti, ecc.) vive in
[roadmap-ai-codex-claude-code.md](roadmap-ai-codex-claude-code.md).
Lo stato attuale di CI/CD e monitoring è documentato in
[documentazione-progetto.md](documentazione-progetto.md) §8; i limiti noti
minori (provisioning Ansible, deploy su tag SHA) in §10.

## Stato attuale (2026-07-05)

```text
✔ CI severa: build, Trivy, composer validate/audit, Psalm, Codeception,
  doppia validazione migration, verifica artefatto prod, promtool
✔ CD versionato in scripts/: backup con retention, migrate:up,
  ricreazione esplicita dell'app, invariante immagine, health check
✔ monitoring: Prometheus + node/cadvisor/mysqld exporter + metriche HTTP
  di Caddy; Grafana pubblica in TLS; 6 regole di alert versionate
✔ container unhealthy → restart (restart: unless-stopped + healthcheck)
✔ runbook operativi in documentazione-progetto.md §9
```

I tre cantieri aperti, in ordine di priorità:

---

## 1. Centralizzazione dei log

Stato attuale dei log, sparsi su tre livelli:

```text
- docker logs <container>          (stdout/stderr, si perdono al ricreate
                                    se non si usa un driver persistente)
- runtime/logs/app.log             (errori applicativi, volume runtime)
- core_log                         (audit di dominio, tabella MySQL)
```

Target consigliato, coerente con lo stack esistente (Grafana già in piedi):

```text
Loki (storage log) + Alloy o Promtail (agente di raccolta)
    → nuovo servizio nello stack docker/monitoring/
    → raccoglie stdout/stderr di TUTTI i container via Docker service discovery
    → bind del file runtime/logs/app.log per i log applicativi
    → datasource Loki provisionato in Grafana accanto a Prometheus
    → retention 14-30 giorni (allineata ai backup)
```

Benefici immediati:

```text
- i log sopravvivono alla ricreazione dei container (oggi ogni deploy
  ricrea l'app e azzera docker logs)
- query LogQL da Grafana: una sola UI per metriche e log
- diagnosi senza SSH: un estratto di log è un link a una query Loki,
  non un dump incollato a mano
```

Alternative valutate e scartate per un singolo VPS: ELK/OpenSearch
(troppo pesante), servizi SaaS (costo e dati fuori dal server).

---

## 2. Notifiche degli alert

Stato attuale: 6 regole di alert Prometheus versionate e validate, ma
senza canale di notifica — gli alert si vedono solo nelle UI.

Primo passo, notifiche umane:

```text
Grafana contact point (Telegram o email)
+ alert rule Grafana che rilancia ALERTS di Prometheus,
oppure Alertmanager dedicato nello stack monitoring.
```

Il passo successivo (webhook → issue incident GitHub per la diagnosi
assistita) è descritto nella
[roadmap AI](roadmap-ai-codex-claude-code.md) e presuppone questo.

---

## 3. Self-healing deterministico

Non richiede AI ed è il livello di resilienza più importante. In gran
parte GIÀ attivo:

```text
✔ container unhealthy → restart (restart: unless-stopped + healthcheck)
✔ deploy: migrate PRIMA dell'avvio, app sempre ricreata, invariante
  immagine (il drift fa fallire il run), health check con retry
✔ backup pre-deploy con retention e guardia sul dump vuoto
✔ alert Prometheus su CPU/RAM/disco/target/MySQL/upstream
```

Prossimo step:

```text
- rollback automatico su smoke test fallito
  (oggi manuale: runbook §9.3, APP_IMAGE=<sha> + up)
```
