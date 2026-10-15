#!/usr/bin/env python3
"""Elimina da webroot/app/assets i file delle build vecchie (lanciato da build-app.sh).

Tiene i file usati da webroot/app/index.html attuale e dalle ultime
KEEP_PREVIOUS versioni salvate in git, seguendo i riferimenti dai file
d'ingresso (index-*.js/css) ai chunk che importano (es. web-*.js). Tutto il
resto in assets/ viene cancellato. Con --dry-run elenca soltanto.
"""
import os, re, subprocess, sys

ROOT = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
ASSETS = os.path.join(ROOT, 'webroot/app/assets')
INDEX = 'webroot/app/index.html'
KEEP_PREVIOUS = 2
# Nomi con l'hash di Vite (8 caratteri) usati come riferimenti tra i file.
REF = re.compile(r'[\w.-]+-[\w-]{8}\.(?:js|css|woff2?|ttf|png|svg|jpe?g|webp|gif)')


def git(*args):
    return subprocess.run(['git', '-C', ROOT, *args], capture_output=True, text=True, check=True).stdout


def entries(html):
    return re.findall(r'assets/([^"\'?#]+)', html)


keep = set()


def walk(name):
    path = os.path.join(ASSETS, name)
    if name in keep or not os.path.isfile(path):
        return
    keep.add(name)
    with open(path, errors='ignore') as f:
        for ref in set(REF.findall(f.read())):
            walk(ref)


with open(os.path.join(ROOT, INDEX)) as f:
    pages = [f.read()]
for commit in git('log', '--format=%H', '--', INDEX).split():
    html = git('show', f'{commit}:{INDEX}')
    if html not in pages:
        pages.append(html)
    if len(pages) > KEEP_PREVIOUS:
        break

for html in pages:
    for name in entries(html):
        walk(name)

if not keep:
    sys.exit('Nessun file in uso trovato: pulizia annullata per sicurezza.')

drop = sorted(set(os.listdir(ASSETS)) - keep)
dry = '--dry-run' in sys.argv
for name in drop:
    if not dry:
        os.remove(os.path.join(ASSETS, name))
print(f"{len(keep)} file tenuti (build attuale + {len(pages) - 1} precedenti), "
      f"{len(drop)} {'da eliminare' if dry else 'eliminati'}" + (': ' + ', '.join(drop) if drop else '.'))
