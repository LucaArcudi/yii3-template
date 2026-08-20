# Documentazione attiva

Questo indice distingue le fonti operative correnti dai documenti storici.
Audit, prompt e roadmap del precedente flusso AI sono stati rimossi dalla
documentazione attiva; restano recuperabili nello storico Git.

## Iniziare

- [README del progetto](../README.md): avvio locale, comandi e sintesi CI/CD.
- [Documentazione di progetto](documentazione-progetto.md): architettura,
  configurazione, database, sviluppo e stato tecnico.
- [Guida di deploy](../README_DEPLOY.md): bootstrap VPS, configurazione
  GitHub, primo deploy, release ordinarie e recovery.
- [Flusso e confini DevOps](DEVOPS_WORKFLOW.md): mappa visuale di repository,
  CI, GHCR, CD, VPS, proxy, monitoring e segreti.

## Backlog

- [Piano di miglioramento](../PIANO_MIGLIORAMENTO_TEMPLATE.md): unica roadmap
  consolidata e distinzione tra attività autonome ed esterne.
- [Roadmap di sviluppo](roadmap-sviluppo.md): backlog Yii/applicativo.
- [Infrastruttura e osservabilità](roadmap-infrastruttura.md): stato attuale e
  rischi operativi residui.
- [Changelog](../CHANGELOG.md): modifiche visibili e operative.

## Procedure

- [Runbook operativi](runbooks/)
- [Monitoring e notifiche Telegram](runbooks/monitoring.md)
- [Inventario e licenze degli asset](assets/README.md)
- [Ricostruzione degli asset frontend](assets/REBUILD.md)

## Fonti normative

Il comportamento effettivo è definito dai file versionati (`compose.yml`,
`docker/`, `.github/workflows/`, `scripts/`) e verificato dai test. In caso di
divergenza, correggere la documentazione nello stesso cambiamento.
