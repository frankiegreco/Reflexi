#!/usr/bin/env python3
"""Genera il PDF "Gestione della finanza personale: la guida pratica in 7 passi".

Uso, dalla root del progetto:  python3 build/guida/generate.py
Scrive build/guida/guida.html (A4, font e logo incorporati) e lo stampa in
webroot/private/guida-finanza-personale-7-passi.pdf con Playwright in Docker
(questa VPS non ha Node, come per build/og), insieme alla copertina
webroot/og/guida-pdf-copertina.png mostrata sulla pagina della guida.
Il PDF non e' pubblico: lo serve
webroot/api/guide_pdf.php solo a chi ha un account (vedi .htaccess).

Il testo segue la guida web /gestione-finanza-personale, piu' ampio, con le
schede da compilare. Tutti i numeri degli esempi sono calcolati qui sotto,
mai scritti a mano.
"""
import base64, html, os, subprocess
from decimal import Decimal, ROUND_HALF_UP

ROOT = os.path.dirname(os.path.dirname(os.path.dirname(os.path.abspath(__file__))))
HERE = os.path.dirname(os.path.abspath(__file__))
OUT_PDF = 'webroot/private/guida-finanza-personale-7-passi.pdf'
PLAYWRIGHT_IMAGE = 'mcr.microsoft.com/playwright:v1.48.0-jammy'


def eur(x, dec=0):
    """Formato italiano: 1.234 o 1.234,50 (arrotondamento commerciale, 62,5 -> 63)."""
    x = float(Decimal(str(x)).quantize(Decimal(1).scaleb(-dec), ROUND_HALF_UP))
    s = f'{x:,.{dec}f}'
    return s.replace(',', 'X').replace('.', ',').replace('X', '.')


def pct(x, dec=0):
    return eur(x * 100, dec) + '%'


def font(file):
    with open(os.path.join(ROOT, 'webroot/fonts', file), 'rb') as f:
        return 'data:font/woff2;base64,' + base64.b64encode(f.read()).decode()


# --- Numeri degli esempi -----------------------------------------------------

NET = 2400
BUDGET = [
    ('Casa (affitto o rata del mutuo)', 750),
    ('Spesa alimentare', 400),
    ('Trasporti', 200),
    ('Bollette e utenze', 150),
    ('Abbonamenti', 50),
    ('Tempo libero e ristoranti', 250),
    ('Varie e spese annuali accantonate', 120),
    ('Risparmio e investimenti', 480),
]
assert sum(v for _, v in BUDGET) == NET

# Spese reali di tre mesi (passo 1): media e scostamento dal budget.
SPESE_3M = [
    ('Casa', [750, 750, 750]),
    ('Spesa alimentare', [430, 395, 460]),
    ('Trasporti', [180, 240, 210]),
    ('Bollette e utenze', [140, 175, 160]),
    ('Abbonamenti', [62, 62, 74]),
    ('Tempo libero e ristoranti', [310, 260, 340]),
    ('Varie', [95, 180, 120]),
]

ESSENZIALI = 1500  # spese essenziali mensili per il fondo di emergenza
FONDO = [('Dipendente a tempo indeterminato, senza figli', 3), ('Famiglia monoreddito o con figli', 6),
         ('Autonomo o reddito variabile', 12)]

DEBITI = [('Mutuo prima casa', 650), ('Finanziamento auto', 180)]
RATE = sum(r for _, r in DEBITI)
DTI = RATE / NET


def fv_monthly(pmt, rate, years):
    i = rate / 12
    n = years * 12
    return pmt * ((1 + i) ** n - 1) / i


PAC = [(y, 200 * 12 * y, fv_monthly(200, .03, y), fv_monthly(200, .05, y)) for y in (10, 20, 30)]
r100 = lambda x: round(x / 100) * 100  # noqa: E731

OBIETTIVO = dict(nome='Anticipo per la casa', importo=30000, gia=6000, anni=4)
OB_MESE = (OBIETTIVO['importo'] - OBIETTIVO['gia']) / (OBIETTIVO['anni'] * 12)

TASSO_RISPARMIO = 480 / NET

# --- Componenti HTML ---------------------------------------------------------

e = html.escape


def table(head, rows, num=(), total=False, cls=''):
    th = ''.join(f'<th class="{"n" if i in num else ""}">{h}</th>' for i, h in enumerate(head))
    body = ''
    for r, row in enumerate(rows):
        tr_cls = ' class="tot"' if total and r == len(rows) - 1 else ''
        body += f'<tr{tr_cls}>' + ''.join(f'<td class="{"n" if i in num else ""}">{c}</td>' for i, c in enumerate(row)) + '</tr>'
    return f'<table class="{cls}"><thead><tr>{th}</tr></thead><tbody>{body}</tbody></table>'


