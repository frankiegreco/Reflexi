# Specifica: Indicatori di mercato, Previsioni e allocazione ipotetica

Questo file copre tre sezioni distinte dell'app, con scopi deliberatamente
diversi (in un caso opposti): **Indicatori** (solo fatti verificabili),
**Previsioni** (una stima predittiva vera e propria, prodotta da un
modello statistico addestrato) e l'**allocatore di investimento ipotetico**
dentro Previsioni (una raccomandazione di allocazione personalizzata su
importo e orizzonte specifici — un salto di categoria ulteriore rispetto
alle Previsioni generali, vedi Parte 3). Non vanno confuse né la logica
dell'una applicata alle altre.

---

## Parte 1 — Indicatori (fatti verificabili)

### Principio non negoziabile

Questa sezione mostra **solo fatti verificabili**: dati storici realmente
accaduti, o opinioni di terzi (analisti di banche d'affari) chiaramente
etichettate come tali. **Non deve mai esistere un numero, un indice o un
"punteggio" generato da Reflexi che suggerisca cosa accadrà in futuro** — non
un algoritmo proprietario di previsione, non un "indice di forza" inventato,
niente che sembri un segnale di acquisto/vendita anche indirettamente.
Ogni valore in questa sezione deve poter rispondere alla domanda "questo è
successo davvero?" con un sì (per lo storico) o "questa è l'opinione
dichiarata di chi?" con un nome verificabile (per i dati di terzi).

Se durante lo sviluppo emerge la tentazione di "combinare" più indicatori in
un unico punteggio riassuntivo (es. media pesata di rendimento + volatilità +
rating analisti in un singolo numero da 1 a 10) — **non farlo**. Mostra gli
indicatori separati, ognuno con la propria fonte. Un punteggio combinato
smette di essere un fatto e diventa un giudizio, anche se costruito da
ingredienti reali.

**Questo principio vale solo per questa sezione.** La Parte 2 (Previsioni)
combina deliberatamente più segnali in un unico numero predittivo — è una
scelta consapevole del titolare del progetto, presa nonostante questo
principio, per uno scopo dichiaratamente diverso (stima, non fatto). Vedi
il "perché" nella Parte 2.

### 1. Metriche storiche per singolo strumento (dati nostri, da `price_history`)

Endpoint `api/instrument_metrics.php?instrument_id=X`, calcola sui dati già
presenti in `price_history` per quel ticker (implementato in
`lib_instrument_metrics.php`):

- **Rendimento totale e annualizzato** su 1/3/5 anni (quanto storico è
  disponibile — se ce n'è meno di quanto richiesto, lo dice esplicitamente
  invece di calcolare su un periodo diverso senza avvisare)
- **Volatilità annualizzata** — deviazione standard dei rendimenti
  giornalieri sulla finestra più recente disponibile (fino a 1 anno),
  moltiplicata per la radice di 252 giorni di borsa (formula standard)
- **Max drawdown** — il calo percentuale più grande registrato dal punto più
  alto al punto più basso successivo, su tutto lo storico disponibile
- **Sharpe ratio approssimato** — (rendimento annualizzato − tasso privo di
  rischio ipotizzato, default 2%, configurabile) / volatilità annualizzata

Tutti calcoli standard di finanza quantitativa sul passato, etichettati
"Rendimento storico", mai "Rendimento atteso".

### 2. Opinioni di analisti esterni (da Yahoo Finance)

Modulo `financialData` dell'endpoint `quoteSummary` di Yahoo Finance —
richiede un handshake cookie+crumb (`fetch_yahoo_crumb` in
`price_fetch.php`; senza, l'endpoint risponde "Invalid Crumb"). Disponibile
per azioni con copertura analisti, quasi mai per ETF/crypto — verificato
live: risposta vuota per VWCE.MI, SXR8.DE. Cache 12h in `analyst_cache`
(migrazione 034), condivisa con la Parte 2.

Se disponibile:

> **Target price medio degli analisti**: 185€ (range 150€–210€, basato su
> 24 analisti — dato Yahoo Finance, non un'elaborazione di Reflexi)

Se non disponibile per uno strumento, la sezione va nascosta, non mostrata
vuota o a zero.

### 3. Dove vive nell'interfaccia

Dentro la vista strumento esistente in Investimenti (dove si apre il
grafico storico), sezione **"Indicatori"** sotto al grafico — non una
pagina separata. Card, una per indicatore, ciascuna con icona informativa
(tooltip) che spiega in una riga cosa significa quel numero.

### 4. Disclaimer obbligatorio, sempre visibile in questa sezione

> Dati storici e opinioni di terzi, non previsioni. Le performance passate
> non garantiscono risultati futuri. Reflexi non fornisce consulenza
> finanziaria né raccomandazioni di acquisto o vendita.

### Casi limite

- Strumento con meno di 30 giorni di `price_history`: mostra solo "Storico
  ancora insufficiente per calcolare questi indicatori"
- Criptovalute: volatilità più alta delle azioni/ETF — nessuna gestione
  speciale, è un fatto vero, non un errore
- Nessun dato analisti disponibile: nascondi la sezione 2 per quello
  strumento

---

## Parte 2 — Previsioni (stima predittiva)

### Perché questa sezione esiste, e perché contraddice la Parte 1 di proposito

Richiesta esplicita del titolare del progetto: una sezione che produca
davvero una percentuale di previsione di rendimento sui titoli/ETF in
portafoglio. Segnalato durante lo sviluppo che questo confligge con il
principio "solo fatti verificabili" della Parte 1, incluso il rischio che
generare previsioni di prezzo somigli a consulenza finanziaria non
autorizzata (rilevante per un'app di finanza personale con utenti
abbonati). Il titolare ha scelto consapevolmente di procedere comunque.
Di conseguenza:

- Questa sezione mostra **deliberatamente** un numero predittivo.
- Resta comunque **onesta sui suoi limiti**: mostra sempre da dove viene il
  numero (il modello, i suoi dati di training, la sua reale capacità
  predittiva misurata) invece di presentarlo come un fatto o una certezza.
- Non è consulenza finanziaria e non va usata come unico riferimento per
  una decisione di investimento — disclaimer sempre visibile in questa
  sezione, in `frontend/src/views/Forecasts.jsx`.

### Cos'è davvero il modello (e cosa non è)

Prima versione di questa sezione: una media pesata a mano (40% trend
storico + 40% upside da target price analisti + 20% momentum). Non era
"intelligenza artificiale" in nessun senso tecnico — solo una formula con
pesi decisi da chi scriveva il codice, non appresi da nessun dato.

Versione attuale (`lib_forecast_model.php`): una **regressione lineare
(OLS)** i cui coefficienti sono **appresi** da un backtest su tutto lo
storico prezzi tracciato da Reflexi (`price_history`, globale — ogni ticker
mai osservato dall'app, non solo quelli dell'utente che guarda la pagina).
Una regressione lineare addestrata su dati è ML nel senso tecnico minimo
del termine: i pesi vengono dai dati, non da chi scrive il codice. Non è
una rete neurale, non è un LLM, non "capisce" i mercati — è un modello
statistico semplice, e va descritto come tale, non gonfiato.

**Feature** (tutte calcolabili "a una data storica" solo dai prezzi, quindi
ricostruibili per ogni punto passato — condizione necessaria per generare
esempi di training):

- **Trend**: CAGR sui migliori 3 anni disponibili (o su tutto lo storico se
  meno di 3 anni)
- **Momentum**: variazione di prezzo (non annualizzata) sugli ultimi 90 giorni
- **Volatilità**: annualizzata, stessa formula della Parte 1
- **Max drawdown**: stesso calcolo della Parte 1

**Label del training**: il rendimento realmente realizzato nei 365 giorni
successivi alla data del campione — un fatto storico verificabile, usato
come bersaglio da imparare, non come input.

**Il target price medio degli analisti NON è una feature del modello.**
Reflexi non ha mai salvato uno storico delle opinioni di analisti nel
tempo (solo l'ultimo valore in cache) — non esiste un dato storico su cui
allenarsi onestamente. Resta visibile in Previsioni come **riferimento
separato**, mai fuso nel numero prodotto dal modello.

**Un modello separato per asset class (equity / bond / crypto), non uno
solo.** Prima versione: un'unica regressione pooled su tutti i ticker
insieme. Bug scoperto ampliando l'universo di training (vedi sotto): il
modello pooled prevedeva rendimenti a 12 mesi di **-60% / -74% per ETF
obbligazionari** (TLT, IEF, AGG, BND, LQD, HYG) — assurdo per fondi a
bassissima volatilità storica. Causa: i coefficienti erano dominati dai
ticker azionari/crypto molto più volatili nel training set, e il modello
estrapolava malissimo fuori da quel range per un bond ETF a bassa
volatilità/drawdown. Non un errore di calcolo, un limite intrinseco di un
modello lineare allenato su feature con scale molto diverse tra asset
class diverse. Soluzione (`forecast_asset_class()` in
`lib_forecast_model.php`, migrazione 036): tre training indipendenti,
ognuno solo sui ticker della propria classe, salvati come righe distinte
in `forecast_model` (colonna `asset_class`). La classificazione usa
`instruments.type`/la lista curata per crypto, e una lista fissa di ticker
bond ETF (`FORECAST_BOND_TICKERS` in `lib_forecast_universe.php`) per
distinguerli dagli ETF azionari — tutto il resto è 'equity'. Risultato
dopo la separazione: R² sale a 0.24 (equity) e 0.50 (bond) sul rispettivo
training set, e le previsioni per i bond ETF tornano in un range
plausibile (circa -2%/+4%, non più -74%). Il crypto resta a R² = 0.04 —
resta un problema difficile, la separazione non lo risolve, semplicemente
non lo aggrava più mescolandolo con altre classi.

### Come si addestra

`webroot/cron/retrain_forecast_model.php`, lanciato manualmente da riga di
comando dentro il container "web" (**mai** come endpoint HTTP — scansiona
tutto `price_history`, è un lavoro da batch). Per ogni ticker con
storico ≥ 395 giorni, campiona ogni 21 giorni lungo tutta la serie,
calcola le 4 feature "a quella data" e il rendimento realizzato nei 365
giorni successivi. I campioni si raggruppano per asset class (vedi sopra)
→ una regressione OLS per classe (equazioni normali, inversione di
matrice via Gauss-Jordan in `lib_forecast_model.php`, nessuna libreria
esterna). Ogni training salva una nuova riga per classe in `forecast_model`
(append-only — resta uno storico di come ogni modello è cambiato) e la
previsione usa sempre l'ultima riga per `(asset_class, trained_at)`. Se una
classe non raggiunge 20 campioni il training per quella classe fallisce
esplicitamente (non inserisce una riga), e Previsioni mostra "modello non
ancora addestrato" per gli strumenti di quella classe invece di un numero
a caso.

Non è collegato a un cron automatico: va rilanciato manualmente quando si
accumula storico prezzi nuovo (ha senso ogni pochi mesi, non ogni giorno —
pochi giorni di dati in più non cambiano sensibilmente i coefficienti).

### Universo di training e delle classifiche

"Tutto il mercato" non è raggiungibile con l'API non ufficiale di Yahoo
usata da questo progetto — nessun elenco completo dei titoli esistenti,
servirebbe un feed dati a pagamento. `FORECAST_UNIVERSE`
(`lib_forecast_universe.php`) è una lista curata e verificata a mano
(ogni ticker testato live contro Yahoo prima di essere aggiunto),
diversificata sulle 4 asset class richieste: ~27 azioni USA/Europa, 5 ETF
azionari, 6 ETF obbligazionari, 8 crypto. `forecasts.php` la unisce ai
ticker che gli utenti hanno effettivamente aggiunto ai propri strumenti
(stesso pattern di `market_trends.php::top_movers`), e per ogni ticker
della lista curata forza un backfill storico (`ensure_price_history`,
1500 giorni) se non già presente — quindi il primo caricamento della
pagina dopo un deploy con ticker nuovi può richiedere fino a un minuto
circa (decine di fetch verso Yahoo la prima volta); i caricamenti
successivi sono rapidi grazie alle cache già esistenti (`price_cache`,
`analyst_cache`).

### Limiti onesti del modello (misurati, non ipotetici)

Addestramento più recente su questa installazione (8 settembre 2026,
dopo l'ampliamento dell'universo e la separazione per asset class):

| Asset class | Campioni | Ticker | R² (training set) |
|---|---|---|---|
| equity | 1953 | 32 | 0.24 |
| bond | 300 | 6 | 0.50 |
| crypto | 654 | 9 | 0.04 |

Numeri bassi o moderati, ed è l'esito onesto e atteso di un problema
difficile (prevedere rendimenti a 12 mesi da 4 feature tecniche) — non un
bug da correggere aggiungendo più feature finché il numero non sale, il
che produrrebbe solo overfitting.

Altri limiti da conoscere prima di modificare questo modulo:

- **R² calcolato sul training set, non out-of-sample**: nessun
  train/test split per ora. Se in futuro si aggiunge una validazione
  out-of-sample, i numeri mostrati in UI andranno aggiornati di
  conseguenza (probabilmente più bassi, o negativi).
- **Campioni sovrapposti**: due date di campionamento a 21 giorni di
  distanza condividono quasi tutta la finestra storica trailing — non sono
  indipendenti in senso statistico stretto. Limite noto e accettato di un
  backtest con storico limitato, non nascosto: va riportato ogni volta che
  si comunica l'R² a un utente o si discute la qualità del modello.
- **Bond ha solo 6 ticker**: tutti ETF USA (AGG, BND, TLT, IEF, LQD, HYG),
  nessuna diversificazione geografica/valutaria — il modello bond
  generalizza su una base ancora piccola e USA-centrica.
- **Crypto resta difficile**: R² = 0.04 anche isolato dalle altre classi —
  la volatilità e i cicli di mercato crypto non sono ben spiegati da
  trend/momentum/volatilità/drawdown trailing. Previsioni per questa
  classe vanno trattate con ancora più scetticismo delle altre due.

### Dove vive nell'interfaccia

Voce di menu dedicata **"Previsioni"** (sidebar, icona bussola) — non
dentro la scheda strumento come Indicatori, perché mostra la stima per
tutti gli strumenti del portafoglio insieme, non solo quello aperto in quel
momento. `frontend/src/views/Forecasts.jsx`.

Due parti sulla stessa pagina:

1. **I tuoi strumenti**: una card per ogni strumento del portafoglio
   dell'utente (vedi sotto per il contenuto di ogni card).
2. **Classifiche**: 4 liste (griglia 2×2) — migliori/peggiori 10 per
   andamento storico (CAGR) e migliori/peggiori 10 per previsione a 12
   mesi. Universo: ogni ticker presente nella tabella `instruments` di
   **tutti** gli utenti (stesso pattern di aggregazione di
   `market_trends.php::top_movers` — solo ticker/nome/tipo, nessun dato
   che identifichi chi lo possiede), non solo il portafoglio di chi guarda
   la pagina. Se l'universo tracciato ha meno di 10 strumenti idonei per
   una classifica, la lista mostra solo quelli disponibili — mai
   placeholder finti per arrivare a 10.

Ogni card del portafoglio mostra, sempre nello stesso ordine:

1. Il numero previsto, in evidenza
2. **Prezzo attuale → prezzo stimato, entrambi in cifra** (non solo la
   percentuale): segnalato dall'utente che "+119%" da solo, accanto a un
   prezzo, è ambiguo — non è chiaro se il prezzo aumenterà di quella
   percentuale o se quella percentuale è qualcos'altro. Stessa logica nelle
   classifiche "previsione" (campo `projected_price` in `forecasts.php`,
   omesso invece nelle classifiche "andamento storico": lì la percentuale è
   un CAGR passato, non una proiezione di prezzo, mostrare un "prezzo
   proiettato" sarebbe scorretto)
3. Da quale training viene (data, numero di campioni/ticker, R²) — così chi
   guarda può giudicare quanto fidarsi, invece di prendere il numero per
   buono
4. Le 4 feature usate, coi loro valori grezzi
5. Il target price analisti, se disponibile, chiaramente etichettato come
   "non incluso nella stima"

### Disclaimer obbligatorio, sempre visibile in questa sezione

> Non è consulenza finanziaria. La percentuale mostrata è la previsione di
> un modello di regressione addestrato su dati storici — non una garanzia,
> non un segnale di acquisto o vendita, e con una capacità predittiva reale
> molto bassa. Nessun modello — incluso questo — può prevedere in modo
> affidabile i mercati finanziari.

### Casi limite

- Modello non ancora addestrato su un'installazione nuova (tabella
  `forecast_model` vuota): la sezione lo dice esplicitamente e invita a
  lanciare il training, non finge un numero
- Strumento con meno di 90 giorni di storico (serve per la feature
  momentum): previsione non disponibile per quello strumento, motivo
  dichiarato
- Nessuna copertura analisti per uno strumento (tipico ETF/crypto): la
  previsione resta disponibile (non dipende dal dato analisti), solo il
  riferimento separato non viene mostrato

---

## Parte 3 — Allocatore di investimento ipotetico ("Voglio investire")

### Perché è un salto di categoria rispetto a Previsioni, e perché è stato costruito comunque

Richiesta esplicita del titolare del progetto: un tool che prenda un
importo e un orizzonte ("voglio investire 10k per i prossimi 4 mesi,
indicami dove e come farlo") e restituisca un'allocazione concreta.
Segnalato prima di costruirlo che questo è diverso da Previsioni in un
modo che conta legalmente: Previsioni mostra la stessa stima generale a
tutti, questo tool produce una **raccomandazione personalizzata su un
importo e un orizzonte specifici di un utente** — la definizione stessa di
consulenza finanziaria (MiFID II/CONSOB: raccomandazione adatta alla
situazione specifica del cliente). Il titolare ha scelto consapevolmente
di costruirlo come raccomandazione vera, non come "simulazione" attenuata.
Di conseguenza, come per Previsioni:

- Il tool restituisce un'allocazione concreta (importo per strumento).
- Resta comunque **onesto sulla metodologia e sui suoi limiti** — mostrati
  sempre, non in piccolo: non è consulenza finanziaria, l'orizzonte
  richiesto viene proiettato da un modello validato solo a 12 mesi, ogni
  riga mostra da quale asset class/R² viene la stima.

### Cos'è davvero (e cosa non è)

**Non è un'ottimizzazione di portafoglio** (niente Markowitz, niente
covarianze tra strumenti — richiederebbe stimare centinaia di correlazioni
a coppie con lo storico disponibile, rumorose quanto le previsioni
stesse). È un ranking rendimento/rischio con due regole di diversificazione
esplicite, pensato per essere spiegabile riga per riga:

1. **Punteggio per candidato**: rendimento stimato sull'orizzonte richiesto
   (vedi sotto) diviso la volatilità storica dello strumento **elevata
   all'esponente del profilo di rischio** (rendimento per unità di
   rischio, come uno Sharpe senza tasso privo di rischio — esponente 1 =
   Sharpe standard), moltiplicato per `sqrt(R²)` del modello della sua
   asset class. Il termine R² scala il punteggio in base a quanto ci si
   può fidare del modello che ha prodotto quella stima — uno strumento
   crypto (R² = 0.04) pesa molto meno di un bond (R² = 0.50) a parità di
   rendimento/rischio grezzo, altrimenti il rumore di un modello poco
   affidabile potrebbe dominare la raccomandazione solo perché quel giorno
   la sua stima grezza è più alta.

   **Profilo di rischio** (richiesto dall'utente dopo aver visto che il
   tool teneva conto solo della volatilità storica in un unico modo fisso
   per tutti — vedi `INVEST_ADVISOR_RISK_PROFILES` in
   `lib_invest_advisor.php`): tre preset, non uno slider continuo (più
   spiegabile, coerente col resto del tool):

   | Profilo | Esponente volatilità | Tetto per strumento |
   |---|---|---|
   | Conservativo | 1.6 | 20% |
   | Bilanciato (default, comportamento originale) | 1.0 | 35% |
   | Aggressivo | 0.5 | 50% |

   Un esponente più alto penalizza la volatilità più duramente nel
   punteggio — cambia non solo i pesi finali ma **quali strumenti entrano
   in classifica**, perché il punteggio si calcola prima della selezione
   per classe. Verificato live: con "conservativo" gli slot azionari
   scelgono AAPL (vol. 25%) invece di TSLA (vol. 47%) che vince con
   "aggressivo" — non è solo una riallocazione dei pesi sullo stesso
   paniere, è un paniere diverso a seconda del profilo.
2. **Selezione**: l'utente scelge anche il numero totale di strumenti
   (1-15, default 9, oltre a importo e orizzonte). Per classe si pescano
   al più `ceil(numero_richiesto / classi_con_candidati)` candidati (con 3
   classi disponibili e default 9 = 3 a testa, come nella prima versione),
   poi si uniscono, si ordinano per punteggio e si tagliano esattamente al
   numero richiesto — con 1 solo strumento richiesto sceglie il migliore
   assoluto invece di forzare la diversificazione quando non ha senso
   (verificato: count=1 → il singolo bond col punteggio più alto, non un
   candidato scelto a caso per "rappresentare" una classe). Con più
   strumenti richiesti garantisce diversificazione strutturale invece di
   poter finire, per esempio, tutto su crypto solo perché quel giorno hanno
   punteggi alti. Se gli strumenti con previsione positiva disponibili sono
   meno del numero richiesto, l'allocazione usa quelli che ci sono e lo
   dichiara (`allocations.length < requested_count` in UI), non ne inventa.
3. **Pesi**: proporzionali al punteggio, con un tetto per singolo
   strumento che dipende dal profilo di rischio (tabella sopra) —
   l'eccesso oltre il tetto si ridistribuisce sugli altri candidati in
   proporzione (`cap_and_redistribute_weights()` in
   `lib_invest_advisor.php`). Se il tetto è matematicamente irraggiungibile
   (es. solo 2 candidati disponibili quel giorno), viene ignorato invece di
   produrre pesi che non sommano al 100%.
4. Solo candidati con **rendimento stimato positivo** sull'orizzonte
   richiesto entrano in classifica — non ha senso "raccomandare" uno
   strumento con un calo previsto.

### L'orizzonte richiesto non è quello su cui il modello è validato

Il modello di Previsioni è addestrato e validato solo su rendimenti a 12
mesi (vedi Parte 2). Un orizzonte diverso (es. 4 mesi) si ottiene
proiettando matematicamente quella stima con la formula di
capitalizzazione composta:

```
rendimento_orizzonte = (1 + rendimento_12_mesi) ^ (mesi_richiesti / 12) - 1
```

Questo è un calcolo corretto **se** il rendimento annuo si mantenesse
costante nel tempo — un'assunzione reale, non un dato validato per quello
specifico orizzonte più corto (o più lungo). Va dichiarato esplicitamente
in UI ogni volta (`InvestAdvisor` in `frontend/src/views/Forecasts.jsx`),
non lasciato implicito nel numero.

### Valuta

L'importo inserito dall'utente è trattato come euro. I rendimenti % usati
per ogni strumento sono calcolati sui prezzi in valuta nativa
(`price_history` per ticker, mai convertita) e applicati direttamente
all'importo in euro assegnato — nessuna conversione valutaria, stessa
scelta già fatta altrove in Reflexi per gli investimenti multi-valuta
(vedi CLAUDE.md, "Multi-valuta investimenti"). Significa che il rischio di
cambio viene ignorato: un rendimento del +20% in dollari non è
esattamente un +20% sull'importo in euro se nel frattempo il cambio
EUR/USD si muove. Semplificazione dichiarata, non un bug silenzioso.

### Dove vive nell'interfaccia

Dentro la pagina Previsioni (non una vista separata), sezione **"Voglio
investire"** subito sotto il disclaimer generale — due campi (importo in
euro, orizzonte in mesi) e un pulsante che chiama `invest_advisor.php`.
Endpoint condiviso con Previsioni tramite `forecast_universe_computed()`
(`lib_forecast_model.php`): stesso universo di strumenti, stessi modelli,
nessun numero diverso tra le due schermate per lo stesso strumento nello
stesso momento.

### Disclaimer obbligatorio, sempre visibile con un risultato

> Non è consulenza finanziaria. Orizzonte ottenuto proiettando
> matematicamente la stima a 12 mesi del modello — non validata su questo
> orizzonte specifico. Rendimenti calcolati sulla valuta nativa di ogni
> strumento e applicati all'importo in euro senza conversione valutaria.
> È un calcolo automatico che può sbagliarsi anche di molto, specialmente
> su orizzonti brevi: non seguirlo come unico riferimento per una
> decisione reale.

### Casi limite

- Nessuno strumento tracciato ha una previsione di rendimento positiva per
  l'orizzonte richiesto: `allocation_available: false` con un messaggio
  esplicito, mai un'allocazione forzata su strumenti in calo previsto
- Orizzonte fuori range (< 1 o > 60 mesi): troncato ai limiti
  (`INVEST_ADVISOR_MIN_MONTHS`/`MAX_MONTHS`) invece di un calcolo assurdo
  su orizzonti non plausibili
- Importo o orizzonte non validi (≤ 0): errore esplicito dall'endpoint,
  nessun calcolo silenzioso su un input insensato
