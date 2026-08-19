# Frontend React + Tailwind — build e deploy

Il backend PHP in `../webroot/api/` **non cambia di una virgola** — questo
progetto parla con le stesse identiche API REST (percorsi assoluti `/api/...`).

**Importante**: da quando esiste la landing page pubblica, l'app React vive
sotto `/app/`, non più sulla radice del sito. `../webroot/index.html` è ora la
landing page statica (non generata da questa build), `../webroot/app/` è dove
va copiato l'output di questa build.

## 1. Installazione (una tantum, sul tuo PC con internet)

```bash
cd frontend
npm install
```

## 2. Sviluppo con hot-reload (opzionale, per testare le modifiche)

```bash
npm run dev
```

Apre un server su `http://localhost:5173`. Le chiamate a `/api/*.php` vengono
girate (proxy) verso il backend PHP configurato in `vite.config.js`
(di default `http://localhost:8080`, cioè il container Docker).

## 3. Build di produzione

```bash
npm run build
```

Genera la cartella `frontend/dist/` con `index.html` + una cartella `assets/`
(file statici, nessun bisogno di Node lato server).

## 4. Deploy

Copia **tutto il contenuto** di `frontend/dist/` dentro `../webroot/app/`
(non più `../webroot/` direttamente — quella cartella ora ospita la landing):

```bash
mkdir -p ../webroot/app
cp -r dist/* ../webroot/app/
```

La cartella `webroot/api/` non va toccata, così come `webroot/index.html`
(la landing) e `webroot/favicon.svg`.

## 5. Nessuna modifica al Docker/XAMPP esistente

Apache serve `app/index.html` e gli `app/assets/*.js/css` come file statici,
esattamente come faceva prima sulla radice — non serve Node.js sul server, non
serve rebuildare il container Docker, non serve toccare `docker-compose.yml`.
Node.js serve solo sul tuo PC, per la build.

## 6. Rebuild dopo modifiche future

Ogni volta che modifichi qualcosa nel frontend (file dentro `frontend/src/`),
rifai `npm run build` e ricopia `dist/*` in `webroot/app/`.
