"""Tabelle e grafici per gli articoli News della categoria investimenti.

Stesso formato di visuals_mutui.py: slug -> lista di (testo_ancora, html).
Gli esempi sono illustrativi (rendimenti ipotetici, non previsioni) e i numeri
sono calcolati qui. Tasse: 26% su redditi finanziari, 12,5% sui titoli di Stato,
bollo 0,2% annuo.
"""
import os, sys
sys.path.insert(0, os.path.dirname(os.path.abspath(__file__)))
from news_html import table, bars, euro  # noqa: E402


def e(v, d=0):
    return euro(v, d)


def pct(v, d=1):
    return euro(v, d) + '%'


def signed_pct(v, d=1):
    return ('+' if v > 0 else '−' if v < 0 else '') + pct(abs(v), d)


VISUALS = {}

# ---------- cos'è un ETF: gestione attiva vs ETF passivo ----------
fund_rows = []
for label, cost in (('Fondo a gestione attiva', 1.5), ('ETF a gestione passiva', 0.2)):
    fund_rows.append([label, pct(cost), e(10000 * (1 + (6 - cost) / 100) ** 20)])
gap = 10000 * (1.058 ** 20 - 1.045 ** 20)
VISUALS['cosa-e-un-etf-guida-per-principianti'] = [
    ('il che tiene i costi annui molto più bassi', table(
        '10.000 euro per 20 anni: quanto pesano i costi (importi in euro)',
        ['', 'Costo annuo', 'Capitale finale'],
        fund_rows,
        note=f'Stesso rendimento lordo ipotetico del 6% annuo per entrambi: la differenza, circa {e(gap)} euro, '
             'è solo l\'effetto dei costi. Tasse e commissioni del broker escluse.')),
]

# ---------- PAC: mediazione del costo ----------
prices = [50, 40, 25, 40, 50, 50]
rows, units = [], 0.0
for m, pr in enumerate(prices, 1):
    q = 200 / pr
    units += q
    rows.append([f'{m}° mese', e(pr, 2), e(q, 2)])
avg_price = sum(prices) / len(prices)
pmc = 200 * len(prices) / units
rows.append(['Totale', '', e(units, 2)])
VISUALS['pac-piano-accumulo-etf-come-funziona'] = [
    ('rispetto a investire tutto insieme in un momento potenzialmente sbagliato', table(
        '200 euro al mese per 6 mesi: quante quote compri',
        ['Mese', 'Prezzo della quota (€)', 'Quote comprate'],
        rows, highlight_last=True,
        note=f'Prezzo medio del periodo: {e(avg_price, 2)} euro. Prezzo medio pagato con il PAC: {e(pmc, 2)} euro, '
             f'più basso perché nei mesi a prezzo basso compri più quote. Prezzi di esempio.')),
]

# ---------- diversificazione: peso di un titolo che dimezza ----------
div_items = []
for n, label in ((1, '1 titolo'), (5, '5 titoli'), (20, '20 titoli'), (1000, 'ETF globale, oltre 1.000 titoli')):
    div_items.append((label, 50 / n, 'expense'))
VISUALS['diversificazione-portafoglio-cosa-significa-davvero'] = [
    ('che replica un intero mercato globale', bars(
        'Se un titolo perde il 50%, quanto perde il portafoglio',
        div_items,
        fmt=lambda v: '−' + (pct(v, 1) if v >= 0.1 else pct(v, 2)),
        note='Portafoglio diviso in parti uguali, altri titoli fermi. Attenzione: se i titoli sono correlati, '
             'quando uno scende tendono a scendere anche gli altri, e il vantaggio si riduce.')),
]

# ---------- accumulazione vs distribuzione ----------
def acc_vs_dist(cap=10000, years=20, price_g=0.04, div=0.02, tax=0.26):
    acc = cap * (1 + price_g + div) ** years
    acc_net = acc - (acc - cap) * tax
    value, basis = cap, cap
    for _ in range(years):
        d = value * div * (1 - tax)
        value = value * (1 + price_g) + d   # dividendo netto reinvestito a mano
        basis += d
    dist_net = value - (value - basis) * tax
    return acc, acc_net, value, dist_net

