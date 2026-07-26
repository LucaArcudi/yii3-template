# F-14 — Pulizia degli asset frontend ArchitectUI

Data della verifica: 2026-07-26.

## Provenienza e build di controllo

Gli asset sono stati ricostruiti fuori dal repository a partire da
`DashboardPack/architectui-html-theme-free`, tag `v4.5.0`, commit
`06ff61cf4d15c281657122208f37658dd3499215`.

Prima di modificare il sorgente upstream è stata eseguita una build di controllo
con Node.js 24.18.0 e npm 11.16.0. Il confronto ha dato questo esito:

- JavaScript, sidecar delle licenze e asset con nome hashato erano identici byte
  per byte a quelli nel repository;
- `main.css` era identico dopo avere tenuto conto delle 11 differenze nel solo
  prefisso relativo degli URL (`../../` nell'output upstream, `../` nella copia
  del progetto), per complessivi 33 byte;
- nel sottoinsieme di asset upstream vendorizzato nel repository, insieme dei
  file, librerie contenute e dimensioni corrispondevano in modo sostanziale.

Il banner `ArchitectUI ... v4.1.0` in `main.css` è testo upstream obsoleto: non
indica una diversa versione degli asset e non è stato modificato.

La build finale è stata prodotta nella stessa directory temporanea, senza
introdurre nel progetto `package.json`, configurazione webpack o
`node_modules`. I due lockfile sono conservati come documentazione di
provenienza, con nomi che non attivano npm nel progetto:

```text
docs/assets/architectui-4.5.0-package-lock-control.json
SHA-256: caec63e8ce4504b90164b1a0a79bf6e081ec991ec76b09e2f489a8f00b7ee085
```

```text
docs/assets/architectui-4.5.0-package-lock-cleanup.json
SHA-256: 10be224c77849ea73fce54955f075b4f33bdd9bba4260f80f461e1e85520a1df
```

Il primo fotografa la build di controllo upstream; il secondo la build dopo le
esclusioni approvate. `docs/assets/README.md` ne documenta ambiente e scopo.

## Verifica della fase 4

### Controlli automatici

| Controllo | Esito |
|---|---|
| `make cs-fix` | OK; 312 file analizzati, nessuna correzione |
| `make psalm` | OK; nessun errore |
| `make test -- --skip-group database` | OK; 102 test, 252 asserzioni |
| Pubblicazione asset nello stack di sviluppo | OK; CSS, JS e Font Awesome Regular restituiti con HTTP 200 e identici agli output locali |

Il `main.css` definitivo pubblicato da Yii è identico byte per byte all'output
locale: 385.727 B, SHA-256
`9d6ef83fd0f578df455b6638512f70aa28d77ebc97cf7e6dc29a565ab1562f20`.

Lo stack di sviluppo è stato avviato, le quattro migration sono state applicate
su un database locale vuoto ed è stato creato un utente amministratore locale
per la prova. La verifica browser è stata eseguita con Chromium 149 tramite
Playwright 1.61.1, con ispezione visiva degli screenshot. Non era disponibile
una sessione grafica con browser a finestra.

Sulle pagine visitate non sono comparsi errori o warning in console, errori
JavaScript di pagina, richieste fallite o risposte HTTP con stato almeno 400.
Ogni pagina caricava soltanto:

- `main.js`, `demo.js`, `app.js`;
- `main.css`, `app-overrides.css`, `app-theme.css`.

Nessuna pagina ha richiesto uno dei bundle eliminati. Il font Font Awesome
Regular è stato richiesto e caricato con successo su tutte le pagine; la build
espone sia il `font-face` Regular, peso 400, sia Solid, peso 900.

