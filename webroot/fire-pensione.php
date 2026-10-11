<!DOCTYPE html>
<html lang="it">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>FIRE e fondo pensione — Reflexi</title>
<meta name="description" content="Calcola il tuo numero FIRE dalla spesa reale e quanti anni mancano all&#x27;indipendenza finanziaria. Proietta i fondi pensione e il TFR fino all&#x27;età del pensionamento.">
<meta name="robots" content="index, follow">
<link rel="canonical" href="https://reflexi.it/fire-pensione.php">
<!-- Open Graph (Facebook, WhatsApp, LinkedIn, Telegram...) -->
<meta property="og:type" content="website">
<meta property="og:url" content="https://reflexi.it/fire-pensione.php">
<meta property="og:site_name" content="Reflexi">
<meta property="og:title" content="FIRE e fondo pensione — Reflexi">
<meta property="og:description" content="Calcola il tuo numero FIRE dalla spesa reale e quanti anni mancano all&#x27;indipendenza finanziaria. Proietta i fondi pensione e il TFR fino all&#x27;età del pensionamento.">
<meta property="og:image" content="https://reflexi.it/og-image.png?v=3">
<meta property="og:image:width" content="1200">
<meta property="og:image:height" content="630">
<meta property="og:locale" content="it_IT">

<!-- Twitter/X Card -->
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="FIRE e fondo pensione — Reflexi">
<meta name="twitter:description" content="Calcola il tuo numero FIRE dalla spesa reale e quanti anni mancano all&#x27;indipendenza finanziaria. Proietta i fondi pensione e il TFR fino all&#x27;età del pensionamento.">
<meta name="twitter:image" content="https://reflexi.it/og-image.png?v=3">
<link rel="icon" type="image/svg+xml" href="/favicon.svg">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=IBM+Plex+Mono:wght@500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="/css/site.css?v=<?= filemtime(__DIR__ . '/css/site.css') ?>">
</head>
<body class="bg-bg text-gray-900 antialiased">

<?php
$active = 'fire';
require_once __DIR__ . '/_news_db.php';
$relatedArticles = news_related(['indipendenza-finanziaria-fire-come-calcolare-il-numero', 'pensione-integrativa-fondo-pensione-quanto-serve', 'tfr-in-azienda-o-fondo-pensione']);
include '_nav.php';
?>

