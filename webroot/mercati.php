<!DOCTYPE html>
<html lang="it">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Mercati — Andamenti di azioni, ETF e crypto | Reflexi</title>
<meta name="description" content="Quotazioni di ETF, azioni e crypto: i titoli più seguiti, i movimenti più forti del giorno e il grafico storico di ogni ticker. Gratis, senza registrazione.">
<meta name="robots" content="index, follow">
<link rel="canonical" href="https://reflexi.it/mercati.php">
<!-- Open Graph (Facebook, WhatsApp, LinkedIn, Telegram...) -->
<meta property="og:type" content="website">
<meta property="og:url" content="https://reflexi.it/mercati.php">
<meta property="og:site_name" content="Reflexi">
<meta property="og:title" content="Mercati — Andamenti di azioni, ETF e crypto | Reflexi">
<meta property="og:description" content="Quotazioni live di ETF, azioni e crypto: watchlist dei titoli più seguiti, i movimenti più forti della giornata e il grafico storico di qualsiasi ticker tu cerchi.">
<meta property="og:image" content="https://reflexi.it/og-image.png?v=3">
<meta property="og:image:width" content="1200">
<meta property="og:image:height" content="630">
<meta property="og:locale" content="it_IT">

<!-- Twitter/X Card -->
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="Mercati — Andamenti di azioni, ETF e crypto | Reflexi">
<meta name="twitter:description" content="Quotazioni live di ETF, azioni e crypto: watchlist, top mover del giorno e grafico storico di qualsiasi ticker tu cerchi.">
<meta name="twitter:image" content="https://reflexi.it/og-image.png?v=3">
<link rel="icon" type="image/svg+xml" href="/favicon.svg">
<link rel="preload" href="/fonts/inter-v20-latin.woff2" as="font" type="font/woff2" crossorigin>
<link rel="stylesheet" href="/css/site.css?v=<?= filemtime(__DIR__ . '/css/site.css') ?>">
<?php
require_once __DIR__ . '/_seo.php';
seo_breadcrumb([['Mercati', '/mercati.php']]);
?>
</head>
<body class="bg-bg text-gray-900 antialiased">

<?php
$active = 'mercati';
require_once __DIR__ . '/_news_db.php';
$relatedArticles = news_related(['cosa-e-un-etf-guida-per-principianti', 'pac-piano-accumulo-etf-come-funziona', 'diversificazione-portafoglio-cosa-significa-davvero']);
include '_nav.php';
?>

<!-- HERO -->
<section class="px-6 pb-8 pt-12 sm:pt-16">
  <div class="mx-auto max-w-3xl text-center">
    <span class="mb-4 inline-block rounded-full bg-accentSoft px-4 py-1.5 text-xs font-semibold uppercase tracking-wider text-accent">Mercati</span>
    <h1 class="mb-3 text-3xl font-extrabold leading-tight tracking-tight sm:text-4xl">Andamenti di azioni, ETF e crypto, in tempo reale</h1>
    <p class="text-lg text-muted">Quotazioni live, senza registrazione — cerca un titolo o guarda i più seguiti.</p>
  </div>
</section>