def blank_table(head, labels, cols, extra_rows=0, num_from=1):
    """Scheda da compilare: etichette nella prima colonna, celle vuote."""
    rows = [[l] + [''] * (cols - 1) for l in labels] + [[''] * cols for _ in range(extra_rows)]
    return table(head, rows, num=tuple(range(num_from, cols)), cls='fill')


def box(title, body, kind='tip'):
    return f'<div class="box {kind}"><div class="box-t">{title}</div>{body}</div>'


def step(n, title, intro):
    return f'<div class="step-head"><div class="step-n">{n}</div><div><div class="kicker">Passo {n} di 7</div><h2>{title}</h2></div></div><p class="lead">{intro}</p>'


def reflexi(text):
    return f'<div class="reflexi"><span class="rx">Con Reflexi</span> {text}</div>'


# --- Pagine ------------------------------------------------------------------

media = [(c, sum(v) / 3) for c, v in SPESE_3M]
tot_media = sum(m for _, m in media)

pages = []

# Copertina
with open(os.path.join(ROOT, 'webroot/favicon.svg')) as f:
    LOGO = f.read()
pages.append(f'''<section class="page cover">
  <div class="c1"></div><div class="c2"></div>
  <div class="brand">{LOGO}<span>reflexi</span></div>
  <div class="cover-body">
    <div class="cover-kicker">Guida gratuita · edizione 2026</div>
    <h1>Gestione della finanza personale</h1>
    <div class="cover-sub">La guida pratica in 7 passi, con esempi, tabelle e schede da compilare</div>
    <ul class="cover-list">
      <li>Sapere dove vanno i tuoi soldi</li>
      <li>Un budget che regge davvero</li>
      <li>Fondo di emergenza, debiti e investimenti</li>
      <li>Obiettivi con una cifra al mese</li>
      <li>7 schede pronte da stampare</li>
    </ul>
  </div>
  <div class="cover-foot">reflexi.it</div>
</section>''')

# Introduzione e indice
pages.append(f'''<section class="page">
  <h2 class="first">Prima di iniziare</h2>
  <p>La gestione della finanza personale non è una materia per esperti né un esercizio di rinuncia. È un metodo per rispondere a poche domande con numeri veri invece che con impressioni: quanto entra, quanto esce, quanto resta, dove va a finire e se ti sta portando dove vuoi arrivare.</p>
  <p>Questa guida ti accompagna in sette passi, nell'ordine in cui conviene affrontarli. Ogni passo ha un esempio con numeri realistici e una <strong>scheda da compilare</strong> nelle ultime pagine: stampala o ricopiala, e alla fine avrai il quadro completo delle tue finanze.</p>
  <h3>Le cinque aree della finanza personale</h3>
  <p>Ogni area ha un numero da tenere d'occhio. Se li conosci tutti e cinque, sai già più della maggior parte delle persone sulla tua situazione.</p>
  {table(['Area', 'Domanda chiave', 'Numero da seguire'], [
      ['Entrate e spese', 'Dove vanno i miei soldi?', 'Spesa mensile per categoria'],
      ['Risparmio', 'Quanto metto da parte?', 'Tasso di risparmio (%)'],
      ['Debiti', "Quanto pesa ciò che devo restituire?", 'Rapporto rata/reddito (%)'],
      ['Investimenti', "Il capitale cresce più dell'inflazione?", 'Rendimento reale annuo'],
      ['Patrimonio', 'Quanto valgo al netto dei debiti?', 'Patrimonio netto (€)'],
  ])}
  <h3>Indice</h3>
  <ol class="toc">
    <li><span>Fotografa entrate e spese reali</span><span>3</span></li>
    <li><span>Dai un tetto a ogni categoria con un budget</span><span>4</span></li>
    <li><span>Costruisci un fondo di emergenza</span><span>5</span></li>
    <li><span>Tieni sotto controllo i debiti</span><span>6</span></li>
    <li><span>Fai lavorare il risparmio con gli investimenti</span><span>7</span></li>
    <li><span>Dai un obiettivo e una scadenza al risparmio</span><span>8</span></li>
    <li><span>Rivedi tutto con una routine regolare</span><span>9</span></li>
    <li class="toc-sub"><span>Le schede da compilare</span><span>10</span></li>
  </ol>
  <p class="small">Tempo per la prima impostazione: circa due ore, una sola volta. Dopo bastano pochi minuti a settimana.</p>
</section>''')