<!-- HERO -->
<section class="px-6 pb-16 pt-14 sm:pt-20">
  <div class="mx-auto grid max-w-5xl items-center gap-10 sm:grid-cols-2">
    <div>
      <span class="mb-4 inline-block rounded-full bg-loan/10 text-loan px-4 py-1.5 text-xs font-semibold uppercase tracking-wider">FIRE e pensione</span>
      <h1 class="mb-4 text-3xl font-extrabold leading-tight tracking-tight sm:text-4xl">Quanto ti manca all'indipendenza finanziaria</h1>
      <p class="mb-6 text-lg text-muted">Il numero FIRE calcolato sulla tua spesa reale degli ultimi dodici mesi, non su una stima a occhio, e gli anni che mancano al traguardo al ritmo di risparmio attuale. Accanto, i fondi pensione proiettati fino al pensionamento.</p>
      <a href="/app/" class="inline-block rounded-xl bg-accent px-7 py-3.5 text-base font-semibold text-white shadow-lg shadow-accent/30 transition hover:bg-accentDark">Prova gratis</a>
    </div>
    <div class="flex justify-center">
      <div class="w-[260px] rounded-[2.5rem] border-[10px] border-gray-900 bg-gray-900 shadow-2xl shadow-gray-400/40">
        <div class="relative h-[520px] overflow-hidden rounded-[1.75rem] bg-bg">
          <div class="absolute left-1/2 top-0 z-10 h-5 w-24 -translate-x-1/2 rounded-b-2xl bg-gray-900"></div>
          <div class="h-full overflow-hidden px-3 pb-3 pt-7">
            <div class="mb-2 text-xs font-bold">FIRE — Indipendenza finanziaria</div>
            <div class="rounded-xl border border-border bg-white p-3 text-center">
              <div class="text-[8px] font-semibold uppercase text-accent">Il tuo numero FIRE</div>
              <div class="font-mono text-lg font-bold text-accent">€ 690.000</div>
              <div class="text-[8px] text-muted">Spesa annua reale 27.600 € × 25</div>
              <div class="mt-2 h-1.5 w-full overflow-hidden rounded-full bg-gray-200"><div class="h-full w-[31%] rounded-full bg-accent"></div></div>
              <div class="mt-1 flex justify-between text-[8px] text-muted"><span>€ 213.900</span><span>31%</span></div>
            </div>
            <div class="mt-2 grid grid-cols-2 gap-2">
            <div class="rounded-lg border border-border bg-white p-2">
              <div class="text-[8px] uppercase text-muted">Anni al traguardo</div>
              <div class="font-mono text-xs font-semibold ">14,5</div>
            </div>
            <div class="rounded-lg border border-border bg-white p-2">
              <div class="text-[8px] uppercase text-muted">Risparmio mensile</div>
              <div class="font-mono text-xs font-semibold text-income">€ 1.250</div>
            </div>
            </div>
            <div class="mt-2 rounded-xl border border-border bg-white p-3">
              <div class="text-[8px] font-semibold uppercase text-muted">Fondo pensione · proiezione a 67 anni</div>
              <div class="font-mono text-sm font-semibold">€ 186.400</div>
              <div class="mt-1 text-[8px] text-muted">Fondo negoziale + TFR · 120 €/mese tuoi, 45 €/mese del datore</div>
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
        <div><span class="font-semibold">Il numero FIRE sulla tua spesa reale</span><p class="text-sm text-muted">Calcolato sulla spesa media effettiva degli ultimi dodici mesi, esclusi investimenti e trasferimenti tra conti.</p></div>
      </div>
      <div class="flex gap-3">
        <span class="mt-1 text-income">✓</span>
        <div><span class="font-semibold">Ipotesi che decidi tu</span><p class="text-sm text-muted">Tasso di prelievo sicuro (il 4% classico o uno più prudente) e rendimento atteso degli investimenti sono modificabili, e il risultato si aggiorna subito.</p></div>
      </div>
      <div class="flex gap-3">
        <span class="mt-1 text-income">✓</span>
        <div><span class="font-semibold">Anni al traguardo</span><p class="text-sm text-muted">Quanto manca all'indipendenza finanziaria con il risparmio mensile reale e il capitale investibile di oggi, liquidità e investimenti compresi.</p></div>
      </div>
      <div class="flex gap-3">
        <span class="mt-1 text-income">✓</span>
        <div><span class="font-semibold">Fondi pensione e TFR</span><p class="text-sm text-muted">Registri fondi negoziali, aperti, PIP o il TFR lasciato in azienda, con i tuoi versamenti e il contributo del datore di lavoro.</p></div>
      </div>
      <div class="flex gap-3">
        <span class="mt-1 text-income">✓</span>
        <div><span class="font-semibold">Proiezione al pensionamento</span><p class="text-sm text-muted">Quanto potrebbero valere i tuoi fondi all'età della pensione, separatamente dal resto del portafoglio, con un rimando al comparatore ufficiale COVIP.</p></div>
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
    <h2 class="mb-3 text-2xl font-bold sm:text-3xl">Scopri quanto ti manca davvero</h2>
    <p class="mb-8 text-white/80">6 mesi gratis, senza carta di credito.</p>
    <a href="/app/" class="inline-block rounded-xl bg-white px-8 py-3.5 text-base font-semibold text-accent shadow-lg transition hover:bg-gray-50">Crea il tuo account</a>
  </div>
</section>

<?php include '_footer.php'; ?>

</body>
</html>
