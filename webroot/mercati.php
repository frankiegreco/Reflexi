<!DOCTYPE html>
<html lang="it">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Mercati — Andamenti di azioni, ETF e crypto | Reflexi</title>
<meta name="description" content="Quotazioni live di ETF, azioni e crypto: watchlist dei titoli più seguiti, i movimenti più forti della giornata e il grafico storico di qualsiasi ticker tu cerchi. Dati gratuiti, senza registrazione.">
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
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=IBM+Plex+Mono:wght@500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="/css/site.css?v=1">
</head>
<body class="bg-bg text-gray-900 antialiased">

<?php
$active = 'mercati';
require_once __DIR__ . '/_news_db.php';
$relatedArticles = news_related(['cosa-e-un-etf-guida-per-principianti', 'pac-piano-accumulo-etf-come-funziona', 'diversificazione-portafoglio-cosa-significa-davvero']);
include '_nav.php';
?>

<!-- HERO -->
<section class="px-6 pb-10 pt-14 sm:pt-20">
  <div class="mx-auto max-w-3xl text-center">
    <span class="mb-4 inline-block rounded-full bg-accentSoft px-4 py-1.5 text-xs font-semibold uppercase tracking-wider text-accent">Mercati</span>
    <h1 class="mb-4 text-3xl font-extrabold leading-tight tracking-tight sm:text-4xl">Andamenti di azioni, ETF e crypto, in tempo reale</h1>
    <p class="mb-2 text-lg text-muted">Quotazioni live, senza registrazione. Cerca un titolo, guarda com'è andato oggi il mercato, o tieni d'occhio i titoli più seguiti.</p>
    <p class="text-xs text-muted">Dati da Yahoo Finance, aggiornati ogni 15 minuti circa. A scopo informativo, non è una raccomandazione di investimento.</p>
  </div>
</section>

<!-- RICERCA + GRAFICO -->
<section class="border-t border-border bg-white px-6 py-12">
  <div class="mx-auto max-w-3xl">
    <h2 class="mb-1 text-center text-xl font-bold">Cerca un titolo</h2>
    <p class="mb-6 text-center text-sm text-muted">Azioni, ETF o crypto — es. "Apple", "VWCE", "Bitcoin"</p>

    <div class="relative">
      <input id="mkt-search-input" type="text" autocomplete="off" placeholder="Cerca un ticker o un nome..."
        class="w-full rounded-xl border border-border bg-bg px-4 py-3 text-sm outline-none transition focus:border-accent focus:bg-white">
      <div id="mkt-search-results" class="absolute left-0 right-0 top-full z-10 mt-1 hidden max-h-72 overflow-y-auto rounded-xl border border-border bg-white shadow-lg"></div>
    </div>

    <div id="mkt-chart-box" class="mt-6 hidden rounded-2xl border border-border bg-white p-5">
      <div class="mb-3 flex flex-wrap items-center justify-between gap-3">
        <div>
          <div id="mkt-chart-title" class="text-base font-bold"></div>
          <div class="flex items-baseline gap-2">
            <span id="mkt-chart-price" class="font-mono text-lg font-semibold"></span>
            <span id="mkt-chart-change" class="font-mono text-sm font-semibold"></span>
          </div>
        </div>
        <div class="flex gap-2">
          <button data-days="30" class="mkt-range rounded-lg px-3 py-1 text-xs font-semibold bg-gray-100 text-gray-600">30gg</button>
          <button data-days="90" class="mkt-range rounded-lg px-3 py-1 text-xs font-semibold bg-accent text-white">90gg</button>
          <button data-days="365" class="mkt-range rounded-lg px-3 py-1 text-xs font-semibold bg-gray-100 text-gray-600">1 anno</button>
        </div>
      </div>
      <div id="mkt-chart-svg" class="h-56 w-full"></div>
      <p id="mkt-chart-empty" class="hidden py-8 text-center text-sm text-muted">Non c'è ancora storico per questo ticker — torna a controllare tra qualche giorno, si costruisce da solo.</p>
    </div>
  </div>
