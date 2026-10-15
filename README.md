# Reflexi — finanza personale, mutui, investimenti e analisi predittiva

Web app di finanza personale multi-utente: conti multipli, transazioni
ricorrenti, mutui (piano di ammortamento "alla francese"), investimenti
(quotazioni live, costo medio ponderato), piani di accumulo (PAC), budget per
categoria, patrimonio netto (conti, investimenti, immobili, conti fruttiferi),
obiettivi di risparmio, indicatori FIRE, fondi pensione, comparatore tassi
mutuo e analisi predittiva (medie storiche, simulazione Monte Carlo,
rilevamento automatico di spese ricorrenti nascoste). Inserimento dati
manuale, nessuna connessione diretta a conti bancari.

Stack: **PHP 8.2** (backend/API, nessun framework) + **MariaDB** (dati) +
**React 18 + Vite + Tailwind** (frontend). L'app autenticata vive sotto
`/app/`; la radice del dominio ospita il sito pubblico in PHP.

## 1. Account e abbonamento

L'app richiede login: ogni dato (conti, transazioni, mutui, investimenti,
budget, obiettivi, ecc.) è isolato per utente. La registrazione è aperta a
chiunque dalla schermata di login, con verifica dell'indirizzo email. Ogni
nuovo utente riceve automaticamente le categorie di default e un conto
"Conto principale" per iniziare.

Ogni nuovo utente parte con **180 giorni di trial gratuito** (calcolato dalla
data di iscrizione), poi deve attivare un abbonamento — mensile o annuale,
entrambi con le stesse funzionalità — per continuare ad accedere. La
sottoscrizione passa da Stripe Checkout, la gestione del metodo di
pagamento e la cancellazione dal Customer Portal Stripe. Un abbonamento con
pagamento fallito ma non ancora cancellato mantiene l'accesso per qualche
giorno di margine, per non tagliare fuori chi ha avuto un problema
temporaneo con la carta. Se il trial scade e non c'è un abbonamento attivo,
l'app mostra la schermata di abbonamento al posto dei dati.

## 2. Moduli funzionali

### 2.1 Mutui

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
  data di fine o nuova rata, eventuale penale (%) e grafico del residuo.
  Il **risparmio netto** sottrae agli interessi risparmiati la detrazione
  IRPEF persa (di default 19% degli interessi pagati nell'anno solare, su
  massimo 4.000 € l'anno per mutuo, contando gli interessi già pagati
  nell'anno in corso; disattivabile per mutui non prima casa) e la penale,
  con dettaglio del calcolo e tabella anno per anno. Il
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

### 2.2 Investimenti

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

### 2.3 Budget

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

### 2.4 Analisi predittiva

Usa solo dati già presenti (transazioni, ricorrenze, mutui, budget). Nella
tab "Analisi" trovi:

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

### 2.5 Patrimonio netto, obiettivi di risparmio e conti fruttiferi

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

### 2.6 FIRE e fondi pensione

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

## 3. Transazioni ricorrenti e riepilogo email

Ogni ricorrenza dovuta (stipendio, abbonamenti, rate) genera automaticamente
la transazione corrispondente ogni giorno e aggiorna la data della prossima
occorrenza — se salta dei giorni, recupera tutte le occorrenze arretrate, non
solo l'ultima. Il bottone "Genera dovute ora" nella tab Ricorrenti le genera
subito.

**Riepilogo email periodico**: dal Profilo ogni utente può scegliere di
ricevere un riepilogo settimanale o mensile (di default nessuno), senza
doppi invii.

## 4. Note su multi-conto e trasferimenti

Ogni conto ha un saldo calcolato dinamicamente (saldo iniziale + entrate −
uscite ± trasferimenti), non un campo statico: quindi non "si disallinea"
mai, anche se modifichi transazioni passate. Un trasferimento tra due tuoi
conti si registra come un'unica transazione di tipo "trasferimento" (conto
sorgente → conto destinazione), non genera doppia scrittura né compare come
entrata/uscita nei grafici mensili.

## 5. Struttura del progetto

```
reflexi/
├── docker-compose.yml          servizi: db (MariaDB), web (PHP/Apache), adminer, caddy
├── Caddyfile
├── docs/                       roadmap e note di progetto
├── build/                      script di build del sito pubblico (CSS Tailwind, immagini social, articoli News)
├── db/
│   ├── init.sql                schema completo per installazioni nuove
│   └── migrations/             NNN_*.sql, applicate in ordine numerico su installazioni esistenti
├── frontend/                   sorgenti React + Tailwind dell'app (+ progetto Android Capacitor in android/)
│   └── src/
│       ├── App.jsx             mappa VIEWS (sezione -> componente), nessun router
│       ├── api.js              unico client REST verso /api/*.php
│       ├── context/            AuthContext (sessione), DataContext (dati condivisi + bump() per invalidare)
│       ├── components/         Sidebar, Modal, StatCard, ProgressBar, SiteNav, ...
│       ├── lib/                calcoli lato client e dati statici (banche, fondi pensione, formattazione)
│       └── views/              una vista per sezione: Dashboard, Transactions, Accounts, Loans, LoanDetail,
│                               Investments, Markets, Forecasts, PaperTrading, Comparator, Fire, PensionFund,
│                               Report, Budget, Categories, Recurring, Subscriptions, Goals, Analytics,
│                               Profile, Auth, Paywall, AdminUsers, AdminLogs, modali di import e transazioni
└── webroot/
    ├── index.php               landing page pubblica (root del dominio)
    ├── mutui.php, investimenti.php, budget.php, analisi.php, patrimonio.php, obiettivi.php,
    │   fire-pensione.php, abbonamenti.php, previsioni.php, simulazione-trading.php
    │                           pagine pubbliche per singola funzione
    ├── calcolatori.php + calcolatore-*.php   calcolatori finanziari standalone
    ├── mercati.php, news.php, news-articolo.php   mercati e articoli
    ├── about.php, faq.php, piani.php, privacy.php, termini.php, cancella-account.php, 404.php
    ├── _nav.php, _footer.php, _seo.php, ...   parziali condivisi dalle pagine pubbliche
    ├── css/, fonts/, og/, assets/              stile compilato, font locali, immagini di anteprima social
    ├── app/                    build compilata del frontend React
    ├── cron/                   job periodici: ricorrenze, riepilogo email, cache e modello delle previsioni
    └── api/
        ├── db.php              connessione PDO, sessione, risposte JSON, helper auth/rate-limit/log
        ├── lib_*.php           logica condivisa (patrimonio netto, proiezioni, obiettivi, ricorrenze,
        │                       previsioni, email, Stripe, eliminazione utente, ...)
        ├── loan_math.php       formule di ammortamento "alla francese"
        ├── auth/               registrazione, login, verifica email, reset e cambio password, eliminazione account
        ├── accounts.php, categories.php, transactions.php, recurring.php, import.php, export.php
        ├── loans.php, loan_rates.php, loan_prepayments.php, reference_rates.php
        ├── instruments.php, instrument_search.php, investments.php, investment_plans.php,
        │   portfolio.php, portfolio_history.php, price_fetch.php, price_history.php
        ├── market_trends.php, forecasts.php, invest_advisor.php, paper_trading.php
        ├── budgets.php, analytics.php, savings_goals.php, goal_plan.php, interest_accounts.php,
        │   real_estate.php, pension_funds.php, fire.php, reports.php, dashboard.php
        ├── admin_users.php, admin_logs.php, admin_reminders.php   area amministrazione
        └── stripe_checkout.php, stripe_portal.php, stripe_webhook.php, pricing.php
```