| Pagina o interazione | Esito | Dettaglio |
|---|---|---|
| Login | OK | HTTP 200; form e icona Font Awesome Regular visibili |
| Dashboard | OK | Login completato, flash visibile, icone e layout corretti |
| Lista utenti | OK | HTTP 200; tabella, azioni e icone visibili |
| Creazione utente | OK | Form aperto e utente locale creato con successo |
| Modifica utente | OK | Form aperto e modifica locale salvata con successo |
| Audit log | OK | Verificato nella vista dell'utente; presenti gli eventi di creazione e modifica |
| Lista ruoli | OK | HTTP 200; tabella e azioni visibili |
| Lista permessi | OK | HTTP 200; sottomenu MetisMenu, tabella e azioni visibili |
| Gruppi di permessi | OK | HTTP 200; tabella e azioni visibili |
| Lista task | OK | HTTP 200 in modalità griglia; tab e azioni visibili |
| Profilo | OK | HTTP 200; form profilo, email e zona pericolosa visibili |
| Cambio lingua | OK | Passaggio italiano → inglese → italiano riuscito; attributo `lang` coerente |
| Centro notifiche | OK | HTTP 200; elenco notifiche visibile |
| Dropdown notifiche | OK | Apertura riuscita; elementi presenti e lista interna scrollabile |
| Dropdown Bootstrap | OK | Apertura e chiusura riuscite |
| Menu laterale | OK | MetisMenu inizializzato; sottomenu espanso e richiuso |
| Toggle sidebar | OK | Chiusura e ripristino riusciti |
| Scrollbar personalizzata | OK con limite noto | Nessuna `.scrollbar-container` nel markup e nessuna inizializzazione Perfect Scrollbar; a 720 px il menu è interamente fruibile |
| Tooltip Bootstrap | Non verificabile | Non esistono trigger `data-bs-toggle="tooltip"` nelle pagine o nel markup applicativo ispezionati |

A 360 px di altezza l'ultimo elemento del menu risulta parzialmente tagliato:
`.app-sidebar` ha `overflow-y: hidden` e il contenitore interno non è
scrollabile. Il comportamento era già presente, perché Perfect Scrollbar non
veniva inizializzato neppure prima della rimozione. La sua eventuale correzione
richiede una modifica separata a markup o CSS applicativo.

## Indagine su `public/assets` in produzione

Nella configurazione di produzione versionata, `public/assets` non è su un
volume persistente:

- `docker/Dockerfile:54-64` copia il checkout in `/app` nell'immagine finale e
  imposta `/app/public` come document root;
- `.dockerignore:1-8` include `public/` nel contesto di build, mentre nel
  checkout pulito `public/assets/.gitignore:1-2` è l'unico file tracciato in
  quella directory;
- `.github/workflows/ci.yml:139-154` esegue il checkout e costruisce l'immagine
  di produzione da quel contenuto versionato;
- `docker/prod/compose.yml:25-28` monta soltanto `/app/runtime`, `/data` e
  `/config`, non `/app/public` o `/app/public/assets`;
- `scripts/deploy.sh:66-70` forza sempre la ricreazione del container
  applicativo dopo il pull; anche il rollback ricrea il container
  (`scripts/deploy.sh:97`).

Gli asset pubblicati da Yii vivono quindi nel writable layer del container.
Con il flusso versionato, la ricreazione elimina automaticamente le copie
stantie e il nuovo container pubblica soltanto gli asset correnti. Non serve
una modifica a `deploy.sh` in questa PR.

Resta un'unica verifica operativa fuori perimetro: il deploy carica anche
`docker/prod/compose.local.yml`, volutamente non tracciato. L'esempio
versionato modifica solo le porte del database, ma dal repository non si può
escludere che il file reale sul VPS aggiunga un mount sotto `/app/public`. Si
propone un controllo read-only del compose risolto e dei mount del container
in produzione; se esistesse tale volume, la sua rimozione e il successivo
force-recreate andrebbero gestiti in una task deploy separata.

## 1. Rimosso

### Bundle e asset orfani

Sono stati rimossi dal caricamento globale e cancellati:

- `assets/scripts/chart_js.js` e
  `assets/scripts/chart_js.js.LICENSE.txt`;
