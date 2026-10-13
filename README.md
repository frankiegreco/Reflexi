# Reflexi — finanza personale, mutui, investimenti e analisi predittiva

Web app di finanza personale multi-utente: conti multipli, transazioni
ricorrenti, mutui (piano di ammortamento "alla francese"), investimenti
(quotazioni live, costo medio ponderato), piani di accumulo (PAC), budget per
categoria, patrimonio netto (conti, investimenti, immobili, conti fruttiferi),
obiettivi di risparmio, indicatori FIRE, fondi pensione, comparatore tassi
mutuo e analisi predittiva (medie storiche, simulazione Monte Carlo,
rilevamento automatico di spese ricorrenti nascoste). Inserimento dati
manuale, nessuna connessione diretta a conti bancari.

Nome di lavoro interno del progetto: **Sparagno** (vedi `docs/ROADMAP.md`).

Stack: **PHP 8.2 + Apache** (backend/API, nessun framework) + **MariaDB**
(dati) + **React 18 + Vite + Tailwind** (frontend) + **Caddy** (reverse proxy,
HTTPS automatico) + **Adminer** (opzionale, ispezione DB), tutto orchestrato
con Docker Compose.

> Il frontend React vive in `frontend/`, compilato e copiato in
> `webroot/app/` prima del deploy (l'app autenticata vive sotto `/app/`; la
> radice del dominio ospita la landing page pubblica in PHP). Istruzioni di
> build complete in `frontend/README.md`. Il backend PHP in `webroot/api/` è
> identico in entrambi i casi.

## 1. Autenticazione, multi-utente e abbonamento

L'app richiede login: ogni dato (conti, transazioni, mutui, investimenti,
budget, obiettivi, ecc.) è isolato per utente tramite sessione PHP e colonna
`user_id` su ogni tabella. La registrazione è aperta a chiunque dalla
schermata di login — non c'è approvazione manuale. Ogni nuovo utente riceve
automaticamente le categorie di default e un conto "Conto principale" per
iniziare.

### 1.1 Trial e abbonamento (Stripe)

Ogni nuovo utente parte con **180 giorni di trial gratuito** (calcolato dalla
data di iscrizione), poi deve attivare un abbonamento — mensile o annuale,
entrambi con le stesse funzionalità — per continuare ad accedere. La
sottoscrizione passa da Stripe Checkout (`stripe_checkout.php`), la gestione
del metodo di pagamento/cancellazione dal Customer Portal Stripe
(`stripe_portal.php`), e gli eventi asincroni (rinnovo, mancato pagamento,
cancellazione) arrivano via webhook (`stripe_webhook.php`) che aggiorna
`subscription_status` sull'utente. Un abbonamento in stato `past_due` (pagamento
fallito ma non ancora cancellato) mantiene comunque l'accesso per qualche
giorno di margine prima di bloccare l'utente, per non tagliare fuori chi ha
avuto un problema temporaneo con la carta. Se il trial scade e non c'è un
abbonamento attivo, il frontend mostra la schermata di paywall (`Paywall.jsx`)
al posto dell'app.

Variabili d'ambiente richieste (in `.env`): `STRIPE_SECRET_KEY`,
`STRIPE_WEBHOOK_SECRET`, `STRIPE_PRICE_MONTHLY`, `STRIPE_PRICE_ANNUAL`.

### 1.2 Installare PHPMailer e Stripe SDK (dipendenze Composer)

Le email di verifica account, reset password e digest periodico usano
PHPMailer; l'integrazione pagamenti usa `stripe/stripe-php`.

**Docker**: il `Dockerfile` esegue `composer install` automaticamente al
build (`docker compose up -d --build`), non serve fare nulla a mano.

