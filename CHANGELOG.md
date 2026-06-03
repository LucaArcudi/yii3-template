# Changelog

## 1.0.0 - 2026-05-02

- Aggiunto dominio notifiche con tabelle, repository, reader, centro notifiche, apertura con mark-read e dropdown ArchitectUI in header con badge non lette.
- Aggiunta notifica automatica su login utente.
- Rimossa la persistenza delle impostazioni globali: logo login, logo header e footer sono configurati via Param/env.
- Rimossi fallback e seed dei permessi `VIEW` generici; restano `VIEW_ALL` e `VIEW_OWN`.
- Aggiunti widget riusabili `Tabs`, `CardList` e `Pagination`.
- Aggiunta vista task a card con FilterBar e paginazione, affiancata alla GridView tramite tabs.
- Aggiunti script `database/migrations/release_1_0_0.sql` e `database/seeders/release_1_0_0.sql`.
- Aggiunti test unitari per widget tabs.
