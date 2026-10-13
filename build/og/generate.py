#!/usr/bin/env python3
"""Genera le immagini di anteprima social (og:image) in webroot/og/.

Uso, dalla root del progetto:  python3 build/og/generate.py
Solo alcune pagine o articoli:   python3 build/og/generate.py /mutui news/<slug>
Tutti gli articoli News:         python3 build/og/generate.py 'news/*'
Gli articoli con tabelle o grafici (<figure> nel corpo) hanno l'etichetta
"Con tabelle ed esempi" accanto alla categoria.

Pagine: titolo = H1 della pagina pubblicata (letto da https://reflexi.it),
etichetta ed emoji da PAGES qui sotto. Articoli News: seo_title, categoria
ed emoji dal database. Va rilanciato quando si aggiunge una pagina o un
articolo (senza immagine, la pagina usa og-image.png generica: vedi
seo_og_image() in webroot/_seo.php). Come build-css.sh, usa Docker: questa
VPS non ha Node, il rendering gira nel container ufficiale di Playwright.
"""
import html, json, os, re, subprocess, sys, urllib.request

ROOT = os.path.dirname(os.path.dirname(os.path.dirname(os.path.abspath(__file__))))
BASE = 'https://reflexi.it'
PLAYWRIGHT_IMAGE = 'mcr.microsoft.com/playwright:v1.48.0-jammy'

# percorso -> (etichetta, emoji, riga in basso)
PAGES = {
    '/mutui': ('Mutui', '🏠', 'Funzionalità · Rientro anticipato e risparmio netto'),
    '/investimenti': ('Investimenti', '📈', 'Funzionalità'),
    '/budget': ('Budget', '🎯', 'Funzionalità'),
    '/analisi': ('Analisi predittiva', '🔮', 'Funzionalità'),
    '/previsioni': ('Previsioni', '🧭', 'Funzionalità'),
    '/simulazione-trading': ('Simulazione trading', '🧪', 'Funzionalità'),
    '/fire-pensione': ('FIRE e pensione', '🔥', 'Funzionalità'),
    '/obiettivi': ('Obiettivi', '🏁', 'Funzionalità'),
    '/abbonamenti': ('Abbonamenti', '🔁', 'Funzionalità'),
    '/patrimonio': ('Patrimonio e report', '📊', 'Funzionalità'),
    '/mercati': ('Mercati', '💹', 'Strumenti gratuiti'),
    '/calcolatori': ('Calcolatori', '🧮', 'Strumenti gratuiti'),
    '/calcolatore-interesse-composto': ('Calcolatore', '🌱', 'Strumenti gratuiti'),
    '/calcolatore-piano-investimento': ('Calcolatore', '📅', 'Strumenti gratuiti'),
    '/calcolatore-obiettivo-risparmio': ('Calcolatore', '🎯', 'Strumenti gratuiti'),
    '/calcolatore-rendimento': ('Calcolatore', '📐', 'Strumenti gratuiti'),
    '/calcolatore-recupero-perdite': ('Calcolatore', '🩹', 'Strumenti gratuiti'),
    '/calcolatore-inflazione': ('Calcolatore', '🎈', 'Strumenti gratuiti'),
    '/calcolatore-budget-503020': ('Calcolatore', '🥧', 'Strumenti gratuiti'),
    '/calcolatore-patrimonio-netto': ('Calcolatore', '⚖️', 'Strumenti gratuiti'),
    '/calcolatore-pensione': ('Calcolatore', '👵', 'Strumenti gratuiti'),
    '/piani': ('Piani e prezzi', '💳', '6 mesi gratis'),
    '/about': ('Chi siamo', '👋', 'Finanza personale in un posto solo'),
    '/faq': ('Domande frequenti', '💬', 'Finanza personale in un posto solo'),
    '/news': ('News e guide', '📰', 'Finanza personale spiegata semplice'),
}
CATEGORIES = {'mutui': 'Mutui', 'investimenti': 'Investimenti', 'budget': 'Budget', 'pianificazione': 'Pianificazione'}


def h1(path):
    page = urllib.request.urlopen(urllib.request.Request(BASE + path, headers={'User-Agent': 'og-generator'}), timeout=30).read().decode()
    m = re.search(r'<h1[^>]*>(.*?)</h1>', page, re.S)
    return re.sub(r'\s+', ' ', html.unescape(re.sub(r'<[^>]+>', '', m.group(1)))).strip()


def env(name):
    for line in open(os.path.join(ROOT, '.env')):
        if line.startswith(name + '='):
            return line.split('=', 1)[1].strip()


items = [{'out': path.lstrip('/') + '.jpg', 'kicker': k, 'emoji': e, 'foot': f, 'title': h1(path)} for path, (k, e, f) in PAGES.items()]

rows = subprocess.run(
    ['docker', 'exec', '-e', 'MYSQL_PWD=' + env('DB_PASSWORD'), 'finanze_db', 'mariadb', '-u' + env('DB_USER'),
     '--default-character-set=utf8mb4', '-B', '-N', env('DB_NAME'), '-e',
     "SELECT slug, COALESCE(seo_title, title), category, cover_emoji, body LIKE '%<figure%' FROM news_articles "
     "WHERE status = 'pubblicato' AND published_at <= NOW()"],
    check=True, capture_output=True, text=True).stdout
for line in rows.strip().splitlines():
    slug, title, category, emoji, has_figures = line.split('\t')
    items.append({'out': f'news/{slug}.jpg', 'kicker': CATEGORIES.get(category, category), 'emoji': emoji,
                  'foot': 'Guide di finanza personale', 'title': title,
                  'badge': '📊 Con tabelle ed esempi' if has_figures == '1' else ''})

# Filtro facoltativo da riga di comando: percorsi di pagina (/mutui), news/<slug> o news/* per tutti.
only = {a.strip('/') for a in sys.argv[1:]}
if only:
    items = [it for it in items if it['out'][:-4] in only
             or any(o.endswith('/*') and it['out'].startswith(o[:-1]) for o in only)]
    if not items:
        sys.exit('Nessuna pagina o articolo corrisponde a: ' + ' '.join(sys.argv[1:]))

with open(os.path.join(ROOT, 'build/og/items.json'), 'w') as f:
    json.dump(items, f, ensure_ascii=False, indent=1)

subprocess.run(['docker', 'run', '--rm', '-v', os.path.join(ROOT, 'build/og') + ':/og', '-v', os.path.join(ROOT, 'webroot') + ':/webroot',
                '-w', '/og', PLAYWRIGHT_IMAGE, 'sh', '-c',
                'cd /tmp && npm i -s playwright@1.48.0 >/dev/null 2>&1 && NODE_PATH=/tmp/node_modules node /og/render.js'], check=True)
