# Aggiungere un nuovo dominio CRUD (checklist)

## Quando usarlo

Nuova entità gestionale con le classiche operazioni CRUD, nel rispetto del
layout a moduli verticali (vedi `AGENTS.md`, Project Structure).

## Checklist

1. Migration SQL idempotente in `database/migrations/` **e classe nella
   catena `App\Migrations`** (`src/Migrations/`, wrapper di
   `SqlSnapshotMigration` sul modello delle `M2026...` esistenti — è la
   catena che la CI valida e che il CD applica) (+ eventuale seed dei
   permessi `<DOMINIO>_VIEW_ALL/VIEW_OWN/CREATE/UPDATE/DELETE`);
2. classi in `src/<Modulo>/<Dominio>/` sul modello di `src/Mes/Task/`
   (Entity, Input, Repository, Reader, Filter, Policy, Presenter, Scope);
3. action in `src/<Modulo>/<Dominio>/Actions/` (Index/View/Create/
   Update/Delete, con `withViewPath('@src/<Modulo>/<Dominio>/views')` nel
   costruttore) e view in `src/<Modulo>/<Dominio>/views/`;
4. rotte in `src/<Modulo>/routes.php` e DI in `src/<Modulo>/di.php`,
   raccolti automaticamente dalla config (per un modulo nuovo basta creare
   i due file);
5. voce di menu in `src/Shared/Navigation/NavigationProvider.php` con la
   `policyClass` del dominio;
6. montare la nuova migration nei compose (`compose.yml` root e
   `docker/prod/compose.yml`) per il bootstrap da zero (initdb.d); **in
   produzione la applica il CD** con `migrate:up` al deploy — nessuna
   applicazione manuale;
7. test unit per Input/Reader e aggiornamento del `CHANGELOG.md`.

## Istruzioni per l'AI

- Ogni action deve chiamare la Policy del dominio: l'autorizzazione poggia
  su questa convenzione (non c'è middleware authz a livello routing).
- Seguire i pattern esistenti di `src/Mes/Task/`; niente architetture nuove.
