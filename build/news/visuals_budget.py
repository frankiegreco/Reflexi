"""Tabelle e grafici per gli articoli News della categoria budget.

Stesso formato di visuals_mutui.py: slug -> lista di (testo_ancora, html).
Gli esempi di budget sono illustrativi; i totali sono calcolati qui e
verificati con assert (ogni budget deve tornare con il reddito indicato).
"""
import os, sys
sys.path.insert(0, os.path.dirname(os.path.abspath(__file__)))
from news_html import table, bars, euro  # noqa: E402


def e(v, d=0):
    return euro(v, d)


def pct(v, d=None):
    """Percentuale: senza decimali se intera, altrimenti con uno (62,5%)."""
    if d is None:
        d = 0 if abs(v - round(v)) < 1e-9 else 1
    return euro(v, d) + '%'


VISUALS = {}

# ---------- 50/30/20 vs budget per categoria ----------
INC = 2000
real = {'Necessità': 800 + 300 + 150, 'Desideri': 450, 'Risparmio': 300}
assert sum(real.values()) == INC
VISUALS['regola-50-30-20-vs-budget-per-categoria'] = [
    ('se il problema è la spesa alimentare o i ristoranti, la regola non lo rivela', table(
        'Reddito netto di 2.000 euro: la regola e un caso reale (importi in euro)',
        ['', 'Regola 50/30/20', 'Caso reale'],
        [['Necessità', f'{e(INC * .5)} (50%)', f'{e(real["Necessità"])} ({pct(real["Necessità"] / INC * 100)})'],
         ['Desideri', f'{e(INC * .3)} (30%)', f'{e(real["Desideri"])} ({pct(real["Desideri"] / INC * 100)})'],
         ['Risparmio', f'{e(INC * .2)} (20%)', f'{e(real["Risparmio"])} ({pct(real["Risparmio"] / INC * 100)})']],
        note='Nel caso reale le necessità sono affitto 800 euro, spesa 300, bollette e trasporti 150: '
             'il solo affitto vale il 40% del reddito e il 50% non basta.')),
]

# ---------- obiettivi di risparmio ----------
goals = [('Viaggio', 6000, 12), ('Fondo di emergenza', 9000, 18), ('Auto usata', 15000, 36), ('Anticipo per la casa', 30000, 60)]
VISUALS['obiettivi-risparmio-come-fissarli-e-raggiungerli'] = [
    ('già in ritardo', table(
        'Dall\'obiettivo al versamento mensile (importi in euro)',
        ['Obiettivo', 'Importo', 'Mesi', 'Al mese'],
        [[g, e(a), str(m), e(a / m)] for g, a, m in goals],
        note='Versamento = importo che manca diviso per i mesi rimasti. Esempi senza interessi, '
             'che per scadenze brevi cambiano poco il risultato.')),
]

# ---------- fondo di emergenza ----------
VISUALS['fondo-di-emergenza-quanto-tenere-da-parte'] = [
    ('dovrebbe orientarsi verso 6 mesi o anche di più', table(
        'Quanto tenere da parte (importi in euro)',
        ['Spese essenziali al mese', '3 mesi', '6 mesi'],
        [[e(s), e(s * 3), e(s * 6)] for s in (1200, 1500, 1800, 2500)],
        note='Spese essenziali: affitto o mutuo, bollette, spesa, trasporti. Non il reddito e non le spese che potresti tagliare.')),
]

# ---------- metodo delle buste ----------
env = [('Spesa', 350, 330), ('Trasporti', 120, 135), ('Svago', 150, 150), ('Regali', 50, 0)]
env_rows = [[n, e(b), e(s), (e(b - s) if b >= s else '−' + e(s - b))] for n, b, s in env]
VISUALS['metodo-delle-buste-budget-digitale'] = [
    ('che non cadono uniformemente ogni mese', table(
        'Un mese con le buste (importi in euro)',
        ['Busta', 'Budget', 'Speso', 'Avanzo'],
        env_rows,
        note='Con il rollover i 20 euro avanzati sulla spesa e i 50 dei regali passano al mese dopo: '
             'a dicembre la busta dei regali avrà accumulato abbastanza. I 15 euro sforati sui trasporti '
             'vanno presi da un\'altra busta.')),
]