acc, acc_net, dist, dist_net = acc_vs_dist()
VISUALS['etf-accumulazione-o-distribuzione-differenze'] = [
    ('viene tassato tutto insieme al momento della vendita', table(
        '10.000 euro per 20 anni: accumulazione o distribuzione (importi in euro)',
        ['', 'Valore finale', 'Netto dopo la vendita'],
        [['ETF ad accumulazione', e(acc), e(acc_net)],
         ['ETF a distribuzione, dividendi reinvestiti', e(dist), e(dist_net)]],
        note=f'Esempio: prezzo +4% l\'anno e dividendi del 2%, tasse al 26%. Con la distribuzione i dividendi sono tassati '
             f'ogni anno e si reinveste solo il netto: alla fine circa {e(acc_net - dist_net)} euro in meno. '
             'Bollo e commissioni esclusi.')),
]

# ---------- TER ----------
ter_rows = []
for ter in (0.07, 0.20, 0.30, 0.50):
    fv = 50000 * (1 + 0.07 - ter / 100) ** 20
    ter_rows.append([pct(ter, 2), e(fv), '—' if ter == 0.07 else '−' + e(50000 * 1.0693 ** 20 - fv)])
VISUALS['ter-costi-etf-quanto-incidono-sul-rendimento'] = [
    ('lavora contro di te invece che a tuo favore', table(
        '50.000 euro per 20 anni al 7% lordo: l\'effetto del TER (importi in euro)',
        ['TER', 'Capitale finale', 'Rispetto al TER 0,07%'],
        ter_rows,
        note='Il TER è sottratto ogni anno dal rendimento. Tasse, bollo e commissioni esclusi.')),
]

# ---------- tassazione ----------
VISUALS['tassazione-investimenti-etf-azioni-italia'] = [
    ('il broker o la banca gestiscono questo calcolo automaticamente', table(
        'Su 1.000 euro di guadagno, quanto resta (importi in euro)',
        ['Strumento', 'Aliquota', 'Tasse', 'Netto'],
        [['Azioni, ETF, obbligazioni societarie', '26%', e(260), e(740)],
         ['Titoli di Stato italiani e white list', '12,5%', e(125), e(875)],
         ['ETF con metà titoli di Stato', 'mista', e(500 * 0.26 + 500 * 0.125), e(1000 - 500 * 0.26 - 500 * 0.125)]],
        note='Esempio dell\'ETF misto: metà del guadagno al 26% e metà al 12,5%. '
             'In più si paga l\'imposta di bollo dello 0,2% annuo sul valore detenuto.')),
]

# ---------- interesse composto ----------
i = 0.07 / 12
fv_pay = lambda pay, n: pay * ((1 + i) ** n - 1) / i
early = fv_pay(200, 120) * (1 + i) ** 360
late = fv_pay(200, 360)
VISUALS['interesse-composto-esempi-reali'] = [
    ('perché ogni anno in più compone su una base sempre più grande', bars(
        '10.000 euro al 7% annuo, guadagni reinvestiti',
        [(f'Dopo {y} anni', 10000 * 1.07 ** y, 'accent') for y in (5, 10, 20, 30)],
        fmt=lambda v: e(v) + ' €',
        note='Rendimento medio ipotetico, non garantito. Tasse e costi esclusi.')),
    ('pur avendo versato una frazione del totale', table(
        '200 euro al mese al 7% annuo: chi inizia prima (importi in euro)',
        ['', 'Versato', 'Capitale a 65 anni'],
        [['Versa dai 25 ai 35 anni, poi si ferma', e(200 * 120), e(early)],
         ['Versa dai 35 ai 65 anni', e(200 * 360), e(late)]],
        note='Rendimento medio ipotetico del 7% annuo, capitalizzato ogni mese. Tasse e costi esclusi.')),
]
assert early > late

# ---------- rendimento totale vs CAGR ----------
def cagr(a, b, y):
    return ((b / a) ** (1 / y) - 1) * 100

cagr_rows = []
for label, a, b, y in (('A', 10000, 15000, 5), ('B', 10000, 15000, 8), ('C', 10000, 20000, 10)):
    cagr_rows.append([f'Investimento {label}', f'{y} anni', signed_pct((b / a - 1) * 100, 0), pct(cagr(a, b, y))])
VISUALS['rendimento-totale-vs-cagr-come-leggere-la-performance'] = [
    ('il numero giusto per confrontare investimenti con orizzonti temporali diversi', table(
        'Stesso rendimento totale, CAGR diverso',
        ['', 'Durata', 'Rendimento totale', 'CAGR'],
        cagr_rows,
        note='A e B guadagnano entrambi il 50%, ma A ci mette 5 anni e B 8: il CAGR lo mostra. '
             'C raddoppia in 10 anni, eppure ha un CAGR più basso di A.')),
]

