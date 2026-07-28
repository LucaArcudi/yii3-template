# Prompt ricorrenti del loop di sviluppo

Principio (fissato nel recap di progetto): **le regole invarianti stanno nel
contesto del repo** (`AGENTS.md`, importato da `CLAUDE.md`), **il prompt
contiene solo la task**. Questi file sono i prompt del loop, versionati per
essere collaudati a mano oggi e dispacciati dall'orchestratore domani.

| Fase del loop | Prompt |
|---|---|
| Richiesta tecnica → analisi | [genera-analisi-tecnica.md](genera-analisi-tecnica.md) |
| Analisi approvata → issue granulari | [genera-issues.md](genera-issues.md) |
| Issue → implementazione | [implementa-issue.md](implementa-issue.md) |
| CI rossa su una PR → fix | [fix-ci-failure.md](fix-ci-failure.md) |
| PR → review | [review-pr.md](review-pr.md) |
| Incident di produzione → diagnosi | [incident-diagnosis.md](incident-diagnosis.md) |

Convenzioni: i segnaposto sono `{{COSÌ}}`; il blocco tra i separatori `---`
è il prompt da usare, il resto è istruzione per chi lo usa. Ogni modifica a
questi prompt va trattata come una modifica di comportamento (CHANGELOG).

Ogni gate umano ha **due esiti**: **approvazione** → il loop prosegue
(analisi approvata → implementazione; PR approvata → merge, sempre
manuale), oppure **rifiuto con note** → il loop torna indietro
(note sull'analisi → revisione via `genera-analisi-tecnica.md`, blocco
"revisione dopo un rifiuto"; "Request changes" sulla PR → rework
dell'agente sul branch). Nessun gate è un passaggio automatico e l'AI non
li scavalca mai: niente merge, niente implementazione senza analisi
approvata.
