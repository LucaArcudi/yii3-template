# Rebuild degli asset frontend (ArchitectUI)

Procedura completa e vincoli per ricostruire i bundle del tema. Gli asset nel
repo sono **output minificati copiati a mano** da una build eseguita fuori dal
repository: qui non esistono `package.json`, webpack o `node_modules`, e così
deve restare. Ogni modifica ai bundle passa da questa procedura — mai editare
i file minificati direttamente.

## 1. Sorgente

| Cosa | Valore |
|---|---|
| Repo | [`DashboardPack/architectui-html-theme-free`](https://github.com/DashboardPack/architectui-html-theme-free) |
| Versione | tag `v4.5.0`, commit `06ff61cf4d15c281657122208f37658dd3499215` |
| Toolchain usata | Node.js 24.18.0, npm 11.16.0 |
| Script di build | `npm run build` (= `webpack --env production`, dal `package.json` upstream) |
| Licenza tema | MIT (DashboardPack) |

Il banner `v4.1.0` dentro `main.css` è testo upstream obsoleto: non indica una
versione diversa e non va "corretto".

## 2. Lockfile documentali (`docs/assets/`)

- `architectui-4.5.0-package-lock-control.json` — dipendenze della build
  upstream **non modificata**, usata come confronto di controllo.
- `architectui-4.5.0-package-lock-cleanup.json` — dipendenze della build dopo
  le esclusioni, inclusa la rimozione dal manifest di `pe7-icon` e `wnumb`.
- Gli SHA-256 correnti sono nel [README](README.md). I nomi sono documentali
  di proposito (diversi da `package-lock.json`): npm non deve attivarsi nel
  repo. Trivy analizza comunque il lockfile *cleanup* via `file-patterns` in
  `trivy.yaml` (il *control* resta solo documentale, non scansionato).

## 3. Esclusioni applicate (rispetto al tema upstream)

Rimossi entry point, import e — dove applicabile — dipendenze di:

| Escluso | Motivo |
|---|---|
| FullCalendar (`core`, `daygrid`, `interaction`, `list`, `timegrid`) | Non usato: l'entry cerca `#calendar`, inesistente nel markup |
| Toastr | Non usato: i flash reali sono Bootstrap alert (`FlashMessages.php`) |
| Chart.js + `@kurkle/color` | Non usato: zero `<canvas>` nel repo (il bundle conteneva due copie di `@kurkle/color`) |
| Perfect Scrollbar | Init interamente dietro guardia `.scrollbar-container`, classe assente dal markup |
| Animate.css + `animate-sass` | Zero usi applicativi (selettori legati alla variante React del tema) |
| Google Maps JS API Loader | Mai impacchettato davvero; `maps.js` era un bundle orfano |
| Font Awesome **Brands** | Set mai usato: font 101 KB mai scaricato + CSS morto |
| CSS jVectorMap | CSS orfano vendorizzato, senza JS né markup |
| Selettori residui react-datepicker e Slick | Residui della variante React / demo, librerie non incluse |
| Snippet Google Analytics in `chart_js.js` | Inerte, eliminato col bundle |
| **`pe7-icon` / Pixeden Stroke 7** | **Licenza**: i termini Pixeden non consentono la redistribuzione in un template pubblico. Icone sostituite con Font Awesome nel markup. Va eliminata dal manifest **prima** della build |
| `wnumb` | Non importata e non emessa dai bundle; rimossa anche dal manifest finale per mantenere corretto l'inventario letto da Trivy |

Modifica additiva upstream: il tema non esponeva il `font-face` **Font
Awesome Regular (peso 400)** pur usandolo nel markup (fallback errato su
Solid); la build corretta lo include. Stili FA inclusi: **Solid + Regular**,
niente Brands, niente SVG/JS.

## 4. Procedura di rebuild

Tutto **fuori dal repository**, in una directory temporanea:

1. `git clone` del repo upstream e checkout del commit di riferimento (§1;
   per un aggiornamento di versione: nuovo tag, e questa pagina va
   aggiornata di conseguenza).
2. **Build di controllo**: copiare il lockfile *control* come
   `package-lock.json`, `npm ci`, `npm run build`. Confrontare l'output con
   la release upstream / gli asset correnti per fissare la baseline (le
   dipendenze `^` risolverebbero versioni diverse senza lockfile).
3. **Applicare le esclusioni** (§3) a `package.json`, agli entry point e
   agli import del tema — inclusa la rimozione di `pe7-icon` e `wnumb` — e
   l'aggiunta del `font-face` FA Regular.
4. `npm install` (rigenera il lockfile) e `npm run build`.
5. **Fix dei prefissi URL** in `main.css`: l'output upstream usa `../../`,
   la copia nel repo usa `../` (6 occorrenze attese con le esclusioni di §3:
   2 font woff2 Font Awesome + 4 riferimenti ai PNG del logo; erano 11
   nella build di controllo non modificata, coi font pe7-icon e Brands).
6. **Copiare nel repo, in `src/Shared/resources/architectui/assets/`, SOLO
   gli output upstream autorizzati**:
   - `styles/main.css`
   - `scripts/main.js` (+ sidecar `.LICENSE.txt`)
   - `scripts/demo.js` (+ sidecar)
   - i font hashati Font Awesome Solid e Regular (`*.woff2` nella radice)
   - i PNG hashati del logo emessi da webpack nella radice (oggi
     `247797d48a903028d1e9.png` e `e621231ec630d4aa48e9.png`): `main.css`
     li referenzia 4 volte (`.app-header__logo .logo-src`), senza di loro
     il logo di header/sidebar va in 404
   Non copiare: bundle esclusi (`chart_js.js`, `fullcalendar.js`,
   `toastr.js`, `scrollbar.js`, `maps.js`), `favicon.ico`, avatar,
   `assets/images/logo*.png` (duplicati **non** hashati dei PNG del logo
   di cui sopra), font Brands. `AssetPublisher` pubblica **l'intera
   directory ricorsivamente**: qualunque file lasciato lì finisce sul web.

## 5. File da non toccare MAI

Scritti a mano, non provengono dalla build, nessun rebuild li rigenera:

- `src/Shared/resources/architectui/assets/scripts/app.js` — JS applicativo
  (vanilla, i18n, select/date-picker/validazione custom)
- `src/Shared/resources/architectui/assets/styles/app-overrides.css`
- `src/Shared/resources/architectui/assets/styles/app-theme.css`

**Nota su `demo.js`**: è upstream, ma contiene l'**unico** gestore di
`.close-sidebar-btn` (collasso sidebar desktop, usato da
`src/Shared/resources/layouts/main.php`). Va ricompilato dal rebuild, mai
rimosso.

## 6. Verifiche post-rebuild

1. Diff byte-per-byte degli output rispetto ai file correnti: le differenze
   devono essere solo quelle attese e spiegabili.
2. `make cs-fix`, `make psalm`, `make test` (in container).
3. Smoke browser sulle pagine principali: icone `fa-regular` a contorno,
   toggle sidebar (hamburger + collasso desktop), dropdown notifiche,
   nessun 404 su font/asset.

## 7. Aggiornamenti obbligatori a valle

- Nuovo lockfile *cleanup* in `docs/assets/` + SHA-256 nel
  [README](README.md) (e nuovo *control* se cambia la versione del tema).
- Se cambia il nome del lockfile: aggiornare il pattern `file-patterns` in
  `trivy.yaml`, o la sorveglianza CVE delle dipendenze frontend smette di
  funzionare in silenzio.
- `THIRD-PARTY-NOTICES.md`: componenti, versioni, licenze (mai dichiarare
  una licenza non letta nel LICENSE del pacchetto).
- Elenco esclusioni in questa pagina (§3) e `CHANGELOG.md`.

## 8. Debito noto (non risolvere "di passaggio")

- Bootstrap CSS vendorizzato 5.3.2 vs Bootstrap JS 5.3.8 (disallineamento
  upstream registrato).
- jQuery 4.0.0 duplicata in `main.js` e `demo.js`; `demo.js` caricato su
  tutte le pagine.

Le provenienze e le licenze di Hamburgers e RFS incorporate nel CSS sono
state ricostruite con confronti sui tag upstream e sono registrate, con i
limiti dell'evidenza, in [`THIRD-PARTY-NOTICES.md`](../../THIRD-PARTY-NOTICES.md).
