# Fix di una CI rossa

Input: una PR con CI fallita e il log del job. Nel loop orchestrato è il
prompt del re-dispatch su `CI_FAILED` (tentativi limitati, poi
`NEEDS_HUMAN`).

---

La CI è fallita su questa PR. Correggila.

PR:

{{PR_O_BRANCH}}

Log del job fallito:

{{LOG_O_LINK_RUN}}

Regole:

- Correggi la **causa radice**, non il sintomo.
- È vietato indebolire CI, test, scansioni o baseline per far passare il
  run (regola di `AGENTS.md`): se il check ha ragione, il difetto è nel
  codice.
- Resta nel perimetro della PR: se il fallimento rivela un problema più
  grande, riportalo invece di allargare il diff.
- Se il fallimento **non dipende dalla PR** (flaky, infrastruttura, base
  image cambiata), dillo esplicitamente con le prove: non forzare un fix
  nel posto sbagliato.

Consegna: commit aggiuntivi sul branch della PR, con la spiegazione di
causa → fix, e l'esito delle verifiche rieseguite in locale dove possibile.

---
