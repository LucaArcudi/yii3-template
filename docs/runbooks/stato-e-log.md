# Stato e log in produzione

Base comune a tutti i runbook: accesso, alias compose, letture di stato.

## Contesto

```bash
ssh deploy@<VPS_IP>
cd /opt/yii3

# alias della tripletta compose usato in tutti i runbook
DC='docker compose --env-file .env.prod -f docker/prod/compose.yml -f docker/prod/compose.local.yml'
```

## Verifiche

```bash
$DC ps                      # stato container
$DC logs app --tail=100     # log applicazione
$DC logs db --tail=100      # log MySQL
git -C /opt/yii3 log -1     # commit del checkout allineato all'ultimo deploy
```

Health check dell'app (l'header è obbligatorio, vedi
[diagnosi-500.md](diagnosi-500.md)):

```bash
curl -fsS -H 'X-Forwarded-Proto: https' http://127.0.0.1:8080/login >/dev/null && echo OK
```

I log di tutti i container sono anche centralizzati in Loki (interrogabili da
Grafana, retention 14 giorni), incluso `runtime/logs/app.log`.

## Istruzioni per l'AI

- Tutti i comandi di questa pagina sono in sola lettura: sempre sicuri.
- Non leggere né riportare il contenuto di `.env.prod` o di altri file con
  segreti.
