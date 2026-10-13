"""Tabelle e grafici per gli articoli News della categoria pianificazione.

Stesso formato di visuals_mutui.py: slug -> lista di (testo_ancora, html).
Esempi illustrativi con numeri calcolati qui; rendimenti ipotetici, non previsioni.
"""
import math, os, sys
sys.path.insert(0, os.path.dirname(os.path.abspath(__file__)))
from news_html import table, bars, euro  # noqa: E402


def e(v, d=0):
    return euro(v, d)


def pct(v, d=None):
    if d is None:
        d = 0 if abs(v - round(v)) < 1e-9 else 1
    return euro(v, d) + '%'


def fv_monthly(pay, rate_pct, months):
    i = rate_pct / 1200
    return pay * months if i == 0 else pay * ((1 + i) ** months - 1) / i


def pmt(principal, rate_pct, months):
    i = rate_pct / 1200
    return principal * i / (1 - (1 + i) ** -months)


VISUALS = {}

# ---------- patrimonio netto ----------
assets = [('Conto corrente e deposito', 8000), ('Investimenti', 25000), ('Casa', 220000)]
debts = [('Mutuo, debito residuo', 140000), ('Finanziamento auto', 6000)]
nw = sum(v for _, v in assets) - sum(v for _, v in debts)
VISUALS['patrimonio-netto-cos-e-perche-seguirlo-nel-tempo'] = [
    ('in base a quanto debito portano e quanto hanno accumulato', table(
        'Esempio di patrimonio netto (importi in euro)',
        ['Voce', 'Importo'],
        [[n, e(v)] for n, v in assets] + [[n, '−' + e(v)] for n, v in debts] + [['Patrimonio netto', e(nw)]],
        highlight_last=True,
        note='La casa conta al suo valore di mercato, il mutuo per il debito che resta da restituire, non per la rata.')),
]

# ---------- tasso di risparmio ----------
def years_to_fire(s, real=5.0):
    """Anni per accumulare 25 volte la spesa annua risparmiando la quota s del reddito, partendo da zero."""
    target = 25 * (1 - s)
    r = real / 100
    return math.log(1 + target * r / s) / math.log(1 + r)

VISUALS['tasso-di-risparmio-cosa-e-come-calcolarlo'] = [
    ('nonostante uno stipendio molto più alto', table(
        'Stipendio e tasso di risparmio (importi in euro al mese)',
        ['Reddito netto', 'Spese', 'Risparmio', 'Tasso'],
        [[e(r), e(s), e(r - s), pct((r - s) / r * 100)] for r, s in ((2500, 2000), (4000, 3900), (1800, 1530))])),
    ('in modo più che proporzionale', bars(
        'Anni di lavoro per raggiungere l\'indipendenza finanziaria',
        [(f'Tasso di risparmio del {p}%', years_to_fire(p / 100), 'accent') for p in (10, 20, 30, 40, 50, 60)],
        fmt=lambda v: e(v, 0) + ' anni',
        note='Partendo da zero, con un rendimento reale ipotetico del 5% annuo e un capitale obiettivo di 25 volte '
             'la spesa annua (regola del 4%). Passare dal 10% al 20% fa risparmiare circa 14 anni.')),
]
assert 13 < years_to_fire(0.1) - years_to_fire(0.2) < 15

# ---------- inflazione e potere d'acquisto ----------
VISUALS['inflazione-potere-acquisto-perche-il-capitale-deve-crescere'] = [
    ('è cambiato quanto puoi comprarci', table(
        'Quanto valgono davvero 10.000 euro fermi sul conto (importi in euro)',
        ['Dopo', 'Inflazione 2%', 'Inflazione 2,5%', 'Inflazione 3%'],
        [[f'{y} anni'] + [e(10000 / (1 + i / 100) ** y) for i in (2, 2.5, 3)] for y in (5, 10, 20, 30)],
        note='Potere d\'acquisto in euro di oggi, con un\'inflazione costante ogni anno.')),
]

# ---------- rapporto debito/reddito ----------
INC = 2500
dti_rows, tot = [], 0
for label, rata in (('Solo la rata del mutuo', 700), ('Più il finanziamento auto', 250), ('Più un prestito personale', 150)):
    tot += rata
    dti_rows.append([label, e(rata), e(tot), pct(tot / INC * 100)])
VISUALS['rapporto-debito-reddito-dti-cosa-e'] = [
    ('a prescindere dal merito creditizio generale del richiedente', table(
        'Reddito netto di 2.500 euro: come sale il DTI (importi in euro)',
        ['', 'Rata', 'Rate totali', 'DTI'],
        dti_rows,
        note='Con il solo mutuo il DTI resta sotto la soglia indicativa del 30-35%; '
             'con le altre rate la supera, anche se ogni singola rata sembra sostenibile.')),
]

