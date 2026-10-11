<?php
$active = 'calcolatori';
require_once __DIR__ . '/_news_db.php';
$relatedArticles = news_related(['regola-50-30-20-vs-budget-per-categoria', 'obiettivi-risparmio-come-fissarli-e-raggiungerli']);
?>
<!DOCTYPE html>
<html lang="it">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Calcolatore regola 50/30/20 — Reflexi</title>
<meta name="description" content="Suddividi il tuo reddito netto mensile tra necessità, desideri e risparmio con la regola 50/30/20. Gratuito, senza registrazione.">
<meta name="robots" content="index, follow">
<link rel="canonical" href="https://reflexi.it/calcolatore-budget-503020">
<!-- Open Graph (Facebook, WhatsApp, LinkedIn, Telegram...) -->
<meta property="og:type" content="website">
<meta property="og:url" content="https://reflexi.it/calcolatore-budget-503020">
<meta property="og:site_name" content="Reflexi">
<meta property="og:title" content="Calcolatore regola 50/30/20 — Reflexi">
<meta property="og:description" content="Suddividi il tuo reddito netto mensile tra necessità, desideri e risparmio con la regola 50/30/20. Gratuito, senza registrazione.">
<meta property="og:image" content="<?php require_once __DIR__ . '/_seo.php'; echo seo_og_image(); ?>">
<meta property="og:image:width" content="1200">
<meta property="og:image:height" content="630">
<meta property="og:locale" content="it_IT">

<!-- Twitter/X Card -->
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="Calcolatore regola 50/30/20 — Reflexi">
<meta name="twitter:description" content="Suddividi il tuo reddito netto mensile tra necessità, desideri e risparmio con la regola 50/30/20. Gratuito, senza registrazione.">
<meta name="twitter:image" content="<?php require_once __DIR__ . '/_seo.php'; echo seo_og_image(); ?>">
<link rel="icon" type="image/svg+xml" href="/favicon.svg">
<link rel="preload" href="/fonts/inter-v20-latin.woff2" as="font" type="font/woff2" crossorigin>
<link rel="stylesheet" href="/css/site.css?v=<?= filemtime(__DIR__ . '/css/site.css') ?>">
<style>
  body { font-family: 'Inter', ui-sans-serif, system-ui, sans-serif; } .font-mono { font-family: 'IBM Plex Mono', ui-monospace, monospace; }
</style>
<?php
require_once __DIR__ . '/_seo.php';
seo_breadcrumb([['Calcolatori', '/calcolatori'], ['Calcolatore regola 50/30/20', '/calcolatore-budget-503020']]);
seo_calculator('Calcolatore regola 50/30/20', '/calcolatore-budget-503020', 'Suddividi il tuo reddito netto mensile tra necessità, desideri e risparmio con la regola 50/30/20. Gratuito, senza registrazione.');
?>
</head>
<body class="bg-bg text-gray-900 antialiased">

<?php include '_nav.php'; ?>

<section class="px-6 pb-6 pt-14 text-center sm:pt-16">
  <a href="/calcolatori" class="mb-4 inline-block text-sm text-muted hover:text-accent">← Tutti i calcolatori</a>
  <div class="mx-auto mb-3 flex h-14 w-14 items-center justify-center rounded-2xl bg-income/10 text-3xl">🥧</div>
  <h1 class="mx-auto mb-3 max-w-2xl text-3xl font-extrabold leading-tight tracking-tight sm:text-4xl">Calcolatore regola 50/30/20</h1>
  <p class="mx-auto max-w-xl text-lg text-muted">Una suddivisione di base del reddito netto: 50% necessità, 30% desideri, 20% risparmio.</p>
</section>

<main class="mx-auto max-w-3xl px-6 py-8">
  <div class="rounded-2xl border border-border bg-white p-6 shadow-sm sm:p-8">
    <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-muted">Reddito netto mensile (€)</label>
    <input id="bud-income" type="number" value="2000" min="0" class="w-full max-w-xs rounded-xl border border-border px-3.5 py-2.5 text-sm outline-none focus:border-accent focus:ring-4 focus:ring-accent/10">

    <div class="mt-6 flex flex-col items-center gap-6 sm:flex-row sm:justify-center">
      <div id="bud-donut" class="h-40 w-40 flex-shrink-0 rounded-full" style="background: conic-gradient(#4f46e5 0% 50%, #f59e0b 50% 80%, #10b981 80% 100%)"></div>
      <div class="space-y-3">
        <div class="flex items-center gap-2"><span class="h-3 w-3 rounded-full bg-accent"></span><span class="w-28 text-sm">Necessità</span><span class="font-mono font-semibold" id="bud-out-needs">—</span></div>
        <div class="flex items-center gap-2"><span class="h-3 w-3 rounded-full bg-loan"></span><span class="w-28 text-sm">Desideri</span><span class="font-mono font-semibold" id="bud-out-wants">—</span></div>
        <div class="flex items-center gap-2"><span class="h-3 w-3 rounded-full bg-income"></span><span class="w-28 text-sm">Risparmio</span><span class="font-mono font-semibold text-income" id="bud-out-savings">—</span></div>
      </div>
    </div>
  </div>

  <div class="mt-10 space-y-4 text-[15px] leading-relaxed text-gray-700">
    <h2 class="text-xl font-bold text-gray-900">Cos'è la regola 50/30/20</h2>
    <p>È una linea guida di bilancio popolare (attribuita alla senatrice ed ex professoressa di Harvard Elizabeth Warren) che suddivide il reddito netto in tre categorie: il <strong>50%</strong> per le necessità (affitto/mutuo, bollette, spesa, trasporti), il <strong>30%</strong> per i desideri (ristoranti, svago, abbonamenti non essenziali), il <strong>20%</strong> per risparmio e investimenti (o per ripagare debiti oltre al minimo dovuto).</p>
    <p>Non è una regola universale — chi vive in una città con affitti molto alti potrebbe dover sforare il 50% per le necessità, chi ha un reddito molto alto potrebbe risparmiare molto più del 20%. È un punto di partenza per riflettere, non un vincolo rigido.</p>
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
  <h2 class="mb-2 text-xl font-bold">Vuoi un budget su misura, non solo tre fette generiche?</h2>
  <p class="mb-6 text-muted">Reflexi ti permette di impostare un limite per ogni categoria reale che usi, con avvisi prima di sforare.</p>
  <a href="/budget" class="inline-block rounded-xl bg-accent px-7 py-3 text-sm font-semibold text-white shadow-lg shadow-accent/25 transition hover:bg-accentDark">Scopri il budget su Reflexi</a>
</section>

<?php include '_footer.php'; ?>

<script>
function euro(n) {
  if (!isFinite(n)) return '—';
  return new Intl.NumberFormat('it-IT', { style: 'currency', currency: 'EUR', maximumFractionDigits: 0 }).format(n);
}
function calc() {
  const income = parseFloat(document.getElementById('bud-income').value) || 0;
  document.getElementById('bud-out-needs').textContent = euro(income * 0.5);
  document.getElementById('bud-out-wants').textContent = euro(income * 0.3);
  document.getElementById('bud-out-savings').textContent = euro(income * 0.2);
}
document.getElementById('bud-income').addEventListener('input', calc);
calc();
</script>

</body>
</html>
