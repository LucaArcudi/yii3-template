# Diagnosi di un 500 dopo il deploy

## Sintomi

L'app risponde 500 (tutta o su alcune rotte) subito dopo un deploy, spesso
con CD verde.

## Verifiche immediate

Alias `$DC`: vedi [stato-e-log.md](stato-e-log.md).

1. `$DC logs app --tail=200` — l'error handler logga su
   stdout/`runtime/logs`;
2. ricordare che il curl di health **deve** includere
   `-H 'X-Forwarded-Proto: https'`: senza header la richiesta appare HTTP e
   il cookie di sessione `Secure` genera un 500 **fuorviante** (falso
   positivo del test, non un errore dell'app);
3. verificare che `.env.prod` contenga `AUTH_COOKIE_SECRET_KEY` non di
   default e `DB_PASSWORD` corretti (il compose fallisce fast se mancano) —
   verificare la **presenza**, non leggere i valori;
4. seguire i controlli completi di [stato-e-log.md](stato-e-log.md), senza
   modificare configurazione o container durante la diagnosi.

## Azioni sicure

- Se la causa è nel codice: fix su branch → PR → CI → merge → CD.
- Se l'ultimo deploy è la causa e serve tempo: rollback
  ([rollback.md](rollback.md)).

## Istruzioni per l'AI

- Diagnosi in sola lettura; il fix passa dal repo, mai a mano sul VPS.
- Non stampare valori di `.env.prod` nei log o nelle risposte.