- `assets/scripts/fullcalendar.js`;
- `assets/scripts/toastr.js` e
  `assets/scripts/toastr.js.LICENSE.txt`;
- `assets/scripts/scrollbar.js` e
  `assets/scripts/scrollbar.js.LICENSE.txt`.

Sono stati inoltre cancellati perché non referenziati:

- `assets/scripts/maps.js` e `assets/scripts/maps.js.LICENSE.txt`;
- `assets/favicon.ico`;
- i dieci avatar sotto `assets/images/avatars/`;
- `assets/images/logo.png` e `assets/images/logo-inverse.png`, duplicati delle
  corrispondenti immagini con nome hashato;
- `assets/1c5c7716b05754cb4eab.woff2`, font Font Awesome Brands.

I bundle globali JavaScript passano da sette a tre: restano `main.js`,
`demo.js` e l'asset applicativo protetto `app.js`.

| Gruppo | Byte rimossi |
|---|---:|
| Bundle caso A e relativi sidecar, incluso Maps orfano | 756.120 |
| Favicon, dieci avatar e due PNG duplicati | 35.553 |
| Font Font Awesome Brands | 101.224 |
| Totale file cancellati | 892.897 |

### Librerie e CSS esclusi dal rebuild

Dal sorgente upstream temporaneo sono stati rimossi gli entry point, gli import
e, dove applicabile, le dipendenze di:

- FullCalendar (`core`, `daygrid`, `interaction`, `list`, `timegrid`);
- Toastr;
- Chart.js e `@kurkle/color`;
- Perfect Scrollbar;
- Animate.css e `animate-sass`;
- Google Maps JS API Loader;
- Font Awesome Brands.

Sono stati esclusi anche il CSS jVectorMap e i selettori residui di
`react-datepicker` e Slick. In `chart_js.js` era presente uno snippet Google
Analytics inerte, eliminato insieme al bundle.

Il componente jVectorMap censito era soltanto SCSS vendorizzato: versione,
origine e licenza non sono determinabili dal materiale upstream disponibile.
I suoi selettori sono assenti dall'output finale.

Il rebuild ha modificato soltanto gli output upstream autorizzati:

| Output | Prima | Dopo | Delta |
|---|---:|---:|---:|
| `assets/styles/main.css` | 473.011 B | 385.727 B | -87.284 B |
| `assets/scripts/main.js` | 164.385 B | 164.151 B | -234 B |
| `assets/scripts/demo.js` | 80.283 B | 80.271 B | -12 B |
| Font Awesome Regular WOFF2 | 0 B | 18.988 B | +18.988 B |

`app.js`, `app-overrides.css` e `app-theme.css` non sono stati modificati; i
rispettivi SHA-256 sono rimasti invariati.

### Cambiamento visivo intenzionale delle icone Regular

L'aggiunta del `font-face` Font Awesome Regular corregge un difetto
preesistente ed è l'unico cambiamento visibile della pulizia. Nel markup ci
sono 23 occorrenze di `fa-regular`, riferite a cinque icone:
`fa-calendar`, `fa-envelope`, `fa-user`, `fa-calendar-check` e
`fa-address-card`.

Prima della correzione `main.css` non esponeva la face con peso 400: il browser
ricadeva sulla face Solid e mostrava queste icone piene. La build finale espone
il font Regular richiesto e le stesse icone vengono quindi rese correttamente
a contorno.

### Risparmio complessivo

| Misura non compressa | Prima | Dopo | Risparmio |
|---|---:|---:|---:|
| Asset ArchitectUI nel repository | 2.196.941 B, 38 file | 1.235.502 B, 16 file | 961.439 B (938,9 KiB) |
| Payload globale JS + CSS | 1.519.143 B | 758.958 B | 760.185 B (742,4 KiB; circa 50,0%) |

## 2. Rimasto

