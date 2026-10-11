<!DOCTYPE html>
<html lang="it">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Simulazione trading con capitale virtuale — Reflexi</title>
<meta name="description" content="Simulatore di trading con capitale virtuale: compra e vendi azioni, ETF e crypto ai prezzi reali di mercato, con commissioni simulate e andamento del conto.">
<meta name="robots" content="index, follow">
<link rel="canonical" href="https://reflexi.it/simulazione-trading.php">
<!-- Open Graph (Facebook, WhatsApp, LinkedIn, Telegram...) -->
<meta property="og:type" content="website">
<meta property="og:url" content="https://reflexi.it/simulazione-trading.php">
<meta property="og:site_name" content="Reflexi">
<meta property="og:title" content="Simulazione trading con capitale virtuale — Reflexi">
<meta property="og:description" content="Simulatore di trading con capitale virtuale: compra e vendi azioni, ETF e crypto ai prezzi reali di mercato, con commissioni simulate e andamento del conto.">
<meta property="og:image" content="https://reflexi.it/og-image.png?v=3">
<meta property="og:image:width" content="1200">
<meta property="og:image:height" content="630">
<meta property="og:locale" content="it_IT">

<!-- Twitter/X Card -->
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="Simulazione trading con capitale virtuale — Reflexi">
<meta name="twitter:description" content="Simulatore di trading con capitale virtuale: compra e vendi azioni, ETF e crypto ai prezzi reali di mercato, con commissioni simulate e andamento del conto.">
<meta name="twitter:image" content="https://reflexi.it/og-image.png?v=3">
<link rel="icon" type="image/svg+xml" href="/favicon.svg">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=IBM+Plex+Mono:wght@500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="/css/site.css?v=<?= filemtime(__DIR__ . '/css/site.css') ?>">
<?php
require_once __DIR__ . '/_seo.php';
seo_breadcrumb([['Simulazione trading', '/simulazione-trading.php']]);
?>
</head>
<body class="bg-bg text-gray-900 antialiased">

<?php
$active = 'trading';
require_once __DIR__ . '/_news_db.php';
$relatedArticles = news_related(['pac-piano-accumulo-etf-come-funziona', 'recupero-perdite-perche-non-e-simmetrico', 'diversificazione-portafoglio-cosa-significa-davvero']);
include '_nav.php';
?>

<!-- HERO -->
<section class="px-6 pb-16 pt-14 sm:pt-20">
  <div class="mx-auto grid max-w-5xl items-center gap-10 sm:grid-cols-2">
    <div>
      <span class="mb-4 inline-block rounded-full bg-income/10 text-income px-4 py-1.5 text-xs font-semibold uppercase tracking-wider">Simulazione trading</span>
      <h1 class="mb-4 text-3xl font-extrabold leading-tight tracking-tight sm:text-4xl">Fai pratica in Borsa senza rischiare un euro</h1>
      <p class="mb-6 text-lg text-muted">Apri un conto simulato con il capitale virtuale che vuoi e compra o vendi azioni, ETF e crypto ai prezzi reali di mercato. Commissioni comprese, perché anche quelle fanno parte dell'esperienza vera.</p>
      <a href="/app/" class="inline-block rounded-xl bg-accent px-7 py-3.5 text-base font-semibold text-white shadow-lg shadow-accent/30 transition hover:bg-accentDark">Prova gratis</a>
    </div>
    <div class="flex justify-center">
      <div class="w-[260px] rounded-[2.5rem] border-[10px] border-gray-900 bg-gray-900 shadow-2xl shadow-gray-400/40">
        <div class="relative h-[520px] overflow-hidden rounded-[1.75rem] bg-bg">
          <div class="absolute left-1/2 top-0 z-10 h-5 w-24 -translate-x-1/2 rounded-b-2xl bg-gray-900"></div>
          <div class="h-full overflow-hidden px-3 pb-3 pt-7">
            <div class="mb-2 text-xs font-bold">Simulazione trading</div>
            <div class="rounded-xl border border-border bg-white p-3">
              <div class="text-[8px] uppercase text-muted">Strategia growth · valore totale</div>
              <div class="font-mono text-lg font-semibold">€ 10.842,30</div>
              <div class="text-[9px] font-semibold text-income">+8,42% dall'inizio</div>
              <svg viewBox="0 0 200 50" class="mt-2 h-12 w-full"><polyline fill="none" stroke="#10b981" stroke-width="2" points="0,40 20,38 40,42 60,33 80,35 100,27 120,30 140,20 160,22 180,12 200,10"/></svg>
            </div>
            <div class="mt-2 grid grid-cols-2 gap-2">
            <div class="rounded-lg border border-border bg-white p-2">
              <div class="text-[8px] uppercase text-muted">Liquidità virtuale</div>
              <div class="font-mono text-xs font-semibold ">€ 2.130</div>
            </div>
            <div class="rounded-lg border border-border bg-white p-2">
              <div class="text-[8px] uppercase text-muted">Valore posizioni</div>
              <div class="font-mono text-xs font-semibold text-income">€ 8.712</div>
            </div>
            </div>
            <div class="mt-2 rounded-xl border border-border bg-white px-3 py-1.5">
              <div class="py-1 text-[8px] font-semibold uppercase text-muted">Posizioni aperte</div>
              <div class="flex items-center justify-between border-b border-border py-1.5 text-[9px] last:border-0"><span class="text-gray-700">NVDA · 12 quote</span><span class="font-mono font-semibold text-income">+14,1%</span></div><div class="flex items-center justify-between border-b border-border py-1.5 text-[9px] last:border-0"><span class="text-gray-700">VWCE.MI · 30 quote</span><span class="font-mono font-semibold text-income">+4,3%</span></div><div class="flex items-center justify-between border-b border-border py-1.5 text-[9px] last:border-0"><span class="text-gray-700">BTC-USD · 0,02</span><span class="font-mono font-semibold text-expense">−2,6%</span></div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- DETTAGLIO -->
