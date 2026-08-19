#!/bin/sh
# Ricompila webroot/css/site.css dal Tailwind statico usato dal sito pubblico
# (webroot/*.php). Va rieseguito ogni volta che si aggiunge una classe nuova
# a una pagina pubblica, o negli articoli News inseriti via Adminer se usano
# classi diverse da quelle già presenti in build/tailwind.config.js (vedi il
# commento sul campo `safelist` in quel file per il perché).
#
# Questa VPS non ha Node installato (vedi CLAUDE.md): usa un container Node
# temporaneo via Docker, che è già presente per il resto dello stack.
set -e
cd "$(dirname "$0")/.."

docker run --rm -v "$PWD:/repo" -w /repo node:20 sh -c "
  npm install -g tailwindcss@3.4.10 >/dev/null 2>&1
  tailwindcss -c ./build/tailwind.config.js -i ./build/tailwind-input.css -o ./webroot/css/site.css --minify
"
echo "webroot/css/site.css rigenerato."