# ---------- recupero perdite ----------
rec_rows = []
for loss in (10, 20, 100 / 3, 50, 75):
    need = (1 / (1 - loss / 100) - 1) * 100
    years = __import__('math').log(1 / (1 - loss / 100)) / __import__('math').log(1.07)
    need = round(need, 1)
    rec_rows.append([f'−{pct(loss, 0)}', '+' + pct(need, 1 if need % 1 else 0), e(years, 1)])
VISUALS['recupero-perdite-perche-non-e-simmetrico'] = [
    ('Perdita del 75%', table(
        'Quanto serve per tornare in pari',
        ['Perdita', 'Guadagno necessario', 'Anni al 7% annuo'],
        rec_rows,
        note='Anni necessari per recuperare con un rendimento medio del 7% l\'anno, senza nuovi versamenti. '
             'La riga del 33% indica un terzo del capitale.')),
]

# ---------- ribilanciamento ----------
eq, bd = 7000 * 1.5, 3000
VISUALS['ribilanciamento-portafoglio-cosa-e-perche'] = [
    ('evitando aggiustamenti inutili per piccole oscillazioni', table(
        'Esempio: portafoglio 70/30 da 10.000 euro (importi in euro)',
        ['', 'Azioni', 'Obbligazioni', '%'],
        [['Partenza', e(7000), e(3000), '70/30'],
         ['Dopo un anno, azioni +50%', e(eq), e(bd), f'{e(eq / (eq + bd) * 100)}/{e(bd / (eq + bd) * 100)}'],
         ['Ribilanciato vendendo azioni', e((eq + bd) * 0.7), e((eq + bd) * 0.3), '70/30'],
         ['Ribilanciato con nuovi versamenti', e(eq), e(eq * 3 / 7), '70/30']],
        note=f'Vendendo sposti {e(eq - (eq + bd) * 0.7)} euro dalle azioni alle obbligazioni. Con i nuovi versamenti '
             f'servono {e(eq * 3 / 7 - bd)} euro in obbligazioni, senza vendere nulla e senza tasse.')),
]

# ---------- BTP ----------
def bond_price(coupon, years, yld):
    """Prezzo per 100 di nominale, cedole semestrali."""
    c, y, n = coupon / 2, yld / 200, years * 2
    return sum(c / (1 + y) ** k for k in range(1, n + 1)) + 100 / (1 + y) ** n

def ytm(coupon, years, price):
    lo, hi = -5.0, 30.0
    for _ in range(200):
        mid = (lo + hi) / 2
        if bond_price(coupon, years, mid) > price:
            lo = mid
        else:
            hi = mid
    return lo

btp_rows = []
for years in (2, 10, 30):
    p = bond_price(3, years, 4)
    btp_rows.append([f'BTP a {years} anni', e(p, 2), signed_pct(p - 100)])
VISUALS['btp-titoli-di-stato-come-funzionano'] = [
    ('il rischio diventa concreto se devi vendere prima', table(
        'Se i tassi salgono dal 3% al 4%: prezzo di un BTP con cedola 3%',
        ['', 'Nuovo prezzo', 'Variazione'],
        btp_rows,
        note='Prezzo per 100 euro di valore nominale, comprato a 100. Cedole semestrali, valori lordi. '
             'Tenendolo fino a scadenza ricevi comunque 100.')),
    ('perché a scadenza ti verranno restituiti 100', table(
        'BTP con cedola 2% e 5 anni alla scadenza: il prezzo cambia il rendimento',
        ['Prezzo di acquisto', 'Cedola', 'Rendimento a scadenza'],
        [[e(p, 0), '2%', pct(ytm(2, 5, p), 2)] for p in (90, 100, 105)],
        note='Rendimento lordo annuo, cedole semestrali. Sopra 100 rendi meno della cedola, sotto 100 di più.')),
]

# ---------- ETF monetari o conto deposito ----------
gross = 10000 * 0.03
etf_gross = 10000 * (0.03 - 0.001)
VISUALS['etf-monetari-o-conto-deposito'] = [
    ('non possono compensare eventuali minusvalenze accumulate', table(
        '10.000 euro per un anno, tasso del 3% (importi in euro)',
        ['', 'Conto deposito vincolato', 'ETF monetario'],
        [['Rendimento lordo', e(gross), e(etf_gross)],
         ['Tasse (26%)', '−' + e(gross * 0.26), '−' + e(etf_gross * 0.26)],
         ['Imposta di bollo (0,2%)', '−' + e(20), '−' + e(20)],
         ['Netto', e(gross * 0.74 - 20), e(etf_gross * 0.74 - 20)]],
        highlight_last=True,
        note='Esempio con lo stesso tasso per entrambi; l\'ETF ha un TER dello 0,10%. Commissioni del broker escluse. '
             'Nella realtà il rendimento dell\'ETF segue i tassi BCE giorno per giorno.')),
]

