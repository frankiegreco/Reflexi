<!DOCTYPE html>
<html lang="it">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Analisi predittiva delle spese e Monte Carlo — Reflexi</title>
<meta name="description" content="Simulazione Monte Carlo del risparmio futuro, abbonamenti dimenticati scovati in automatico e avvisi prima di sforare il budget: una proiezione vera.">
<meta name="robots" content="index, follow">
<link rel="canonical" href="https://reflexi.it/analisi">
<!-- Open Graph (Facebook, WhatsApp, LinkedIn, Telegram...) -->
<meta property="og:type" content="website">
<meta property="og:url" content="https://reflexi.it/analisi">
<meta property="og:site_name" content="Reflexi">
<meta property="og:title" content="Analisi predittiva delle spese e Monte Carlo — Reflexi">
<meta property="og:description" content="Simulazione Monte Carlo del risparmio futuro, abbonamenti dimenticati scovati in automatico e avvisi prima di sforare il budget: una proiezione vera.">
<meta property="og:image" content="https://reflexi.it/og-image.png?v=3">
<meta property="og:image:width" content="1200">
<meta property="og:image:height" content="630">
<meta property="og:locale" content="it_IT">

<!-- Twitter/X Card -->
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="Analisi predittiva delle spese e Monte Carlo — Reflexi">
<meta name="twitter:description" content="Simulazione Monte Carlo del risparmio futuro, abbonamenti dimenticati scovati in automatico e avvisi prima di sforare il budget: una proiezione vera.">
<meta name="twitter:image" content="https://reflexi.it/og-image.png?v=3">
<link rel="icon" type="image/svg+xml" href="/favicon.svg">
<link rel="preload" href="/fonts/inter-v20-latin.woff2" as="font" type="font/woff2" crossorigin>
<link rel="stylesheet" href="/css/site.css?v=<?= filemtime(__DIR__ . '/css/site.css') ?>">
<?php
require_once __DIR__ . '/_seo.php';
seo_breadcrumb([['Analisi predittiva', '/analisi']]);
?>
</head>
<body class="bg-bg text-gray-900 antialiased">

<?php
$active = 'analisi';
require_once __DIR__ . '/_news_db.php';
$relatedArticles = news_related(['indipendenza-finanziaria-fire-come-calcolare-il-numero', 'abbonamenti-dimenticati-come-scovare-spese-ricorrenti', 'tasso-di-risparmio-cosa-e-come-calcolarlo']);
include '_nav.php';
?>

<!-- HERO -->
<section class="px-6 pb-16 pt-14 sm:pt-20">
  <div class="mx-auto grid max-w-5xl items-center gap-10 sm:grid-cols-2">
    <div>
      <span class="mb-4 inline-block rounded-full bg-accentSoft px-4 py-1.5 text-xs font-semibold uppercase tracking-wider text-accent">Analisi predittiva</span>
      <h1 class="mb-4 text-3xl font-extrabold leading-tight tracking-tight sm:text-4xl">Non una media, una simulazione vera</h1>
      <p class="mb-6 text-lg text-muted">Migliaia di scenari calcolati sulla tua reale variabilità storica (Monte Carlo), più il rilevamento automatico di abbonamenti che paghi senza saperlo.</p>
      <a href="/app/" class="inline-block rounded-xl bg-accent px-7 py-3.5 text-base font-semibold text-white shadow-lg shadow-accent/30 transition hover:bg-accentDark">Prova gratis</a>
    </div>
    <div class="flex justify-center">
      <div class="w-[260px] rounded-[2.5rem] border-[10px] border-gray-900 bg-gray-900 shadow-2xl shadow-gray-400/40">
        <div class="relative h-[520px] overflow-hidden rounded-[1.75rem] bg-bg">
          <div class="absolute left-1/2 top-0 z-10 h-5 w-24 -translate-x-1/2 rounded-b-2xl bg-gray-900"></div>
          <div class="h-full overflow-hidden px-3 pb-3 pt-7">
            <div class="mb-2 text-xs font-bold">Proiezione a 6 mesi</div>
            <div class="rounded-xl border border-border bg-white p-3">
              <div class="flex h-16 items-end gap-1">
                <div class="w-full rounded-t bg-accent/20" style="height:40%"></div>
                <div class="w-full rounded-t bg-accent/30" style="height:55%"></div>
                <div class="w-full rounded-t bg-accent/40" style="height:35%"></div>
                <div class="w-full rounded-t bg-accent/60" style="height:70%"></div>
                <div class="w-full rounded-t bg-accent/80" style="height:60%"></div>
                <div class="w-full rounded-t bg-accent" style="height:85%"></div>
              </div>
              <div class="mt-1 text-[8px] text-muted">78% probabilità di risparmio cumulato positivo</div>
            </div>
            <div class="mt-2 rounded-lg border border-amber-200 bg-amber-50 p-2">
              <div class="text-[8px] font-semibold uppercase text-amber-700">✨ Forse hai anche questo</div>
              <div class="mt-1 flex items-center justify-between">
                <span class="text-[9px]">Netflix — 27 volte rilevate</span>
                <span class="rounded bg-white px-1.5 py-0.5 text-[7px] font-bold text-accent">Dichiara</span>
              </div>
            </div>
            <div class="mt-2 rounded-lg border border-border bg-white p-2">
              <div class="flex justify-between text-[9px]"><span>Budget Ristoranti</span><span class="font-semibold text-expense">93%</span></div>
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
        <div><span class="font-semibold">Simulazione Monte Carlo</span><p class="text-sm text-muted">2.000 scenari calcolati sulla tua variabilità storica reale — una fascia di probabilità, non una singola cifra ottimista.</p></div>
      </div>
      <div class="flex gap-3">
        <span class="mt-1 text-income">✓</span>
        <div><span class="font-semibold">Rilevamento abbonamenti dimenticati</span><p class="text-sm text-muted">L'app individua da sola pattern di spesa regolari non dichiarati come ricorrenti — capita più spesso di quanto si pensi.</p></div>
      </div>
      <div class="flex gap-3">
        <span class="mt-1 text-income">✓</span>
        <div><span class="font-semibold">Avvisi di budget proattivi</span><p class="text-sm text-muted">Proiezione a fine mese basata sul ritmo di spesa attuale, non solo sul totale già speso.</p></div>
      </div>
      <div class="flex gap-3">
        <span class="mt-1 text-income">✓</span>
        <div><span class="font-semibold">Proiezione del mese in corso</span><p class="text-sm text-muted">Quanto probabilmente risparmierai (o sforerai) entro fine mese, aggiornato ogni giorno.</p></div>
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
    <h2 class="mb-3 text-2xl font-bold sm:text-3xl">Guarda il tuo futuro finanziario, non solo il passato</h2>
    <p class="mb-8 text-white/80">6 mesi gratis, senza carta di credito.</p>
    <a href="/app/" class="inline-block rounded-xl bg-white px-8 py-3.5 text-base font-semibold text-accent shadow-lg transition hover:bg-gray-50">Crea il tuo account</a>
  </div>
</section>

<?php include '_footer.php'; ?>

</body>
</html>
