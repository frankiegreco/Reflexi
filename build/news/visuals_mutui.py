"""Tabelle e grafici per gli articoli News della categoria mutui.

Ogni voce di VISUALS e': slug -> lista di (testo_ancora, html). La figura viene
inserita subito dopo il paragrafo o l'elenco che contiene testo_ancora (vedi
build/news/make_visuals_migration.py, che produce la migrazione SQL).
Tutti i numeri sono calcolati qui, con le stesse formule dell'app
(ammortamento alla francese, webroot/api/loan_math.php).
"""
import os, sys
sys.path.insert(0, os.path.dirname(os.path.abspath(__file__)))
from news_html import table, bars, euro  # noqa: E402


# ---------- formule ----------

def pmt(principal, rate_pct, months):
    i = rate_pct / 1200
    return principal / months if i == 0 else principal * i / (1 - (1 + i) ** -months)


def pv(payment, rate_pct, months):
    i = rate_pct / 1200
    return payment * months if i == 0 else payment * (1 - (1 + i) ** -months) / i


def schedule(principal, months, rate_for_month):
    """Piano mese per mese; rate_for_month(m) = tasso del mese m (1-based).
    Al cambio di tasso la rata si ricalcola sui mesi residui (come le banche)."""
    bal, rows, rate, pay = principal, [], None, 0.0
    for m in range(1, months + 1):
        r = rate_for_month(m)
        if r != rate:
            rate, pay = r, pmt(bal, r, months - m + 1)
        interest = bal * r / 1200
        cap = bal if m == months else pay - interest
        bal -= cap
        rows.append({'m': m, 'pay': pay if m < months else cap + interest, 'int': interest, 'cap': cap, 'bal': max(bal, 0.0)})
    return rows


def total_interest(rows):
    return sum(r['int'] for r in rows)


def taeg(principal, rate_pct, months, upfront_fees, monthly_fee):
    """TAEG: tasso annuo effettivo che uguaglia importo netto erogato e flussi pagati."""
    pay = pmt(principal, rate_pct, months) + monthly_fee
    lo, hi = 0.0, 0.05
    for _ in range(200):
        mid = (lo + hi) / 2
        if pv(pay, mid * 1200, months) > principal - upfront_fees:
            lo = mid
        else:
            hi = mid
    return ((1 + lo) ** 12 - 1) * 100


def e(v, d=0):
    return euro(v, d)


def pct(v, d=1):
    return euro(v, d) + '%'


VISUALS = {}

# ---------- ammortamento alla francese ----------
A = schedule(150000, 300, lambda m: 3.5)
VISUALS['ammortamento-alla-francese-guida-mutuo-rata-costante'] = [
    ('Ecco perché negli anni iniziali il debito residuo scende così lentamente', table(
        'Come cambia la rata nel tempo (importi in euro)',
        ['Rata', 'Interessi', 'Capitale', 'Debito residuo'],
        [[f'{n}ª', e(A[n - 1]['int']), e(A[n - 1]['cap']), e(A[n - 1]['bal'])]
         for n in (1, 61, 121, 181, 241, 300)],
        note='Mutuo da 150.000 euro a 25 anni, tasso fisso 3,5%, rata costante di 750,94 euro. '
             'Le rate indicate sono la prima del 1°, 6°, 11°, 16° e 21° anno, e l\'ultima.')
     + bars('Interessi pagati ogni 5 anni',
            [(f'Anni {k * 5 + 1}-{k * 5 + 5}', sum(r['int'] for r in A[k * 60:(k + 1) * 60]), 'loan') for k in range(5)],
            fmt=lambda v: e(v) + ' €',
            note=f'Stesso mutuo: in totale {e(total_interest(A))} euro di interessi, più della metà nei primi 10 anni.')),
]
assert sum(r['int'] for r in A[:120]) > total_interest(A) / 2

# ---------- tasso fisso o variabile ----------
FIX, VAR = 3.3, 2.5
fixed = schedule(150000, 300, lambda m: FIX)
scen = [('Variabile, tassi stabili', 0.0), ('Variabile, +1,5 punti dal 4° anno', 1.5), ('Variabile, −0,5 punti dal 4° anno', -0.5)]
rows_fv = [[f'Fisso {pct(FIX)}', e(fixed[0]['pay']), e(fixed[36]['pay']), e(total_interest(fixed))]]
for label, delta in scen:
    s = schedule(150000, 300, lambda m, d=delta: VAR + (d if m > 36 else 0))
    rows_fv.append([label, e(s[0]['pay']), e(s[36]['pay']), e(total_interest(s))])
