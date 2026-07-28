# Disco quasi pieno

## Sintomi

- Alert `DiskAlmostFull` (critical: spazio disponibile su `/` sotto il 15%);
  a cascata: MySQL che degrada o si ferma, deploy che falliscono in pull.

## Verifiche immediate

```bash
df -h /                          # quanto manca davvero
du -sh /opt/yii3/backups         # dump accumulati
docker system df                 # immagini/layer/volumi/cache build
journalctl --disk-usage          # log di sistema
```

## Azioni sicure

In ordine di sicurezza decrescente:

1. **Backup** — la retention automatica (14 giorni) elimina da sola
   qualunque `db_<timestamp>.sql`, anche manuale; ne sono esclusi solo i
   dump **rinominati** a mano (vedi [backup-restore.md](backup-restore.md)):
   quelli vanno valutati e rimossi consapevolmente, mai alla cieca.
2. **Immagini dangling**: `docker image prune -f` (solo layer senza tag,
   sicuro). Le immagini taggate vecchie di app si possono rimuovere
   **tranne** quella in esecuzione e la precedente (serve al rollback).
3. **Log di sistema**: `journalctl --vacuum-time=14d`.
4. Loki ha già retention 14 giorni: non è un accumulatore da pulire a mano.

## Cosa NON fare

- Mai `docker system prune -a --volumes`: cancella i volumi, incluso
  `db_data`.
- Mai toccare `/var/lib/docker` a mano.
- Non cancellare il backup pre-deploy più recente.

## Istruzioni per l'AI

- Diagnosi in sola lettura; qualunque rimozione solo su richiesta esplicita
  dell'utente, elencando prima cosa verrebbe rimosso e perché è sicuro.
