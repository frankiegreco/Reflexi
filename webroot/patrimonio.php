<!DOCTYPE html>
<html lang="it">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Patrimonio netto e report — Reflexi</title>
<meta name="description" content="Patrimonio netto di conti, investimenti e immobili meno i mutui, con lo storico nel tempo. Report su entrate, uscite e spesa per categoria ed esercente.">
<meta name="robots" content="index, follow">
<link rel="canonical" href="https://reflexi.it/patrimonio">
<!-- Open Graph (Facebook, WhatsApp, LinkedIn, Telegram...) -->
<meta property="og:type" content="website">
<meta property="og:url" content="https://reflexi.it/patrimonio">
<meta property="og:site_name" content="Reflexi">
<meta property="og:title" content="Patrimonio netto e report — Reflexi">
<meta property="og:description" content="Patrimonio netto di conti, investimenti e immobili meno i mutui, con lo storico nel tempo. Report su entrate, uscite e spesa per categoria ed esercente.">
<meta property="og:image" content="<?php require_once __DIR__ . '/_seo.php'; echo seo_og_image(); ?>">
<meta property="og:image:width" content="1200">
<meta property="og:image:height" content="630">
<meta property="og:locale" content="it_IT">

<!-- Twitter/X Card -->
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="Patrimonio netto e report — Reflexi">
<meta name="twitter:description" content="Patrimonio netto di conti, investimenti e immobili meno i mutui, con lo storico nel tempo. Report su entrate, uscite e spesa per categoria ed esercente.">
<meta name="twitter:image" content="<?php require_once __DIR__ . '/_seo.php'; echo seo_og_image(); ?>">
<link rel="icon" type="image/svg+xml" href="/favicon.svg">
<link rel="preload" href="/fonts/inter-v20-latin.woff2" as="font" type="font/woff2" crossorigin>
<link rel="stylesheet" href="/css/site.css?v=<?= filemtime(__DIR__ . '/css/site.css') ?>">
<?php
require_once __DIR__ . '/_seo.php';
seo_breadcrumb([['Patrimonio e report', '/patrimonio']]);
?>
</head>
<body class="bg-bg text-gray-900 antialiased">

<?php
$active = 'patrimonio';
require_once __DIR__ . '/_news_db.php';
$relatedArticles = news_related(['patrimonio-netto-cos-e-perche-seguirlo-nel-tempo', 'tasso-di-risparmio-cosa-e-come-calcolarlo', 'inflazione-potere-acquisto-perche-il-capitale-deve-crescere']);
include '_nav.php';
?>

