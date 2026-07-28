# Deploy manuale dal VPS

## Quando usarlo

Serve un deploy fuori dal flusso automatico (CD spento, immagine specifica da
portare in produzione).

## Procedura

Il percorso canonico è **lo stesso script usato dal CD** — include migration,
ricreazione esplicita, invariante immagine, health check e rollback
automatico. Il **backup pre-deploy però no**: nel CD è uno step separato,
a mano va lanciato prima:

```bash
bash /opt/yii3/scripts/backup-db.sh

# senza APP_IMAGE vale quello di .env.prod; per una versione precisa:
APP_IMAGE=ghcr.io/lucaarcudi/yii3-template:<sha> bash /opt/yii3/scripts/deploy.sh
```

In alternativa: GitHub → Actions → CD → *Run workflow*, con l'input
`image_tag` facoltativo.

## Cosa NON fare

- Non deployare con sequenze compose manuali (`pull` + `up`) al posto dello
  script: si perdono invariante immagine e rollback automatico (e le
  migration girerebbero senza backup, se si salta anche il passo sopra).
- Non usare il tag `latest` per un deploy mirato: è mobile, usare lo SHA.

## Istruzioni per l'AI

- Operazione di produzione: solo su richiesta esplicita dell'utente
  (AGENTS.md, Safety Boundaries).
- Non modificare lo script sul VPS: la fonte è `scripts/deploy.sh` nel repo.
