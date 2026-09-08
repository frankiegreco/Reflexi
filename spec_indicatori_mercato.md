# Specifica: Indicatori di mercato e Previsioni per titoli/ETF

Questo file copre due sezioni distinte dell'app, con scopi deliberatamente
opposti: **Indicatori** (solo fatti verificabili) e **Previsioni** (una
stima predittiva vera e propria, prodotta da un modello statistico
addestrato). Non vanno confuse né la logica dell'una applicata all'altra.

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

### Come si addestra

`webroot/cron/retrain_forecast_model.php`, lanciato manualmente da riga di
comando dentro il container "web" (**mai** come endpoint HTTP — scansiona
tutto `price_history`, è un lavoro da batch). Per ogni ticker con
storico ≥ 395 giorni, campiona ogni 21 giorni lungo tutta la serie,
calcola le 4 feature "a quella data" e il rendimento realizzato nei 365
giorni successivi. Pool di tutti i ticker → un'unica regressione OLS
(equazioni normali, inversione di matrice via Gauss-Jordan in
`lib_forecast_model.php`, nessuna libreria esterna). Ogni training salva
una nuova riga in `forecast_model` (append-only — resta uno storico di
come il modello è cambiato) e la previsione usa sempre l'ultima riga per
`trained_at`.

Non è collegato a un cron automatico: va rilanciato manualmente quando si
accumula storico prezzi nuovo (ha senso ogni pochi mesi, non ogni giorno —
pochi giorni di dati in più non cambiano sensibilmente i coefficienti).

### Limiti onesti del modello (misurati, non ipotetici)

Primo addestramento reale su questa installazione (8 settembre 2026): 907
campioni da 7 ticker con storico sufficiente (AAPL, ADA-USD, CSSPX.MI,
ETH-USD, SOL-EUR, SXR8.DE, VWCE.MI) — gli altri ticker tracciati non
avevano ancora abbastanza storico (< 395 giorni). **R² = 0.067**: il
modello spiega circa il 6,7% della variabilità dei rendimenti nel proprio
stesso set di training. È un numero basso, ed è l'esito onesto e atteso di
un problema difficile (prevedere rendimenti a 12 mesi da 4 feature
tecniche) — non un bug da correggere aggiungendo più feature finché il
numero non sale, il che produrrebbe solo overfitting.

Altri limiti da conoscere prima di modificare questo modulo:

- **R² calcolato sul training set, non out-of-sample**: nessun
  train/test split per ora. Se in futuro si aggiunge una validazione
  out-of-sample, il numero mostrato in UI andrà aggiornato di conseguenza
  (probabilmente più basso, o negativo).
- **Campioni sovrapposti**: due date di campionamento a 21 giorni di
  distanza condividono quasi tutta la finestra storica trailing — non sono
  indipendenti in senso statistico stretto. Limite noto e accettato di un
  backtest con storico limitato (pochi ticker con anni di dati), non
  nascosto: va riportato ogni volta che si comunica l'R² a un utente o si
  discute la qualità del modello.
- **Pochi ticker indipendenti**: 7 ticker nel primo training, di cui 2
  fortemente correlati tra loro (ETH-USD/ADA-USD, entrambe crypto) — il
  modello generalizza su una base ancora piccola. Cresce con l'uso reale
  dell'app (più utenti → più ticker → più storico → training migliori).

### Dove vive nell'interfaccia

Voce di menu dedicata **"Previsioni"** (sidebar, icona bussola) — non
dentro la scheda strumento come Indicatori, perché mostra la stima per
tutti gli strumenti del portafoglio insieme, non solo quello aperto in quel
momento. `frontend/src/views/Forecasts.jsx`.

Ogni card mostra, sempre nello stesso ordine:

1. Il numero previsto, in evidenza
2. Da quale training viene (data, numero di campioni/ticker, R²) — così chi
   guarda può giudicare quanto fidarsi, invece di prendere il numero per
   buono
3. Le 4 feature usate, coi loro valori grezzi
4. Il target price analisti, se disponibile, chiaramente etichettato come
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