# ---------- tracciare a mano vs collegamento ----------
VISUALS['tracciare-spese-manualmente-vs-collegamento-bancario'] = [
    ('senza collegare nulla a nessuno', table(
        'Inserimento manuale o collegamento automatico',
        ['', 'A mano', 'Collegamento bancario'],
        [['Tempo richiesto', 'Pochi secondi a spesa', 'Quasi nullo'],
         ['Consapevolezza', 'Alta: ogni spesa è una scelta', 'Bassa: le spese scorrono in un elenco'],
         ['Accesso ai dati', 'Nessuno oltre a te', 'Un servizio terzo vede ogni movimento'],
         ['Completezza', 'Dipende dalla costanza', 'Tutto il conto, in automatico']],
        numeric=set())),
]

# ---------- abbonamenti dimenticati ----------
subs = [('Piattaforma di streaming', 9.99), ('App per l\'allenamento', 4.99), ('Spazio cloud', 2.99),
        ('Rivista digitale', 6.99), ('Prova gratuita diventata a pagamento', 7.99)]
tot_m = sum(m for _, m in subs)
VISUALS['abbonamenti-dimenticati-come-scovare-spese-ricorrenti'] = [
    ('a renderlo invisibile mese dopo mese', table(
        'Cinque piccoli abbonamenti dimenticati (importi in euro)',
        ['Abbonamento', 'Al mese', 'In un anno'],
        [[n, e(m, 2), e(m * 12, 2)] for n, m in subs] + [['Totale', e(tot_m, 2), e(tot_m * 12, 2)]],
        highlight_last=True,
        note='Importi di esempio. Nessuno pesa da solo, insieme superano i 380 euro l\'anno.')),
]
assert tot_m * 12 > 380

# ---------- budget familiare ----------
fam = [('Casa (affitto o mutuo)', 900), ('Spesa alimentare', 600), ('Trasporti', 250), ('Bollette', 200),
       ('Figli', 250), ('Salute', 80), ('Tempo libero', 220), ('Spese annuali accantonate', 100), ('Risparmio', 400)]
FAM_INC = 3000
assert sum(v for _, v in fam) == FAM_INC
VISUALS['budget-familiare-mensile-come-farlo'] = [
    ('Il risparmio va trattato come una voce di spesa', table(
        'Esempio: famiglia con 3.000 euro netti al mese (importi in euro)',
        ['Categoria', 'Budget', 'Quota'],
        [[n, e(v), pct(v / FAM_INC * 100)] for n, v in fam]
        + [['Totale', e(FAM_INC), '100%']],
        highlight_last=True,
        note='Il risparmio è una voce come le altre, messa da parte a inizio mese. '
             'Le spese annuali (bollo, assicurazioni) sono divise per dodici.')),
]

# ---------- spese fisse e variabili ----------
annual = [('Bollo auto', 250), ('Assicurazione auto', 600), ('TARI', 280), ('Manutenzione caldaia', 100)]
tot_a = sum(v for _, v in annual)
VISUALS['spese-fisse-e-variabili-differenza'] = [
    ('come se fosse una spesa fissa', table(
        'Le spese annuali trasformate in quota mensile (importi in euro)',
        ['Spesa', 'All\'anno', 'Al mese'],
        [[n, e(v), e(v / 12, 2)] for n, v in annual] + [['Totale', e(tot_a), e(tot_a / 12, 2)]],
        highlight_last=True,
        note='Importi di esempio. Accantonando poco più di 100 euro al mese, nessuna di queste spese mette in crisi il mese in cui arriva.')),
]