**XAMPP**: installa Composer (https://getcomposer.org/download/), poi:
```bash
cd webroot
composer install
```
Questo crea una cartella `webroot/vendor/` — necessaria perché l'app funzioni.

### 1.3 Configurare l'invio email (SMTP)

Senza SMTP configurato, **la registrazione resta bloccata**: l'utente viene
creato ma l'email di verifica non parte, e senza verificare l'email non si può
accedere. Per iniziare rapidamente in fase di test, il modo più semplice è un
account gratuito su [Mailtrap.io](https://mailtrap.io) (sandbox SMTP: le email
non vengono davvero recapitate, le vedi in una casella di test — perfetto per
sviluppo). Per la produzione, un account Gmail con "password per le app",
oppure servizi come Brevo o SendGrid.

**Docker**: imposta le variabili `SMTP_*` nel file `.env` (vedi
`.env.example` nella root del progetto), poi `docker compose up -d` per
applicarle.

**XAMPP**: copia `webroot/.env.example` in `webroot/.env` e compila i valori
SMTP — viene letto automaticamente da `db.php` (Docker non serve, usa già le
sue variabili d'ambiente).

### 1.4 Limiti di sicurezza onesti da conoscere

- **Isolamento dati**: ogni lettura/scrittura filtra per `user_id` della
  sessione — un utente non può vedere i dati di un altro. Ogni riferimento tra
  tabelle passato dal client (es. l'`account_id` di una transazione) viene
  verificato con `assert_owned()` prima di essere usato in una scrittura, così
  un utente autenticato non può agganciare i propri dati a risorse di un
  altro utente semplicemente passando un ID diverso.
- **Sessioni**: cookie `httponly`, `SameSite=Lax`. In produzione l'app gira
  dietro Caddy con HTTPS automatico (vedi sezione 3) — il cookie di sessione
  non deve mai viaggiare in chiaro.
- **Rate limiting**: tentativi di login/registrazione/reset password sono
  limitati per IP (tabella `rate_limit_attempts`), letto da
  `X-Forwarded-For` perché dietro il reverse proxy Caddy `REMOTE_ADDR`
  mostrerebbe l'IP del proxy, non del visitatore.
- **Cache prezzi condivisa**: `price_cache` e `price_history` restano globali
  tra tutti gli utenti (per ticker) di proposito — riduce le chiamate a Yahoo
  Finance, e i prezzi di mercato non sono comunque un dato privato.

## 2. Requisiti e sviluppo locale

### 2.1 Requisiti sulla VM/VPS

- Docker + Docker Compose plugin installati (`docker --version`, `docker compose version`)
- Una VM/VPS Debian/Ubuntu minimal va benissimo: 1 vCPU e 512MB RAM sono più
  che sufficienti per un carico da singolo homelab/piccolo numero di utenti.

### 2.2 Sviluppo locale con XAMPP (opzionale, prima della VM)

Puoi sviluppare e testare tutto "a banco" con XAMPP prima di spostarti sulla VM:

1. Copia il contenuto di `webroot/` dentro `htdocs/finanze/` di XAMPP.
2. Avvia Apache e MySQL dal pannello di controllo XAMPP.
3. Apri phpMyAdmin (`http://localhost/phpmyadmin`), crea un database `finanze`
   e importa `db/init.sql` dal tab "Importa".
4. Apri `http://localhost/finanze/app/` nel browser per l'app (la radice
   `http://localhost/finanze/` mostra la landing page statica).

Il file `webroot/api/db.php` è già pronto per funzionare così senza modifiche:
se non trova le variabili d'ambiente che userà Docker, usa i default XAMPP
(`localhost`, utente `root`, nessuna password). Quando poi passerai alla VM
con Docker Compose, le variabili d'ambiente definite in `.env` prenderanno
automaticamente il sopravvento — non devi toccare `db.php` una seconda volta.

Unica accortezza: verifica che l'estensione `pdo_mysql` sia attiva in PHP (in
XAMPP di solito lo è già di default — controllabile da
`http://localhost/dashboard/phpinfo.php`).

Per lavorare sul frontend con hot-reload, vedi `frontend/README.md`
(`npm run dev`, proxy verso il backend PHP configurato in `vite.config.js`).

## 3. Deploy in produzione (Docker + Caddy/HTTPS)

```bash
# copia il progetto sulla VPS, poi:
cd reflexi
cp .env.example .env
nano .env          # password DB, SITE_ADDRESS (dominio reale), SMTP, chiavi Stripe
docker compose up -d --build
```

Al primo avvio MariaDB crea automaticamente le tabelle e le categorie di
default (vedi `db/init.sql`), e viene creato un "Conto principale" di partenza
per ogni nuovo utente registrato.

Lo stack espone 4 servizi (vedi `docker-compose.yml`):
- `db`: MariaDB, dati nel volume `db_data`.
- `web`: PHP/Apache, monta `./webroot` come volume — modifiche ai file PHP
  sono live senza rebuild, porta interna `${WEB_PORT:-8080}` esposta anche
  sull'host per accesso diretto/debug.
- `adminer`: interfaccia web per ispezionare il DB, su `${ADMINER_PORT:-8081}`
  — rimuovibile da `docker-compose.yml` se non serve.
- `caddy`: reverse proxy davanti a `web`, ottiene automaticamente un
  certificato HTTPS (Let's Encrypt) per il dominio impostato in
  `SITE_ADDRESS` nel `.env` (es. `reflexi.it, www.reflexi.it`). In locale,
  lasciando `SITE_ADDRESS=localhost`, serve in HTTP semplice senza
  certificato. È il punto d'ingresso pubblico dell'app (porte 80/443).

## 4. Moduli funzionali

### 4.1 Mutui

Nella tab "Mutui" puoi registrare un mutuo con capitale erogato, tasso annuo,
durata in anni e data di erogazione. L'app calcola in automatico, con il
metodo di ammortamento "alla francese" (rata costante, lo standard italiano):

- rata mensile, piano di ammortamento completo (quota capitale/interessi per ogni rata)
- capitale residuo aggiornato ad oggi
- **simulazione di estinzione anticipata**: scegli una data ipotetica e l'app
  ti mostra quanto capitale dovresti versare per estinguere e quanti
  interessi risparmieresti rispetto a proseguire fino alla scadenza naturale
- un bottone per segnare il mutuo come effettivamente estinto a una certa data
- **simulazione di rientro anticipato** (scheda "Rientro anticipato"):
  ipotizzi versamenti volontari in conto capitale, sporadici (data + importo)
  e/o periodici fissi (importo, frequenza mensile/trimestrale/semestrale/
  annuale, data di inizio e fine facoltativa), e scegli se usarli per ridurre
  la **durata** (rata invariata) o la **rata** (scadenza invariata). L'app
  confronta il piano con e senza versamenti: interessi risparmiati, nuova
  data di fine o nuova rata, eventuale penale (%) e grafico del residuo. Il
  calcolo parte dal residuo di oggi e usa il tasso del piano mese per mese
  (comprese variazioni future già inserite); ogni versamento vale come
  riduzione del debito dalla sua data (la rata successiva paga già gli
  interessi sul residuo ridotto). Gira tutto nel browser
  (`frontend/src/lib/loanPrepay.js`) e lo scenario resta salvato solo nel
  browser, non nel database
- **versamenti registrati** (stessa scheda, "Registra versamento" o il
  pulsante "Registra" accanto a un versamento sporadico simulato): un
  versamento già fatto (data non futura, importo inferiore al residuo) entra
  nel piano reale del mutuo (tabella `loan_prepayments`, migrazione 048) e
  riduce durata o rata a scelta. Residuo, scadenza, rata, patrimonio netto e
  proiezioni si ricalcolano; la rata ricorrente collegata viene riallineata
  (importo e data di fine). Facoltativamente registra anche l'uscita sul
  conto, per la sola quota di proprietà. Eliminando il versamento il piano
  torna com'era (e, se richiesto, viene eliminata anche l'uscita)

Se spunti "Crea automaticamente la rata mensile come transazione ricorrente",
la rata calcolata viene registrata anche come ricorrenza mensile nel conto che
scegli — così compare regolarmente nelle tue uscite e nei grafici del
riepilogo, senza doverla inserire a mano ogni mese. Se poi estingui il mutuo,
la ricorrenza collegata viene disattivata automaticamente.

Nel Riepilogo, se hai mutui attivi, vedrai anche tre indicatori aggregati:
debito residuo totale, rata mensile complessiva, e interessi ancora da pagare
su tutti i mutui in corso.

**Quota di proprietà** (campo "Quota di proprietà (%)", default 100): per un
mutuo cointestato, ad es. al 50%, mostra accanto alle cifre reali del mutuo
intero anche la tua quota personale di capitale residuo, rata e interessi. I
totali aggregati nel Riepilogo riflettono la quota (è la lettura corretta per
il tuo patrimonio netto personale); le proiezioni della tab Analisi restano
invece basate sulla rata intera, perché quel denaro esce davvero dal conto
ogni mese a prescindere da chi ne ha la proprietà legale. La quota si propaga
"alla fonte": quando crei/modifichi un mutuo con rata ricorrente collegata,
l'importo di quella ricorrenza viene impostato sulla quota reale — Analisi,
Budget e Riepilogo, lavorando sulle transazioni reali generate, ereditano la
correzione senza logica duplicata. **Limite onesto**: questo corregge solo le
rate generate da quel momento in poi; lo storico già registrato con l'importo
pieno va corretto a mano dalla tab Transazioni, se necessario.

**Comparatore tassi** (tab "Confronta mutui" / `reference_rates.php`): una
tabella di riferimento con tassi di mercato reali (banca, TAN/TAEG, rata,
profilo del mutuo, fonte e data di osservazione), popolata da una ricerca
periodica e aggiornata manualmente — non un'interrogazione live alle banche,
sullo stesso principio dei portali di comparazione mutui.

### 4.2 Investimenti

Registri manualmente le operazioni di acquisto e vendita di ETF, azioni, ecc.,
identificando ogni strumento con il suo **ticker** (univoco). L'app calcola in
automatico, con il metodo del **costo medio ponderato**:

- quantità in portafoglio e prezzo medio di carico
- quotazione live del ticker (via Yahoo Finance, nessuna chiave richiesta)
- valore attuale della posizione e plus/minusvalenza non realizzata (€ e %)
- plusvalenze/minusvalenze **realizzate** quando vendi (parzialmente o del tutto)
- **imposta sulle plusvalenze**: aliquota configurabile per strumento (26% di
  default, es. 12,5% per titoli di stato), calcolata e trattenuta ad ogni
  vendita così il movimento di cassa collegato riflette il netto realmente
  accreditato, non il lordo
- storico del valore di portafoglio nel tempo (`portfolio_history.php`,
  `price_history.php`), non solo la quotazione attuale

**Formato del ticker**: deve essere quello riconosciuto da Yahoo Finance.
Titoli quotati a Piazza Affari terminano in `.MI` (es. `VWCE.MI`, `SWDA.MI`,
`ENI.MI`); titoli USA di solito non hanno suffisso (es. `AAPL`). Verifica il
ticker esatto su finance.yahoo.com prima di inserirlo (`instrument_search.php`
aiuta nella ricerca).

**Cache delle quotazioni**: per non interrogare l'API esterna ad ogni
caricamento, il prezzo viene tenuto in cache 15 minuti. Il bottone "Aggiorna
quotazioni" forza un refresh immediato.

**Limiti da tenere presenti**:
- L'endpoint Yahoo Finance usato non è un'API ufficiale documentata — funziona
  bene in pratica ma potrebbe cambiare o smettere di funzionare senza
  preavviso. Se succede, l'app mostra "Quotazione non disponibile" senza
  bloccarsi, e continua a mostrare l'ultimo prezzo noto marcato come non
  aggiornato.
- **Multi-valuta**: se hai strumenti in valute diverse (es. un ETF in EUR e
  un'azione USA in USD), i totali di portafoglio vengono mostrati **separati
  per valuta**, non convertiti in un'unica cifra — evita di sommare mele con
  pere in modo silenzioso. Se preferisci un totale unico in EUR, per ora è un
  calcolo che devi fare tu con il cambio del giorno.
- Se spunti "Registra anche il movimento di cassa", l'operazione crea anche
  una transazione normale (uscita per gli acquisti, entrata per le vendite)
  nel conto che scegli, così compare nei grafici del riepilogo generale.

**Piani di accumulo (PAC)** (`investment_plans.php`): investi un importo fisso
a intervalli regolari (settimanale/mensile/annuale) in uno strumento; l'app
calcola da sola le quote acquistate in base al prezzo del giorno
dell'esecuzione, con una data di inizio/fine opzionale.

### 4.3 Budget

Nella tab "Categorie"/"Budget" trovi una tabella dove imposti un limite di
spesa mensile per qualunque categoria di uscita. Il limite vale ogni mese
finché non lo cambi o lo rimuovi — non serve reimpostarlo a ogni inizio mese.
Se attivi il **rollover** su una categoria, l'eccedenza non spesa in un mese
si riporta al mese successivo invece di azzerarsi.

Nel Riepilogo, se hai almeno un budget impostato, compare una card con una
barra di avanzamento per categoria: verde sotto l'80%, gialla tra 80% e 100%,
rossa (con icona di avviso) se sfori. È calcolato sul mese corrente in corso,
non su un periodo che scegli tu. Ogni categoria può anche essere classificata
come necessità/desiderio/risparmio, usata nel riquadro "Spending Ratios".

### 4.4 Analisi predittiva

Nessuna migration richiesta: usa solo dati già presenti (transazioni,
ricorrenze, mutui, budget). Nella tab "Analisi" trovi:

**Storico (ultimi 6/12 mesi completi, mese in corso escluso)**: reddito medio,
spesa media, risparmio medio, tasso di risparmio. Sono esclusi acquisti/
vendite di investimenti e trasferimenti tra conti — non sono spesa/entrata di
consumo, sono allocazione di capitale, e mescolarli avrebbe falsato il tasso
di risparmio.

**Come funziona la proiezione futura** — il punto cruciale da capire: ogni
mese futuro viene scomposto in due parti:
- **Parte nota/certa**: la somma delle ricorrenze attive (stipendio,
  abbonamenti, rate mutuo) effettivamente valide in quel mese specifico — se
  una ricorrenza ha una data di fine (es. un mutuo che finisce, un
  abbonamento con scadenza), da quel mese in poi non viene più conteggiata.
  Sono inclusi anche i mutui attivi senza una rata ricorrente collegata.
- **Parte variabile**: tutto il resto (spesa quotidiana, ristoranti, ecc.),
  stimata con la **media storica reale** degli ultimi mesi — non una
  proiezione a caso, ma il tuo comportamento effettivo.

Questo significa che la proiezione a 12 mesi riflette correttamente, ad
esempio, la fine di un mutuo o l'aggiunta di una nuova ricorrenza futura,
invece di limitarsi a ripetere la media di oggi all'infinito.

**Mese in corso**: le uscite/entrate variabili di quanto già registrato
vengono proiettate a fine mese con un semplice "tasso giornaliero" (spesa
finora ÷ giorni trascorsi × giorni totali del mese) sommato alla parte
nota/ricorrente esatta. Il "margine discrezionale residuo" ti dice quanto
puoi ancora spendere in spese variabili questo mese senza superare la tua
media storica.

**Avvisi per categoria**: per ogni categoria di spesa, proietta il ritmo
attuale a fine mese e lo confronta con il budget impostato (se c'è) o con la
media storica di quella categoria (altrimenti). Compaiono solo le categorie
al 90% o oltre del riferimento, ordinate per severità.

**Due funzioni avanzate**, ispirate a come lavorano i grandi gestori di
finanza personale:

- **Rilevamento automatico di spese ricorrenti nascoste** (come Rocket
  Money/Truebill). Analizza tutte le uscite non ancora collegate a una
  ricorrenza, le raggruppa per categoria + importo simile (tolleranza 10%), e
  verifica se gli intervalli tra le occorrenze sono regolari nel tempo
  (statisticamente: coefficiente di variazione basso). Se trova almeno 3
  occorrenze con cadenza regolare (settimanale, mensile, trimestrale,
  semestrale, annuale), la segnala come probabile abbonamento o spesa fissa
  mai dichiarata, con un bottone per registrarla come ricorrenza in un clic.
  **Limite onesto**: è un'euristica basata su importo+intervallo, non
  riconosce il nome del commerciante (l'app non ha quel dato) — due spese
  diverse nella stessa categoria con importo simile e cadenza simile per
  coincidenza potrebbero essere segnalate insieme.
- **Simulazione Monte Carlo** (come Personal Capital/Empower per la
  pianificazione pensionistica, adattata al breve termine). Invece di
  proiettare un'unica cifra "media", genera 2.000 scenari futuri: per ogni
  mese futuro pesca casualmente (con reinserimento, tecnica del "bootstrap")
  uno dei risparmi netti variabili realmente osservati in un mese storico, e
  lo somma alla parte nota/ricorrente di quel mese. Il risultato è una fascia
  di probabilità (10°-90° percentile) invece di una singola retta, più la
  probabilità che il risparmio cumulato finale sia positivo. **Limite
  onesto**: con solo 6 mesi di storico, il bootstrap pesca da un campione
  piccolo — più mesi di dati accumuli, più la simulazione diventa
  statisticamente solida. Con meno di 3 mesi di storico la simulazione non
  viene nemmeno calcolata (comparirebbe un risultato fuorviante).

**Limiti onesti generali**: con meno di 3 mesi di storico l'app calcola
comunque tutto, ma lo segnala con un banner — le medie su pochi dati non sono
affidabili; la proiezione della parte variabile assume che il comportamento
di spesa resti simile a quello storico, non prevede eventi eccezionali che tu
sai ma l'algoritmo no.

### 4.5 Patrimonio netto, obiettivi di risparmio e conti fruttiferi

- **Patrimonio netto** (`lib_networth.php`, condiviso da dashboard, report e
  FIRE): somma saldo liquido dei conti + valore investimenti + valore immobili
  − debiti residui (mutui). Uno snapshot giornaliero viene salvato in
  `net_worth_history` per mostrare un vero andamento nel tempo nei report,
  non solo il valore di oggi.
- **Immobili** (`real_estate.php`): valore stimato di un immobile, con un
  mutuo collegabile opzionalmente (per calcolare correttamente il debito
  residuo associato nel patrimonio netto).
- **Conti fruttiferi** (`interest_accounts.php`): capitale che matura
  interesse a un tasso noto, con aliquota fiscale sull'interesse ed
  eventuale data di fine vincolo. Concettualmente distinti dai conti
  transazionali (`accounts`): quando un conto normale ha
  `interest_enabled` attivo, l'interesse maturato genera invece una vera
  transazione che aumenta il saldo reale del conto (mensile/trimestrale/
  annuale), così entra automaticamente nel patrimonio netto senza gestione
  separata.
- **Obiettivi di risparmio** (`savings_goals.php`): un importo target, una
  data di inizio e (opzionale) una data obiettivo, per tracciare il progresso
  verso un traguardo specifico (es. fondo emergenza, acconto casa).

### 4.6 FIRE e fondi pensione

- **FIRE** (`fire.php`): calcola il "numero FIRE" (spesa annua media × 100 /
  tasso di prelievo configurato, regola del 4% di default) sulla spesa media
  reale degli ultimi 12 mesi (esclusi investimenti/trasferimenti), lo
  confronta col patrimonio investibile attuale (liquidità + investimenti), e
  proietta quanti anni mancano al traguardo dato il risparmio mensile attuale
  e il rendimento atteso annuo — entrambi i parametri (tasso di prelievo,
  rendimento atteso) sono configurabili per utente.
- **Fondi pensione** (`pension_funds.php`): registri i fondi (negoziale,
  aperto, PIP, TFR aziendale) con saldo attuale, contributo mensile proprio ed
  eventuale contributo del datore di lavoro, e rendimento atteso — per
  proiettare la crescita nel tempo separatamente dal resto del portafoglio
  investimenti.

## 5. Transazioni ricorrenti e digest email

Le ricorrenze non si generano da sole in background: vanno "processate". Due modi:

1. **Manuale**: bottone "Genera dovute ora" nella tab Ricorrenti.
2. **Automatico via cron sulla VM** (consigliato, così non te ne devi ricordare):

```bash
crontab -e
# aggiungi una riga per l'esecuzione ogni giorno alle 7:00
0 7 * * * curl -s -X POST "http://localhost:8080/api/recurring.php?action=process" > /dev/null
```

Ogni ricorrenza dovuta genera automaticamente la transazione corrispondente e
aggiorna la data della prossima occorrenza — se salti dei giorni, recupera
tutte le occorrenze arretrate, non solo l'ultima.

**Digest email periodico** (`webroot/cron/send_digest.php`): se un utente ha
impostato una preferenza `digest_frequency` (settimanale o mensile, default
"nessuno") nel Profilo, questo script — anch'esso da schedulare via crontab —
invia via SMTP un riepilogo periodico, evitando doppi invii tramite
`last_digest_sent_at`.

## 6. Aggiornare l'app e il database

I file PHP sono montati come volume (`./webroot:/var/www/html`), quindi puoi
modificarli direttamente sulla VM e vedrai l'effetto a refresh, senza
rebuild. Serve un rebuild solo se cambi il `Dockerfile` o `composer.json` (es.
nuove estensioni PHP o dipendenze):

```bash
docker compose up -d --build
```

Le installazioni nuove ottengono lo schema completo da `db/init.sql` al primo
avvio di MariaDB. Su un'installazione già esistente, applica manualmente ogni
nuova migration in `db/migrations/` (in ordine numerico, solo quelle non
ancora applicate) con:

```bash
docker exec -i finanze_db mysql -u root -p"$DB_ROOT_PASSWORD" finanze < db/migrations/0NN_nome.sql
```
(su XAMPP: `mysql -u root finanze < db/migrations/0NN_nome.sql`)

Un errore "duplicate column"/"table already exists" significa che quella
migration è già stata applicata — va ignorato.

Per il frontend, dopo ogni modifica in `frontend/src/`: `npm run build` in
`frontend/` e ricopia `dist/*` in `webroot/app/` (vedi `frontend/README.md`).

## 7. Backup

Tutto lo stato vive nel volume Docker `db_data` (MariaDB). Due opzioni:

**Dump logico (consigliato per un backup facile da ispezionare):**
```bash
docker exec finanze_db mysqldump -u root -p"$DB_ROOT_PASSWORD" finanze > backup_$(date +%F).sql
```

**Snapshot della VM/VPS** — copre sia il DB che i file in un colpo solo, utile
come backup complementare al dump logico.

## 8. Struttura del progetto

```
reflexi/
├── docker-compose.yml         servizi: db (MariaDB), web (PHP/Apache), adminer, caddy (reverse proxy HTTPS)
├── Caddyfile
├── .env.example                credenziali DB, porte, APP_URL, SMTP, SITE_ADDRESS, chiavi Stripe
├── docs/
│   └── ROADMAP.md
├── db/
│   ├── init.sql                schema completo per installazioni nuove (utenti multi-tenant, Stripe, ecc.)
│   └── migrations/             applicate in ordine numerico su installazioni esistenti
│       ├── 001_loans.sql                      modulo Mutui
│       ├── 002_investments.sql                modulo Investimenti
│       ├── 003_budgets.sql                    modulo Budget
│       ├── 004_loan_ownership.sql             quota di proprietà mutui
│       ├── 005_auth_multitenancy.sql          utenti + isolamento dati per utente
│       ├── 006_rate_limiting.sql              rate limiting login/registrazione/reset
│       ├── 007_investment_plans.sql           piani di accumulo (PAC)
│       ├── 008_capital_gains_tax.sql          imposta su plusvalenze investimenti
│       ├── 009_goals_digest_history_networth.sql  storico prezzi, obiettivi risparmio, digest, rollover budget, immobili
│       ├── 010_stripe_subscriptions.sql       abbonamento/trial via Stripe
│       ├── 011_interest_accounts.sql          conti fruttiferi dedicati
│       ├── 012_account_interest.sql           interesse sui conti transazionali esistenti
│       ├── 013_fire_pension_reports.sql       FIRE, fondi pensione, storico patrimonio netto
│       ├── 014_category_classification.sql    classificazione categorie (necessità/desiderio/risparmio)
│       ├── 015_account_currency.sql           valuta per conto
│       ├── 016_reference_mortgage_rates.sql   tabella tassi mutuo di riferimento (comparatore)
│       ├── 017_mortgage_rate_calc_fields.sql  campi aggiuntivi calcolo tassi di riferimento
│       ├── 018_add_bancoposta_rate.sql        tasso di riferimento Bancoposta
│       ├── 019_dedupe_reference_mortgage_rates.sql  deduplica tassi di riferimento
│       └── 019_extend_trial_180_days.sql      estensione trial a 180 giorni per utenti già in trial
├── frontend/                   sorgenti React + Tailwind dell'app (vedi frontend/README.md per build/deploy)
│   └── src/
│       ├── App.jsx              mappa VIEWS (sezione -> componente), nessun router
│       ├── api.js                unico client REST verso /api/*.php
│       ├── context/              AuthContext (sessione), DataContext (dati condivisi + bump() per invalidare)
│       ├── components/           Sidebar, Modal, StatCard, ProgressBar, ServiceIcon
│       ├── lib/                  dati statici (banche, categorie abbonamenti, fondi pensione, formattazione)
│       └── views/                una vista per sezione: Dashboard, Transactions, Accounts, Loans, LoanDetail,
│                                  Investments, Comparator, Fire, PensionFund, Report, Budget, Categories,
│                                  Recurring, Subscriptions, Goals, Analytics, Profile, Auth, Paywall, TransactionModal
└── webroot/
    ├── index.php                landing page pubblica (root del dominio)
    ├── about.php, faq.php, piani.php, privacy.php, termini.php, cancella-account.php   pagine statiche pubbliche
    ├── calcolatori.php + calcolatore-*.php    calcolatori finanziari standalone (inflazione, interesse
    │                                          composto, pensione, patrimonio netto, budget 50/30/20, ecc.)
    ├── mutui.php, investimenti.php, budget.php, analisi.php   pagine marketing per singolo modulo
    ├── _nav.php, _footer.php    header/footer condivisi, inclusi dalle pagine statiche sopra
    ├── css/style.css, js/app.js  residui della vecchia UI Bootstrap/vanilla JS pre-React: non più
    │                             referenziati da nessuna pagina, non toccare/estendere
    ├── assets/                  build compilata della landing/pagine statiche
    ├── app/                     output di "npm run build" del frontend React (copiato da frontend/dist/)
    ├── cron/
    │   └── send_digest.php      invio digest periodici via SMTP (schedulato da crontab sulla VM)
    ├── composer.json            dipendenze PHPMailer + Stripe SDK
    ├── .env.example             config SMTP per XAMPP (Docker usa le sue variabili d'ambiente)
    └── api/
        ├── db.php                connessione PDO, sessione PHP, gestore errori JSON, helper auth/rate-limit
        ├── lib_mailer.php        invio email via SMTP (verifica, reset password, digest)
        ├── lib_seed.php          categorie/conto di default per un nuovo utente registrato
        ├── lib_networth.php      calcolo patrimonio netto condiviso (dashboard, report, FIRE)
        ├── lib_stripe.php        wrapper Stripe SDK (checkout, portale, verifica accesso attivo)
        ├── loan_math.php         formule di ammortamento "alla francese" condivise
        ├── auth/
        │   ├── register.php, login.php, logout.php, me.php, change_password.php, delete_account.php
        │   └── verify_email.php, resend_verification.php, forgot_password.php, reset_password.php
        ├── accounts.php          CRUD conti + saldo calcolato + interesse su conto
        ├── categories.php        CRUD categorie + classificazione necessità/desiderio/risparmio
        ├── transactions.php      CRUD transazioni con filtri
        ├── recurring.php         CRUD ricorrenze + generazione automatica
        ├── loans.php             CRUD mutui + piano di ammortamento + simulazione estinzione
        ├── loan_prepayments.php  versamenti volontari in conto capitale (riducono durata o rata del piano)
        ├── reference_rates.php   tassi mutuo di riferimento per il comparatore
        ├── instruments.php       CRUD strumenti finanziari (ticker, nome, tipo, valuta, aliquota fiscale)
        ├── instrument_search.php ricerca ticker
        ├── investments.php       CRUD operazioni di acquisto/vendita
        ├── investment_plans.php  piani di accumulo (PAC)
        ├── portfolio.php         calcolo posizioni, costo medio, plus/minusvalenze
        ├── portfolio_history.php storico valore portafoglio
        ├── price_fetch.php       quotazioni live da Yahoo Finance con cache (globale, non per utente)
        ├── price_history.php     storico prezzi giornalieri
        ├── budgets.php           CRUD budget mensili per categoria + rollover + calcolo speso/percentuale
        ├── analytics.php         analisi predittiva: medie storiche, proiezione futura, Monte Carlo, alert
        ├── savings_goals.php     CRUD obiettivi di risparmio
        ├── interest_accounts.php CRUD conti fruttiferi dedicati
        ├── real_estate.php       CRUD immobili (per patrimonio netto)
        ├── pension_funds.php     CRUD fondi pensione + proiezione
        ├── fire.php              calcolo indicatori FIRE (indipendenza finanziaria)
        ├── reports.php           report aggregati (patrimonio netto nel tempo, ecc.)
        ├── dashboard.php         dati aggregati per i grafici
        ├── export.php            export CSV con gli stessi filtri della lista
        ├── stripe_checkout.php   avvio Stripe Checkout per un piano
        ├── stripe_portal.php     link al Customer Portal Stripe (gestione/cancellazione)
        ├── stripe_webhook.php    ricezione eventi Stripe (rinnovo, mancato pagamento, cancellazione)
        └── vendor/                dipendenze Composer (PHPMailer, Stripe SDK)
```

## 9. Note su multi-conto e trasferimenti

Ogni conto ha un saldo calcolato dinamicamente (saldo iniziale + entrate −
uscite ± trasferimenti), non un campo statico: quindi non "si disallinea"
mai, anche se modifichi transazioni passate. Un trasferimento tra due tuoi
conti si registra come un'unica transazione di tipo "trasferimento" (conto
sorgente → conto destinazione), non genera doppia scrittura né compare come
entrata/uscita nei grafici mensili.

## 10. Sicurezza

L'app è multi-utente con autenticazione a sessione (vedi sezione 1) ed è già
in produzione, esposta pubblicamente dietro il reverse proxy Caddy con HTTPS
automatico. Punti da tenere presenti:

- Il cookie di sessione è `httponly`/`SameSite=Lax`: in produzione deve
  sempre viaggiare su HTTPS (garantito da Caddy quando `SITE_ADDRESS` è un
  dominio reale, non `localhost`).
- Ogni riferimento tra tabelle passato dal client è validato con
  `assert_owned()` prima di essere usato (vedi 1.4) — non fidarti solo del
  filtro `user_id` in lettura.
- Le credenziali (DB, SMTP, chiavi Stripe) vivono solo in `.env`, mai
  committate; il webhook Stripe è protetto da `STRIPE_WEBHOOK_SECRET` per
  verificare che gli eventi arrivino davvero da Stripe.
- Se accedi all'infrastruttura (Adminer, Docker) da fuori la rete della
  VPS, preferisci una VPN (es. WireGuard/Tailscale) piuttosto che esporre
  quelle porte pubblicamente — solo l'app stessa (via Caddy) è pensata per
  essere pubblica.
