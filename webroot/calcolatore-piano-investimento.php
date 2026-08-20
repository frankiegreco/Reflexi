<?php
$active = 'calcolatori';
require_once __DIR__ . '/_news_db.php';
$relatedArticles = news_related(['pac-piano-accumulo-etf-come-funziona', 'cosa-e-un-etf-guida-per-principianti']);
?>
<!DOCTYPE html>
<html lang="it">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Calcolatore piano di investimento periodico — Reflexi</title>
<meta name="description" content="Calcola quanto puoi accumulare investendo una cifra fissa ogni mese, con grafico anno per anno. Gratuito, senza registrazione.">
<meta name="robots" content="index, follow">
<link rel="canonical" href="https://reflexi.it/calcolatore-piano-investimento.php">
<!-- Open Graph (Facebook, WhatsApp, LinkedIn, Telegram...) -->
<meta property="og:type" content="website">
<meta property="og:url" content="https://reflexi.it/calcolatore-piano-investimento.php">
<meta property="og:site_name" content="Reflexi">
<meta property="og:title" content="Calcolatore piano di investimento periodico — Reflexi">
<meta property="og:description" content="Calcola quanto puoi accumulare investendo una cifra fissa ogni mese, con grafico anno per anno. Gratuito, senza registrazione.">
<meta property="og:image" content="https://reflexi.it/og-image.png?v=3">
<meta property="og:image:width" content="1200">
<meta property="og:image:height" content="630">
<meta property="og:locale" content="it_IT">

<!-- Twitter/X Card -->
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="Calcolatore piano di investimento periodico — Reflexi">
<meta name="twitter:description" content="Calcola quanto puoi accumulare investendo una cifra fissa ogni mese, con grafico anno per anno. Gratuito, senza registrazione.">
<meta name="twitter:image" content="https://reflexi.it/og-image.png?v=3">
<link rel="icon" type="image/svg+xml" href="/favicon.svg">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=IBM+Plex+Mono:wght@500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="/css/site.css?v=1">
<style>
  body { font-family: 'Inter', ui-sans-serif, system-ui, sans-serif; } .font-mono { font-family: 'IBM Plex Mono', ui-monospace, monospace; }
</style>
</head>
<body class="bg-bg text-gray-900 antialiased">

<?php include '_nav.php'; ?>

<section class="px-6 pb-6 pt-14 text-center sm:pt-16">
  <a href="/calcolatori.php" class="mb-4 inline-block text-sm text-muted hover:text-accent">← Tutti i calcolatori</a>
  <div class="mx-auto mb-3 flex h-14 w-14 items-center justify-center rounded-2xl bg-income/10 text-3xl">💰</div>
  <h1 class="mx-auto mb-3 max-w-2xl text-3xl font-extrabold leading-tight tracking-tight sm:text-4xl">Calcolatore di piano di investimento periodico</h1>
  <p class="mx-auto max-w-xl text-lg text-muted">Quanto puoi accumulare investendo una cifra fissa ogni mese (come un PAC), oltre a un capitale iniziale.</p>
</section>

