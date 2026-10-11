# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Cos'è

Reflexi (nome di lavoro interno: "Sparagno") è una web app di finanza personale
multi-utente: conti multipli, transazioni ricorrenti, mutui (ammortamento alla
francese), investimenti (prezzi live da Yahoo Finance), budget per categoria,
analisi predittiva (medie storiche, Monte Carlo, rilevamento automatico di
ricorrenze nascoste), obiettivi di risparmio, patrimonio netto, FIRE e fondi
pensione. Inserimento dati manuale, nessuna connessione bancaria diretta.
Abbonamento gestito via Stripe con trial gratuito. Vedi `README.md` per la
documentazione utente completa di ogni modulo (mutui, investimenti, budget,
analisi predittiva) e `docs/ROADMAP.md` per lo stato del progetto.

## Stack e struttura

- **Backend**: PHP 8.2 + Apache, nessun framework — un file per endpoint sotto
  `webroot/api/`, PDO puro verso MariaDB. Composer solo per due librerie
  (`phpmailer/phpmailer`, `stripe/stripe-php`).
- **Frontend**: React 18 + Vite + Tailwind in `frontend/`, sorgente separato
  dal backend. Il build compilato viene copiato in `webroot/app/` (l'app vive
  sotto `/app/`, non sulla radice — `webroot/index.html` è la landing page
  statica pubblica, file PHP separati). `webroot/css/style.css` e
  `webroot/js/app.js` sono residui della vecchia UI Bootstrap/vanilla JS
  pre-React: non più serviti da nessuna pagina, da non toccare/estendere.