# ---------- pensione integrativa ----------
VISUALS['pensione-integrativa-fondo-pensione-quanto-serve'] = [
    ('a parità di sforzo mensile', table(
        '100 euro al mese fino a 67 anni (importi in euro)',
        ['Inizi a', 'Versato', 'Capitale a 67 anni'],
        [[f'{a} anni', e(100 * 12 * (67 - a)), e(fv_monthly(100, 4, 12 * (67 - a)))] for a in (25, 30, 35, 45, 55)],
        note='Rendimento ipotetico del 4% annuo, al netto dei costi; tasse e vantaggi fiscali esclusi.')),
]

# ---------- FIRE ----------
VISUALS['indipendenza-finanziaria-fire-come-calcolare-il-numero'] = [
    ('per aumentare il margine di sicurezza', table(
        'Il numero FIRE con regole più o meno prudenti (migliaia di euro)',
        ['Spese annuali', 'Prelievo 4%', 'Prelievo 3,5%', 'Prelievo 3%'],
        [[e(s / 1000)] + [e(s / w / 1000) for w in (0.04, 0.035, 0.03)] for s in (20000, 30000, 40000)],
        note='Capitale necessario = spese annuali diviso la percentuale di prelievo. Al 4% è 25 volte la spesa, al 3% oltre 33. '
             'Esempio: con 30.000 euro di spese servono 750.000 euro al 4% e 1 milione al 3%.')),
]

# ---------- finanze di coppia ----------
A, B, COMMON = 3000, 1500, 1800
VISUALS['finanze-di-coppia-conti-separati-o-cointestati'] = [
    ('non con la stessa cifra assoluta', table(
        'Spese comuni di 1.800 euro, redditi di 3.000 e 1.500 euro (importi in euro)',
        ['', 'Versa A', 'Versa B', 'Peso sul reddito'],
        [['Metà a testa', e(COMMON / 2), e(COMMON / 2), f'{pct(COMMON / 2 / A * 100)} / {pct(COMMON / 2 / B * 100)}'],
         ['In proporzione al reddito', e(COMMON * A / (A + B)), e(COMMON * B / (A + B)), f'{pct(COMMON / (A + B) * 100)} / {pct(COMMON / (A + B) * 100)}']],
        note='Con la divisione a metà B versa il 60% del suo reddito, A il 30%. In proporzione entrambi versano il 40%.')),
]

# ---------- TFR ----------
VISUALS['tfr-in-azienda-o-fondo-pensione'] = [
    ('raramente fa crescere il capitale in termini reali', table(
        'Rivalutazione del TFR in azienda',
        ['Inflazione', 'Rivalutazione', 'Rendimento reale'],
        [[pct(i), pct(1.5 + 0.75 * i, 2), ('+' if 1.5 - 0.25 * i >= 0 else '−') + pct(abs(1.5 - 0.25 * i), 2)]
         for i in (1, 2, 4, 8)],
        note='Rivalutazione = 1,5% + 75% dell\'inflazione, prima delle tasse. Rendimento reale: rivalutazione meno inflazione. '
             'Con l\'inflazione alta il TFR resta indietro.')),
    ('Tassazione finale agevolata', table(
        'Tassazione finale del fondo pensione',
        ['Anni di partecipazione', 'Aliquota'],
        [[str(y) if y < 35 else '35 o più', pct(max(9, 15 - 0.3 * max(0, y - 15)))] for y in (15, 20, 25, 30, 35)],
        note='Dal sedicesimo anno l\'aliquota scende di 0,3 punti l\'anno, fino al minimo del 9%.')),
]

# ---------- gestire i soldi per età ----------
VISUALS['gestire-i-soldi-a-20-30-40-50-anni'] = [
    ('verificare la propria posizione contributiva', table(
        'Le priorità decennio per decennio',
        ['Età', 'Priorità', 'Da controllare'],
        [['20 anni', 'Abitudine al risparmio, primo fondo di emergenza', 'Spese e debiti al consumo'],
         ['30 anni', 'Casa, famiglia, piano di accumulo, TFR', 'Rapporto debito/reddito'],
         ['40 anni', 'Accelerare il risparmio, verso la pensione', 'Tasso di risparmio e stile di vita'],
         ['50 anni', 'Ridurre il rischio, stimare la pensione', 'Asset allocation e contributi']],
        numeric=set())),
]