# Passo 1
rows1 = [[c, *[eur(x) for x in v], eur(sum(v) / 3)] for c, v in SPESE_3M]
rows1.append(['Totale', *[eur(sum(v[i] for _, v in SPESE_3M)) for i in range(3)], eur(tot_media)])
pages.append(f'''<section class="page">
  {step(1, 'Fotografa entrate e spese reali', 'Ogni gestione delle finanze parte dai dati, non dalle impressioni. Prima di decidere quanto spendere devi sapere quanto spendi davvero.')}
  <h3>Come fare</h3>
  <ul>
    <li><strong>Raccogli gli estratti conto degli ultimi tre mesi</strong> di tutti i conti e le carte. Tre mesi bastano a far emergere le abitudini senza farsi ingannare da un mese anomalo.</li>
    <li><strong>Somma le entrate nette</strong>: stipendi, pensioni, entrate extra ricorrenti. Se il reddito è variabile, usa come base il mese più basso, non la media. Tredicesima e quattordicesima non vanno nel mensile: decidi a parte a cosa destinarle.</li>
    <li><strong>Raggruppa le uscite per categoria</strong>: casa, spesa, trasporti, bollette, abbonamenti, tempo libero, varie. Poche categorie chiare funzionano meglio di trenta.</li>
    <li><strong>Separa spese fisse e variabili</strong>: le fisse (affitto, rata, assicurazioni) cambiano poco; le variabili (spesa, ristoranti, carburante) sono dove puoi davvero intervenire.</li>
  </ul>
  <h3>Esempio: tre mesi di spese reali (euro)</h3>
  {table(['Categoria', 'Mese 1', 'Mese 2', 'Mese 3', 'Media'], rows1, num=(1, 2, 3, 4), total=True)}
  <p class="small">Su {eur(NET)} € netti al mese, la media delle spese è {eur(tot_media)} €: restano circa {eur(NET - tot_media)} € al mese, ma senza un piano finiscono spesso nelle spese variabili.</p>
  {box('Attenzione agli abbonamenti', '<p>Nell\'esempio gli abbonamenti passano da 62 a 74 € al terzo mese: un rinnovo con aumento di prezzo. È una delle voci che crescono senza che ce ne accorgiamo. Controlla ogni addebito ricorrente e chiediti se lo useresti ancora se dovessi riattivarlo oggi.</p>')}
  {reflexi('importi le transazioni dal file CSV o Excel della banca, i saldi si calcolano da soli e gli abbonamenti dimenticati vengono rilevati in automatico.')}
  <p class="see">Scheda A a pagina 10</p>
</section>''')

# Passo 2
rows2 = [[c, eur(v), pct(v / NET)] for c, v in BUDGET] + [['Totale', eur(NET), '100%']]
need = 750 + 400 + 200 + 150
want = 50 + 250 + 120
pages.append(f'''<section class="page">
  {step(2, 'Dai un tetto a ogni categoria con un budget', 'Ora che sai quanto spendi, puoi decidere quanto vuoi spendere. Il budget non è un divieto: è una decisione presa in anticipo, a mente fredda, invece che davanti alla vetrina.')}
  <h3>Esempio: budget mensile con {eur(NET)} € netti</h3>
  {table(['Categoria', 'Budget (€)', 'Quota'], rows2, num=(1, 2), total=True)}
  <h3>Tre regole che fanno reggere il budget</h3>
  <ul>
    <li><strong>Il risparmio è una voce come le altre</strong>, messa da parte a inizio mese con un bonifico automatico. Quello che "avanza a fine mese" di solito non avanza.</li>
    <li><strong>Parti da quanto spendi davvero</strong> e lima del 10–15% dove ti sembra di esagerare. Limiti troppo stretti vengono abbandonati dopo poche settimane.</li>
    <li><strong>Accantona le spese annuali</strong> (bollo, assicurazioni, regali, manutenzioni) dividendole per dodici: così non arrivano come una sorpresa.</li>
  </ul>
  {box('La regola 50/30/20 come punto di partenza', f'<p>Divide il reddito netto in necessità (50%), desideri (30%) e risparmio (20%). Nell\'esempio le necessità (casa, spesa, trasporti, bollette) valgono {eur(need)} € ({pct(need / NET)}), più del 50%: succede spesso quando la casa pesa quasi un terzo del reddito. Il budget compensa con desideri più bassi, {eur(want)} € ({pct(want / NET)}), e il risparmio resta al {pct(480 / NET)}.</p>')}
  {reflexi('imposti un limite per ogni categoria, ricevi un avviso prima di sforare (in base al ritmo di spesa del mese) e, con il rollover, quello che non spendi si riporta al mese successivo.')}
  <p class="see">Scheda B a pagina 10</p>
</section>''')

