"""Genera la migrazione 064: link alla guida /gestione-finanza-personale negli articoli correlati.

Uso, dalla root del progetto:
  python3 build/news/guida_finanza_personale_links.py > db/migrations/064_news_link_guida_finanza_personale.sql
Aggiunge un paragrafo in fondo al corpo di ogni articolo, con un testo di
ancora diverso per articolo (link interno verso la pagina pilastro della
keyword "gestione finanza personale"). Gli articoli che contengono gia' il
link non vengono toccati, quindi rilanciare la migrazione non duplica nulla.
"""
from news_html import a, p, sql_str

GUIDE = '/gestione-finanza-personale'

LINKS = {
    'budget-familiare-mensile-come-farlo':
        'Il budget è solo uno dei passi: nella ' + a(GUIDE, 'guida alla gestione della finanza personale') +
        ' trovi anche fondo di emergenza, debiti, investimenti e obiettivi, con esempi e tabelle.',
    'gestire-i-soldi-a-20-30-40-50-anni':
        'Qualunque sia la tua età, il metodo di base è lo stesso: lo trovi nella ' +
        a(GUIDE, 'guida alla gestione della finanza personale in 7 passi') + '.',
    'tasso-di-risparmio-cosa-e-come-calcolarlo':
        'Il tasso di risparmio è uno dei cinque numeri da seguire per ' +
        a(GUIDE, 'gestire la finanza personale') + ', insieme a spese per categoria, rapporto rata/reddito, rendimento reale e patrimonio netto.',
    'spese-fisse-e-variabili-differenza':
        'Separare spese fisse e variabili è il primo passo della ' +
        a(GUIDE, 'gestione della finanza personale') + ': nella guida trovi anche quelli successivi, dal budget agli investimenti.',
    'regola-50-30-20-vs-budget-per-categoria':
        'Qualunque metodo di budget tu scelga, è solo una parte del quadro: la ' +
        a(GUIDE, 'guida alla gestione delle finanze personali') + ' mette in fila tutti i passi, dal fondo di emergenza agli investimenti.',
    'fondo-di-emergenza-quanto-tenere-da-parte':
        'Il fondo di emergenza viene subito dopo il budget nella ' +
        a(GUIDE, 'gestione della finanza personale') + ': nella guida in 7 passi vedi cosa fare prima e cosa dopo.',
    'tracciare-spese-manualmente-vs-collegamento-bancario':
        'Se stai scegliendo tra foglio Excel e un\'app di finanza personale, nella ' +
        a(GUIDE, 'guida alla gestione della finanza personale') + ' trovi un confronto e una lista di cosa cercare in un\'app.',
    'obiettivi-risparmio-come-fissarli-e-raggiungerli':
        'Gli obiettivi funzionano meglio quando il resto è in ordine: budget, fondo di emergenza e debiti. Li trovi tutti nella ' +
        a(GUIDE, 'guida alla gestione della finanza personale') + '.',
    'patrimonio-netto-cos-e-perche-seguirlo-nel-tempo':
        'Il patrimonio netto è il numero che riassume tutta la ' +
        a(GUIDE, 'gestione della finanza personale') + ': nella guida vedi come migliorarlo un passo alla volta.',
    'paga-prima-te-stesso-risparmio-automatico':
        'Il risparmio automatico è il cuore di una buona ' +
        a(GUIDE, 'gestione delle finanze personali') + ': nella guida in 7 passi vedi dove destinarlo, dal fondo di emergenza agli investimenti.',
    'budget-a-base-zero-come-funziona':
        'Il budget a base zero è un ottimo punto di partenza. Per il quadro completo leggi la ' +
        a(GUIDE, 'guida alla gestione della finanza personale') + '.',
    'finanze-di-coppia-conti-separati-o-cointestati':
        'Conti separati o cointestati, i passi per ' + a(GUIDE, 'gestire bene la finanza personale') +
        ' restano gli stessi: budget, fondo di emergenza, debiti, investimenti e obiettivi.',
}

print('-- Link interno alla guida /gestione-finanza-personale negli articoli correlati.')
print('-- Generata da build/news/guida_finanza_personale_links.py. Idempotente.')
for slug, text in LINKS.items():
    print(f"UPDATE news_articles SET body = CONCAT(body, {sql_str(p(text))})\n"
          f"WHERE slug = {sql_str(slug)} AND body NOT LIKE '%{GUIDE}%';")
