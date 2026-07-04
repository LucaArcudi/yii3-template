# Changelog

## Unreleased

- Adottato `yiisoft/db-migration`: gli snapshot SQL di release sono eseguiti da una catena di migration (`App\Migrations`) validata in CI sia per idempotenza su DB esistente sia per bootstrap da database vuoto.
- Aggiunto comando console `user:create` per il primo utente admin (password generata stampata una sola volta, ruolo di default `ADMIN`).
- CD: step `migrate:up` tra backup e avvio della nuova versione dell'app.
- Corretto l'ordine degli script initdb.d nei compose (root e prod): `release_1_0_2` è lo schema base completo e deve precedere le altre release, che ne referenziano le tabelle via FK.
- Inclusa `database/` nell'immagine prod (`.dockerignore` è una allowlist e la escludeva: il `migrate:up` del CD legge gli snapshot da `/app/database`); nuovo step CI che verifica l'artefatto prod (file richiesti dal deploy e bit di esecuzione).

## 1.0.0 - 2026-05-02

- Aggiunto dominio notifiche con tabelle, repository, reader, centro notifiche, apertura con mark-read e dropdown ArchitectUI in header con badge non lette.
- Aggiunta notifica automatica su login utente.
- Rimossa la persistenza delle impostazioni globali: logo login, logo header e footer sono configurati via Param/env.
- Rimossi fallback e seed dei permessi `VIEW` generici; restano `VIEW_ALL` e `VIEW_OWN`.
- Aggiunti widget riusabili `Tabs`, `CardList` e `Pagination`.
- Aggiunta vista task a card con FilterBar e paginazione, affiancata alla GridView tramite tabs.
- Aggiunti script `database/migrations/release_1_0_0.sql` e `database/seeders/release_1_0_0.sql`.
- Aggiunti test unitari per widget tabs.
