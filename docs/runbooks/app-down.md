# App giù

## Sintomi

- Alert `UpstreamProxyDown` (critical: `caddy_reverse_proxy_upstreams_healthy == 0`)
  o `TargetDown`; sito irraggiungibile o 502 dal proxy.
- **Prima di tutto guardare la label `upstream` dell'alert**: il proxy serve
  sia l'app sia Grafana. Questo runbook riguarda solo l'upstream dell'app;
  se a essere giù è l'upstream Grafana, il problema è dello stack
  monitoring, non dell'applicazione.

## Verifiche immediate

Alias `$DC`: vedi [stato-e-log.md](stato-e-log.md).

1. `$DC ps` — il container `app` è up? è in restart loop?
2. Health check diretto:
   `curl -fsS -H 'X-Forwarded-Proto: https' http://127.0.0.1:8080/login`
3. `$DC logs app --tail=200` — crash, errori fatali, OOM.
4. C'è un deploy recente? (`git -C /opt/yii3 log -1`, ultimi run CD):
   se sì, passare a [deploy-failed.md](deploy-failed.md).
5. Escludere cause a monte: disco pieno ([disk-full.md](disk-full.md)),
   DB giù ([db-down.md](db-down.md)).

## Azioni sicure

Rialzare l'app **pinnando l'immagine attualmente in esecuzione** — mai con
`latest`, che sul VPS è stantio ora che il CD deploya tag SHA (incidente
già vissuto: un recreate con `latest` ha riportato in produzione l'immagine
del giorno prima):

```bash
# risolvere l'immagine dal container, anche se fermo (-a); ps senza -a
# non elenca i container exited e restituirebbe stringa vuota
cid=$($DC ps -aq app)
CURRENT=$(docker inspect --format '{{.Config.Image}}' "$cid")

# GUARDIA: se $cid o $CURRENT sono vuoti, FERMARSI QUI — con APP_IMAGE
# vuoto il compose ricade su :latest (stantio). In quel caso fare un
# deploy mirato con lo SHA dell'ultimo run CD verde (deploy-manuale.md).
[ -n "$CURRENT" ] && APP_IMAGE="$CURRENT" $DC up -d app
```

In alternativa il percorso canonico completo:
`ansible-playbook playbooks/app.yml` (ricrea pinnando l'immagine in
esecuzione) oppure un deploy mirato ([deploy-manuale.md](deploy-manuale.md)).

## Cosa NON fare

- Mai `up -d --force-recreate` senza pinnare `APP_IMAGE`.
- Non riavviare il proxy o il DB "per sicurezza" se il sintomo è solo
  sull'app.

## Istruzioni per l'AI

- Diagnosi in sola lettura sempre consentita; il riavvio è un'operazione di
  produzione, solo su richiesta esplicita.
- Raccogliere per il report: alert e timestamp, commit deployato, ultimo run
  CD, `$DC ps`, ultimi log, esito health check.