# Passo 3
rows3 = [[s, str(m) if m != 12 else '6–12', (eur(ESSENZIALI * m) + ' €') if m != 12 else f'{eur(ESSENZIALI * 6)}–{eur(ESSENZIALI * 12)} €'] for s, m in FONDO]
pages.append(f'''<section class="page">
  {step(3, 'Costruisci un fondo di emergenza', "Prima di investire, metti al sicuro l'imprevisto: una caldaia da cambiare, una spesa medica, qualche mese senza lavoro. Il fondo di emergenza è ciò che evita di dover vendere investimenti nel momento peggiore o di finire in un prestito.")}
  <h3>Quanto deve essere grande</h3>
  <p>Il riferimento più diffuso è da <strong>3 a 6 mesi di spese essenziali</strong>: casa, spesa, utenze, trasporti, rate. Tempo libero e abbonamenti non contano, perché in emergenza si tagliano.</p>
  {table(['Situazione', 'Mesi', f'Fondo (spese essenziali {eur(ESSENZIALI)} €/mese)'], rows3, num=(1, 2))}
  <h3>Dove tenerlo</h3>
  <ul>
    <li><strong>Su un conto separato</strong> da quello di tutti i giorni, così non lo spendi senza accorgertene.</li>
    <li><strong>Disponibile in pochi giorni</strong>: conto deposito svincolabile, conto remunerato o strumenti del mercato monetario. Non in azioni, che possono perdere il 30% proprio quando ti servono.</li>
    <li><strong>Un pezzo alla volta</strong>: se il traguardo sembra lontano, il primo obiettivo è un mese di spese. Con 300 € al mese, {eur(ESSENZIALI)} € arrivano in {ESSENZIALI // 300} mesi.</li>
  </ul>
  {box('Quando si usa', '<p>Solo per imprevisti veri e necessari. Le vacanze, il telefono nuovo e i regali di Natale sono spese prevedibili: hanno un obiettivo dedicato (passo 6), non il fondo di emergenza. Se lo usi, la priorità del mese dopo è ricostituirlo.</p>', 'warn')}
  {reflexi('crei un obiettivo "Fondo di emergenza" con importo e scadenza: il progresso si aggiorna da solo con le tue transazioni.')}
  <p class="see">Scheda C a pagina 11</p>
</section>''')

# Passo 4
rows4 = [[n, eur(r)] for n, r in DEBITI] + [['Totale rate', eur(RATE)]]
pages.append(f'''<section class="page">
  {step(4, 'Tieni sotto controllo i debiti', 'Mutuo, prestiti e acquisti a rate fanno parte della finanza personale tanto quanto il risparmio. Non sono un male in sé: il problema è quando la loro somma toglie respiro a tutto il resto.')}
  <h3>Il numero da seguire: rapporto rata/reddito</h3>
  <p>Somma tutte le rate mensili e dividile per il reddito netto. Le banche italiane in genere non concedono un mutuo se la rata supera il <strong>30–35% del reddito netto</strong>: è un buon limite anche per te.</p>
  {table(['Debito', 'Rata mensile (€)'], rows4, num=(1,), total=True)}
  <p class="small">Con {eur(NET)} € netti, {eur(RATE)} € di rate valgono il <strong>{pct(DTI, 1)}</strong> del reddito: appena sotto il limite. Un nuovo finanziamento, anche piccolo, lo farebbe superare.</p>
  <h3>Come gestire i debiti</h3>
  <ul>
    <li><strong>Fai l'elenco completo</strong>: creditore, debito residuo, tasso (TAEG), rata e scadenza. Spesso è la prima volta che si vede il totale.</li>
    <li><strong>Chiudi prima i debiti più costosi</strong>: carte revolving e prestiti al consumo possono superare il 15–20% annuo, molto più di quanto rende qualsiasi investimento.</li>
    <li><strong>Conosci il piano di ammortamento del mutuo</strong>: nei primi anni la rata è fatta soprattutto di interessi. Sapere quanto paghi di interessi aiuta a valutare una surroga o un rientro anticipato.</li>
    <li><strong>Prima di un rientro anticipato</strong> verifica di avere il fondo di emergenza: un mutuo ridotto non ti paga la caldaia rotta.</li>
  </ul>
  {reflexi('il mutuo ha il suo piano di ammortamento alla francese, la simulazione di estinzione e rientro anticipato con il risparmio netto (detrazione inclusa) e la rata collegata al budget del mese.')}
  <p class="see">Scheda D a pagina 11</p>
</section>''')