</section>

<!-- WATCHLIST -->
<section class="border-t border-border px-6 py-14">
  <div class="mx-auto max-w-5xl">
    <h2 class="mb-1 text-center text-xl font-bold">Titoli più seguiti</h2>
    <p class="mb-8 text-center text-sm text-muted">Una selezione di ETF, azioni e crypto tra i più popolari</p>
    <div id="mkt-watchlist" class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4">
      <div class="col-span-full py-6 text-center text-sm text-muted">Caricamento quotazioni...</div>
    </div>
  </div>
</section>

<!-- TOP MOVER -->
<section class="border-t border-border bg-white px-6 py-14">
  <div class="mx-auto max-w-3xl">
    <h2 class="mb-1 text-center text-xl font-bold">I movimenti più forti oggi</h2>
    <p class="mb-8 text-center text-sm text-muted">Tra gli strumenti tracciati dagli utenti di Reflexi</p>
    <div id="mkt-movers" class="space-y-2">
      <div class="py-6 text-center text-sm text-muted">Caricamento...</div>
    </div>
  </div>
</section>

<!-- Articoli News correlati -->
<?php if (!empty($relatedArticles)): ?>
<section class="border-t border-border px-6 py-16">
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
function fmtChange(pct) {
  if (pct === null || pct === undefined) return '<span class="text-muted">—</span>';
  const sign = pct > 0 ? '+' : '';
  const cls = pct > 0 ? 'text-income' : (pct < 0 ? 'text-expense' : 'text-muted');
  return `<span class="${cls}">${sign}${pct.toFixed(2)}%</span>`;
}
function typeLabel(t) {
  return { etf: 'ETF', azione: 'Azione', crypto: 'Crypto', fondo: 'Fondo', altro: 'Altro' }[t] || t;
}

// --- Watchlist ---
fetch('/api/market_trends.php?action=watchlist').then(r => r.json()).then(items => {
  const box = document.getElementById('mkt-watchlist');
  if (!Array.isArray(items) || !items.length) { box.innerHTML = '<div class="col-span-full py-6 text-center text-sm text-muted">Quotazioni non disponibili al momento.</div>'; return; }
  box.innerHTML = items.map(i => `
    <button data-ticker="${i.ticker}" class="mkt-pick rounded-2xl border border-border bg-white p-4 text-left transition hover:border-accent hover:shadow-md">
      <div class="mb-1 text-[10px] font-semibold uppercase tracking-wide text-muted">${typeLabel(i.type)}</div>
      <div class="mb-2 truncate text-sm font-bold" title="${i.name}">${i.name}</div>
      <div class="font-mono text-base font-semibold">${fmtPrice(i.price, i.currency)}</div>
      <div class="font-mono text-xs font-semibold">${fmtChange(i.change_pct)}</div>
    </button>`).join('');
  box.querySelectorAll('.mkt-pick').forEach(btn => btn.addEventListener('click', () => selectTicker(btn.dataset.ticker, btn.querySelector('.text-sm.font-bold').textContent)));
}).catch(() => { document.getElementById('mkt-watchlist').innerHTML = '<div class="col-span-full py-6 text-center text-sm text-muted">Quotazioni non disponibili al momento.</div>'; });

