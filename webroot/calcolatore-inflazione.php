<?php
$active = 'calcolatori';
require_once __DIR__ . '/_news_db.php';
$relatedArticles = news_related(['inflazione-potere-acquisto-perche-il-capitale-deve-crescere', 'tasso-di-risparmio-cosa-e-come-calcolarlo']);
?>
<!DOCTYPE html>
<html lang="it">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Calcolatore di inflazione e potere d'acquisto — Reflexi</title>
<meta name="description" content="Scopri quanto varrà davvero una somma di denaro tra qualche anno, al netto dell'inflazione. Gratuito, senza registrazione.">
<meta name="robots" content="index, follow">
<link rel="canonical" href="https://reflexi.it/calcolatore-inflazione">
<!-- Open Graph (Facebook, WhatsApp, LinkedIn, Telegram...) -->
<meta property="og:type" content="website">
<meta property="og:url" content="https://reflexi.it/calcolatore-inflazione">
<meta property="og:site_name" content="Reflexi">
<meta property="og:title" content="Calcolatore di inflazione e potere d'acquisto — Reflexi">
<meta property="og:description" content="Scopri quanto varrà davvero una somma di denaro tra qualche anno, al netto dell'inflazione. Gratuito, senza registrazione.">
<meta property="og:image" content="<?php require_once __DIR__ . '/_seo.php'; echo seo_og_image(); ?>">
<meta property="og:image:width" content="1200">
<meta property="og:image:height" content="630">
<meta property="og:locale" content="it_IT">

<!-- Twitter/X Card -->
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="Calcolatore di inflazione e potere d'acquisto — Reflexi">
<meta name="twitter:description" content="Scopri quanto varrà davvero una somma di denaro tra qualche anno, al netto dell'inflazione. Gratuito, senza registrazione.">
<meta name="twitter:image" content="<?php require_once __DIR__ . '/_seo.php'; echo seo_og_image(); ?>">
<link rel="icon" type="image/svg+xml" href="/favicon.svg">
<link rel="preload" href="/fonts/inter-v20-latin.woff2" as="font" type="font/woff2" crossorigin>
<link rel="stylesheet" href="/css/site.css?v=<?= filemtime(__DIR__ . '/css/site.css') ?>">
<style>
  body { font-family: 'Inter', ui-sans-serif, system-ui, sans-serif; } .font-mono { font-family: 'IBM Plex Mono', ui-monospace, monospace; }
</style>
<?php
require_once __DIR__ . '/_seo.php';
seo_breadcrumb([['Calcolatori', '/calcolatori'], ['Calcolatore inflazione', '/calcolatore-inflazione']]);
seo_calculator('Calcolatore inflazione', '/calcolatore-inflazione', 'Scopri quanto varrà davvero una somma di denaro tra qualche anno, al netto dell\'inflazione. Gratuito, senza registrazione.');
?>
</head>
<body class="bg-bg text-gray-900 antialiased">

<?php include '_nav.php'; ?>

<section class="px-6 pb-6 pt-14 text-center sm:pt-16">
  <a href="/calcolatori" class="mb-4 inline-block text-sm text-muted hover:text-accent">← Tutti i calcolatori</a>
  <div class="mx-auto mb-3 flex h-14 w-14 items-center justify-center rounded-2xl bg-loan/10 text-3xl">📉</div>
  <h1 class="mx-auto mb-3 max-w-2xl text-3xl font-extrabold leading-tight tracking-tight sm:text-4xl">Calcolatore di potere d'acquisto (inflazione)</h1>
  <p class="mx-auto max-w-xl text-lg text-muted">Quanto varrà davvero una somma tra qualche anno, al netto dell'inflazione.</p>
</section>