La tabella censisce le librerie frontend emesse negli output finali e la
dipendenza runtime ancora dichiarata nel lock ma non emessa. Le versioni npm
sono quelle esatte del lockfile finale; per i pacchetti npm le licenze sono
state lette dai rispettivi file `LICENSE`, non dedotte dal tipo di progetto.
Bootstrap CSS e Hamburgers sono eccezioni vendorizzate, marcate esplicitamente
quando mancano metadati o un file `LICENSE` autonomo.

| Libreria o componente | Versione | Presenza finale | Licenza dichiarata | Note |
|---|---:|---|---|---|
| ArchitectUI HTML Free | 4.5.0 | Sorgente del tema e output compilati | MIT | `LICENSE` del repository upstream, copyright DashboardPack |
| Bootstrap JavaScript | 5.3.8 | `main.js` | MIT | Versione e licenza confermate da lockfile, sidecar e `node_modules/bootstrap/LICENSE` |
| Bootstrap CSS vendorizzato | 5.3.2 nel banner del sorgente | `main.css` | non dimostrabile da un `LICENSE` autonomo; il banner dichiara MIT | Non proviene dal pacchetto Bootstrap 5.3.8 risolto dal lock; la provenienza esatta resta distinta |
| RFS incorporato in Bootstrap CSS | non determinabile | Mixin SCSS compilati in `main.css` | non dimostrabile da un `LICENSE` autonomo; l'header vendorizzato dichiara MIT | Origine indicata: `https://github.com/twbs/rfs` |
| Normalize.css incorporato in Bootstrap Reboot | non determinabile | Fork manuale compilato in `main.css` | non dimostrabile da un `LICENSE` autonomo; l'header vendorizzato dichiara MIT | Origine indicata: `https://github.com/necolas/normalize.css` |
| `@popperjs/core` | 2.11.8 | `main.js`, usato dai componenti Bootstrap | MIT | Da `node_modules/@popperjs/core/LICENSE.md` |
| jQuery | 4.0.0 | Duplicata in `main.js` e `demo.js` | MIT | Versione confermata anche nei due sidecar |
| MetisMenu | 3.1.0 | `main.js`; menu laterale verificato | MIT | Da `node_modules/metismenu/LICENSE` |
| Font Awesome Free | 7.1.0 | CSS, font Solid e Regular | `CC-BY-4.0 AND OFL-1.1 AND MIT` | **Non è solo MIT.** Il codice è MIT; i font emessi sono SIL OFL 1.1, con Reserved Font Name “Font Awesome”; CC BY 4.0 si applica agli asset icona SVG/JS, che questa build non emette |
| `pe7-icon` | 1.0.4 | CSS e font Pe-icon-7-stroke | MIT | Da `node_modules/pe7-icon/license.md` |
| Hamburgers | non determinabile | CSS del solo tipo `elastic` in `main.css` | non determinabile | È codice SCSS vendorizzato, non una dipendenza del lock; il sorgente conserva autore e origine ma non versione né file `LICENSE` |
| `wnumb` | 1.2.0 | Dichiarata nel lock, ma non importata e senza fingerprint negli output | MIT | Da `node_modules/wnumb/LICENSE.MD`; non aggiunge peso al payload attuale |

Il `font-face` Font Awesome Regular punta al nuovo
`assets/6f05ca9ab7b5345dbc07.woff2`; il Solid continua a puntare al font esistente.
Il font Brands, il relativo `font-face` e le classi dei singoli glifi Brands
non sono presenti. Il core Font Awesome conserva i selettori generici
`.fa-brands` e `.fab`, ma senza font o glifi Brands non forniscono icone di
marca.

## 3. DA DOCUMENTARE

Elenco pronto per le note di terze parti relative agli asset distribuiti:

