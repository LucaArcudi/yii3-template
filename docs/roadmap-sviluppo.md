# Backlog applicativo Yii3

Stato al 21 agosto 2026. Questo documento contiene esclusivamente le
funzionalità P6 e P7 del template Yii3. È separato dal percorso Docker, CI/CD e
monitoring, che è concluso e descritto nella
[roadmap infrastrutturale](roadmap-infrastruttura.md).

Hardening applicativo, integrità dei dati, supply chain, qualità e frontend
(P2-P5) restano nel
[piano di miglioramento](../PIANO_MIGLIORAMENTO_TEMPLATE.md) e non vengono
duplicati qui.

## P6 — Release 1.1

| Stato | Attività | Note |
|---|---|---|
| Aperto | Utente super | Aggiungere `is_super` a `core_user` ed escludere gli utenti super dalle liste previste, con migration, policy e test. |
| Aperto | Notifiche | Migliorare centro notifiche e canali mantenendo `Core/Notification` come punto di estensione. |
| Aperto | Select dipendenti | Implementare un esempio nei form e nei filtri statici senza autosubmit, preservando `FilterBar`. |

## P7 — Evoluzioni successive

| Stato | Attività | Note |
|---|---|---|
| Aperto | Multitenancy | Introdurre un modello semplice basato su `tenant_id`, definendo scope, indici, ownership e migrazione dei dati esistenti. |
| Aperto | Pagamenti Stripe | Definire confini del dominio, webhook, idempotenza, errori, dati sensibili e test. |

P7 viene valutato soltanto dopo la stabilizzazione della release 1.1.

## Come si lavora il backlog

Ogni attività segue il flusso standard del repo: richiesta → branch → PR →
CI verde → review → merge → deploy automatico. Una issue è facoltativa. Per
le modifiche di schema si usa `./yii migrate:create` (mai nuovi file initdb).
