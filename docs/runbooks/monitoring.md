# Monitoring e notifiche Telegram

Lo stack `docker/monitoring/` è separato dai deploy applicativi e comprende
Prometheus, Grafana, node-exporter, cAdvisor, mysqld-exporter, Loki e Alloy.
Questa procedura configura lo stack sul VPS e ne verifica il funzionamento;
non esegue restore, modifiche ai volumi o altre operazioni distruttive.

## 1. Prerequisiti

Devono essere già attivi lo stack applicativo e il proxy Caddy. In particolare
devono esistere la rete `caddy_public`, la rete interna dell'app e il volume
runtime indicati in `docker/monitoring/.env`.

Il database richiede un utente dedicato. La password scelta deve coincidere con
`MYSQLD_EXPORTER_PASSWORD`; non va riutilizzata per l'applicazione. Collegarsi a
MySQL come amministratore e creare una sola volta l'utente read-only:

```sql
CREATE USER 'exporter'@'%' IDENTIFIED BY '<PASSWORD>'
  WITH MAX_USER_CONNECTIONS 3;
GRANT PROCESS, REPLICATION CLIENT, SELECT ON *.* TO 'exporter'@'%';
```

Il grant segue i requisiti ufficiali di `mysqld_exporter`. L'host `%` è
necessario perché l'exporter arriva dalla rete Docker interna; la porta MySQL
non deve essere esposta pubblicamente.

## 2. Creare il bot Telegram

1. Aprire la chat verificata `@BotFather` e inviare `/newbot`.
2. Scegliere nome e username del bot, quindi conservare il token restituito.
3. Per una chat privata, aprire il nuovo bot e inviargli `/start`; interrogare
   `https://api.telegram.org/bot<TOKEN>/getUpdates` e leggere
   `result[].message.chat.id`.
4. Per un gruppo, aggiungere il bot al gruppo e leggere l'ID dalla URL di
   Telegram Web: è il numero dopo `#` e normalmente è negativo.

Token e risposta di `getUpdates` non vanno incollati in issue, log o commit.
Se il token viene esposto, revocarlo subito tramite `@BotFather`.

## 3. Configurare e avviare lo stack

Sul VPS, dal checkout del progetto:

```bash
cp docker/monitoring/.env.example docker/monitoring/.env
chmod 600 docker/monitoring/.env
```

Compilare tutti i valori del file, inclusi `TELEGRAM_BOT_TOKEN` e
`TELEGRAM_CHAT_ID`, senza aggiungere quote. Verificare poi la configurazione e
avviare lo stack usando sempre il file esplicito:

```bash
docker compose --env-file docker/monitoring/.env \
  -f docker/monitoring/compose.yml config --quiet
docker compose --env-file docker/monitoring/.env \
  -f docker/monitoring/compose.yml pull
docker compose --env-file docker/monitoring/.env \
  -f docker/monitoring/compose.yml up -d --wait
docker compose --env-file docker/monitoring/.env \
  -f docker/monitoring/compose.yml ps
```

La CD dell'applicazione non ricrea lo stack monitoring: dopo una modifica alle
sue immagini o configurazioni, il comando `up -d --wait` resta manuale.

## 4. Verificare metriche, log e alert

Prometheus è pubblicato soltanto sul loopback del VPS. Verificarne la readiness:

```bash
curl -fsS http://127.0.0.1:9090/-/ready
```

Aprire un tunnel locale con `ssh -L 9090:127.0.0.1:9090 <utente>@<vps>` e
controllare in `http://127.0.0.1:9090/targets` che i target `prometheus`, `node`,
`cadvisor`, `mysql` e `caddy` siano `UP`. Il target HTTP di mysqld-exporter può
essere `UP` anche quando il database non è raggiungibile: in Prometheus eseguire
anche la query `mysql_up` e verificare che valga `1`.

In Grafana:

1. verificare in **Connections → Data sources** i datasource Prometheus e Loki;
2. in **Explore**, eseguire la query Loki `{job="docker"}` e controllare che
   arrivino log recenti;
3. in **Alerting → Alert rules**, verificare la regola
   `prometheus-alerts-firing`;
4. in **Alerting → Contact points**, aprire `telegram`, usare **Test** e
   confermare che il messaggio arrivi nella chat scelta.

Il test del contact point verifica il percorso Grafana → Telegram senza causare
un guasto reale. Non fermare servizi di produzione per forzare un alert.

## 5. Diagnosi rapida

```bash
docker compose --env-file docker/monitoring/.env \
  -f docker/monitoring/compose.yml logs --tail=200 prometheus grafana
docker compose --env-file docker/monitoring/.env \
  -f docker/monitoring/compose.yml logs --tail=200 loki alloy mysqld-exporter
```

- `mysql_up == 0`: verificare utente/password e appartenenza alla rete interna
  dell'app.
- Target `caddy` down: verificare `caddy-proxy`, `Caddyfile.base` e rete
  `caddy_public`.
- Nessun log in Loki: verificare i log di Alloy, il socket Docker e il nome del
  volume `APP_RUNTIME_VOLUME`.
- Test Telegram fallito: verificare token, chat ID e che il bot appartenga alla
  chat; per una chat privata deve essere stato inviato almeno `/start`.

Su WSL2 il mount `/:/host:ro,rslave` di node-exporter può non essere supportato.
La prova completa dello stack è destinata al VPS Linux; CI continua a validare
staticamente Compose e le configurazioni di Prometheus, Loki e Alloy.