VISUALS['mutuo-tasso-fisso-o-variabile-come-scegliere'] = [
    ('Fase del ciclo dei tassi', table(
        'Fisso o variabile: tre scenari a confronto (importi in euro)',
        ['Scenario', 'Rata iniziale', 'Rata dal 4° anno', 'Interessi totali'],
        rows_fv,
        note=f'Mutuo da 150.000 euro a 25 anni. Tassi di esempio: fisso {pct(FIX)}, variabile in partenza {pct(VAR)}. '
             'Nel variabile la rata si ricalcola sul debito residuo quando cambia il tasso.')),
]

# ---------- LTV ----------
HOUSE = 200000
ltv_rows = []
for ltv in (60, 80, 90, 100):
    loan = HOUSE * ltv / 100
    margin = HOUSE * 0.85 - loan
    ltv_rows.append([f'{ltv}%', e(loan), e(HOUSE - loan), ('' if margin >= 0 else '−') + e(abs(margin))])
VISUALS['loan-to-value-ltv-mutuo-quanto-puoi-finanziare'] = [
    ('lo stesso calo lascia comunque un margine consistente', table(
        'Immobile da 200.000 euro: LTV, anticipo e margine della banca (importi in euro)',
        ['LTV', 'Mutuo', 'Anticipo', 'Margine se il valore cala del 15%'],
        ltv_rows,
        note='Margine: valore dell\'immobile dopo un calo del 15% (170.000 euro) meno il mutuo. '
             'Un margine negativo vuol dire che la casa vale meno del debito. Per semplicità il debito è quello iniziale.')),
]

# ---------- spese accessorie ----------
def spese_table(price=200000, loan=160000, rendita=700):
    """Stima per scenario. Da privato (persona fisica, prezzo-valore): registro 2% (prima casa) o 9%
    sul valore catastale = rendita x 1,05 x 110 (prima casa) o x 120, minimo 1.000 euro, piu' ipotecaria
    e catastale fisse di 50 euro l'una. Da impresa: IVA 4% sul prezzo piu' tre imposte fisse da 200 euro.
    Imposta sostitutiva sul mutuo 0,25% (prima casa) o 2%. Notaio, perizia, istruttoria: stime."""
    notaio, bank, agency = 2500 + 1800, 300 + 800, price * 0.03 * 1.22
    cols = [('prima', max(1000, rendita * 115.5 * 0.02) + 100, loan * 0.0025),
            ('seconda', max(1000, rendita * 126 * 0.09) + 100, loan * 0.02),
            ('impresa', price * 0.04 + 600, loan * 0.0025)]
    no_ag = [imp + notaio + s + bank for _, imp, s in cols]
    with_ag = [t + agency for t in no_ag]
    return table(
        'Esempio: casa da 200.000 euro con mutuo da 160.000 (importi in euro)',
        ['', 'Prima casa da privato', 'Seconda casa da privato', 'Prima casa da impresa'],
        [['Imposte sull\'acquisto'] + [e(imp) for _, imp, _ in cols],
         ['Notaio, due atti'] + [e(notaio)] * 3,
         ['Imposta sul mutuo'] + [e(s) for _, _, s in cols],
         ['Perizia e istruttoria'] + [e(bank)] * 3,
         ['Totale senza agenzia'] + [e(t) for t in no_ag],
         ['in % del prezzo'] + [pct(t / price * 100) for t in no_ag],
         ['Con agenzia (3% + IVA)'] + [e(t) for t in with_ag],
         ['in % del prezzo'] + [pct(t / price * 100) for t in with_ag]],
        note='Stima indicativa. Da privato le imposte si pagano sul valore catastale (qui con una rendita di 700 euro), '
             'non sul prezzo: per questo la prima casa costa molto meno. Da impresa si paga l\'IVA sul prezzo (4% prima casa). '
             'Notaio: atto di compravendita e atto di mutuo. Notaio, perizia e istruttoria variano: chiedi sempre i preventivi.')



VISUALS['spese-notarili-accessorie-mutuo-costo-reale-acquisto-casa'] = [
    ('Un\'ultima voce, se coinvolta un\'agenzia', table(
        'Le spese accessorie in sintesi',
        ['Voce', 'Da cosa dipende'],
        [['Notaio (compravendita e mutuo)', 'Valore dell\'immobile e notaio scelto: onorari negoziabili'],
         ['Imposte di registro, ipotecaria e catastale', 'Prima o seconda casa, acquisto da privato o da impresa (IVA)'],
         ['Imposta sostitutiva sul mutuo', 'Importo finanziato, aliquota agevolata per la prima casa'],
         ['Perizia e istruttoria', 'Banca: costo fisso o in percentuale, a volte azzerato in promozione'],
         ['Agenzia immobiliare', 'Percentuale sul prezzo, da contrattare prima']],
        note='Chiedi un preventivo scritto al notaio e il dettaglio delle spese alla banca prima di firmare il preliminare.',
        numeric=set())),
    ('con forti variazioni in base a prima/seconda casa', spese_table()),
]

