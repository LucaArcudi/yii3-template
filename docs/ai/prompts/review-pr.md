# Review di una PR

Input: una PR aperta. Output: rilievi puntuali e un verdetto. La review AI
**non sostituisce** quella umana richiesta dal flusso: la prepara.

---

Fai la review di questa PR.

PR:

{{PR_O_DIFF}}

Verifica, nell'ordine:

1. bug evidenti e regressioni;
2. test mancanti rispetto ai cambiamenti funzionali;
3. migration rischiose (perdita dati, non idempotenti, ordine);
4. coerenza con i pattern del repository (moduli verticali, policy chiamate
   dalle action, DI/rotte per modulo);
5. documentazione e `CHANGELOG.md` mancanti a fronte di cambi di
   comportamento;
6. indebolimenti di CI, scansioni o baseline;
7. rischi di deploy: modifiche a compose/Dockerfile/workflow, nuove
   variabili d'ambiente richieste in produzione (il CD deploya ogni merge
   su `main`).

Regole:

- Non fidarti della descrizione della PR: verifica ogni claim sul diff e
  sul codice reale.
- Solo difetti concreti, con: `file:riga`, problema, severità, fix
  proposto. Niente rilievi di stile o preferenze.

Chiudi con il verdetto: **approva** o **richiedi modifiche**, elencando
separatamente ciò che è bloccante da ciò che è consigliato.

---
