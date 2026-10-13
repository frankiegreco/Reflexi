"""Helper HTML per il corpo degli articoli News (campo `body` di news_articles).

Il corpo e' HTML salvato nel DB e stampato da webroot/news-articolo.php: niente
JavaScript, solo classi Tailwind. Tutte le classi usate qui devono stare nella
`safelist` di build/tailwind.config.js (il DB non e' scansionabile da Tailwind),
e dopo una classe nuova va rilanciato build/build-css.sh.

Componenti:
  p, h2, h3, ul, a     testo, come negli articoli esistenti
  table(...)           tabella di esempio (scorre in orizzontale su telefono)
  bars(...)            grafico a barre orizzontali in puro HTML/CSS
I numeri di tabelle e grafici vanno calcolati con uno script, non a mano.
"""
import html

P = '<p class="mb-4 text-[15px] leading-relaxed text-gray-700">'
H2 = '<h2 class="mt-10 mb-3 text-xl font-bold text-gray-900">'
H3 = '<h3 class="mt-6 mb-2 text-lg font-bold text-gray-900">'
UL = '<ul class="mb-4 list-disc space-y-2 pl-5 text-[15px] leading-relaxed text-gray-700">'

# Colori delle barre: stessi token del sito (tailwind.config.js).
BAR_COLORS = {'accent': 'bg-accent', 'loan': 'bg-loan', 'income': 'bg-income', 'expense': 'bg-expense', 'gray': 'bg-gray-400'}


def a(href, text):
    return f'<a href="{href}" class="text-accent hover:underline font-medium">{text}</a>'


def p(t):
    return P + t + '</p>'


def h2(t):
    return H2 + t + '</h2>'


def h3(t):
    return H3 + t + '</h3>'


def ul(*items):
    return UL + ''.join(f'<li>{i}</li>' for i in items) + '</ul>'


def euro(v, decimals=0):
    """Formato italiano: 1.234 o 1.234,56 (senza simbolo)."""
    s = f'{v:,.{decimals}f}'
    return s.replace(',', '#').replace('.', ',').replace('#', '.')


def table(title, header, rows, note=None, highlight_last=False, numeric=None):
    """header: lista di intestazioni. rows: liste di celle (stringhe HTML).
    La prima colonna e' l'etichetta, le altre sono numeri allineati a destra e
    mai spezzati su due righe: con molte colonne la tabella scorre in
    orizzontale sul telefono. Con 4 colonne togli l'unita' dalle celle e
    mettila nel titolo (es. "(importi in euro)"), altrimenti non entra in 390px.
    highlight_last: ultima riga in grassetto (es. un totale).
    numeric: indici delle colonne numeriche (default: tutte tranne la prima);
    le colonne di testo vanno a capo e restano allineate a sinistra."""
    if numeric is None:
        numeric = set(range(1, len(header)))
    # Tabelle solo testo: celle allineate in alto, le righe vanno a capo in modo diverso.
    valign = '' if numeric else ' align-top'
    th = ''.join(
        f'<th class="px-3 py-2 font-semibold sm:px-4 sm:py-2.5{" text-right" if i in numeric else ""}">{h}</th>' for i, h in enumerate(header))
    body = ''
    for r, row in enumerate(rows):
        bold = ' font-semibold text-gray-900' if highlight_last and r == len(rows) - 1 else ''
        cells = ''.join(
            f'<td class="px-3 py-2 sm:px-4 sm:py-2.5{valign}{" whitespace-nowrap text-right font-mono" if i in numeric else ""}{bold}">{c}</td>' for i, c in enumerate(row))
        body += f'<tr class="border-t border-border">{cells}</tr>'
    out = (f'<figure class="my-6"><figcaption class="mb-2 text-sm font-semibold text-gray-900">{title}</figcaption>'
           f'<div class="overflow-x-auto rounded-xl border border-border bg-white">'
           f'<table class="w-full text-left text-[13px] text-gray-700 sm:text-sm">'
           f'<thead class="bg-gray-50 text-[11px] uppercase tracking-wide text-muted"><tr>{th}</tr></thead>'
           f'<tbody>{body}</tbody></table></div>')
    if note:
        out += f'<p class="mt-2 text-xs text-muted">{note}</p>'
    return out + '</figure>'


def bars(title, items, note=None, fmt=lambda v: euro(v)):
    """items: lista di (etichetta, valore, colore) con colore in BAR_COLORS.
    Le barre sono proporzionali al valore massimo."""
    top = max(v for _, v, _ in items) or 1
    rows = ''
    for label, value, color in items:
        width = max(1.0, value / top * 100)
        rows += (f'<div><div class="mb-1 flex justify-between gap-3 text-sm"><span class="text-gray-700">{label}</span>'
                 f'<span class="whitespace-nowrap font-mono text-gray-900">{fmt(value)}</span></div>'
                 f'<div class="h-2.5 rounded-full bg-gray-100"><div class="h-2.5 rounded-full {BAR_COLORS[color]}" style="width:{width:.1f}%"></div></div></div>')
    out = (f'<figure class="my-6 rounded-xl border border-border bg-white p-5">'
           f'<figcaption class="mb-4 text-sm font-semibold text-gray-900">{title}</figcaption>'
           f'<div class="space-y-3">{rows}</div>')
    if note:
        out += f'<p class="mt-4 text-xs text-muted">{note}</p>'
    return out + '</figure>'


def sql_str(s):
    return "'" + s.replace("'", "''") + "'"


def esc(s):
    return html.escape(s, quote=False)
