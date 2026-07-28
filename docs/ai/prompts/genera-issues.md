# Genera issue dall'analisi approvata

Input: un'analisi tecnica **approvata**. Output: issue granulari pronte per
`.github/ISSUE_TEMPLATE/` (feature o ai-task), ordinate per dipendenza.

---

Da questa analisi tecnica approvata deriva l'elenco delle issue di
implementazione.

Analisi approvata:

{{ANALISI_TECNICA_O_LINK}}

Regole:

- Una issue = un'unità di lavoro consegnabile con una PR piccola e
  rivedibile; se un passo dell'analisi è troppo grande, spezzalo.
- Ordina per dipendenza e dichiarala ("richiede #passo-N").
- Non aggiungere lavoro non presente nell'analisi: se manca qualcosa,
  segnalalo a parte come "fuori analisi, da approvare".

Per ogni issue produci, nel formato del template di repo:

- titolo (imperativo, specifico);
- contesto e obiettivo (2-4 frasi);
- criteri di accettazione verificabili;
- file e riferimenti (dall'analisi);
- verifiche da eseguire prima della consegna.

---