<section class="border-t border-border bg-white px-6 py-16">
  <div class="mx-auto max-w-3xl">
    <h2 class="mb-8 text-center text-2xl font-bold">Cosa puoi fare</h2>
    <div class="space-y-5">
      <div class="flex gap-3">
        <span class="mt-1 text-income">✓</span>
        <div><span class="font-semibold">Capitale virtuale, prezzi reali</span><p class="text-sm text-muted">Scegli con quanto partire e negozia ai prezzi di mercato veri di azioni, ETF e crypto: la sola cosa finta è il denaro.</p></div>
      </div>
      <div class="flex gap-3">
        <span class="mt-1 text-income">✓</span>
        <div><span class="font-semibold">Commissioni simulate</span><p class="text-sm text-muted">Ogni operazione ha un costo fisso, come presso un vero broker: impari perché tanti piccoli trade frequenti erodono il rendimento.</p></div>
      </div>
      <div class="flex gap-3">
        <span class="mt-1 text-income">✓</span>
        <div><span class="font-semibold">Andamento del conto nel tempo</span><p class="text-sm text-muted">Il grafico del valore del conto è ricostruito dai prezzi di chiusura storici reali, non da istantanee salvate a caso.</p></div>
      </div>
      <div class="flex gap-3">
        <span class="mt-1 text-income">✓</span>
        <div><span class="font-semibold">Più conti, più strategie</span><p class="text-sm text-muted">Apri conti separati per confrontare approcci diversi, ad esempio un portafoglio di ETF contro uno di singole azioni.</p></div>
      </div>
      <div class="flex gap-3">
        <span class="mt-1 text-income">✓</span>
        <div><span class="font-semibold">Separato dalle tue finanze reali</span><p class="text-sm text-muted">Nulla di quello che fai nel simulatore tocca saldi, patrimonio o budget: puoi sbagliare tutte le volte che vuoi.</p></div>
      </div>
    </div>

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
    <h2 class="mb-3 text-2xl font-bold sm:text-3xl">Impara a investire prima di farlo davvero</h2>
    <p class="mb-8 text-white/80">6 mesi gratis, senza carta di credito.</p>
    <a href="/app/" class="inline-block rounded-xl bg-white px-8 py-3.5 text-base font-semibold text-accent shadow-lg transition hover:bg-gray-50">Crea il tuo account</a>
  </div>
</section>

<?php include '_footer.php'; ?>

</body>
</html>