<main class="mx-auto max-w-3xl px-6 py-8">
  <div class="rounded-2xl border border-border bg-white p-6 shadow-sm sm:p-8">
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
      <div>
        <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-muted">Capitale iniziale (€)</label>
        <input id="pi-capital" type="number" value="1000" min="0" class="w-full rounded-xl border border-border px-3.5 py-2.5 text-sm outline-none focus:border-accent focus:ring-4 focus:ring-accent/10">
      </div>
      <div>
        <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-muted">Versamento mensile (€)</label>
        <input id="pi-monthly" type="number" value="200" min="0" class="w-full rounded-xl border border-border px-3.5 py-2.5 text-sm outline-none focus:border-accent focus:ring-4 focus:ring-accent/10">
      </div>
      <div>
        <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-muted">Rendimento annuo atteso (%)</label>
        <input id="pi-rate" type="number" value="6" step="0.1" min="0" class="w-full rounded-xl border border-border px-3.5 py-2.5 text-sm outline-none focus:border-accent focus:ring-4 focus:ring-accent/10">
      </div>
      <div>
        <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-muted">Durata (anni)</label>
        <input id="pi-years" type="number" value="20" min="1" max="60" class="w-full rounded-xl border border-border px-3.5 py-2.5 text-sm outline-none focus:border-accent focus:ring-4 focus:ring-accent/10">
      </div>
    </div>

    <div class="mt-6 grid grid-cols-1 gap-3 sm:grid-cols-3">
      <div class="rounded-xl border border-border p-4"><div class="text-[11px] uppercase text-muted">Totale versato</div><div id="pi-out-invested" class="font-mono text-xl font-semibold">—</div></div>
      <div class="rounded-xl border border-border p-4"><div class="text-[11px] uppercase text-muted">Guadagno stimato</div><div id="pi-out-gain" class="font-mono text-xl font-semibold text-income">—</div></div>
      <div class="rounded-xl border-2 border-accent bg-accentSoft p-4"><div class="text-[11px] uppercase text-accent">Valore finale</div><div id="pi-out-total" class="font-mono text-xl font-bold text-accent">—</div></div>
    </div>

    <div class="mt-6">
      <div class="mb-2 flex items-center gap-4 text-xs text-muted">
        <span class="flex items-center gap-1.5"><span class="h-2.5 w-2.5 rounded-full bg-accent/30"></span>Versato</span>
        <span class="flex items-center gap-1.5"><span class="h-2.5 w-2.5 rounded-full bg-income"></span>Guadagno</span>
      </div>
      <div id="pi-chart" class="flex h-52 items-end gap-1"></div>
      <div id="pi-chart-labels" class="mt-1 flex justify-between text-[10px] text-muted"></div>
    </div>
  </div>

  <div class="mt-10 space-y-4 text-[15px] leading-relaxed text-gray-700">
    <h2 class="text-xl font-bold text-gray-900">Perché investire un po' alla volta funziona</h2>
    <p>Un piano di investimento periodico (in Italia spesso chiamato PAC, piano di accumulo capitale) consiste nell'investire una cifra fissa a intervalli regolari, invece che tutto in un'unica soluzione. Questo approccio ha due vantaggi concreti: rende l'investimento accessibile anche senza un grande capitale di partenza, e mediando il prezzo di acquisto nel tempo riduce l'impatto di comprare tutto nel momento sbagliato (il cosiddetto "dollar-cost averaging").</p>
    <p>La stima qui sopra usa un rendimento medio costante ogni anno — nella realtà i mercati oscillano, quindi il risultato finale reale potrebbe essere diverso, in un senso o nell'altro. È una proiezione utile per farsi un'idea, non una promessa.</p>
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
  <a href="/calcolatori.php" class="mb-8 inline-block text-sm font-semibold text-accent hover:underline">Vedi tutti i calcolatori →</a>
  <h2 class="mb-2 text-xl font-bold">Vuoi un PAC vero, che compra da solo?</h2>
  <p class="mb-6 text-muted">Reflexi calcola le quote acquistate al prezzo reale del giorno, automaticamente.</p>
  <a href="/investimenti.php" class="inline-block rounded-xl bg-accent px-7 py-3 text-sm font-semibold text-white shadow-lg shadow-accent/25 transition hover:bg-accentDark">Scopri gli investimenti su Reflexi</a>
</section>

<?php include '_footer.php'; ?>

<script>
function euro(n) {
  if (!isFinite(n)) return '—';
  return new Intl.NumberFormat('it-IT', { style: 'currency', currency: 'EUR', maximumFractionDigits: 0 }).format(n);
}

function calc() {
  const capital = parseFloat(document.getElementById('pi-capital').value) || 0;
  const monthly = parseFloat(document.getElementById('pi-monthly').value) || 0;
  const rate = parseFloat(document.getElementById('pi-rate').value) || 0;
  const years = parseInt(document.getElementById('pi-years').value) || 0;
  const monthlyRate = (rate / 100) / 12;

  const chart = document.getElementById('pi-chart');
  const labels = document.getElementById('pi-chart-labels');
  chart.innerHTML = ''; labels.innerHTML = '';

  let total = capital;
  let invested = capital;
  const yearVals = [];
  yearVals.push({ total, invested });
  for (let y = 1; y <= years; y++) {
    for (let m = 0; m < 12; m++) {
      total = total * (1 + monthlyRate) + monthly;
      invested += monthly;
    }
    yearVals.push({ total, invested });
  }
  const maxVal = Math.max(...yearVals.map((v) => v.total));
  const step = Math.max(1, Math.ceil(years / 10));

  yearVals.forEach((v, y) => {
    const col = document.createElement('div');
    col.className = 'flex h-full w-full flex-col justify-end';
    const gain = v.total - v.invested;
    const investedPct = Math.max(1, (v.invested / maxVal) * 100);
    const gainPct = Math.max(0, (gain / maxVal) * 100);
    col.innerHTML = `<div class="w-full rounded-t bg-income" style="height:${gainPct}%"></div><div class="w-full bg-accent/30" style="height:${investedPct}%"></div>`;
    chart.appendChild(col);
    if (y % step === 0 || y === years) {
      const lbl = document.createElement('span');
      lbl.textContent = y;
      labels.appendChild(lbl);
    }
  });

  const final = yearVals[yearVals.length - 1];
  document.getElementById('pi-out-invested').textContent = euro(final.invested);
  document.getElementById('pi-out-gain').textContent = euro(final.total - final.invested);
  document.getElementById('pi-out-total').textContent = euro(final.total);
}
['pi-capital', 'pi-monthly', 'pi-rate', 'pi-years'].forEach((id) => document.getElementById(id).addEventListener('input', calc));
calc();
</script>

</body>
</html>