| Nome | Versione | Origine | Licenza da riportare |
|---|---:|---|---|
| ArchitectUI HTML Free | 4.5.0 | `https://github.com/DashboardPack/architectui-html-theme-free` | MIT |
| Bootstrap JavaScript | 5.3.8 | `https://github.com/twbs/bootstrap` | MIT |
| Bootstrap CSS vendorizzato | snapshot marcato 5.3.2 | sorgente SCSS incluso in ArchitectUI 4.5.0 | da verificare con il `LICENSE` della versione sorgente; il banner vendorizzato dichiara MIT |
| RFS incorporato in Bootstrap CSS | versione non determinabile | `https://github.com/twbs/rfs`, indicato nell'header SCSS | da verificare con il `LICENSE` della versione sorgente; l'header vendorizzato dichiara MIT |
| Normalize.css incorporato in Bootstrap Reboot | versione non determinabile | `https://github.com/necolas/normalize.css`, indicato nell'header SCSS | da verificare con il `LICENSE` della versione sorgente; l'header vendorizzato dichiara MIT |
| Popper Core | 2.11.8 | `https://github.com/popperjs/popper-core` | MIT |
| jQuery | 4.0.0 | `https://github.com/jquery/jquery` | MIT |
| MetisMenu | 3.1.0 | `https://github.com/onokumus/metismenu` | MIT |
| Font Awesome Free | 7.1.0 | `https://github.com/FortAwesome/Font-Awesome` | MIT per il codice; SIL OFL 1.1 per i font Solid/Regular; Reserved Font Name “Font Awesome”; il pacchetto dichiara anche CC BY 4.0 per SVG/JS non emessi |
| `pe7-icon` | 1.0.4 | `https://github.com/prasannatm/pe7-icon` | MIT |
| Hamburgers, tipo `elastic` | versione non determinabile | `https://github.com/jonsuh/hamburgers`, indicato nell'header SCSS | licenza non determinabile dal materiale vendorizzato |
| `wnumb` — dipendenza dichiarata, non distribuita | 1.2.0 | `https://github.com/leongersen/wnumb` | MIT |

`wnumb` non va descritta come libreria consegnata al browser finché resta assente
dagli output; la riga serve a mantenerla nell'inventario del lock fino alla
decisione sulla dipendenza sorgente.

## 4. DA DECIDERE

### Consolidamento dei set di icone

Il progetto usa due set di icone:

- Font Awesome: 121 riferimenti in 37 file applicativi, dei quali 90 Solid, 23
  Regular e 8 generici;
- `pe7-icon`: 44 riferimenti in 28 file applicativi, più 3 riferimenti nei test;
- 18 file applicativi usano entrambi i set.

Consolidare tutto su Font Awesome richiederebbe di modificare 44 riferimenti in
28 file applicativi e 3 riferimenti nei test. Consentirebbe di eliminare circa
349.321 B (341,1 KiB) dal repository: 9.585 B stimati di CSS e 339.736 B tra
EOT, WOFF, TTF e SVG Pe7. Su un browser moderno il risparmio normalmente
scaricato sarebbe circa 68.141 B non compressi, cioè CSS più il solo WOFF.

Consolidare tutto su `pe7-icon` richiederebbe di modificare 121 riferimenti in
37 file e farebbe perdere le classi Regular già usate. Eliminerebbe circa
187.630 B (183,2 KiB): 55.490 B stimati di CSS Font Awesome e 132.140 B dei
font Solid e Regular.

Nessuna delle due opzioni è stata applicata perché richiede modifiche al markup
e ai test.

### Duplicazione jQuery e `demo.js`

jQuery 4.0.0 resta compilata sia in `main.js` sia in `demo.js`. Eliminare
`demo.js` può risparmiare al massimo i suoi 80.271 B (78,4 KiB), ma quel bundle
contiene ancora il comportamento di chiusura della sidebar e le opzioni tema.
Le alternative sono trasferire esplicitamente quel comportamento nel codice
applicativo oppure introdurre un chunk vendor condiviso nel build upstream. Il
risparmio attribuibile alla sola seconda copia di jQuery non è isolabile con
affidabilità dal file minificato.

### Dipendenza `wnumb`