<!-- TERMINALE MERCATI (stile CoinMarketCap/TradingView) -->
<section class="border-t border-border bg-white px-4 py-10 sm:px-6 sm:py-14">
  <div class="mx-auto max-w-6xl">

    <!-- Ricerca -->
    <div class="relative mb-8">
      <svg class="pointer-events-none absolute left-4 top-1/2 -translate-y-1/2 text-muted" width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
      <input id="mkt-search-input" type="text" autocomplete="off" placeholder="Cerca un ticker o un nome — es. Apple, VWCE, Bitcoin"
        class="w-full rounded-xl border border-border bg-white py-3.5 pl-11 pr-4 text-sm text-gray-900 placeholder:text-muted outline-none transition focus:border-accent">
      <div id="mkt-search-results" class="absolute left-0 right-0 top-full z-20 mt-2 hidden max-h-72 overflow-y-auto rounded-xl border border-border bg-white shadow-2xl"></div>
    </div>

    <!-- Grafico titolo selezionato -->
    <div id="mkt-chart-box" class="mb-12 hidden rounded-2xl border border-border bg-white p-5 sm:p-6">
      <div class="mb-5 flex flex-wrap items-start justify-between gap-4">
        <div>
          <div id="mkt-chart-title" class="text-sm font-semibold text-muted"></div>
          <div class="mt-1 flex items-baseline gap-2.5">
            <span id="mkt-chart-price" class="font-mono text-2xl font-bold text-gray-900"></span>
            <span id="mkt-chart-change" class="font-mono text-sm font-semibold"></span>
          </div>
        </div>
        <div class="flex gap-1 rounded-lg bg-gray-100 p-1">
          <button data-range="24h" class="mkt-range rounded-md px-3 py-1.5 text-xs font-semibold text-gray-600 transition">24h</button>
          <button data-range="1m" class="mkt-range rounded-md px-3 py-1.5 text-xs font-semibold text-gray-600 transition">1m</button>
          <button data-range="1a" class="mkt-range rounded-md px-3 py-1.5 text-xs font-semibold text-gray-600 transition">1a</button>
          <button data-range="all" class="mkt-range rounded-md px-3 py-1.5 text-xs font-semibold text-gray-600 transition">All</button>
        </div>
      </div>
      <div id="mkt-chart-svg" class="relative h-64 w-full sm:h-72"></div>
      <p id="mkt-chart-empty" class="hidden py-10 text-center text-sm text-muted">Non c'è ancora storico per questo ticker — si costruisce da solo, torna a controllare tra qualche giorno.</p>
    </div>

    <!-- Watchlist -->
    <div class="mb-3 flex items-center justify-between">
      <h2 class="text-xs font-bold uppercase tracking-wider text-muted">Titoli più seguiti</h2>
      <span class="text-xs text-muted">Aggiornato ogni ~15 min</span>
    </div>
    <div class="mb-12 overflow-x-auto rounded-2xl border border-border">
      <table class="w-full border-collapse sm:min-w-[560px] text-sm">
        <thead>
          <tr class="border-b border-border text-left text-[11px] uppercase tracking-wider text-muted">
            <th class="px-3 py-3 sm:px-4 font-semibold">#</th>
            <th class="px-3 py-3 sm:px-4 font-semibold">Nome</th>
            <th class="px-3 py-3 sm:px-4 text-right font-semibold">Prezzo</th>
            <th class="px-3 py-3 sm:px-4 text-right font-semibold">24h</th>
            <th class="hidden px-3 py-3 sm:px-4 text-right font-semibold sm:table-cell">Ultimi 14gg</th>
          </tr>
        </thead>
        <tbody id="mkt-watchlist-body">
          <tr><td colspan="5" class="px-4 py-8 text-center text-sm text-muted">Caricamento quotazioni...</td></tr>
        </tbody>
      </table>
    </div>

    <!-- Top mover -->
    <div class="mb-3 flex items-center justify-between">
      <h2 class="text-xs font-bold uppercase tracking-wider text-muted">I movimenti più forti oggi</h2>
      <span class="text-xs text-muted">Tra gli strumenti tracciati dagli utenti Reflexi</span>
    </div>
    <div class="overflow-x-auto rounded-2xl border border-border">
      <table class="w-full border-collapse sm:min-w-[560px] text-sm">
        <thead>
          <tr class="border-b border-border text-left text-[11px] uppercase tracking-wider text-muted">
            <th class="px-3 py-3 sm:px-4 font-semibold">#</th>
            <th class="px-3 py-3 sm:px-4 font-semibold">Nome</th>
            <th class="px-3 py-3 sm:px-4 text-right font-semibold">Prezzo</th>
            <th class="px-3 py-3 sm:px-4 text-right font-semibold">24h</th>
            <th class="hidden px-3 py-3 sm:px-4 text-right font-semibold sm:table-cell">Ultimi 14gg</th>
          </tr>
        </thead>
        <tbody id="mkt-movers-body">
          <tr><td colspan="5" class="px-4 py-8 text-center text-sm text-muted">Caricamento...</td></tr>
        </tbody>
      </table>
    </div>

    <p class="mt-6 text-center text-xs text-muted">Dati da Yahoo Finance. A scopo informativo, non è una raccomandazione di investimento.</p>
  </div>