# Passo 5
rows5 = [[str(y), eur(v), eur(r100(a)), eur(r100(b))] for y, v, a, b in PAC]
pages.append(f'''<section class="page">
  {step(5, 'Fai lavorare il risparmio con gli investimenti', "Il denaro fermo sul conto perde valore ogni anno per effetto dell'inflazione: con un'inflazione del 2%, 10.000 € oggi comprano quanto 8.200 € tra dieci anni. Una volta coperto il fondo di emergenza, il risparmio che non ti serve nei prossimi anni può lavorare per te.")}
  <h3>Esempio: 200 € al mese investiti</h3>
  {table(['Anni', 'Versato (€)', 'Al 3% annuo (€)', 'Al 5% annuo (€)'], rows5, num=(1, 2, 3))}
  <p class="small">Interesse composto con capitalizzazione mensile, valori arrotondati alle centinaia, al lordo di tasse e costi. I rendimenti passati non garantiscono quelli futuri.</p>
  <p>La differenza tra le colonne mostra due cose: il <strong>tempo</strong> conta più dell'importo (in 30 anni gli interessi superano i versamenti al 5%) e anche <strong>pochi punti di rendimento</strong>, o di costi, fanno una grande differenza sul lungo periodo.</p>
  <h3>Le regole di base</h3>
  <ul>
    <li><strong>Investi solo denaro di cui non hai bisogno per almeno 5–10 anni.</strong> Nel breve periodo i mercati possono scendere molto.</li>
    <li><strong>Diversifica</strong>: un ETF globale contiene migliaia di aziende di decine di Paesi; una singola azione può dimezzare.</li>
    <li><strong>Guarda i costi</strong>: un fondo all'1,5% annuo contro un ETF allo 0,2% si mangia una parte enorme del rendimento in 30 anni.</li>
    <li><strong>Automatizza con un PAC</strong> (piano di accumulo): una cifra fissa ogni mese, senza cercare il momento giusto.</li>
    <li><strong>Ricorda le tasse</strong>: in Italia le plusvalenze sono tassate al 26% (12,5% sui titoli di Stato).</li>
  </ul>
  {reflexi('segui ETF e azioni con quotazioni reali, costo medio di carico e plusvalenze; il PAC genera da solo gli acquisti al prezzo di chiusura di ogni data.')}
</section>''')

# Passo 6
pages.append(f'''<section class="page">
  {step(6, 'Dai un obiettivo e una scadenza al risparmio', 'Risparmiare "per sicurezza" funziona peggio che risparmiare per qualcosa. Un obiettivo con importo e scadenza si trasforma in una cifra mensile precisa, e una cifra precisa si può mettere nel budget.')}
  <h3>Esempio: {OBIETTIVO["nome"].lower()}</h3>
  {table(['Voce', 'Valore'], [
      ['Importo da raggiungere', eur(OBIETTIVO['importo']) + ' €'],
      ['Già messo da parte', eur(OBIETTIVO['gia']) + ' €'],
      ['Mancano', eur(OBIETTIVO['importo'] - OBIETTIVO['gia']) + ' €'],
      ['Scadenza', f"{OBIETTIVO['anni']} anni ({OBIETTIVO['anni'] * 12} mesi)"],
      ['Da mettere da parte ogni mese', eur(OB_MESE) + ' €'],
  ], num=(1,), total=True)}
  <p class="small">Senza contare eventuali interessi: se il denaro è su un conto remunerato, la cifra mensile scende un po'.</p>
  <h3>Come fissare obiettivi che si raggiungono</h3>
  <ul>
    <li><strong>Pochi e ordinati per priorità</strong>: prima il fondo di emergenza, poi gli obiettivi a breve (1–3 anni), poi quelli lunghi.</li>
    <li><strong>Breve termine, strumenti sicuri</strong>: ciò che ti serve entro tre anni non va investito in azioni.</li>
    <li><strong>Lungo termine, pensa alla previdenza</strong>: la pensione pubblica sarà più bassa dell'ultimo stipendio. Un fondo pensione ha vantaggi fiscali (deducibilità fino a 5.164,57 € l'anno) e la scelta su dove destinare il TFR va fatta consapevolmente.</li>
    <li><strong>Rendi visibile il progresso</strong>: vedere la barra che avanza è il miglior incentivo a continuare.</li>
  </ul>
  {box('Il tuo tasso di risparmio', f'<p>Dividi quanto risparmi in un mese per il reddito netto. Nell\'esempio 480 € su {eur(NET)} € valgono il {pct(TASSO_RISPARMIO)}. Sotto il 10% il margine per gli imprevisti è sottile; dal 20% in su gli obiettivi arrivano molto prima. Più che la percentuale conta la costanza.</p>')}
  {reflexi('ogni obiettivo ha importo e scadenza, il progresso si muove da solo con le tue transazioni reali e la sezione FIRE e pensione calcola quanto ti serve per smettere di lavorare.')}
  <p class="see">Scheda E a pagina 12</p>
</section>''')

