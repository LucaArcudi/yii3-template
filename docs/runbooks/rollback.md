# Rollback di una release

## Quando usarlo

Una release va ritirata: o il deploy è fallito (caso automatico), o la
release è "sana" ma difettosa e va sostituita con la precedente.

## Rollback automatico (deploy fallito)

Se avvio, invariante immagine o health check falliscono, `deploy.sh`
**ripristina da solo** l'immagine che girava prima (registrata per digest,
non per tag): il run del CD risulta rosso ma l'app resta sulla versione
precedente. Le migration **non** vengono annullate — per il restore del
backup pre-deploy vedi [backup-restore.md](backup-restore.md). Per l'analisi
del fallimento vedi [deploy-failed.md](deploy-failed.md).

## Rollback manuale (release sana da ritirare)

Ogni build è taggata con lo SHA del commit. Il percorso coerente è GitHub →
Actions → CD → *Run workflow*, passando come `image_tag` lo SHA completo
della release precedente: il workflow allinea allo stesso commit sia
l'immagine sia il checkout.

Solo se GitHub Actions non è disponibile e serve un ripristino immediato,
si può ricreare direttamente l'app sulla vecchia immagine. L'alias `$DC`
è lo stesso definito in [stato-e-log.md](stato-e-log.md):

```bash
cd /opt/yii3
DC='docker compose --env-file .env.prod -f docker/prod/compose.yml -f docker/prod/compose.local.yml'
# Sostituire il valore con lo SHA completo della release precedente.
TARGET_SHA='INSERIRE_SHA_COMPLETO'
APP_IMAGE="ghcr.io/lucaarcudi/yii3-template:${TARGET_SHA}" $DC pull app
APP_IMAGE="ghcr.io/lucaarcudi/yii3-template:${TARGET_SHA}" $DC up -d app
```

Questo fallback lascia temporaneamente immagine e checkout disallineati:
non eseguire migration o altri script del checkout e ripristinare appena
possibile il percorso canonico. Il prossimo run automatico del CD deploya lo
SHA del nuovo run su `main`; il rollback definitivo resta il **revert del
commit su `main` via PR**.

## Istruzioni per l'AI

- Il rollback manuale è un'operazione di produzione: solo su richiesta
  esplicita dell'utente.
- Il contributo utile dell'AI è la PR di revert su `main` (quella sì nel
  flusso normale branch → PR → CI).
