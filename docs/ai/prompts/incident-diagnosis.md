# Diagnosi di un incident di produzione

Input: il pacchetto di evidenze raccolto con i runbook (issue `Incident`).
I runbook citabili sono in `docs/runbooks/`.

---

Analizza questo incident di produzione.

Evidenze:

- Alert, severity e timestamp: {{ALERT_SEVERITY_TIMESTAMP}}
- Commit deployato: {{GIT_LOG_1}}
- Ultimo run CD: {{LINK_RUN_CD}}
- Stato container: {{DOCKER_PS}}
- Log rilevanti (app/db/proxy): {{LOG}}
- Esito health check: {{HEALTH}}
- Metriche CPU/RAM/disco: {{METRICHE}}
- Ultimi errori applicativi: {{APP_LOG}}

Regole (fisse, senza eccezioni):

- Nessuna modifica in produzione.
- Non chiedere e non usare segreti.
- Nessuna operazione distruttiva sul DB, nemmeno come suggerimento.

Produci:

1. **Causa probabile** — con il ragionamento dalle evidenze, e le ipotesi
   scartate.
2. **Mitigazione immediata** — se basta un'azione da runbook, cita il
   runbook esatto (`docs/runbooks/...`) invece di inventare procedure.
3. **Fix strutturale** — se serve una modifica al codice, descrivila e
   preparala come branch/PR nel flusso normale.
4. **Evidenze mancanti** — cosa avrebbe reso la diagnosi certa, da
   aggiungere a monitoring o runbook.

---