# ---------- under 36 ----------
u36 = []
for label, loan in (('Senza garanzia (mutuo all\'80%)', 160000), ('Con garanzia (mutuo al 100%)', 200000)):
    u36.append([label, e(HOUSE - loan), e(pmt(loan, 3, 360)), e(pmt(loan, 3, 360) * 360 - loan)])
VISUALS['mutuo-giovani-under-36-garanzia-prima-casa'] = [
    ('cambia chi si fa garante di una parte del rischio', table(
        'Casa da 200.000 euro: con e senza garanzia (importi in euro)',
        ['', 'Anticipo', 'Rata mensile', 'Interessi totali'],
        u36,
        note='Esempio a 30 anni con tasso fisso del 3%, spese accessorie escluse (restano comunque da pagare). '
             'Meno anticipo vuol dire più debito: rata e interessi salgono.')),
]

# ---------- estinzione anticipata: tasso del mutuo vs rendimento alternativo ----------
def grow(rate, years=10, cap=10000):
    return cap * ((1 + rate / 100) ** years - 1)

VISUALS['estinzione-anticipata-mutuo-quando-conviene'] = [
    ('il confronto si stringe molto', bars(
        'Cosa rendono 10.000 euro in 10 anni',
        [('Estinguere un mutuo al 2%', grow(2), 'income'),
         ('Estinguere un mutuo al 3,5%', grow(3.5), 'income'),
         ('Estinguere un mutuo al 5%', grow(5), 'income'),
         ('Investire al 6% annuo (non garantito)', grow(6) * 0.74, 'accent')],
        fmt=lambda v: e(v) + ' €',
        note='Approssimazione: il risparmio di interessi cresce come un capitale investito al tasso del mutuo. '
             'Investimento: rendimento atteso del 6% annuo, con il 26% di tasse sul guadagno. Detrazione fiscale esclusa.')),
]

# ---------- surroga ----------
RES, M = 120000, 240
old, new = schedule(RES, M, lambda m: 4.0), schedule(RES, M, lambda m: 3.0)
VISUALS['surroga-mutuo-cosa-e-come-funziona'] = [
    ('più anni di risparmio sugli interessi puoi ottenere dal cambio', table(
        'Esempio di surroga: 120.000 euro di debito residuo, 20 anni rimasti',
        ['', 'Tasso 4% (attuale)', 'Tasso 3% (surroga)'],
        [['Rata mensile', e(old[0]['pay'], 2) + ' €', e(new[0]['pay'], 2) + ' €'],
         ['Interessi ancora da pagare', e(total_interest(old)) + ' €', e(total_interest(new)) + ' €'],
         ['Risparmio con la surroga', '—', e(total_interest(old) - total_interest(new)) + ' €']],
        highlight_last=True,
        note='Stessa durata residua. La surroga per legge non costa nulla a chi la chiede.')),
]

# ---------- TAN e TAEG ----------
offers = [('Offerta A', 3.0, 3000, 3.0), ('Offerta B', 3.1, 300, 0.0)]
taeg_rows = []
for name, tan, fees, inc in offers:
    spese = e(fees) + (f' + {e(inc, 2)} a rata' if inc else '')
    taeg_rows.append([name, pct(tan, 2), spese, pct(taeg(150000, tan, 300, fees, inc), 2)])
VISUALS['tan-e-taeg-mutuo-differenza'] = [
    ('Il TAN resta utile per un\'altra domanda', table(
        'Due offerte per 150.000 euro a 25 anni (importi in euro)',
        ['', 'TAN', 'Spese', 'TAEG'],
        taeg_rows,
        numeric={1, 3},
        note='Spese: istruttoria e perizia, più l\'eventuale costo di incasso di ogni rata. '
             'L\'offerta A ha il TAN più basso ma costa di più: il TAEG lo mostra.')),
]
assert taeg(150000, 3.0, 300, 3000, 3.0) > taeg(150000, 3.1, 300, 300, 0)

# ---------- Euribor e IRS ----------
SPREAD = 1.0
eur_rows = []
for euribor in (1.0, 2.0, 3.0, 4.0):
    r = euribor + SPREAD
    eur_rows.append([pct(euribor), pct(r), e(pmt(150000, r, 300), 2), ('+' + e(pmt(150000, r, 300) - pmt(150000, 1.0 + SPREAD, 300))) if euribor > 1 else '—'])
