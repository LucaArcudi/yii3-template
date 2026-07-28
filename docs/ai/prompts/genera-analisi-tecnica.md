# Genera analisi tecnica

Primo passo del loop. Input: una richiesta tecnica (issue `Richiesta
tecnica`). Output: il documento che l'umano approva **prima** di qualunque
implementazione. In caso di **rifiuto**, le note dell'approvatore diventano
input obbligatorio della revisione: si rilancia questo stesso prompt
compilando il blocco "revisione dopo un rifiuto".

---

Analizza questa richiesta tecnica e produci un'analisi tecnica. Non
implementare nulla: l'output di questo lavoro è solo il documento.

Richiesta tecnica:

{{RICHIESTA_TECNICA_O_LINK_ISSUE}}

Se questa è una revisione dopo un rifiuto:

- Analisi precedente: {{ANALISI_PRECEDENTE_O_LINK}}
- Note di rifiuto dell'approvatore: {{NOTE_DI_RIFIUTO}}

Le note di rifiuto hanno precedenza su qualunque scelta dell'analisi
precedente: nella nuova analisi rispondi esplicitamente a ciascuna nota,
indicando cosa cambia di conseguenza.

Regole:

- Leggi il codice reale prima di proporre: ogni file citato deve esistere,
  con il percorso esatto.
- Segui i pattern del repository; non proporre architetture nuove.
- Dichiara esplicitamente i punti di incertezza e come verificarli.
- Se la richiesta è ambigua o contraddittoria, fermati su domande precise
  invece di scegliere arbitrariamente.

Struttura dell'output:

1. **Obiettivo** — riformulato con parole tue.
2. **Criteri di accettazione** — ripresi dalla richiesta tecnica e
   raffinati in forma verificabile (sono l'input di `genera-issues.md`:
   se non li riporti qui, si perdono nel passo successivo).
3. **Approccio proposto** — e alternative scartate, con il motivo.
4. **File e moduli coinvolti** — percorsi reali.
5. **Piano a passi** — ognuno piccolo e verificabile.
6. **Migration e dati** — se servono, con impatto sul DB esistente.
7. **Test previsti** — quali e dove.
8. **Rischi e punti di incertezza** — cosa va verificato prima o durante.
9. **Stima di massima** — per passo.

---
