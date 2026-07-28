# Deploy fallito (run CD rosso)

## Sintomi

Run del workflow CD concluso in errore; eventualmente alert
`UpstreamProxyDown` se anche il ripristino non è andato a buon fine.

## Cosa è già successo da solo

`deploy.sh` è progettato per fallire in sicurezza:

- **fallimento in `migrate:up`** → la nuova versione non è mai partita:
  l'app resta sulla versione precedente, nessun rollback necessario;
- **fallimento in avvio / invariante immagine / health check** → rollback
  automatico sull'immagine precedente (via digest). L'app deve essere
  di nuovo su quella versione.

## Verifiche immediate

1. Log del run CD su GitHub: individuare lo **step esatto** fallito
   (backup → migrate → up → invariante → health) e l'errore.
2. Sul VPS ([stato-e-log.md](stato-e-log.md)): `$DC ps`, health check,
   `$DC logs app --tail=200` — confermare che la versione precedente giri.
3. Se il run dice "ROLLBACK FALLITO": app potenzialmente giù, passare
   subito ad [app-down.md](app-down.md).

## Azioni sicure

- Migration fallita che ha lasciato il DB a metà: restore del backup
  pre-deploy ([backup-restore.md](backup-restore.md)).
- Correzione della causa su un branch → PR → CI → merge: il CD rideploya.

## Cosa NON fare

- Non rilanciare il deploy "per vedere se passa" senza aver capito lo step
  fallito.
- Non applicare fix a mano sul VPS: ogni correzione passa dal repo.

## Istruzioni per l'AI

- Analizzare il log del run e proporre la diagnosi: quale step, quale causa.
- Il fix va consegnato come branch/PR (flusso normale); niente comandi di
  produzione senza richiesta esplicita.
- Se serve solo un'azione da runbook, citare il runbook giusto invece di
  inventare procedure.