- **Sito pubblico (`webroot/*.php`, non l'app React)**: usa Tailwind
  **compilato staticamente** in `webroot/css/site.css`, non il CDN
  (`cdn.tailwindcss.com` compila in JIT nel browser — sconsigliato in
  produzione, blocca il rendering iniziale). Il file va rigenerato con
  `build/build-css.sh` ogni volta che si aggiunge una classe Tailwind nuova
  a una pagina pubblica o a un articolo News (il campo `body` di
  `news_articles` è testo nel DB, non scansionabile da Tailwind: le sue
  classi vanno elencate nella `safelist` di `build/tailwind.config.js`,
  altrimenti verrebbero scartate dal build). Questa VPS non ha Node: lo
  script usa un container `node:20` temporaneo via Docker.
- **App mobile Android**: stesso codice React impacchettato con Capacitor in
  `frontend/android/` (progetto Android Studio). Vedi sezione dedicata più sotto.
- **DB**: MariaDB 11, schema in `db/init.sql` (installazioni nuove) +
  `db/migrations/NNN_*.sql` applicate in ordine numerico per chi aggiorna
  un'installazione esistente. Ogni tabella dati è multi-tenant via colonna
  `user_id` con `FOREIGN KEY ... ON DELETE CASCADE`.
- **Infra**: Docker Compose con 4 servizi — `db` (MariaDB), `web` (PHP/Apache,
  monta `./webroot` come volume — modifiche ai file PHP sono live senza
  rebuild), `adminer` (ispezione DB via browser), `caddy` (reverse proxy,
  HTTPS automatico via Let's Encrypt in produzione con `SITE_ADDRESS`
  impostato a un dominio reale).

## Comandi comuni

**Frontend** (da `frontend/`):
```bash
npm install
npm run dev      # dev server su :5173, proxy /api/*.php -> backend Docker su :8080
npm run build    # genera frontend/dist/
```
Dopo ogni build va copiato manualmente in `webroot/app/`:
```bash
cp -r dist/* ../webroot/app/
```
Non esiste hot-deploy automatico backend→frontend: il passo di copia è manuale.

**Backend/infra** (dalla root):
```bash
docker compose up -d --build   # build + avvio; rebuild necessario solo se cambia Dockerfile/composer.json
docker exec -i finanze_db mysql -u root -p"$DB_ROOT_PASSWORD" finanze < db/migrations/0NN_nome.sql
docker exec finanze_db mysqldump -u root -p"$DB_ROOT_PASSWORD" finanze > backup_$(date +%F).sql
```

Nessuna suite di test automatizzati nel repo (né PHP né JS) al momento — verificare le modifiche manualmente via browser/API.

## Architettura backend (`webroot/api/`)

- `db.php`: incluso da ogni endpoint. Fornisce `db()` (singleton PDO),
  `respond()`/`respond_error()` (output JSON, con `exit`), `json_input()`
  (body JSON del POST/PUT), gestori globali di errori/eccezioni che
  garantiscono **sempre** risposta JSON (mai una pagina di errore HTML — il
  frontend farebbe fallire il parsing). Sessione PHP (`session_start()`)
  è l'unico meccanismo di autenticazione.
- **Auth per-request**: `current_user_id()` legge `$_SESSION['user_id']`;
  `require_auth()` interrompe con 401 se assente — va chiamata a inizio di
  ogni endpoint protetto. `assert_owned($table, $id, $uid)` verifica che una
  risorsa referenziata (conto, categoria, ecc. passata dal client) appartenga
  davvero all'utente loggato prima di usarla in una scrittura — **va sempre
  chiamata per ogni FK esterna accettata in input**, altrimenti un utente
  potrebbe agganciare dati a risorse di un altro utente semplicemente
  passando un ID diverso.
- **Area admin**: `require_admin()` (in `db.php`) = `require_auth()` + flag
  `users.is_admin` riletto dal DB a ogni richiesta. Il flag si assegna solo
  via SQL (migrazione 041), mai da API. `admin_users.php` espone l'elenco
  utenti con i soli dati di account: non deve mai leggere tabelle dati
  finanziari (conti, transazioni, ...). Lato frontend la voce "Admin · Utenti"
  in `Sidebar.jsx` appare solo se `user.is_admin`.
- **Storico accessi** (`user_sessions`, migrazione 042): una riga per login
  (IP, user agent), `last_seen_at` aggiornato da `touch_login_session()`
  dentro `require_auth()` (max 1 scrittura/minuto), `ended_at` al logout.
  Serve al dettaglio utente dell'area admin; righe oltre 180 giorni cancellate
  al login. Le sessioni aperte prima del 2026-10-11 non hanno storico.
- **Lock di sessione**: `db.php` chiude subito la sessione dopo
  `session_start()` (`session_write_close()`), altrimenti una richiesta lenta
  blocca in coda tutte le altre dello stesso utente (l'app sembra congelata).
  `$_SESSION` resta leggibile; per SCRIVERLA va chiamato prima
  `session_reopen()` (oggi solo login, logout, eliminazione account).
- **Rate limiting**: `rate_limit_check()`/`rate_limit_record()` per azioni
  sensibili (login, registrazione) via tabella `rate_limit_attempts`, per IP
  (letto da `X-Forwarded-For` perché dietro Caddy `REMOTE_ADDR` è l'IP del
  proxy, non del client).
- **Convenzione endpoint**: un file per risorsa (`accounts.php`,
  `transactions.php`, `loans.php`, ...), routing interno su `method()` +
  parametro `action`/id in query string, nessun router centralizzato.
  `loan_math.php` contiene le formule di ammortamento condivise tra
  `loans.php` e la generazione di rate ricorrenti. `lib_networth.php`
  centralizza il calcolo del patrimonio netto usato da più endpoint
  (dashboard, report, FIRE). `lib_seed.php` crea categorie/conto di default
  per un nuovo utente registrato. `lib_stripe.php` incapsula le chiamate
  Stripe SDK; `stripe_webhook.php` gestisce gli eventi asincroni
  (rinnovo/cancellazione abbonamento).
- **Saldi conto**: sempre calcolati dinamicamente (saldo iniziale ± somma
  transazioni), mai un campo persistito — non serve mai "ricalcolare",
  qualunque modifica storica è automaticamente coerente.
- **Prezzi investimenti**: `price_fetch.php` interroga Yahoo Finance (endpoint
  non ufficiale) con cache 15 minuti in tabella `price_cache`, **globale per
  ticker** e non per utente (di proposito, per ridurre le chiamate esterne).
- **Cache previsioni**: `cron/warm_forecast_cache.php` (ogni 30 min da
  crontab) rinfresca prezzi/storico dell'universo curato e dati analisti, così
  `forecasts.php` legge solo dalla cache (a freddo impiegava ~40 s).
- **Cron** (`webroot/cron/send_digest.php`): invio digest periodici via SMTP;
  processing delle ricorrenze dovute avviene invece via chiamata HTTP a
  `recurring.php?action=process` schedulata da crontab sulla VM, non da PHP CLI.

## Architettura frontend (`frontend/src/`)

- `App.jsx`: mappa `VIEWS` (stringa → componente) è la fonte di verità delle
  sezioni dell'app; niente router — lo stato `view` in `Shell` decide cosa
  renderizzare. Per aggiungere una sezione: creare il file in `views/`,
  registrarlo in `VIEWS` e aggiungere la voce in `Sidebar.jsx`.
- `Gate` in `App.jsx` è il punto di ingresso che decide cosa mostrare in base
  ad auth state (`AuthContext`) e stato abbonamento (`user.has_access` —
  se `false` mostra `Paywall` invece della UI applicativa).
- `context/AuthContext.jsx`: sessione utente, login/logout; su 401 globale
  (evento `auth:unauthorized` emesso da `api.js`) riporta l'utente al login.
- `context/DataContext.jsx`: dati condivisi (conti, categorie, ecc.) con
  `bump()` per invalidare/ricaricare dopo mutazioni — pattern usato invece di
  una libreria di data-fetching.
- `api.js`: unico punto di chiamata REST (`apiGet`/`apiSend`/`apiDelete`),
  base path `/api`; ogni endpoint PHP è chiamato per nome file diretto (es.
  `apiGet('transactions.php?...')`). Nessun client HTTP esterno.

## App mobile Android (Capacitor)

`frontend/capacitor.config.json` non impacchetta il bundle React: la WebView
nativa carica direttamente `https://reflexi.it/app/` (`server.url`), quindi la
maggior parte delle modifiche al frontend web arriva automaticamente anche
nell'app mobile, senza rebuild — **tranne** cambi che toccano plugin nativi,
permessi, icona o config Capacitor, che richiedono rebuild + reinstallazione.

- **Status bar**: la sola configurazione statica `plugins.StatusBar` in
  `capacitor.config.json` non è affidabile quando la WebView carica un URL
  remoto — serve anche la chiamata esplicita a runtime. Fatto in `App.jsx`
  (componente `App()`, gira sempre, anche su login/paywall):
  `StatusBar.setOverlaysWebView({ overlay: false })` +
  `setBackgroundColor`/`setStyle`, dietro un guard `Capacitor.isNativePlatform()`.
- **Tasto indietro hardware**: gestito in `App.jsx` dentro `Shell` (serve lo
  stato `view`, che non esiste nel componente `App()` top-level) via
  `@capacitor/app`'s `backButton` listener. Su qualunque view diversa da
  `dashboard` non fa nulla (nessuna navigazione automatica nella cronologia
  della WebView — comportamento voluto, non un bug). Sulla dashboard, doppio
  tap entro 2s chiude l'app (`CapacitorApp.exitApp()`), con un toast "Premi
  ancora per uscire" al primo tap.
- **Lato nativo** (`frontend/android/app/src/main/java/.../MainActivity.java`)
  resta lo scaffold di default di Capacitor (`BridgeActivity` vuota) — tutta
  la logica sopra passa dai plugin JS, non serve codice nativo custom.
- **Build**: questa VPS non ha Node/Java/Android SDK — la build va sempre
  fatta in locale (vedi `frontend/MOBILE_SETUP.md`): `npm install` →
  `npx cap sync android` (propaga `capacitor.config.json` e i plugin al
  progetto nativo) → Android Studio, **incrementando `versionCode`** in
  `android/app/build.gradle` prima di ogni build firmata da reinstallare.

## Note di dominio da conoscere prima di modificare questi moduli

- **Quota di proprietà mutui**: propagata "alla fonte" — quando un mutuo con
  rata ricorrente collegata viene creato/modificato, l'importo della
  ricorrenza generata è già la quota (non la rata intera). Analisi/Budget/
  Riepilogo leggono le transazioni reali generate da quella ricorrenza, quindi
  ereditano la correzione senza logica duplicata. Le proiezioni di cassa in
  Analisi usano invece sempre la rata intera (è quanto esce davvero dal conto).
- **Analisi predittiva** (`analytics.php`): ogni mese futuro proiettato si
  scompone in parte nota (ricorrenze attive valide in quel mese specifico,
  rispettando eventuali date di fine) + parte variabile (media storica reale).
  Non è una semplice estrapolazione della media corrente.
- **Multi-valuta investimenti**: i totali di portafoglio restano separati per
  valuta di proposito (nessuna conversione automatica silenziosa).
- **Trasferimenti tra conti**: una singola transazione di tipo
  `trasferimento` (non doppia scrittura), esclusa dai grafici entrate/uscite.
