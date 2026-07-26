# Provenienza degli asset ArchitectUI

Questa directory conserva i lockfile usati per verificare e ricostruire gli
asset frontend durante la pulizia F-14.

Entrambi derivano da
[`DashboardPack/architectui-html-theme-free`](https://github.com/DashboardPack/architectui-html-theme-free),
tag `v4.5.0`, commit
`06ff61cf4d15c281657122208f37658dd3499215`, e sono stati prodotti fuori da
questo repository con Node.js 24.18.0 e npm 11.16.0.

- `architectui-4.5.0-package-lock-control.json` descrive le dipendenze esatte
  della build upstream non modificata usata per il confronto di controllo.
  SHA-256:
  `caec63e8ce4504b90164b1a0a79bf6e081ec991ec76b09e2f489a8f00b7ee085`.
- `architectui-4.5.0-package-lock-cleanup.json` descrive le dipendenze esatte
  della build finale dopo le esclusioni approvate. SHA-256:
  `10be224c77849ea73fce54955f075b4f33bdd9bba4260f80f461e1e85520a1df`.

I file hanno nomi documentali intenzionalmente diversi da `package-lock.json`.
Non introducono npm o una pipeline frontend nel progetto e non sono usati
dall'applicazione, da Composer o dalla CI. Servono esclusivamente come prova di
provenienza, riproducibilità e supporto al censimento delle licenze.
