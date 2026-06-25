# Yii3 Template

Applicazione Yii3 con tema ArchitectUI e domini admin per utenti, ruoli, permessi, menu, task e notifiche.

## Release 1.0.0

Prima del deploy applicare gli script SQL idempotenti:

```powershell
Get-Content database\migrations\release_1_0_0.sql | mysql -uroot yii3_template
Get-Content database\seeders\release_1_0_0.sql | mysql -uroot yii3_template
```

Gli script aggiungono:

- `core_notification` e `core_notification_user` per notifiche utente.
- layout configurato via Param/env (`APP_LOGO`, `APP_LOGO_SMALL`, `APP_FOOTER_LEFT`, `APP_FOOTER_RIGHT`).
- pulizia dei permessi `*_VIEW` generici rimasti, sostituiti da `*_VIEW_ALL` e `*_VIEW_OWN`.

Il testo UI introdotto in questa release usa italiano come default. Non e presente una struttura locale di cataloghi traduzione nel progetto; quando verra aggiunta, le stringhe nuove sono concentrate nelle view/widget dei moduli `Notification` e `Task`.

## Verifica locale

```powershell
$env:APP_ENV='test'; vendor\bin\codecept.bat run Unit
$env:APP_ENV='test'; vendor\bin\codecept.bat run Functional
```

Per smoke test manuale:

```powershell
$env:APP_ENV='dev'; php yii serve --port=8088
```

## Trivy

Le scansioni locali usano l'immagine ufficiale `aquasec/trivy:0.71.2`, quindi non richiedono una installazione locale di Trivy. In questa fase sono in modalita report-only con `exit-code 0`.

```bash
make trivy
```

Per scansionare anche l'immagine Docker:

```bash
docker compose -f compose.yml build app
make trivy-image
```

Sono esclusi `.local`, `dump`, `dumps`, `backup`, `backups`, `.git`, `vendor`, `.env` e i dump compressi/locali configurati in `trivy.yaml`. In CI l'action e pinnata a `aquasecurity/trivy-action@v0.36.0`; per contesti piu rigidi sostituire il tag con un commit SHA verificato.

## GitHub Container Registry

La CI pubblica l'immagine del servizio `app` su `ghcr.io/<owner>/<repo>` solo su `push` verso `main`, dopo il job di test e Trivy. Usa `GITHUB_TOKEN` con permessi `contents: read` e `packages: write`, e pubblica i tag `${GITHUB_SHA}` e `latest`.

## Produzione

La configurazione Docker di produzione usa l'immagine GHCR `ghcr.io/tuo-user/yii3-template:latest` tramite `docker/prod/compose.yml`. Non esegue build locali dell'app nel compose di produzione. Per sovrascrivere il riferimento senza cambiare il file, usare `PROD_IMAGE` e `PROD_IMAGE_TAG`.

Per il deploy manuale ripetibile vedere `README_DEPLOY.md`.