<main class="mx-auto max-w-3xl px-6 py-8">
  <div class="rounded-2xl border border-border bg-white p-6 shadow-sm sm:p-8">
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
      <div>
        <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-muted">Importo attuale (€)</label>
        <input id="inf-amount" type="number" value="10000" min="0" class="w-full rounded-xl border border-border px-3.5 py-2.5 text-sm outline-none focus:border-accent focus:ring-4 focus:ring-accent/10">
      </div>
      <div>
        <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-muted">Inflazione media annua (%)</label>
        <input id="inf-rate" type="number" value="2" step="0.1" min="0" class="w-full rounded-xl border border-border px-3.5 py-2.5 text-sm outline-none focus:border-accent focus:ring-4 focus:ring-accent/10">
      </div>
      <div>
        <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-muted">Tra quanti anni</label>
        <input id="inf-years" type="number" value="10" min="1" max="60" class="w-full rounded-xl border border-border px-3.5 py-2.5 text-sm outline-none focus:border-accent focus:ring-4 focus:ring-accent/10">
      </div>
    </div>

    <div class="mt-6 flex items-center justify-center gap-4">
      <div class="rounded-xl border border-border bg-white px-6 py-4 text-center">
        <div class="text-[10px] uppercase text-muted">Oggi</div>
        <div id="inf-visual-today" class="font-mono text-lg font-semibold">—</div>
      </div>
      <svg aria-hidden="true" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="flex-shrink-0 text-expense"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
      <div class="rounded-xl border-2 border-expense bg-expense/5 px-6 py-4 text-center">
        <div class="text-[10px] uppercase text-expense">Potere d'acquisto futuro</div>
        <div id="inf-out" class="font-mono text-lg font-bold text-expense">—</div>
      </div>
    </div>
  </div>

  <div class="mt-10 space-y-4 text-[15px] leading-relaxed text-gray-700">
    <h2 class="text-xl font-bold text-gray-900">Perché tenere i soldi fermi ha un costo nascosto</h2>
    <p>L'inflazione riduce nel tempo quello che puoi comprare con la stessa somma di denaro. 10.000€ tenuti fermi su un conto corrente che non matura interessi, con un'inflazione media del 2% l'anno, varranno realmente circa 8.200€ tra 10 anni in termini di potere d'acquisto — anche se il numero sul conto resta 10.000€.</p>
    <p>Questo non significa che tenere liquidità sia sbagliato (serve per le emergenze e le spese a breve termine), ma è il motivo per cui il risparmio di lungo periodo, se lasciato completamente infruttifero, perde valore reale nel tempo.</p>
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
  <h2 class="mb-2 text-xl font-bold">Fai lavorare i tuoi risparmi</h2>
  <p class="mb-6 text-muted">Reflexi traccia anche gli interessi su conti deposito e vincolati, con proiezione netta reale.</p>
  <a href="/app/?mode=register" class="inline-block rounded-xl bg-accent px-7 py-3 text-sm font-semibold text-white shadow-lg shadow-accent/25 transition hover:bg-accentDark">Prova Reflexi gratis</a>
  <p class="mt-4 text-sm text-muted">Come funziona in Reflexi: <a href="/patrimonio" class="font-semibold text-accent hover:underline">Patrimonio e report →</a></p>
</section>

<?php include '_footer.php'; ?>

<script>
function euro(n) {
  if (!isFinite(n)) return '—';
  return new Intl.NumberFormat('it-IT', { style: 'currency', currency: 'EUR', maximumFractionDigits: 0 }).format(n);
}
function calc() {
  const amount = parseFloat(document.getElementById('inf-amount').value) || 0;
  const rate = parseFloat(document.getElementById('inf-rate').value) || 0;
  const years = parseFloat(document.getElementById('inf-years').value) || 0;
  const realValue = amount / Math.pow(1 + rate / 100, years);
  document.getElementById('inf-visual-today').textContent = euro(amount);
  document.getElementById('inf-out').textContent = euro(realValue);
}
['inf-amount', 'inf-rate', 'inf-years'].forEach((id) => document.getElementById(id).addEventListener('input', calc));
calc();
</script>

</body>
</html>
