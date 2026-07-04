Deploy Yii3 Template

Questo progetto usa Docker Compose, GitHub Actions e GHCR per una pipeline CI/CD semplice su VPS.

Flusso attuale:

push su main
→ CI
→ Trivy
→ build immagine Docker
→ push su GHCR
→ CD automatico
→ backup DB
→ pull immagine su VPS
→ docker compose up -d
→ health check
Server

VPS Contabo:

IP: <VPS_IP>
Percorso progetto: /opt/yii3
Utente deploy: deploy

L’utente deploy è usato per deploy, Docker Compose, backup e tunnel DB.

Root va tenuto solo come accesso di emergenza.

File importanti
.env.prod                         segreti reali di produzione, NON committare
.env.prod.example                 esempio sicuro versionabile
docker/prod/compose.yml           compose produzione versionato
docker/prod/compose.local.yml     override locale VPS, NON committare se contiene config specifica
.github/workflows/ci.yml          CI, Trivy, build e push immagine GHCR
.github/workflows/cd.yml          CD automatico/manuale
backups/                          backup DB generati sul server
Accesso SSH

Da PC locale:

ssh deploy@<VPS_IP>

Se configurato in ~/.ssh/config:

ssh yii3-vps
Verifica stato VPS
cd /opt/yii3

docker compose \
  --env-file .env.prod \
  -f docker/prod/compose.yml \
  -f docker/prod/compose.local.yml \
  ps

Log app:

docker compose \
  --env-file .env.prod \
  -f docker/prod/compose.yml \
  -f docker/prod/compose.local.yml \
  logs app --tail=100

Log DB:

docker compose \
  --env-file .env.prod \
  -f docker/prod/compose.yml \
  -f docker/prod/compose.local.yml \
  logs db --tail=100
Deploy automatico

Il deploy parte automaticamente dopo CI verde su branch main.

Il workflow CD:

entra in SSH sulla VPS come deploy;
entra in /opt/yii3;
crea un backup DB;
esegue docker compose pull;
esegue docker compose up -d;
verifica container;
fa health check HTTP locale.
Deploy manuale da GitHub Actions

È ancora possibile lanciare il deploy manualmente:

GitHub → Actions → CD → Run workflow

Serve se vuoi rilanciare un deploy senza fare un nuovo push.

Deploy manuale da VPS

Da VPS:

ssh deploy@<VPS_IP>
cd /opt/yii3

Pull immagine:

docker compose \
  --env-file .env.prod \
  -f docker/prod/compose.yml \
  -f docker/prod/compose.local.yml \
  pull

Riavvio container:

docker compose \
  --env-file .env.prod \
  -f docker/prod/compose.yml \
  -f docker/prod/compose.local.yml \
  up -d

Verifica:

docker compose \
  --env-file .env.prod \
  -f docker/prod/compose.yml \
  -f docker/prod/compose.local.yml \
  ps

Health check:

curl -fsS http://127.0.0.1:8080 > /dev/null
Backup manuale DB

Il CD esegue già un backup prima del deploy.

Per fare un backup manuale:

cd /opt/yii3

mkdir -p /opt/yii3/backups

docker compose \
  --env-file .env.prod \
  -f docker/prod/compose.yml \
  -f docker/prod/compose.local.yml \
  exec -T db sh -lc 'mysqldump --no-tablespaces -u"$MYSQL_USER" -p"$MYSQL_PASSWORD" "$MYSQL_DATABASE"' \
  > /opt/yii3/backups/db_$(date +%F_%H-%M-%S).sql

Verifica backup:

ls -lh /opt/yii3/backups
head -n 20 /opt/yii3/backups/*.sql
tail -n 20 /opt/yii3/backups/*.sql

Retention consigliata:

find /opt/yii3/backups -type f -name "db_*.sql" -mtime +7 -delete
Tunnel DB

Il DB non deve essere esposto pubblicamente.

Da PC locale:

ssh -N -L 3307:127.0.0.1:3307 deploy@<VPS_IP>

Se il terminale resta fermo, il tunnel è attivo.

In DBeaver/HeidiSQL:

Host: 127.0.0.1
Port: 3307
User: valore MYSQL_USER da .env.prod
Password: valore MYSQL_PASSWORD da .env.prod
Database: valore MYSQL_DATABASE da .env.prod
Login GHCR sulla VPS

La VPS deve poter fare pull da GHCR.

Login manuale:

docker login ghcr.io

L’immagine attuale è:

ghcr.io/lucaarcudi/yii3-template:latest
Secrets GitHub Actions

Repository secrets necessari:

VPS_HOST
VPS_USER
VPS_SSH_KEY

Esempio:

VPS_HOST=<VPS_IP>
VPS_USER=deploy
VPS_SSH_KEY=chiave privata SSH dedicata a GitHub Actions

La chiave pubblica corrispondente deve essere presente sulla VPS in:

/home/deploy/.ssh/authorized_keys
Aggiornamento database

Gli script in /docker-entrypoint-initdb.d vengono eseguiti solo alla prima inizializzazione del volume DB.

Su database già esistente, applicare eventuali patch manualmente:

cd /opt/yii3

docker compose \
  --env-file .env.prod \
  -f docker/prod/compose.yml \
  -f docker/prod/compose.local.yml \
  exec -T db sh -lc 'mysql -u"$MYSQL_USER" -p"$MYSQL_PASSWORD" "$MYSQL_DATABASE"' \
  < database/migrations/release_1_0_2.sql
Rollback base

Attualmente il deploy usa latest.

Rollback manuale possibile usando un tag noto:

cd /opt/yii3

APP_IMAGE=ghcr.io/lucaarcudi/yii3-template:<tag-precedente> docker compose \
  --env-file .env.prod \
  -f docker/prod/compose.yml \
  -f docker/prod/compose.local.yml \
  pull app

APP_IMAGE=ghcr.io/lucaarcudi/yii3-template:<tag-precedente> docker compose \
  --env-file .env.prod \
  -f docker/prod/compose.yml \
  -f docker/prod/compose.local.yml \
  up -d app

Verifica:

curl -fsS http://127.0.0.1:8080 > /dev/null

Per rendere persistente il rollback, aggiornare APP_IMAGE in .env.prod.

Note pre-Ansible

Prima di introdurre Ansible, lo stato attuale deve rimanere chiaro:

deploy manuale funzionante
deploy automatico funzionante
backup DB pre-deploy funzionante
utente deploy funzionante
root solo emergenza
DB accessibile solo via tunnel SSH

Ansible servirà dopo per automatizzare provisioning e configurazione server:

install Docker
creazione utente deploy
configurazione SSH
firewall
directory /opt/yii3
file compose
backup
hardening base