</section>

<!-- Articoli News correlati -->
<?php if (!empty($relatedArticles)): ?>
<section class="border-t border-border bg-white px-6 py-16">
  <div class="mx-auto max-w-5xl">
    <h2 class="mb-6 text-center text-xl font-bold">Continua a leggere</h2>
    <div class="grid grid-cols-1 gap-5 sm:grid-cols-3">
      <?php foreach ($relatedArticles as $r): ?>
      <a href="/news/<?= htmlspecialchars($r['slug']) ?>" class="group flex flex-col rounded-2xl border border-border bg-bg p-6 transition hover:border-accent hover:bg-accentSoft">
        <div class="mb-3 flex h-10 w-10 items-center justify-center rounded-xl bg-white text-xl"><?= htmlspecialchars($r['cover_emoji']) ?></div>
        <h3 class="mb-1.5 text-base font-bold"><?= htmlspecialchars($r['title']) ?></h3>
        <p class="text-sm text-muted"><?= htmlspecialchars($r['excerpt']) ?></p>
      </a>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<!-- CTA -->
<section class="px-6 py-20">
  <div class="mx-auto max-w-2xl rounded-3xl bg-gradient-to-br from-accent via-accentDark to-indigo-900 p-10 text-center text-white sm:p-14">
    <h2 class="mb-3 text-2xl font-bold sm:text-3xl">Vuoi collegare questi titoli al tuo portafoglio?</h2>
    <p class="mb-8 text-white/80">Con Reflexi tracci quanto possiedi davvero, con costo medio di carico, plus/minus e PAC automatici.</p>
    <a href="/investimenti.php" class="inline-block rounded-xl bg-white px-8 py-3.5 text-base font-semibold text-accent shadow-lg transition hover:bg-gray-50">Scopri Investimenti</a>
  </div>
</section>

<?php include '_footer.php'; ?>

<script>
const euroLike = (currency) => new Intl.NumberFormat('it-IT', { style: 'currency', currency: currency || 'EUR', maximumFractionDigits: 2 });
function fmtPrice(price, currency) {
  try { return euroLike(currency).format(price); } catch (e) { return price.toFixed(2) + ' ' + (currency || ''); }
}
function changeColor(pct) { return pct > 0 ? '#10b981' : (pct < 0 ? '#f43f5e' : '#6b7080'); }
function changePill(pct) {
  if (pct === null || pct === undefined) return '<span class="text-xs text-muted">—</span>';
  const sign = pct > 0 ? '+' : '';
  const cls = pct > 0 ? 'bg-income/10 text-income' : (pct < 0 ? 'bg-expense/10 text-expense' : 'bg-gray-100 text-muted');
  const arrow = pct > 0 ? '▲' : (pct < 0 ? '▼' : '');
  return `<span class="inline-flex items-center gap-1 rounded-md px-2 py-1 font-mono text-xs font-semibold ${cls}">${arrow} ${sign}${pct.toFixed(2)}%</span>`;
}
function typeLabel(t) {
  return { etf: 'ETF', azione: 'Azione', crypto: 'Crypto', fondo: 'Fondo', altro: 'Altro' }[t] || t;
}
function sparklineSVG(prices) {
  if (!Array.isArray(prices) || prices.length < 2) return '<span class="text-xs text-muted">—</span>';
  const W = 96, H = 30, PAD = 2;
  const min = Math.min(...prices), max = Math.max(...prices);
  const range = (max - min) || 1;
  const stepX = (W - PAD * 2) / (prices.length - 1);
  const pts = prices.map((p, i) => {
    const x = PAD + i * stepX;
    const y = H - PAD - ((p - min) / range) * (H - PAD * 2);
    return `${x.toFixed(1)},${y.toFixed(1)}`;
  }).join(' ');
  const color = changeColor(prices[prices.length - 1] - prices[0]);
  return `<svg width="${W}" height="${H}" viewBox="0 0 ${W} ${H}" preserveAspectRatio="none"><polyline points="${pts}" fill="none" stroke="${color}" stroke-width="1.5"/></svg>`;
}

