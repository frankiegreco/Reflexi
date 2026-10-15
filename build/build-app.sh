#!/bin/sh
# Compila l'app React (frontend/) e la pubblica in webroot/app/, poi elimina
# i bundle delle build vecchie: Vite crea file con un hash nel nome a ogni
# build e la copia li accumulava accanto ai nuovi.
#
# Restano i file usati dalla build appena fatta e dalle due precedenti
# (le ultime due versioni di webroot/app/index.html salvate in git), per chi
# ha ancora l'app aperta con una pagina caricata prima dell'aggiornamento.
#
# Questa VPS non ha Node installato (vedi CLAUDE.md): usa un container Node
# temporaneo via Docker, come build-css.sh.
set -e
cd "$(dirname "$0")/.."

docker run --rm -v "$PWD/frontend:/app" -w /app node:20 sh -c "npm run build"
cp -r frontend/dist/* webroot/app/

python3 build/prune-app-assets.py
