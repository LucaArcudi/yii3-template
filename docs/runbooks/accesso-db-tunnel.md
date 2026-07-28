# Accesso al DB dal PC locale (tunnel SSH)

## Quando usarlo

Ispezione dati o query manuali dal client SQL locale. Il DB non è mai
raggiungibile direttamente da internet.

## Procedura

```bash
ssh -N -L 3307:127.0.0.1:3307 deploy@<VPS_IP>
```

Poi collegarsi con il client SQL a `127.0.0.1:3307` usando le credenziali di
`.env.prod` (sul VPS).

## Cosa NON fare

- Nessuna scrittura manuale sui dati di produzione fuori da una procedura
  concordata ([backup-restore.md](backup-restore.md) per le patch).
- Non esporre la porta del DB su interfacce pubbliche "per comodità".

## Istruzioni per l'AI

- L'AI non apre tunnel né si collega al DB di produzione: questo runbook è
  una procedura per l'operatore umano.