# ---------- arrivo di un figlio ----------
VISUALS['arrivo-di-un-figlio-pianificare-le-spese'] = [
    ('Su orizzonti di quindici o vent', table(
        '50 euro al mese per 18 anni (importi in euro)',
        ['Rendimento annuo', 'Versato', 'Capitale a 18 anni'],
        [[pct(r), e(50 * 216), e(fv_monthly(50, r, 216))] for r in (0, 2, 4)],
        note='Rendimenti ipotetici, al netto dei costi e prima delle tasse. 0% è il salvadanaio, 4% un piano di accumulo diversificato.')),
]

# ---------- comprare o affittare ----------
PRICE, LOAN, RATE = 250000, 200000, 3.5
monthly = pmt(LOAN, RATE, 300)
bal, interest_by_year = LOAN, []
for y in range(25):
    yi = 0.0
    for _ in range(12):
        it = bal * RATE / 1200
        yi += it
        bal -= monthly - it
    interest_by_year.append(yi)
RENT = 900 * 12
buy_cols = []
for stay in (5, 10, 20):
    avg_int = sum(interest_by_year[:stay]) / stay
    costs = {'int': avg_int, 'maint': PRICE * 0.01, 'opp': (PRICE - LOAN) * 0.03, 'fees': 15000 / stay}
    buy_cols.append(costs)
VISUALS['comprare-o-affittare-casa-cosa-conviene'] = [
    ('l\'acquisto tende a diventarlo', table(
        'Costi che non tornano indietro, all\'anno (importi in euro)',
        ['', 'Resti 5 anni', 'Resti 10 anni', 'Resti 20 anni'],
        [['Interessi del mutuo (media)'] + [e(c['int']) for c in buy_cols],
         ['Manutenzione (1% del valore)'] + [e(c['maint']) for c in buy_cols],
         ['Rendimento perso sull\'anticipo (3%)'] + [e(c['opp']) for c in buy_cols],
         ['Spese di acquisto divise per gli anni'] + [e(c['fees']) for c in buy_cols],
         ['Totale chi compra'] + [e(sum(c.values())) for c in buy_cols],
         ['Affitto di una casa simile', e(RENT), e(RENT), e(RENT)]],
        note='Esempio: casa da 250.000 euro, anticipo 50.000, mutuo di 200.000 a 25 anni al 3,5%, spese di acquisto di 15.000 euro, '
             'affitto di 900 euro al mese costante. Tasse sull\'immobile, rivalutazione della casa e aumenti dell\'affitto esclusi.')),
]

# ---------- inflazione dello stile di vita ----------
SAL, SP = 2000, 1800
ls_rows = [['Prima dell\'aumento', e(SAL), e(SP), e(SAL - SP), pct((SAL - SP) / SAL * 100)]]
for label, extra_sp in (('Aumento tutto speso', 300), ('Metà risparmiata', 150), ('Aumento tutto risparmiato', 0)):
    inc, sp = SAL + 300, SP + extra_sp
    ls_rows.append([label, e(inc), e(sp), e(inc - sp), pct((inc - sp) / inc * 100)])
VISUALS['inflazione-dello-stile-di-vita'] = [('Migliori il tenore di vita', table(
    'Un aumento di 300 euro al mese: tre modi di usarlo (importi in euro)',
    ['', 'Spese', 'Risparmio', 'Tasso'],
    [[r[0], r[2], r[3], r[4]] for r in ls_rows],
    note='Reddito di 2.000 euro prima e 2.300 dopo l\'aumento. Se lo spendi tutto, il tasso di risparmio scende.'))]

# ---------- assicurazioni ----------
VISUALS['assicurazioni-che-servono-davvero'] = [
    ('Le assicurazioni utili coprono questi rischi grandi e improbabili', table(
        'Il criterio applicato',
        ['Evento', 'Danno possibile', 'Come coprirlo'],
        [['Telefono rotto o rubato', 'Qualche centinaio di euro', 'Fondo di emergenza'],
         ['Guasto all\'auto o alla caldaia', 'Fino a qualche migliaio', 'Fondo di emergenza'],
         ['Danni causati a terzi', 'Decine o centinaia di migliaia', 'RC del capofamiglia'],
         ['Incendio o danni gravi alla casa', 'Il valore della casa', 'Polizza casa'],
         ['Decesso con figli o mutuo cointestato', 'Anni di reddito', 'Temporanea caso morte']],
        numeric=set())),
]

if __name__ == '__main__':
    for slug, items in VISUALS.items():
        print(slug, len(items))