function marketRow(i, rank) {
  const name = i.name.replace(/"/g, '&quot;');
  return `
    <tr data-ticker="${i.ticker}" data-name="${name}" class="mkt-row cursor-pointer border-b border-border last:border-0 transition hover:bg-gray-50">
      <td class="px-3 py-3 sm:px-4 font-mono text-xs text-muted">${rank}</td>
      <td class="px-3 py-3 sm:px-4">
        <div class="font-semibold text-gray-900">${i.name}</div>
        <div class="text-xs text-muted">${i.ticker} · ${typeLabel(i.type)}</div>
      </td>
      <td class="px-3 py-3 sm:px-4 text-right font-mono text-gray-900">${fmtPrice(i.price, i.currency)}</td>
      <td class="px-3 py-3 sm:px-4 text-right">${changePill(i.change_pct)}</td>
      <td class="hidden px-3 py-3 sm:px-4 text-right sm:table-cell">${sparklineSVG(i.sparkline)}</td>
    </tr>`;
}

function bindRows(tbody) {
  tbody.querySelectorAll('.mkt-row').forEach((row) => row.addEventListener('click', () => selectTicker(row.dataset.ticker, row.dataset.name)));
}

// --- Watchlist ---
fetch('/api/market_trends.php?action=watchlist').then(r => r.json()).then(items => {
  const tbody = document.getElementById('mkt-watchlist-body');
  if (!Array.isArray(items) || !items.length) { tbody.innerHTML = '<tr><td colspan="5" class="px-4 py-8 text-center text-sm text-muted">Quotazioni non disponibili al momento.</td></tr>'; return; }
  tbody.innerHTML = items.map((i, idx) => marketRow(i, idx + 1)).join('');
  bindRows(tbody);
}).catch(() => { document.getElementById('mkt-watchlist-body').innerHTML = '<tr><td colspan="5" class="px-4 py-8 text-center text-sm text-muted">Quotazioni non disponibili al momento.</td></tr>'; });

// --- Top mover ---
fetch('/api/market_trends.php?action=top_movers').then(r => r.json()).then(items => {
  const tbody = document.getElementById('mkt-movers-body');
  if (!Array.isArray(items) || !items.length) { tbody.innerHTML = '<tr><td colspan="5" class="px-4 py-8 text-center text-sm text-muted">Non ci sono ancora abbastanza dati per calcolare i movimenti di oggi.</td></tr>'; return; }
  tbody.innerHTML = items.map((i, idx) => marketRow(i, idx + 1)).join('');
  bindRows(tbody);
}).catch(() => { document.getElementById('mkt-movers-body').innerHTML = '<tr><td colspan="5" class="px-4 py-8 text-center text-sm text-muted">Dati non disponibili al momento.</td></tr>'; });

// --- Ricerca ---
const searchInput = document.getElementById('mkt-search-input');
const searchResults = document.getElementById('mkt-search-results');
let searchTimer = null;
searchInput.addEventListener('input', () => {
  clearTimeout(searchTimer);
  const q = searchInput.value.trim();
  if (q.length < 2) { searchResults.classList.add('hidden'); return; }
  searchTimer = setTimeout(() => {
    fetch('/api/market_trends.php?action=search&q=' + encodeURIComponent(q)).then(r => r.json()).then(items => {
      if (!Array.isArray(items) || !items.length) { searchResults.innerHTML = '<div class="px-4 py-3 text-sm text-muted">Nessun risultato</div>'; searchResults.classList.remove('hidden'); return; }
      searchResults.innerHTML = items.map(i => `
        <button data-ticker="${i.ticker}" data-name="${i.name.replace(/"/g, '&quot;')}" class="mkt-result flex w-full items-center justify-between border-b border-border px-4 py-2.5 text-left last:border-0 hover:bg-accentSoft">
          <span class="text-sm font-medium text-gray-900">${i.name} <span class="text-xs text-muted">${i.ticker}</span></span>
          <span class="text-[10px] font-semibold uppercase text-muted">${typeLabel(i.type)}</span>
        </button>`).join('');
      searchResults.classList.remove('hidden');
      searchResults.querySelectorAll('.mkt-result').forEach(btn => btn.addEventListener('click', () => {
        selectTicker(btn.dataset.ticker, btn.dataset.name);
        searchResults.classList.add('hidden');
        searchInput.value = '';
      }));
    }).catch(() => { searchResults.classList.add('hidden'); });
  }, 300);
});
document.addEventListener('click', (e) => { if (!e.target.closest('#mkt-search-input') && !e.target.closest('#mkt-search-results')) searchResults.classList.add('hidden'); });

// --- Grafico principale ---
const RANGE_DAYS = { '1m': 30, '1a': 365, all: 3650 };
let currentTicker = null;
let currentRange = '1m';
let currentHistory = [];

function selectTicker(ticker, name) {
  currentTicker = ticker;
  document.getElementById('mkt-chart-box').classList.remove('hidden');
  document.getElementById('mkt-chart-title').textContent = `${name} · ${ticker}`;
  document.getElementById('mkt-chart-box').scrollIntoView({ behavior: 'smooth', block: 'start' });
  loadQuote();
  loadHistory();
}

function loadQuote() {
  fetch('/api/market_trends.php?action=quote&ticker=' + encodeURIComponent(currentTicker)).then(r => r.json()).then(q => {
    if (q.error) return;
    document.getElementById('mkt-chart-price').textContent = fmtPrice(q.price, q.currency);
    document.getElementById('mkt-chart-change').innerHTML = changePill(q.change_pct);
  });
}

function loadHistory() {
  const url = currentRange === '24h'
    ? `/api/market_trends.php?action=intraday&ticker=${encodeURIComponent(currentTicker)}`
    : `/api/market_trends.php?action=history&ticker=${encodeURIComponent(currentTicker)}&days=${RANGE_DAYS[currentRange]}`;
  fetch(url).then(r => r.json()).then(points => {
    currentHistory = points;
    renderChart(points);
  });
}

function renderChart(points) {
  const svgBox = document.getElementById('mkt-chart-svg');
  const empty = document.getElementById('mkt-chart-empty');
  if (!Array.isArray(points) || points.length < 2) {
    svgBox.innerHTML = '';
    empty.classList.remove('hidden');
    return;
  }
  empty.classList.add('hidden');

  // viewBox = dimensioni reali del contenitore (non un 800x280 fisso): così
  // il font-size delle etichette (in unità di viewBox) corrisponde a pixel
  // fisici veri, invece di rimpicciolirsi illeggibile quando lo schermo è
  // più stretto di 800px come su mobile.
  const W = Math.round(svgBox.clientWidth) || 800;
  const H = Math.round(svgBox.clientHeight) || 280;
  const PADL = 8, PADR = 56, PADT = 16, PADB = 24;
  const prices = points.map(p => parseFloat(p.price));
  const min = Math.min(...prices), max = Math.max(...prices);
  const range = (max - min) || 1;
  const plotW = W - PADL - PADR, plotH = H - PADT - PADB;
  const stepX = plotW / (points.length - 1);
  const xAt = (i) => PADL + i * stepX;
  const yAt = (p) => PADT + plotH - ((p - min) / range) * plotH;
  const coords = prices.map((p, i) => [xAt(i), yAt(p)]);

  const linePath = coords.map(([x, y], i) => (i === 0 ? 'M' : 'L') + x.toFixed(1) + ',' + y.toFixed(1)).join(' ');
  const areaPath = linePath + ` L${coords[coords.length - 1][0].toFixed(1)},${PADT + plotH} L${coords[0][0].toFixed(1)},${PADT + plotH} Z`;
  const color = changeColor(prices[prices.length - 1] - prices[0]);
  const gradId = 'mktGrad' + Math.random().toString(36).slice(2, 8);

  const gridLevels = [min, min + range / 2, max];
  const gridLines = gridLevels.map(v => {
    const y = yAt(v);
    return `<line x1="${PADL}" y1="${y.toFixed(1)}" x2="${W - PADR}" y2="${y.toFixed(1)}" stroke="#e7e9f0"/>
      <text x="${W - PADR + 8}" y="${(y + 3.5).toFixed(1)}" fill="#6b7080" font-size="10" font-family="IBM Plex Mono, monospace">${fmtPrice(v, points[0].currency)}</text>`;
  }).join('');

  svgBox.innerHTML = `
    <svg viewBox="0 0 ${W} ${H}" class="h-full w-full" preserveAspectRatio="none" id="mkt-svg-el">
      <defs>
        <linearGradient id="${gradId}" x1="0" y1="0" x2="0" y2="1">
          <stop offset="0%" stop-color="${color}" stop-opacity="0.25"/>
          <stop offset="100%" stop-color="${color}" stop-opacity="0"/>
        </linearGradient>
      </defs>
      ${gridLines}
      <path d="${areaPath}" fill="url(#${gradId})" stroke="none"></path>
      <path d="${linePath}" fill="none" stroke="${color}" stroke-width="2"></path>
      <g id="mkt-crosshair" style="opacity:0">
        <line x1="0" y1="${PADT}" x2="0" y2="${PADT + plotH}" stroke="#000000" stroke-opacity="0.12"></line>
        <circle r="3.5" fill="${color}" stroke="#ffffff" stroke-width="1.5"></circle>
      </g>
    </svg>
    <div id="mkt-tooltip" class="pointer-events-none absolute hidden rounded-lg border border-border bg-white px-2.5 py-1.5 text-xs text-gray-900 shadow-lg"></div>
    <div class="mt-1 flex justify-between text-[11px] text-muted">
      <span>${points[0].date}</span>
      <span>${points[points.length - 1].date}</span>
    </div>`;

  const svgEl = document.getElementById('mkt-svg-el');
  const crosshair = document.getElementById('mkt-crosshair');
  const tooltip = document.getElementById('mkt-tooltip');
  const crossLine = crosshair.querySelector('line');
  const crossDot = crosshair.querySelector('circle');

  function moveCrosshair(clientX) {
    const rect = svgEl.getBoundingClientRect();
    const relX = ((clientX - rect.left) / rect.width) * W;
    let idx = Math.round((relX - PADL) / stepX);
    idx = Math.max(0, Math.min(points.length - 1, idx));
    const [x, y] = coords[idx];
    crossLine.setAttribute('x1', x); crossLine.setAttribute('x2', x);
    crossDot.setAttribute('cx', x); crossDot.setAttribute('cy', y);
    crosshair.style.opacity = '1';

    const pxRatio = rect.width / W;
    tooltip.style.left = Math.min(rect.width - 100, Math.max(0, x * pxRatio + 8)) + 'px';
    tooltip.style.top = Math.max(0, (y * (rect.height / H)) - 36) + 'px';
    tooltip.innerHTML = `<div class="font-mono font-semibold">${fmtPrice(prices[idx], points[0].currency)}</div><div class="text-muted">${points[idx].date}</div>`;
    tooltip.classList.remove('hidden');
  }
  function hideCrosshair() { crosshair.style.opacity = '0'; tooltip.classList.add('hidden'); }

  svgEl.addEventListener('mousemove', (e) => moveCrosshair(e.clientX));
  svgEl.addEventListener('mouseleave', hideCrosshair);
  // Touch: mousemove non scatta sui device touch, altrimenti il crosshair
  // sarebbe una funzione solo desktop.
  svgEl.addEventListener('touchstart', (e) => moveCrosshair(e.touches[0].clientX), { passive: true });
  svgEl.addEventListener('touchmove', (e) => moveCrosshair(e.touches[0].clientX), { passive: true });
  svgEl.addEventListener('touchend', hideCrosshair);
}

document.querySelectorAll('.mkt-range').forEach(btn => {
  btn.addEventListener('click', () => {
    currentRange = btn.dataset.range;
    document.querySelectorAll('.mkt-range').forEach(b => b.className = 'mkt-range rounded-md px-3 py-1.5 text-xs font-semibold text-gray-600 transition');
    btn.className = 'mkt-range rounded-md bg-accent px-3 py-1.5 text-xs font-semibold text-white transition';
    if (currentTicker) loadHistory();
  });
});
document.querySelector('.mkt-range[data-range="1m"]').className = 'mkt-range rounded-md bg-accent px-3 py-1.5 text-xs font-semibold text-white transition';
</script>

</body>
</html>