<!-- HERO -->
<section class="px-6 pb-16 pt-14 sm:pt-20">
  <div class="mx-auto grid max-w-5xl items-center gap-10 sm:grid-cols-2">
    <div>
      <span class="mb-4 inline-block rounded-full bg-accent/10 text-accent px-4 py-1.5 text-xs font-semibold uppercase tracking-wider">Patrimonio e report</span>
      <h1 class="mb-4 text-3xl font-extrabold leading-tight tracking-tight sm:text-4xl">Il quadro intero, immobili inclusi</h1>
      <p class="mb-6 text-lg text-muted">Conti, investimenti e immobili, meno il debito residuo dei mutui: il tuo patrimonio netto in un numero solo, e come cambia mese dopo mese. Con i report per capire da dove arriva ogni variazione.</p>
      <a href="/app/" class="inline-block rounded-xl bg-accent px-7 py-3.5 text-base font-semibold text-white shadow-lg shadow-accent/30 transition hover:bg-accentDark">Prova gratis</a>
    </div>
    <div class="flex justify-center">
      <div class="w-[260px] rounded-[2.5rem] border-[10px] border-gray-900 bg-gray-900 shadow-2xl shadow-gray-400/40">
        <div class="relative h-[520px] overflow-hidden rounded-[1.75rem] bg-bg">
          <div class="absolute left-1/2 top-0 z-10 h-5 w-24 -translate-x-1/2 rounded-b-2xl bg-gray-900"></div>
          <div class="h-full overflow-hidden px-3 pb-3 pt-7">
            <div class="mb-2 text-xs font-bold">Report</div>
            <div class="rounded-xl border border-border bg-white p-3">
              <div class="text-[8px] uppercase text-muted">Patrimonio netto</div>
              <div class="font-mono text-lg font-semibold">€ 148.320</div>
              <div class="text-[9px] font-semibold text-income">+€ 9.410 negli ultimi 12 mesi</div>
              <svg aria-hidden="true" viewBox="0 0 200 50" class="mt-2 h-12 w-full"><polyline fill="none" stroke="#4f46e5" stroke-width="2" points="0,42 18,40 36,38 54,39 72,34 90,31 108,30 126,26 144,23 162,22 180,16 200,12"/></svg>
            </div>
            <div class="mt-2 rounded-xl border border-border bg-white px-3 py-1.5">
              <div class="flex items-center justify-between border-b border-border py-1.5 text-[9px] last:border-0"><span class="text-gray-700">Conti e liquidità</span><span class="font-mono font-semibold text-income">€ 24.300</span></div><div class="flex items-center justify-between border-b border-border py-1.5 text-[9px] last:border-0"><span class="text-gray-700">Investimenti</span><span class="font-mono font-semibold text-income">€ 41.870</span></div><div class="flex items-center justify-between border-b border-border py-1.5 text-[9px] last:border-0"><span class="text-gray-700">Immobili</span><span class="font-mono font-semibold text-income">€ 210.000</span></div><div class="flex items-center justify-between border-b border-border py-1.5 text-[9px] last:border-0"><span class="text-gray-700">Mutui residui</span><span class="font-mono font-semibold text-expense">−€ 127.850</span></div>
            </div>
            <div class="mt-2 grid grid-cols-2 gap-2">
            <div class="rounded-lg border border-border bg-white p-2">
              <div class="text-[8px] uppercase text-muted">Entrate (mese)</div>
              <div class="font-mono text-xs font-semibold text-income">€ 3.420</div>
            </div>
            <div class="rounded-lg border border-border bg-white p-2">
              <div class="text-[8px] uppercase text-muted">Uscite (mese)</div>
              <div class="font-mono text-xs font-semibold text-expense">€ 2.380</div>
            </div>
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
        <div><span class="font-semibold">Patrimonio netto completo</span><p class="text-sm text-muted">Liquidità dei conti, valore aggiornato degli investimenti e degli immobili, meno il debito residuo dei mutui collegati.</p></div>
      </div>
      <div class="flex gap-3">
        <span class="mt-1 text-income">✓</span>
        <div><span class="font-semibold">Andamento nel tempo</span><p class="text-sm text-muted">Un'istantanea del patrimonio viene salvata ogni giorno, così vedi l'andamento reale degli ultimi mesi e non solo il valore di oggi.</p></div>
      </div>
      <div class="flex gap-3">
        <span class="mt-1 text-income">✓</span>
        <div><span class="font-semibold">Immobili con il loro mutuo</span><p class="text-sm text-muted">Inserisci il valore stimato di casa o altri immobili e collega il mutuo: il patrimonio considera solo la parte che è davvero tua.</p></div>
      </div>
      <div class="flex gap-3">
        <span class="mt-1 text-income">✓</span>
        <div><span class="font-semibold">Report su entrate e uscite</span><p class="text-sm text-muted">Entrate contro uscite, spesa per categoria e per esercente, su periodi da 3 a 24 mesi.</p></div>
      </div>
      <div class="flex gap-3">
        <span class="mt-1 text-income">✓</span>
        <div><span class="font-semibold">Conti che maturano interessi</span><p class="text-sm text-muted">Conti deposito e conti remunerati con tasso e tassazione: gli interessi maturati entrano da soli nel saldo e quindi nel patrimonio.</p></div>
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
    <h2 class="mb-3 text-2xl font-bold sm:text-3xl">Guarda il tuo patrimonio come è davvero</h2>
    <p class="mb-8 text-white/80">6 mesi gratis, senza carta di credito.</p>
    <a href="/app/" class="inline-block rounded-xl bg-white px-8 py-3.5 text-base font-semibold text-accent shadow-lg transition hover:bg-gray-50">Crea il tuo account</a>
  </div>
</section>

<?php include '_footer.php'; ?>

</body>
</html>