# Passo 7
pages.append(f'''<section class="page">
  {step(7, 'Rivedi tutto con una routine regolare', 'La gestione della finanza personale non si imposta una volta per sempre. La differenza tra chi ha il controllo delle proprie finanze e chi no non è il reddito: è l\'abitudine di guardare i numeri a intervalli regolari.')}
  <div class="routine">
    <div><div class="r-t">Ogni settimana · 10 minuti</div><ul>
      <li>Registra o importa le spese della settimana</li>
      <li>Controlla le categorie vicine al limite</li>
      <li>Verifica che non ci siano addebiti sconosciuti</li></ul></div>
    <div><div class="r-t">Ogni mese · 30 minuti</div><ul>
      <li>Confronta budget e speso, categoria per categoria</li>
      <li>Calcola il tasso di risparmio del mese</li>
      <li>Sposta il risparmio su fondo, obiettivi e investimenti</li>
      <li>Aggiusta i limiti irrealistici</li></ul></div>
    <div><div class="r-t">Ogni anno · 2 ore</div><ul>
      <li>Aggiorna il patrimonio netto e confrontalo con l'anno prima</li>
      <li>Ribilancia il portafoglio se si è allontanato dalla tua allocazione</li>
      <li>Rivedi obiettivi, assicurazioni e fondo pensione</li>
      <li>Disdici i servizi che non usi più</li></ul></div>
  </div>
  <h3>Il patrimonio netto: il numero che riassume tutto</h3>
  <p>Somma ciò che possiedi (liquidità, investimenti, valore della casa) e sottrai ciò che devi (mutuo residuo, prestiti). Il valore assoluto conta meno della direzione: se cresce di anno in anno, tutti gli altri passi stanno funzionando.</p>
  <h3>Gli errori più comuni</h3>
  <ul>
    <li><strong>Inflazione dello stile di vita</strong>: ogni aumento di stipendio si trasforma in spese nuove. Destina almeno metà di ogni aumento al risparmio.</li>
    <li><strong>Strumenti scollegati</strong>: il budget su un foglio, il mutuo in un PDF della banca, gli investimenti nell'app del broker. Il quadro intero non lo vede mai nessuno.</li>
    <li><strong>Mollare dopo un mese andato male</strong>: capita a tutti. Si riparte dal mese dopo, senza ricominciare da zero.</li>
  </ul>
  {reflexi('il patrimonio netto si calcola da solo (immobili e quote cointestate inclusi) e le proiezioni Monte Carlo mostrano dove ti porta il ritmo attuale.')}
  <p class="see">Schede F e G a pagina 12–13</p>
</section>''')

# Schede
CAT = ['Casa (affitto/rata)', 'Spesa alimentare', 'Trasporti', 'Bollette e utenze', 'Abbonamenti',
       'Tempo libero e ristoranti', 'Salute', 'Figli', 'Varie', 'Spese annuali (÷12)']
pages.append(f'''<section class="page">
  <div class="kicker">Schede da compilare</div>
  <h2 class="first">Scheda A · Entrate e spese degli ultimi tre mesi</h2>
  {blank_table(['Voce', 'Mese 1', 'Mese 2', 'Mese 3', 'Media'], ['Entrate nette'] + CAT, 5, extra_rows=1)}
  <p class="small">Totale spese medie: ____________ €   ·   Entrate − spese: ____________ €</p>
  <h2>Scheda B · Il mio budget mensile</h2>
  {blank_table(['Categoria', 'Budget', 'Speso', 'Differenza'], CAT + ['Risparmio e investimenti'], 4)}
  <p class="small">Il totale dei budget deve essere uguale alle entrate nette. Il risparmio si mette da parte a inizio mese.</p>
</section>''')

pages.append(f'''<section class="page">
  <h2 class="first">Scheda C · Il mio fondo di emergenza</h2>
  {table(['Voce', 'Importo (€)'], [
      ['Casa (affitto o rata)', ''], ['Spesa alimentare', ''], ['Utenze', ''], ['Trasporti', ''],
      ['Rate di altri debiti', ''], ['Altre spese essenziali', ''],
      ['<strong>Spese essenziali al mese</strong> (A)', ''],
      ['Mesi da coprire: 3, 6 o più (B)', ''],
      ['<strong>Fondo di emergenza obiettivo</strong> (A × B)', ''],
      ['Già disponibile oggi', ''],
      ['Da accantonare al mese', ''],
  ], num=(1,), cls='fill')}
  <h2>Scheda D · I miei debiti</h2>
  {blank_table(['Debito', 'Residuo (€)', 'TAEG', 'Rata (€)', 'Scadenza'], [], 5, extra_rows=6)}
  <p class="small">Totale rate: ________ €   ÷   reddito netto: ________ €   =   rapporto rata/reddito: ______ %  (limite consigliato 30–35%)</p>
</section>''')

pages.append(f'''<section class="page">
  <h2 class="first">Scheda E · I miei obiettivi di risparmio</h2>
  {blank_table(['Obiettivo', 'Importo (€)', 'Già messo da parte', 'Scadenza (mesi)', 'Al mese (€)'], [], 5, extra_rows=5)}
  <p class="small">Al mese = (importo − già messo da parte) ÷ mesi alla scadenza. Ordina gli obiettivi per priorità: il fondo di emergenza viene prima.</p>
  <h2>Scheda F · La mia routine</h2>
  <div class="checks">
    <div><div class="r-t">Ogni settimana</div>
      <p>☐ Spese registrate o importate</p><p>☐ Categorie vicine al limite controllate</p><p>☐ Nessun addebito sconosciuto</p></div>
    <div><div class="r-t">Ogni mese</div>
      <p>☐ Budget e speso confrontati</p><p>☐ Tasso di risparmio calcolato</p><p>☐ Risparmio spostato dove deve andare</p><p>☐ Limiti aggiornati</p></div>
    <div><div class="r-t">Ogni anno</div>
      <p>☐ Patrimonio netto aggiornato</p><p>☐ Portafoglio ribilanciato</p><p>☐ Obiettivi e assicurazioni rivisti</p><p>☐ Servizi inutili disdetti</p></div>
  </div>
</section>''')