VISUALS['euribor-irs-mutuo-cosa-sono'] = [
    ('quando la BCE alza i tassi, l\'Euribor sale', table(
        'Mutuo variabile: rata al variare dell\'Euribor (importi in euro)',
        ['Euribor', 'Tasso (spread 1%)', 'Rata', 'In più al mese'],
        eur_rows,
        note='Mutuo da 150.000 euro a 25 anni, rata calcolata come se il tasso restasse quello per tutta la durata.')),
]

# ---------- detrazione interessi ----------
det_rows = []
for year in (1, 5, 10, 15, 20, 25):
    yi = sum(r['int'] for r in A[(year - 1) * 12:year * 12])
    det_rows.append([f'{year}° anno', e(yi), e(min(yi, 4000)), e(min(yi, 4000) * 0.19)])
VISUALS['detrazione-interessi-mutuo-prima-casa'] = [
    ('si traducono di solito in un rimborso', table(
        'Quanto si recupera anno per anno (importi in euro)',
        ['Anno', 'Interessi pagati', 'Detraibili (max 4.000)', 'Detrazione (19%)'],
        det_rows,
        note='Mutuo da 150.000 euro a 25 anni al 3,5%. Nei primi anni gli interessi superano il tetto di 4.000 euro: '
             'la parte in eccesso non si detrae.')),
]

# ---------- quanto mutuo posso chiedere ----------
VISUALS['quanto-mutuo-posso-chiedere-con-il-mio-stipendio'] = [
    ('Allungare la durata aumenta l\'importo ottenibile', table(
        'Mutuo ottenibile con la regola del terzo, al 3% (importi in euro)',
        ['Reddito netto', 'Rata max', 'In 25 anni', 'In 30 anni'],
        [[e(red), e(red / 3), e(pv(red / 3, 3, 300), 0), e(pv(red / 3, 3, 360), 0)]
         for red in (1500, 2000, 2500, 3000)],
        note='Importi arrotondati. Il tasso reale dipende dal mercato e dalla banca.')),
]

# ---------- mutuo cointestato ----------
C = schedule(200000, 300, lambda m: 3.5)
int1 = sum(r['int'] for r in C[:12])
co_rows = []
for a_q, b_q in ((50, 50), (70, 30)):
    co_rows.append([f'{a_q}% / {b_q}%', e(C[0]['pay'] * a_q / 100) + ' / ' + e(C[0]['pay'] * b_q / 100),
                    e(min(int1 * a_q / 100, 4000 * a_q / 100) * 0.19) + ' / ' + e(min(int1 * b_q / 100, 4000 * b_q / 100) * 0.19)])
VISUALS['mutuo-cointestato-come-funziona'] = [
    ('entro la propria parte del limite', table(
        'Mutuo da 200.000 euro, quote diverse (importi in euro)',
        ['Quote', 'Rata a testa', 'Detrazione 1° anno a testa'],
        co_rows,
        note=f'25 anni al 3,5%: rata {e(C[0]["pay"], 2)} euro, interessi del 1° anno {e(int1)} euro. '
             'Ognuno detrae la sua quota di interessi entro la sua quota del tetto di 4.000 euro. '
             'Verso la banca, però, entrambi rispondono dell\'intera rata.')),
]

# ---------- rinegoziazione ----------
base = schedule(120000, 240, lambda m: 4.0)
opts = [('Situazione attuale: 4%, 20 anni', 4.0, 240), ('Tasso al 3%, stessa durata', 3.0, 240),
        ('Durata a 25 anni, stesso tasso', 4.0, 300), ('Tasso al 3% e 25 anni', 3.0, 300)]
ren_rows = []
for label, r, n in opts:
    s = schedule(120000, n, lambda m, r=r: r)
    diff = total_interest(s) - total_interest(base)
    ren_rows.append([label, e(s[0]['pay'], 2), e(total_interest(s)), '—' if n == 240 and r == 4.0 else ('+' if diff > 0 else '−') + e(abs(diff))])
VISUALS['rinegoziazione-mutuo-come-funziona'] = [
    ('accorciarla fa l\'opposto', table(
        'Cosa cambia rinegoziando 120.000 euro di debito residuo (importi in euro)',
        ['Opzione', 'Rata', 'Interessi da pagare', 'Rispetto a oggi'],
        ren_rows,
        note='Allungare la durata abbassa la rata ma fa pagare più interessi in totale.')),
]

if __name__ == '__main__':
    for slug, items in VISUALS.items():
        print(slug, len(items))
