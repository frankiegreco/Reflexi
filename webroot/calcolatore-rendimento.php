<?php
$active = 'calcolatori';
require_once __DIR__ . '/_news_db.php';
$relatedArticles = news_related(['rendimento-totale-vs-cagr-come-leggere-la-performance', 'tassazione-investimenti-etf-azioni-italia']);
?>
<!DOCTYPE html>
<html lang="it">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Calcolatore di rendimento (ROI) — Reflexi</title>
<meta name="description" content="Calcola il rendimento totale e annualizzato (CAGR) di un investimento da valore iniziale, valore finale e durata in anni. Gratuito, senza registrazione.">
<meta name="robots" content="index, follow">
<link rel="canonical" href="https://reflexi.it/calcolatore-rendimento">
<!-- Open Graph (Facebook, WhatsApp, LinkedIn, Telegram...) -->
<meta property="og:type" content="website">
<meta property="og:url" content="https://reflexi.it/calcolatore-rendimento">
<meta property="og:site_name" content="Reflexi">
<meta property="og:title" content="Calcolatore di rendimento (ROI) — Reflexi">
<meta property="og:description" content="Calcola il rendimento totale e annualizzato (CAGR) di un investimento da valore iniziale, valore finale e durata in anni. Gratuito, senza registrazione.">
<meta property="og:image" content="https://reflexi.it/og-image.png?v=3">
<meta property="og:image:width" content="1200">
<meta property="og:image:height" content="630">
<meta property="og:locale" content="it_IT">

<!-- Twitter/X Card -->
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="Calcolatore di rendimento (ROI) — Reflexi">
<meta name="twitter:description" content="Calcola il rendimento totale e annualizzato (CAGR) di un investimento da valore iniziale, valore finale e durata in anni. Gratuito, senza registrazione.">
<meta name="twitter:image" content="https://reflexi.it/og-image.png?v=3">
<link rel="icon" type="image/svg+xml" href="/favicon.svg">
<link rel="preload" href="/fonts/inter-v20-latin.woff2" as="font" type="font/woff2" crossorigin>
<link rel="stylesheet" href="/css/site.css?v=<?= filemtime(__DIR__ . '/css/site.css') ?>">
<style>
  body { font-family: 'Inter', ui-sans-serif, system-ui, sans-serif; } .font-mono { font-family: 'IBM Plex Mono', ui-monospace, monospace; }
</style>
<?php
require_once __DIR__ . '/_seo.php';
seo_breadcrumb([['Calcolatori', '/calcolatori'], ['Calcolatore di rendimento (ROI)', '/calcolatore-rendimento']]);
seo_calculator('Calcolatore di rendimento (ROI)', '/calcolatore-rendimento', 'Calcola il rendimento totale e annualizzato (CAGR) di un investimento da valore iniziale, valore finale e durata in anni. Gratuito, senza registrazione.');
?>
</head>
<body class="bg-bg text-gray-900 antialiased">

<?php include '_nav.php'; ?>

<section class="px-6 pb-6 pt-14 text-center sm:pt-16">
  <a href="/calcolatori" class="mb-4 inline-block text-sm text-muted hover:text-accent">← Tutti i calcolatori</a>
  <div class="mx-auto mb-3 flex h-14 w-14 items-center justify-center rounded-2xl bg-income/10 text-3xl">📊</div>
  <h1 class="mx-auto mb-3 max-w-2xl text-3xl font-extrabold leading-tight tracking-tight sm:text-4xl">Calcolatore di rendimento (ROI)</h1>
  <p class="mx-auto max-w-xl text-lg text-muted">Quanto ha reso davvero un investimento, in totale e su base annua (CAGR).</p>
</section>