`wnumb` 1.2.0 resta dichiarata dal sorgente ArchitectUI ma non è importata né
emessa. La sua rimozione dal manifest temporaneo non cambierebbe il payload
runtime, ma ridurrebbe il lock e il perimetro della futura documentazione.

### Componenti Bootstrap non provati

Il build importa l'intero JavaScript Bootstrap e gran parte del suo CSS. I
dropdown sono usati e verificati; non esistono invece trigger tooltip nelle
pagine ispezionate. Un'eventuale build Bootstrap selettiva richiede un audit
dedicato di tutti i componenti e non è stata autorizzata.

### Sidebar con viewport molto bassa

Sotto circa 400 px di altezza il menu può essere tagliato dall'`overflow`
della sidebar. Correggerlo richiede una scelta sul comportamento responsive e
una modifica a markup o CSS applicativo.

## 5. NON RISOLTO

- Il repository continua a non contenere un manifest frontend attivo o la
  configurazione di build. I due lockfile archiviati sotto `docs/assets/`
  fissano le versioni esatte, ma sono soltanto documentazione e non attivano
  npm nel progetto.
- Gli asset restano output minificati copiati manualmente. Un futuro rebuild
  richiede nuovamente il sorgente upstream corretto e il lockfile conservato.
- jQuery resta duplicata in due bundle globali e `demo.js` resta caricato su
  tutte le pagine.
- Restano due set di icone e il CSS vendorizzato di Hamburgers.
- Bootstrap JS e il CSS vendorizzato non sono allineati alla stessa
  provenienza/versione: il lock risolve JS 5.3.8, mentre gli SCSS locali si
  identificano come 5.3.2.
- Il CSS Bootstrap vendorizzato incorpora RFS e un fork di Normalize.css senza
  conservarne versione o file `LICENSE` autonomi.
- `wnumb` resta una dipendenza sorgente non usata.
- La build temporanea installa anche la toolchain di sviluppo; `npm install` ha
  segnalato 31 vulnerabilità nel suo albero. Il repository non conserva tale
  albero e questo lavoro non ha determinato se qualcuna riguardi codice
  effettivamente consegnato al browser.
- Le note di terze parti del progetto devono ancora essere aggiornate usando
  l'elenco della sezione 3.
- F-14 è chiuso per i bundle e gli asset approvati, ma resta aperto per il debito
  statico sopra elencato e per la mancanza di una distinta frontend
  versionata.

## 6. INCERTEZZE

- Hamburgers è vendorizzato senza versione e senza file `LICENSE`: autore e
  origine sono presenti nell'header, ma non bastano a dichiararne la licenza.
- Il vecchio SCSS jVectorMap rimosso dall'output non aveva package, versione,
  header di licenza o file `LICENSE`; origine e licenza storiche non sono
  dimostrabili dal sorgente ArchitectUI disponibile.
- La versione esatta del CSS Bootstrap è ricavabile soltanto dal banner del
  sorgente vendorizzato (5.3.2), non dal lockfile finale (che descrive il
  pacchetto Bootstrap 5.3.8 usato dal JavaScript).
- Le versioni esatte di RFS e Normalize.css e i relativi testi `LICENSE` non
  sono ricavabili dal materiale vendorizzato; sono presenti soltanto origine e
  dichiarazione MIT negli header SCSS.
- Il peso isolato dei blocchi CSS Font Awesome, Pe7 e Hamburgers è una stima
  basata sui confini dei rispettivi selettori minificati; i totali per file e
  per payload sono invece misure esatte.
- Non essendo disponibile una sessione grafica, la verifica manuale è stata
  sostituita da Chromium headless, interazioni reali e ispezione visiva degli
  screenshot. I tooltip non hanno potuto essere esercitati perché manca
  qualsiasi trigger nel markup.
- Il difetto della sidebar a 360 px è stato riprodotto ed è compatibile con il
  comportamento preesistente, ma non esiste un test browser storico eseguito
  prima della modifica con cui confrontarlo automaticamente.
