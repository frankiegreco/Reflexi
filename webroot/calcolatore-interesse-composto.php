<?php
$active = 'calcolatori';
require_once __DIR__ . '/_news_db.php';
$relatedArticles = news_related(['interesse-composto-esempi-reali', 'ter-costi-etf-quanto-incidono-sul-rendimento']);
?>
<!DOCTYPE html>
<html lang="it">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Calcolatore di interesse composto gratuito — Reflexi</title>
<meta name="description" content="Calcola quanto cresce un capitale nel tempo con l'interesse composto, con grafico anno per anno. Gratuito, senza registrazione.">
<meta name="robots" content="index, follow">
<link rel="canonical" href="https://reflexi.it/calcolatore-interesse-composto">
<!-- Open Graph (Facebook, WhatsApp, LinkedIn, Telegram...) -->
<meta property="og:type" content="website">
<meta property="og:url" content="https://reflexi.it/calcolatore-interesse-composto">
<meta property="og:site_name" content="Reflexi">
<meta property="og:title" content="Calcolatore di interesse composto gratuito — Reflexi">
<meta property="og:description" content="Calcola quanto cresce un capitale nel tempo con l'interesse composto, con grafico anno per anno. Gratuito, senza registrazione.">
<meta property="og:image" content="<?php require_once __DIR__ . '/_seo.php'; echo seo_og_image(); ?>">
<meta property="og:image:width" content="1200">
<meta property="og:image:height" content="630">
<meta property="og:locale" content="it_IT">

<!-- Twitter/X Card -->
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="Calcolatore di interesse composto gratuito — Reflexi">
<meta name="twitter:description" content="Calcola quanto cresce un capitale nel tempo con l'interesse composto, con grafico anno per anno. Gratuito, senza registrazione.">
<meta name="twitter:image" content="<?php require_once __DIR__ . '/_seo.php'; echo seo_og_image(); ?>">
<link rel="icon" type="image/svg+xml" href="/favicon.svg">
<link rel="preload" href="/fonts/inter-v20-latin.woff2" as="font" type="font/woff2" crossorigin>
<link rel="stylesheet" href="/css/site.css?v=<?= filemtime(__DIR__ . '/css/site.css') ?>">
<?php
require_once __DIR__ . '/_seo.php';
seo_breadcrumb([['Calcolatori', '/calcolatori'], ['Calcolatore interesse composto', '/calcolatore-interesse-composto']]);
seo_calculator('Calcolatore interesse composto', '/calcolatore-interesse-composto', 'Calcola quanto cresce un capitale nel tempo con l\'interesse composto, con grafico anno per anno. Gratuito, senza registrazione.');
?>
</head>
<body class="bg-bg text-gray-900 antialiased">

<?php include '_nav.php'; ?>

<section class="px-6 pb-6 pt-14 text-center sm:pt-16">
  <a href="/calcolatori" class="mb-4 inline-block text-sm text-muted hover:text-accent">← Tutti i calcolatori</a>
  <div class="mx-auto mb-3 flex h-14 w-14 items-center justify-center rounded-2xl bg-accentSoft text-3xl">📈</div>
  <h1 class="mx-auto mb-3 max-w-2xl text-3xl font-extrabold leading-tight tracking-tight sm:text-4xl">Calcolatore di interesse composto</h1>
  <p class="mx-auto max-w-xl text-lg text-muted">Quanto cresce un capitale nel tempo, reinvestendo ogni anno gli interessi maturati invece di incassarli.</p>
</section>

