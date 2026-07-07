# Roadmap infrastruttura e osservabilità

Evoluzioni dell'infrastruttura di produzione e dello stack di osservabilità.
Sono prerequisiti tecnici indipendenti dall'AI; la roadmap che li usa come
base (incident automation, fix CI assistiti, ecc.) vive in
[roadmap-ai-codex-claude-code.md](roadmap-ai-codex-claude-code.md).
Lo stato attuale di CI/CD e monitoring è documentato in
[documentazione-progetto.md](documentazione-progetto.md) §8; i limiti noti
minori (provisioning Ansible) in §10.

## Stato attuale (2026-07-07)

```text
✔ CI severa: build, Trivy, composer validate/audit, Psalm, Codeception,
  doppia validazione migration, verifica artefatto prod, promtool,
  validazione config Loki/Alloy
✔ CD versionato in scripts/: backup con retention, migrate:up,
  ricreazione esplicita dell'app, invariante immagine, health check,
  deploy sul tag SHA del commit, rollback automatico su deploy fallito
✔ monitoring: Prometheus + node/cadvisor/mysqld exporter + metriche HTTP
  di Caddy; Grafana pubblica in TLS; 6 regole di alert versionate
✔ log centralizzati: Loki + Alloy (container e log applicativo),
  datasource in Grafana, retention 14 giorni
✔ notifiche alert su Telegram (provisioning Grafana versionato)
✔ container unhealthy → restart (restart: unless-stopped + healthcheck)
✔ runbook operativi in documentazione-progetto.md §9
```

I tre cantieri, chiusi il 2026-07-07:

---

## 1. Centralizzazione dei log — ✔ FATTO (2026-07-07)

Implementato con Loki + Alloy nello stack `docker/monitoring/`
(documentazione in [documentazione-progetto.md](documentazione-progetto.md)
§8.9):

```text
✔ Alloy raccoglie stdout/stderr di TUTTI i container (Docker service
  discovery, socket read-only) + runtime/logs/app.log (volume app ro)
✔ Loki su filesystem (volume loki_data), retention 14 giorni allineata
  ai backup, solo rete interna
✔ datasource Loki provisionato in Grafana accanto a Prometheus
✔ config validate in CI (loki -verify-config, alloy fmt)
```

Benefici ottenuti: i log sopravvivono alla ricreazione dei container a
ogni deploy, query LogQL da Grafana (una sola UI per metriche e log),
diagnosi senza SSH (un estratto di log è un link a una query Loki).

Alternative valutate e scartate per un singolo VPS: ELK/OpenSearch
(troppo pesante), servizi SaaS (costo e dati fuori dal server);
Promtail scartato perché in maintenance mode (Alloy è il successore).

---

## 2. Notifiche degli alert — ✔ FATTO (2026-07-07)

Implementato con il provisioning alerting di Grafana
(`docker/monitoring/grafana/provisioning/alerting/`, vedi
[documentazione-progetto.md](documentazione-progetto.md) §8.9):

```text
✔ contact point Telegram (token e chat ID da docker/monitoring/.env)
✔ alert rule ponte che rilancia ALERTS{alertstate="firing"} di
  Prometheus: una notifica per (alertname, severity), repeat 4h
✔ notification policy versionata; le soglie restano SOLO nelle regole
  Prometheus validate da promtool
```

Alertmanager dedicato scartato in questa fase: un servizio in più senza
benefici finché il fan-out è un solo canale. Il passo successivo
(webhook → issue incident GitHub per la diagnosi assistita) è descritto
nella [roadmap AI](roadmap-ai-codex-claude-code.md) e presuppone questo.

---

## 3. Self-healing deterministico — ✔ FATTO (2026-07-07)

Non richiede AI ed è il livello di resilienza più importante:

```text
✔ container unhealthy → restart (restart: unless-stopped + healthcheck)
✔ deploy: migrate PRIMA dell'avvio, app sempre ricreata, invariante
  immagine (il drift fa fallire il run), health check con retry
✔ backup pre-deploy con retention e guardia sul dump vuoto
✔ alert Prometheus su CPU/RAM/disco/target/MySQL/upstream
✔ deploy sul tag SHA del commit (niente più latest): run riproducibili
✔ rollback automatico: deploy.sh registra l'immagine in esecuzione
  (digest) e la ripristina se up, invariante o health check falliscono;
  le migration non vengono annullate (runbook §9.4 per il restore)
```