# ---------- acquisti d'impulso ----------
WAGE = 15
VISUALS['acquisti-impulsivi-come-evitarli'] = [
    ('vista così la decisione cambia', table(
        'Il prezzo in ore di lavoro, con 15 euro netti l\'ora',
        ['Acquisto', 'Prezzo', 'Ore di lavoro'],
        [[n, e(p) + ' €', e(p / WAGE, 1 if (p / WAGE) % 1 else 0)] for n, p in
         (('Maglietta', 25), ('Cuffie', 60), ('Scarpe', 120), ('Smartwatch', 300), ('Smartphone', 900))],
        note='15 euro l\'ora corrispondono a circa 2.400 euro netti al mese per 160 ore. '
             'Le scarpe da 120 euro valgono una giornata di lavoro.')),
    ('aumenta la consapevolezza', bars(
        'Piccole spese abituali: quanto costano in un anno',
        [('Un acquisto online da 25 euro a settimana', 25 * 52, 'expense'),
         ('Caffè e snack, 4 euro al giorno lavorativo', 4 * 220, 'loan'),
         ('Cibo a domicilio, 30 euro due volte al mese', 30 * 24, 'loan')],
        fmt=lambda v: e(v) + ' €',
        note='Esempi indicativi: 52 settimane, 220 giorni lavorativi, 24 consegne l\'anno.')),
]

# ---------- sfida 52 settimane ----------
cls = [sum(range(1, w + 1)) for w in (13, 26, 39, 52)]
rev = [sum(range(52, 52 - w, -1)) for w in (13, 26, 39, 52)]
assert cls[-1] == rev[-1] == 1378
VISUALS['sfida-52-settimane-risparmio'] = [
    ('a dicembre, il mese più costoso, si mettono da parte pochi euro', table(
        'Quanto hai messo da parte a fine trimestre (importi in euro)',
        ['Trimestre', 'Classica', 'Al contrario'],
        [[f'{q}° trimestre', e(c), e(r)] for q, c, r in zip(range(1, 5), cls, rev)],
        note='Classica: da 1 a 52 euro. Al contrario: da 52 a 1. Totale uguale, ma al contrario a metà anno '
             'hai già messo da parte quasi i tre quarti della cifra.')),
]

# ---------- paga prima te stesso ----------
SAL = 1800
mi = 0.04 / 12
pyf_rows = []
for p in (5, 10, 15, 20):
    m = SAL * p / 100
    pyf_rows.append([pct(p), e(m), e(m * 12), e(m * ((1 + mi) ** 120 - 1) / mi)])
VISUALS['paga-prima-te-stesso-risparmio-automatico'] = [
    ('e aumentala gradualmente', table(
        'Stipendio netto di 1.800 euro (importi in euro)',
        ['Quota', 'Al mese', 'In un anno', 'In 10 anni'],
        pyf_rows,
        note='In 10 anni: versamenti investiti con un rendimento ipotetico del 4% annuo, tasse escluse.')),
]

# ---------- budget a base zero ----------
steps = [('Reddito netto del mese', 2200, None),
         ('Spese fisse: affitto, bollette, assicurazioni, abbonamenti', 1000, 'out'),
         ('Risparmio e obiettivi', 350, 'out'),
         ('Spesa e trasporti', 530, 'out'),
         ('Tempo libero', 200, 'out'),
         ('Quota per le spese annuali', 120, 'out')]
left, z_rows = 0, []
for label, amount, kind in steps:
    left = amount if kind is None else left - amount
    z_rows.append([label, e(amount) if kind is None else '−' + e(amount), e(left)])
assert left == 0
VISUALS['budget-a-base-zero-come-funziona'] = [
    ('Le scelte diventano esplicite invece che subite', table(
        'Un mese a base zero (importi in euro)',
        ['Voce', 'Importo', 'Da assegnare'],
        z_rows,
        note='Ogni euro ha una destinazione: alla fine restano zero euro da assegnare, non zero euro sul conto.')),
]

if __name__ == '__main__':
    for slug, items in VISUALS.items():
        print(slug, len(items))
