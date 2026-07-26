# Provenienza degli asset ArchitectUI

Gli asset frontend in questa directory derivano da
[`DashboardPack/architectui-html-theme-free`](https://github.com/DashboardPack/architectui-html-theme-free),
tag `v4.5.0`, commit
`06ff61cf4d15c281657122208f37658dd3499215`, distribuito con licenza MIT.

La build upstream si esegue con:

```bash
npm run build
```

Le versioni esatte risolte durante la build di controllo e quella finale sono
documentate nei lockfile:

- [`architectui-4.5.0-package-lock-control.json`](../../../../docs/assets/architectui-4.5.0-package-lock-control.json);
- [`architectui-4.5.0-package-lock-cleanup.json`](../../../../docs/assets/architectui-4.5.0-package-lock-cleanup.json).

I file generati sotto `assets/` sono output di build copiati nel progetto e non
vanno modificati a mano. Fanno eccezione `assets/scripts/app.js`,
`assets/styles/app-overrides.css` e `assets/styles/app-theme.css`: sono codice
applicativo locale e non output upstream.

Le attribuzioni e i testi delle licenze del codice distribuito sono raccolti
in [`THIRD-PARTY-NOTICES.md`](../../../../THIRD-PARTY-NOTICES.md).
