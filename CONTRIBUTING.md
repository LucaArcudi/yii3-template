# Contribuire

Grazie dell'interesse! Il progetto è rilasciato sotto licenza [MIT](LICENSE):
contribuendo accetti che il tuo codice sia distribuito con la stessa licenza.

## Flusso

1. Forka il repository e crea un branch a partire da `main`.
2. Sviluppa in locale con l'ambiente Docker (vedi [README](README.md)).
3. Prima di aprire la PR devono passare in locale:

   ```bash
   make cs-fix   # stile (PHP CS Fixer)
   make psalm    # analisi statica
   make test     # suite Codeception
   ```

4. Apri la PR verso `main` descrivendo cosa cambia e perché. La CI deve
   essere verde. Tieni presente che `main` è protetto e al merge deploya
   automaticamente in produzione: PR piccole e focalizzate hanno vita facile.

## Convenzioni

- Stile secondo `.php-cs-fixer.php` (`make cs-fix` sistema quasi tutto da solo).
- Messaggi di commit brevi, all'imperativo; italiano o inglese indifferente.
- Per refactoring meccanici è disponibile `make rector`.
- Aggiorna `CHANGELOG.md` per le modifiche visibili agli utenti.

## Segnalazioni

- Bug e proposte: apri una issue.
- **Vulnerabilità di sicurezza: non aprire issue pubbliche** — usa la
  segnalazione privata di GitHub (tab *Security* → *Report a vulnerability*).
