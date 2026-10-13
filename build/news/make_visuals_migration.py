"""Genera una migrazione SQL che inserisce tabelle e grafici negli articoli News.

Uso:  python3 build/news/make_visuals_migration.py visuals_mutui 054_news_mutui_tabelle
Legge il corpo attuale degli articoli dal DB, trova per ogni figura il
paragrafo (o elenco) che contiene il testo di ancora e scrive un UPDATE con
REPLACE: inserisce la figura subito dopo quel blocco. L'UPDATE non tocca gli
articoli che hanno gia' una figura, quindi rilanciarlo non duplica nulla.
"""
import importlib, os, subprocess, sys

ROOT = os.path.dirname(os.path.dirname(os.path.dirname(os.path.abspath(__file__))))
sys.path.insert(0, os.path.dirname(os.path.abspath(__file__)))
from news_html import sql_str  # noqa: E402


def env(name):
    for line in open(os.path.join(ROOT, '.env')):
        if line.startswith(name + '='):
            return line.split('=', 1)[1].strip().strip('"\'')


def body_of(slug):
    out = subprocess.run(
        ['docker', 'exec', '-e', 'MYSQL_PWD=' + env('DB_PASSWORD'), 'finanze_db', 'mariadb', '-u' + env('DB_USER'),
         '--default-character-set=utf8mb4', '-B', '-N', '--raw', env('DB_NAME'), '-e',
         f"SELECT body FROM news_articles WHERE slug = {sql_str(slug)}"],
        check=True, capture_output=True, text=True).stdout
    assert out, f'articolo non trovato: {slug}'
    return out.rstrip('\n')


def block_end(body, anchor):
    i = body.find(anchor)
    assert i >= 0 and body.count(anchor) == 1, f'ancora non unica o assente: {anchor!r}'
    ends = [j + len(tag) for tag in ('</p>', '</ul>') if (j := body.find(tag, i)) >= 0]
    return min(ends)


def main(module, name):
    visuals = importlib.import_module(module).VISUALS
    sql = [f'-- Migration {name.split("_")[0]}: tabelle e grafici negli articoli News ({module}).',
           '-- Generata da build/news/make_visuals_migration.py: ogni figura va dopo il',
           '-- paragrafo indicato. Non tocca gli articoli che hanno gia\' una figura.', '']
    for slug, items in visuals.items():
        body = body_of(slug)
        expr = 'body'
        for anchor, fig in items:
            end = block_end(body, anchor)
            start = max(body.rfind('<p', 0, end - 1), body.rfind('<ul', 0, end - 1))
            block = body[start:end]
            assert body.count(block) == 1
            expr = f'REPLACE({expr},\n    {sql_str(block)},\n    {sql_str(block + fig)})'
        sql.append(f'UPDATE news_articles SET body = {expr},\n    updated_at = NOW()\n'
                   f"WHERE slug = {sql_str(slug)} AND body NOT LIKE '%<figure%';\n")
    path = os.path.join(ROOT, 'db/migrations', name + '.sql')
    open(path, 'w').write('\n'.join(sql))
    print(path, len(visuals), 'articoli')


if __name__ == '__main__':
    main(sys.argv[1], sys.argv[2])