pages.append(f'''<section class="page">
  <h2 class="first">Scheda G · Il cruscotto dei cinque numeri</h2>
  <p>Compilalo una volta a trimestre: in un anno vedrai a colpo d'occhio se stai andando nella direzione giusta.</p>
  {blank_table(['Numero', 'Trim. 1', 'Trim. 2', 'Trim. 3', 'Trim. 4'], [
      'Spesa media mensile (€)', 'Tasso di risparmio (%)', 'Rapporto rata/reddito (%)',
      'Fondo di emergenza (mesi coperti)', 'Investimenti (€)', 'Debiti residui (€)', 'Patrimonio netto (€)'], 5)}
  <div class="end">
    <h2>Metti in pratica la guida con Reflexi</h2>
    <p>Tutti i passi di questa guida, in un'unica app: budget per categoria con avvisi, mutuo con piano di ammortamento, investimenti con quotazioni reali, obiettivi, patrimonio netto e previsioni. Le schede si compilano da sole con le tue transazioni.</p>
    <p class="end-link">reflexi.it</p>
  </div>
  <p class="disclaimer">Questa guida ha scopo informativo ed educativo e non costituisce consulenza finanziaria, fiscale o legale. Gli esempi sono semplificati; aliquote e limiti fiscali sono quelli in vigore in Italia al momento della pubblicazione. Per decisioni importanti rivolgiti a un professionista abilitato. © 2026 Reflexi — reflexi.it</p>
</section>''')