<main class="mx-auto max-w-3xl px-6 py-8">
  <div class="rounded-2xl border border-border bg-white p-6 shadow-sm sm:p-8">
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
      <div>
        <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-muted">Valore iniziale (€)</label>
        <input id="roi-initial" type="number" value="10000" min="0" class="w-full rounded-xl border border-border px-3.5 py-2.5 text-sm outline-none focus:border-accent focus:ring-4 focus:ring-accent/10">
      </div>
      <div>
        <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-muted">Valore finale (€)</label>
        <input id="roi-final" type="number" value="13500" min="0" class="w-full rounded-xl border border-border px-3.5 py-2.5 text-sm outline-none focus:border-accent focus:ring-4 focus:ring-accent/10">
      </div>
      <div>
        <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-muted">Durata (anni)</label>
        <input id="roi-years" type="number" value="3" min="0.1" step="0.1" class="w-full rounded-xl border border-border px-3.5 py-2.5 text-sm outline-none focus:border-accent focus:ring-4 focus:ring-accent/10">
      </div>
    </div>

    <div class="mt-6 flex items-center justify-center gap-4">
      <div class="rounded-xl border border-border bg-white px-6 py-4 text-center">
        <div class="text-[10px] uppercase text-muted">Iniziale</div>
        <div class="font-mono text-lg font-semibold" id="roi-visual-initial">—</div>
      </div>
      <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="flex-shrink-0 text-accent"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
      <div class="rounded-xl border-2 border-accent bg-accentSoft px-6 py-4 text-center">
        <div class="text-[10px] uppercase text-accent">Finale</div>
        <div class="font-mono text-lg font-bold text-accent" id="roi-visual-final">—</div>
      </div>
    </div>

    <div class="mt-6 grid grid-cols-1 gap-3 sm:grid-cols-2">
      <div class="rounded-xl border border-border p-4 text-center"><div class="text-[11px] uppercase text-muted">Rendimento totale</div><div id="roi-out-total" class="font-mono text-2xl font-semibold text-income">—</div></div>
      <div class="rounded-xl border-2 border-accent bg-accentSoft p-4 text-center"><div class="text-[11px] uppercase text-accent">Rendimento annuo (CAGR)</div><div id="roi-out-annual" class="font-mono text-2xl font-bold text-accent">—</div></div>
    </div>
  </div>

  <div class="mt-10 space-y-4 text-[15px] leading-relaxed text-gray-700">
    <h2 class="text-xl font-bold text-gray-900">Rendimento totale vs rendimento annuo: perché non sono la stessa cosa</h2>
    <p>Il <strong>rendimento totale</strong> ti dice quanto è cresciuto il tuo capitale dall'inizio alla fine, senza considerare quanto tempo ci è voluto. Il <strong>CAGR</strong> (Compound Annual Growth Rate, tasso di crescita annuo composto) invece "spalma" quel rendimento su base annua, permettendoti di confrontare investimenti con durate diverse in modo equo.</p>
    <p>Esempio: un rendimento totale del 35% in 3 anni non è lo stesso di un 35% in 10 anni — il primo corrisponde a circa il 10,5% annuo composto, il secondo a circa il 3%. Il CAGR è lo strumento giusto per questo tipo di confronto.</p>
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
  <h2 class="mb-2 text-xl font-bold">Vuoi il rendimento reale del tuo portafoglio, calcolato da solo?</h2>
  <p class="mb-6 text-muted">Reflexi calcola plus e minusvalenze con il costo medio di carico, aggiornato ad ogni quotazione.</p>
  <a href="/investimenti" class="inline-block rounded-xl bg-accent px-7 py-3 text-sm font-semibold text-white shadow-lg shadow-accent/25 transition hover:bg-accentDark">Scopri gli investimenti su Reflexi</a>
</section>

<?php include '_footer.php'; ?>

<script>
function euro(n) {
  if (!isFinite(n)) return '—';
  return new Intl.NumberFormat('it-IT', { style: 'currency', currency: 'EUR', maximumFractionDigits: 0 }).format(n);
}
function calc() {
  const initial = parseFloat(document.getElementById('roi-initial').value) || 0;
  const final = parseFloat(document.getElementById('roi-final').value) || 0;
  const years = parseFloat(document.getElementById('roi-years').value) || 0;

  const totalReturn = initial > 0 ? ((final - initial) / initial) * 100 : 0;
  const cagr = (initial > 0 && years > 0) ? (Math.pow(final / initial, 1 / years) - 1) * 100 : 0;

  document.getElementById('roi-visual-initial').textContent = euro(initial);
  document.getElementById('roi-visual-final').textContent = euro(final);
  document.getElementById('roi-out-total').textContent = totalReturn.toFixed(1) + '%';
  document.getElementById('roi-out-annual').textContent = cagr.toFixed(1) + '%';
}
['roi-initial', 'roi-final', 'roi-years'].forEach((id) => document.getElementById(id).addEventListener('input', calc));
calc();
</script>

</body>
</html>
