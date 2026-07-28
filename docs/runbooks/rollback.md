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

Ogni build è taggata con lo SHA del commit e il CD deploya proprio quel tag
(alias `$DC`: vedi [stato-e-log.md](stato-e-log.md)):

```bash
cd /opt/yii3
APP_IMAGE=ghcr.io/lucaarcudi/yii3-template:<sha-precedente> $DC pull app
APP_IMAGE=ghcr.io/lucaarcudi/yii3-template:<sha-precedente> $DC up -d app
```

**Attenzione**: il prossimo run del CD rideploya lo SHA del commit corrente
di `main` — il rollback definitivo è il **revert del commit su `main` via
PR**.

## Istruzioni per l'AI

- Il rollback manuale è un'operazione di produzione: solo su richiesta
  esplicita dell'utente.
- Il contributo utile dell'AI è la PR di revert su `main` (quella sì nel
  flusso normale branch → PR → CI).