CSS = f'''
@font-face {{ font-family: Inter; font-weight: 400 800; src: url({font('inter-v20-latin.woff2')}) format('woff2'); }}
@font-face {{ font-family: Inter; font-weight: 400 800; src: url({font('inter-v20-latin-ext.woff2')}) format('woff2'); unicode-range: U+0100-02BA, U+1E00-1E9F; }}
@page {{ size: A4; margin: 0; }}
* {{ box-sizing: border-box; }}
body {{ margin: 0; font-family: Inter, sans-serif; color: #1f2333; font-size: 10.5pt; line-height: 1.55; }}
.page {{ width: 210mm; height: 297mm; padding: 18mm 18mm 16mm; position: relative; page-break-after: always; overflow: hidden; }}
.page:not(.cover)::after {{ content: 'Gestione della finanza personale · reflexi.it'; position: absolute; bottom: 9mm; left: 18mm; font-size: 8pt; color: #9a9fb0; }}
.page:not(.cover)::before {{ counter-increment: pg; content: counter(pg); position: absolute; bottom: 9mm; right: 18mm; font-size: 8pt; color: #9a9fb0; }}
body {{ counter-reset: pg 1; }}
h1, h2, h3 {{ color: #14162b; letter-spacing: -0.02em; }}
h2 {{ font-size: 17pt; margin: 22px 0 8px; line-height: 1.2; }}
h2.first {{ margin-top: 0; }}
h3 {{ font-size: 12pt; margin: 16px 0 6px; }}
p {{ margin: 0 0 8px; }}
ul {{ margin: 0 0 8px; padding-left: 18px; }} li {{ margin-bottom: 4px; }}
.lead {{ font-size: 11.5pt; color: #3b4054; }}
.small {{ font-size: 9pt; color: #6b7080; }}
.kicker {{ font-size: 8.5pt; font-weight: 700; text-transform: uppercase; letter-spacing: .08em; color: #4f46e5; }}
.step-head {{ display: flex; align-items: center; gap: 14px; margin-bottom: 10px; }}
.step-head h2 {{ margin: 2px 0 0; }}
.step-n {{ flex: none; width: 46px; height: 46px; border-radius: 12px; background: #4f46e5; color: #fff; font-size: 22pt; font-weight: 800; display: flex; align-items: center; justify-content: center; }}
table {{ width: 100%; border-collapse: collapse; margin: 6px 0 8px; font-size: 9.5pt; border: 1px solid #e7e9f0; }}
th {{ background: #f3f4f8; text-align: left; font-size: 8pt; text-transform: uppercase; letter-spacing: .04em; color: #6b7080; padding: 6px 8px; }}
td {{ padding: 5px 8px; border-top: 1px solid #e7e9f0; }}
.n {{ text-align: right; white-space: nowrap; font-variant-numeric: tabular-nums; }}
tr.tot td {{ font-weight: 700; color: #14162b; background: #fafbfd; }}
table.fill td {{ height: 26px; }}
table.fill td + td {{ border-left: 1px solid #e7e9f0; }}
.box {{ border-radius: 10px; padding: 10px 14px; margin: 10px 0; background: #eef0fd; }}
.box.warn {{ background: #fff7e8; }}
.box-t {{ font-weight: 700; margin-bottom: 4px; color: #14162b; }}
.box p {{ margin: 0; font-size: 9.5pt; }}
.reflexi {{ border-left: 3px solid #10b981; padding: 6px 12px; margin: 12px 0 6px; font-size: 9.5pt; color: #3b4054; background: #f2fbf7; border-radius: 0 8px 8px 0; }}
.rx {{ font-weight: 700; color: #0e9f6e; }}
.see {{ font-size: 9pt; font-weight: 600; color: #4f46e5; text-align: right; margin-top: 4px; }}
.toc {{ padding-left: 0; list-style: none; counter-reset: t; }}
.toc li {{ display: flex; justify-content: space-between; border-bottom: 1px dotted #c9ccd8; padding: 5px 0; counter-increment: t; }}
.toc li > span:first-child::before {{ content: counter(t) '. '; font-weight: 700; color: #4f46e5; }}
.toc li.toc-sub > span:first-child::before {{ content: ''; }}
.toc li.toc-sub {{ font-weight: 600; }}
.routine, .checks {{ display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 10px; margin: 10px 0; }}
.routine > div, .checks > div {{ border: 1px solid #e7e9f0; border-radius: 10px; padding: 10px 12px; font-size: 9.5pt; }}
.routine ul {{ padding-left: 14px; margin: 0; }}
.checks p {{ margin: 0 0 6px; }}
.r-t {{ font-weight: 700; color: #4f46e5; margin-bottom: 6px; font-size: 9.5pt; }}
.end {{ margin-top: 26px; border-radius: 14px; padding: 18px 22px; color: #fff; background: linear-gradient(135deg, #4f46e5, #4338ca 60%, #1e1b4b); }}
.end h2 {{ color: #fff; margin: 0 0 6px; font-size: 15pt; }}
.end p {{ color: rgba(255,255,255,.85); }}
.end-link {{ font-weight: 700; font-size: 12pt; color: #fff !important; margin: 0; }}
.disclaimer {{ position: absolute; left: 18mm; right: 18mm; bottom: 16mm; font-size: 7.5pt; color: #9a9fb0; }}
.cover {{ color: #fff; background: linear-gradient(150deg, #4f46e5 0%, #4338ca 55%, #1e1b4b 100%); padding: 22mm 20mm; }}
.cover .c1 {{ position: absolute; right: -60mm; top: -50mm; width: 150mm; height: 150mm; border-radius: 50%; background: rgba(255,255,255,.07); }}
.cover .c2 {{ position: absolute; left: -50mm; bottom: -70mm; width: 130mm; height: 130mm; border-radius: 50%; background: rgba(255,255,255,.06); }}
.brand {{ display: flex; align-items: center; gap: 12px; font-size: 20pt; font-weight: 800; position: relative; }}
.brand svg {{ width: 44px; height: 44px; border-radius: 11px; }}
.cover-body {{ position: absolute; left: 20mm; right: 20mm; top: 78mm; }}
.cover-kicker {{ display: inline-block; padding: 5px 14px; border-radius: 999px; background: rgba(255,255,255,.16); font-size: 9pt; font-weight: 700; letter-spacing: .1em; text-transform: uppercase; }}
.cover h1 {{ color: #fff; font-size: 38pt; line-height: 1.08; margin: 18px 0 14px; letter-spacing: -0.03em; }}
.cover-sub {{ font-size: 15pt; color: rgba(255,255,255,.85); max-width: 150mm; line-height: 1.35; }}
.cover-list {{ list-style: none; padding: 0; margin-top: 34px; font-size: 11.5pt; }}
.cover-list li {{ margin-bottom: 8px; }}
.cover-list li::before {{ content: '✓'; display: inline-block; width: 22px; font-weight: 800; color: #6ee7b7; }}
.cover-foot {{ position: absolute; left: 20mm; bottom: 18mm; font-size: 11pt; font-weight: 600; color: rgba(255,255,255,.75); }}
'''

doc = f'<!doctype html><html lang="it"><head><meta charset="utf-8"><title>Gestione della finanza personale: la guida pratica in 7 passi</title><style>{CSS}</style></head><body>{"".join(pages)}</body></html>'
with open(os.path.join(HERE, 'guida.html'), 'w') as f:
    f.write(doc)

os.makedirs(os.path.join(ROOT, os.path.dirname(OUT_PDF)), exist_ok=True)
subprocess.run(['docker', 'run', '--rm', '-v', f'{HERE}:/guida', '-v', f'{os.path.join(ROOT, "webroot/private")}:/out',
                '-v', f'{os.path.join(ROOT, "webroot/og")}:/og',
                PLAYWRIGHT_IMAGE, 'sh', '-c',
                'cd /tmp && npm i -s playwright@1.48.0 >/dev/null 2>&1 && cp /guida/render.js . && node render.js'],
               check=True)
print(f'scritto {OUT_PDF}')