<main class="mx-auto max-w-3xl px-6 py-8">
  <div class="rounded-2xl border border-border bg-white p-6 shadow-sm sm:p-8">
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
      <div>
        <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-muted">Capitale iniziale (€)</label>
        <input id="ci-capital" type="number" value="10000" min="0" class="w-full rounded-xl border border-border px-3.5 py-2.5 text-sm outline-none focus:border-accent focus:ring-4 focus:ring-accent/10">
      </div>
      <div>
        <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-muted">Tasso annuo (%)</label>
        <input id="ci-rate" type="number" value="5" step="0.1" min="0" class="w-full rounded-xl border border-border px-3.5 py-2.5 text-sm outline-none focus:border-accent focus:ring-4 focus:ring-accent/10">
      </div>
      <div>
        <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-muted">Durata (anni)</label>
        <input id="ci-years" type="number" value="20" min="1" max="60" class="w-full rounded-xl border border-border px-3.5 py-2.5 text-sm outline-none focus:border-accent focus:ring-4 focus:ring-accent/10">
      </div>
      <div>
        <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-muted">Capitalizzazione</label>
        <select id="ci-freq" class="w-full rounded-xl border border-border px-3.5 py-2.5 text-sm outline-none focus:border-accent focus:ring-4 focus:ring-accent/10">
          <option value="1">Annuale</option>
          <option value="12" selected>Mensile</option>
          <option value="365">Giornaliera</option>
        </select>
      </div>
    </div>

    <div class="mt-6 grid grid-cols-1 gap-3 sm:grid-cols-3">
      <div class="rounded-xl border border-border p-4"><div class="text-[11px] uppercase text-muted">Capitale investito</div><div id="ci-out-invested" class="font-mono text-xl font-semibold">—</div></div>
      <div class="rounded-xl border border-border p-4"><div class="text-[11px] uppercase text-muted">Interessi generati</div><div id="ci-out-interest" class="font-mono text-xl font-semibold text-income">—</div></div>
      <div class="rounded-xl border-2 border-accent bg-accentSoft p-4"><div class="text-[11px] uppercase text-accent">Valore finale</div><div id="ci-out-total" class="font-mono text-xl font-bold text-accent">—</div></div>
    </div>

    <div class="mt-6">
      <div class="mb-2 flex items-center gap-4 text-xs text-muted">
        <span class="flex items-center gap-1.5"><span class="h-2.5 w-2.5 rounded-full bg-accent/30"></span>Capitale versato</span>
        <span class="flex items-center gap-1.5"><span class="h-2.5 w-2.5 rounded-full bg-income"></span>Interessi maturati</span>
      </div>
      <div id="ci-chart" class="flex h-52 items-end gap-1"></div>
      <div id="ci-chart-labels" class="mt-1 flex justify-between text-[10px] text-muted"></div>
    </div>

    <div class="mt-6 overflow-x-auto">
      <table class="w-full text-sm">
        <thead>
          <tr class="border-b border-border text-left text-[11px] uppercase tracking-wide text-muted">
            <th class="py-2">Anno</th><th class="py-2 text-right">Versato</th><th class="py-2 text-right">Interessi</th><th class="py-2 text-right">Totale</th>
          </tr>
        </thead>
        <tbody id="ci-table"></tbody>
      </table>
    </div>
  </div>

  <div class="mt-10 space-y-4 text-[15px] leading-relaxed text-gray-700">
    <h2 class="text-xl font-bold text-gray-900">Come funziona l'interesse composto</h2>
    <p>L'interesse composto è l'interesse calcolato non solo sul capitale iniziale, ma anche sugli interessi già maturati nei periodi precedenti. È il motivo per cui Einstein lo avrebbe definito (probabilmente in modo apocrifo, ma l'idea regge) "l'ottava meraviglia del mondo": più a lungo lasci crescere un capitale, più l'effetto si amplifica, perché ogni euro di interesse comincia a sua volta a generare interesse.</p>
    <p>La formula di base è <span class="font-mono text-sm">M = C × (1 + r/n)<sup>n×t</sup></span>, dove C è il capitale iniziale, r il tasso annuo, n il numero di capitalizzazioni all'anno, t gli anni. Più frequente è la capitalizzazione (mensile invece di annuale), più velocemente cresce il capitale, anche a parità di tasso nominale.</p>
    <p>Questo calcolatore non tiene conto di tasse su eventuali plusvalenze né di commissioni — verifica sempre le condizioni reali del tuo strumento di investimento.</p>
  </div>
</main>

<!-- Articoli News correlati -->
<?php if (!empty($relatedArticles)): ?>
<section class="border-t border-border bg-white px-6 py-16">
  <div class="mx-auto max-w-5xl">
    <h2 class="mb-6 text-center text-xl font-bold">Continua a leggere</h2>
    <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
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

<section class="px-6 py-16 text-center">
  <p class="mb-4 text-sm text-muted">Cerchi anche un altro calcolatore?</p>
  <a href="/calcolatori" class="mb-8 inline-block text-sm font-semibold text-accent hover:underline">Vedi tutti i calcolatori →</a>
  <h2 class="mb-2 text-xl font-bold">Vuoi che questi calcoli si aggiornino da soli col tempo?</h2>
  <p class="mb-6 text-muted">Reflexi collega i tuoi investimenti reali, con quotazioni live e proiezioni sempre aggiornate.</p>
  <a href="/app/" class="inline-block rounded-xl bg-accent px-7 py-3 text-sm font-semibold text-white shadow-lg shadow-accent/25 transition hover:bg-accentDark">Prova Reflexi gratis</a>
  <p class="mt-4 text-sm text-muted">Come funziona in Reflexi: <a href="/investimenti" class="font-semibold text-accent hover:underline">Investimenti →</a></p>
</section>

<?php include '_footer.php'; ?>

<script>
function euro(n) {
  if (!isFinite(n)) return '—';
  return new Intl.NumberFormat('it-IT', { style: 'currency', currency: 'EUR', maximumFractionDigits: 0 }).format(n);
}

function calc() {
  const capital = parseFloat(document.getElementById('ci-capital').value) || 0;
  const rate = parseFloat(document.getElementById('ci-rate').value) || 0;
  const years = parseInt(document.getElementById('ci-years').value) || 0;
  const freq = parseInt(document.getElementById('ci-freq').value) || 1;

  const chart = document.getElementById('ci-chart');
  const labels = document.getElementById('ci-chart-labels');
  const table = document.getElementById('ci-table');
  chart.innerHTML = ''; labels.innerHTML = ''; table.innerHTML = '';

  let maxVal = capital;
  const yearVals = [];
  for (let y = 0; y <= years; y++) {
    const val = capital * Math.pow(1 + (rate / 100) / freq, freq * y);
    yearVals.push(val);
    if (val > maxVal) maxVal = val;
  }

  const step = Math.max(1, Math.ceil(years / 10));
  yearVals.forEach((v, y) => {
    const col = document.createElement('div');
    col.className = 'flex h-full w-full flex-col justify-end';
    const interestPart = v - capital;
    const capPct = Math.max(1, (capital / maxVal) * 100);
    const intPct = Math.max(0, (interestPart / maxVal) * 100);
    col.innerHTML = `<div class="w-full rounded-t bg-income" style="height:${intPct}%"></div><div class="w-full bg-accent/30" style="height:${capPct}%"></div>`;
    chart.appendChild(col);

    if (y % step === 0 || y === years) {
      const lbl = document.createElement('span');
      lbl.textContent = y;
      labels.appendChild(lbl);
    }
    if (y % step === 0 || y === years) {
      const row = document.createElement('tr');
      row.className = 'border-b border-border last:border-0';
      row.innerHTML = `<td class="py-1.5">${y}</td><td class="py-1.5 text-right font-mono">${euro(capital)}</td><td class="py-1.5 text-right font-mono text-income">${euro(interestPart)}</td><td class="py-1.5 text-right font-mono font-semibold">${euro(v)}</td>`;
      table.appendChild(row);
    }
  });

  const final = yearVals[yearVals.length - 1];
  document.getElementById('ci-out-invested').textContent = euro(capital);
  document.getElementById('ci-out-interest').textContent = euro(final - capital);
  document.getElementById('ci-out-total').textContent = euro(final);
}
['ci-capital', 'ci-rate', 'ci-years', 'ci-freq'].forEach((id) => document.getElementById(id).addEventListener('input', calc));
calc();
</script>

</body>
</html>