// --- Top mover ---
fetch('/api/market_trends.php?action=top_movers').then(r => r.json()).then(items => {
  const box = document.getElementById('mkt-movers');
  if (!Array.isArray(items) || !items.length) { box.innerHTML = '<div class="py-6 text-center text-sm text-muted">Non ci sono ancora abbastanza dati per calcolare i movimenti di oggi.</div>'; return; }
  box.innerHTML = items.map(i => `
    <button data-ticker="${i.ticker}" class="mkt-pick flex w-full items-center justify-between rounded-xl border border-border bg-bg px-4 py-3 text-left transition hover:border-accent">
      <div>
        <div class="text-sm font-bold">${i.name}</div>
        <div class="text-xs text-muted">${i.ticker} · ${typeLabel(i.type)}</div>
      </div>
      <div class="text-right">
        <div class="font-mono text-sm font-semibold">${fmtPrice(i.price, i.currency)}</div>
        <div class="font-mono text-xs font-semibold">${fmtChange(i.change_pct)}</div>
      </div>
    </button>`).join('');
  box.querySelectorAll('.mkt-pick').forEach(btn => btn.addEventListener('click', () => selectTicker(btn.dataset.ticker, btn.querySelector('.text-sm.font-bold').textContent)));
}).catch(() => { document.getElementById('mkt-movers').innerHTML = '<div class="py-6 text-center text-sm text-muted">Dati non disponibili al momento.</div>'; });

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
          <span class="text-sm font-medium">${i.name} <span class="text-xs text-muted">${i.ticker}</span></span>
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

// --- Grafico ---
let currentTicker = null;
let currentDays = 90;

function selectTicker(ticker, name) {
  currentTicker = ticker;
  document.getElementById('mkt-chart-box').classList.remove('hidden');
  document.getElementById('mkt-chart-title').textContent = name + ' (' + ticker + ')';
  document.getElementById('mkt-chart-box').scrollIntoView({ behavior: 'smooth', block: 'nearest' });
  loadQuote();
  loadHistory();
}

function loadQuote() {
  fetch('/api/market_trends.php?action=quote&ticker=' + encodeURIComponent(currentTicker)).then(r => r.json()).then(q => {
    if (q.error) return;
    document.getElementById('mkt-chart-price').textContent = fmtPrice(q.price, q.currency);
    document.getElementById('mkt-chart-change').innerHTML = fmtChange(q.change_pct);
  });
}

function loadHistory() {
  fetch(`/api/market_trends.php?action=history&ticker=${encodeURIComponent(currentTicker)}&days=${currentDays}`).then(r => r.json()).then(renderChart);
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

  const W = 600, H = 220, PAD = 8;
  const prices = points.map(p => parseFloat(p.price));
  const min = Math.min(...prices), max = Math.max(...prices);
  const range = (max - min) || 1;
  const stepX = (W - PAD * 2) / (points.length - 1);
  const coords = prices.map((p, i) => {
    const x = PAD + i * stepX;
    const y = H - PAD - ((p - min) / range) * (H - PAD * 2);
    return [x, y];
  });
  const linePath = coords.map(([x, y], i) => (i === 0 ? 'M' : 'L') + x.toFixed(1) + ',' + y.toFixed(1)).join(' ');
  const areaPath = linePath + ` L${coords[coords.length - 1][0].toFixed(1)},${H - PAD} L${coords[0][0].toFixed(1)},${H - PAD} Z`;
  const trendUp = prices[prices.length - 1] >= prices[0];
  const color = trendUp ? '#10b981' : '#f43f5e';

  svgBox.innerHTML = `
    <svg viewBox="0 0 ${W} ${H}" class="h-full w-full" preserveAspectRatio="none">
      <path d="${areaPath}" fill="${color}" fill-opacity="0.08" stroke="none"></path>
      <path d="${linePath}" fill="none" stroke="${color}" stroke-width="2" vector-effect="non-scaling-stroke"></path>
    </svg>
    <div class="mt-1 flex justify-between text-[11px] text-muted">
      <span>${points[0].date}</span>
      <span>${points[points.length - 1].date}</span>
    </div>`;
}

document.querySelectorAll('.mkt-range').forEach(btn => {
  btn.addEventListener('click', () => {
    currentDays = parseInt(btn.dataset.days, 10);
    document.querySelectorAll('.mkt-range').forEach(b => b.className = 'mkt-range rounded-lg px-3 py-1 text-xs font-semibold bg-gray-100 text-gray-600');
    btn.className = 'mkt-range rounded-lg px-3 py-1 text-xs font-semibold bg-accent text-white';
    if (currentTicker) loadHistory();
  });
});
</script>

</body>
</html>
