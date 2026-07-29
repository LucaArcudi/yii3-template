# Deploy manuale dal VPS

## Quando usarlo

Serve portare in produzione un'immagine specifica fuori dal trigger
automatico.

## Procedura

Il percorso canonico resta GitHub → Actions → CD → *Run workflow*. L'input
`image_tag` è obbligatorio e accetta esclusivamente lo SHA completo di 40
caratteri della release. Il workflow verifica immagine e commit, allinea
`/opt/yii3` allo stesso SHA, esegue backup, migration, ricreazione esplicita,
invariante immagine, health check ed eventuale rollback automatico.

## Percorso d'emergenza dal VPS

Usarlo solo se GitHub Actions non è disponibile. Gli script diretti non
allineano il checkout: prima di procedere, `HEAD` deve già coincidere con lo
SHA dell'immagine. In caso contrario fermarsi, senza improvvisare checkout o
reset sul server:

```bash
# Sostituire il valore con lo SHA completo di 40 caratteri.
TARGET_SHA='INSERIRE_SHA_COMPLETO'
test "$(git -C /opt/yii3 rev-parse HEAD)" = "$TARGET_SHA" || {
  echo "STOP: checkout e immagine non corrispondono"
  exit 1
}
bash /opt/yii3/scripts/backup-db.sh
APP_IMAGE="ghcr.io/lucaarcudi/yii3-template:${TARGET_SHA}" \
  bash /opt/yii3/scripts/deploy.sh
```

## Cosa NON fare

- Non deployare con sequenze compose manuali (`pull` + `up`) al posto dello
  script: si perdono invariante immagine e rollback automatico (e le
  migration girerebbero senza backup, se si salta anche il passo sopra).
- Non usare il tag `latest` per un deploy mirato: è mobile, usare lo SHA.
- Non usare SHA abbreviati e non riallineare il checkout con `reset --hard` o
  `checkout --force`.

## Istruzioni per l'AI

- Operazione di produzione: solo su richiesta esplicita dell'utente
  (AGENTS.md, Safety Boundaries).
- Non modificare lo script sul VPS: la fonte è `scripts/deploy.sh` nel repo.
