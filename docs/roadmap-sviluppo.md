# Roadmap di sviluppo

Backlog funzionale del template, estratto dai componenti della dashboard
(2026-07-05) e aggiornato con lo stato reale. La roadmap infrastrutturale
vive in [roadmap-infrastruttura.md](roadmap-infrastruttura.md), quella AI
in [roadmap-ai-codex-claude-code.md](roadmap-ai-codex-claude-code.md);
lo stato di CI/CD e monitoring è documentato in
[documentazione-progetto.md](documentazione-progetto.md) §8.

## Fatto

| Attività | Esito |
|---|---|
| Migration e seeder | Adottato `yiisoft/db-migration`: catena `App\Migrations` validata in CI (idempotenza + bootstrap da zero), comando `user:create` per il primo admin. |
| Traduzioni | Italiano lingua di default, inglese selezionabile (`src/Shared/resources/messages/en/`). |
| Documentazione | README onboarding, CHANGELOG, documentazione di progetto completa in `docs/`, CONTRIBUTING, licenza MIT. |

## Backlog admin (pre 1.1)

| Attività | Note |
|---|---|
| Utente super | Flag `is_super` in `core_user` ed esclusione dalle liste utenti. |
| Notifiche | Migliorie al sistema di notifiche (centro notifiche, canali). |
| Form: select dipendenti | Gestire select dipendenti nei form di creazione/modifica e nei filtri statici senza autosubmit (nei filtri da modale; in FilterBar funzionano già in autosubmit). Implementare un esempio. |
| DB tweak | Chiavi esterne e indici: definire cancellazione a cascata o restrizione per utenti, ruoli e permessi. Da fare con una migration del framework. |

## Prossimi step (post 1.1)

| Attività | Note |
|---|---|
| Multitenancy | Integrazione tenant per multiutenza, implementazione semplice tramite `tenant_id`. |
| Pagamenti | Integrazione Stripe per la gestione dei pagamenti online. |

## Come si lavora il backlog

Ogni attività segue il flusso standard del repo: issue → branch → PR →
CI verde → review → merge → deploy automatico. Per le modifiche di schema
si usa `./yii migrate:create` (mai nuovi file initdb).