# ---------- PAC o PIC ----------
def pac_vs_pic(path):
    """path: 13 prezzi (oggi + 12 mesi). PIC compra tutto al primo, PAC 1.000 al mese ai primi 12."""
    pic = 12000 / path[0] * path[-1]
    pac = sum(1000 / p for p in path[:12]) * path[-1]
    return pic, pac

scen = [('Il mercato sale del 12%', [100 + k for k in range(13)]),
        ('Scende del 20% e poi recupera', [100 - k * 20 / 6 for k in range(7)] + [80 + k * 20 / 6 for k in range(1, 7)]),
        ('Scende del 20% e resta giù', [100 - k * 20 / 12 for k in range(13)])]
pp_rows = []
for label, path in scen:
    pic, pac = pac_vs_pic(path)
    pp_rows.append([label, e(pic), e(pac)])
VISUALS['pac-o-pic-investire-tutto-subito-o-a-rate'] = [
    ('investire tutto il giorno prima di un forte ribasso', table(
        '12.000 euro: tutto subito o 1.000 al mese, valore dopo un anno (importi in euro)',
        ['Scenario', 'PIC', 'PAC'],
        pp_rows,
        note='Scenari di esempio con prezzi che si muovono in modo regolare. Il PIC vince quando il mercato sale, '
             'il PAC quando scende; se scende e poi recupera, il PAC ha comprato più quote a prezzo basso.')),
]

# ---------- asset allocation ----------
VISUALS['asset-allocation-cos-e-come-sceglierla'] = [
    ('Oscillazioni ampie, potenziale di crescita più alto su orizzonti lunghi', bars(
        'Se le azioni perdono il 30%, quanto perde il portafoglio',
        [('Prudente: 30% azioni', 9, 'income'), ('Dinamico: 80% azioni', 24, 'loan'), ('Tutto in azioni', 30, 'expense')],
        fmt=lambda v: '−' + pct(v, 0),
        note='Per semplicità obbligazioni e liquidità restano ferme. Nella realtà possono salire o scendere anche loro.')),
]

# ---------- volatilità e max drawdown ----------
ra = [8, 8, 8, 8, 8]
rb = [25, -20, 30, -15]
target = 1.08 ** 5
rb.append(round((target / (1.25 * 0.8 * 1.3 * 0.85) - 1) * 100, 1))
va, vb, rows_v = 100.0, 100.0, []
peak, mdd = 100.0, 0.0
for y in range(5):
    va *= 1 + ra[y] / 100
    vb *= 1 + rb[y] / 100
    peak = max(peak, vb)
    mdd = max(mdd, (peak - vb) / peak * 100)
    rows_v.append([f'{y + 1}° anno', f'{e(va, 0)} ({signed_pct(ra[y], 0)})', f'{e(vb, 0)} ({signed_pct(rb[y], 0)})'])
VISUALS['volatilita-e-max-drawdown-misurare-il-rischio'] = [
    ('il suo max drawdown è del 40%', table(
        'Due investimenti da 100 euro, stesso risultato dopo 5 anni',
        ['', 'Investimento A', 'Investimento B'],
        rows_v,
        note=f'Entrambi arrivano a circa {e(va, 0)} euro, cioè l\'8% medio annuo. A non ha mai perso; '
             f'B ha un max drawdown del {e(mdd, 0)}% (da 125 a 100 nel secondo anno) e una volatilità molto più alta.')),
]

# ---------- dividendi ----------
VISUALS['dividendi-come-funzionano-e-come-sono-tassati'] = [
    ('che somma andamento del prezzo e dividendi', table(
        '100 azioni da 50 euro, dividendo di 1 euro per azione (importi in euro)',
        ['', 'Prima dello stacco', 'Dopo lo stacco'],
        [['Valore delle azioni', e(5000), e(4900)],
         ['Dividendo incassato, netto 26%', '—', e(74)],
         ['Totale', e(5000), e(4974)]],
        highlight_last=True,
        note='Il prezzo scende all\'incirca dell\'importo del dividendo. Il patrimonio non cresce: '
             'cambia forma, e la parte incassata paga subito le tasse.')),
]

if __name__ == '__main__':
    for slug, items in VISUALS.items():
        print(slug, len(items))
