<!DOCTYPE html>
<html lang="it">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Obiettivi di risparmio — Reflexi</title>
<meta name="description" content="Obiettivi di risparmio con importo e scadenza: progressi sul risparmio reale e un piano mensile per arrivarci, con le categorie dove recuperare margine.">
<meta name="robots" content="index, follow">
<link rel="canonical" href="https://reflexi.it/obiettivi.php">
<!-- Open Graph (Facebook, WhatsApp, LinkedIn, Telegram...) -->
<meta property="og:type" content="website">
<meta property="og:url" content="https://reflexi.it/obiettivi.php">
<meta property="og:site_name" content="Reflexi">
<meta property="og:title" content="Obiettivi di risparmio — Reflexi">
<meta property="og:description" content="Obiettivi di risparmio con importo e scadenza: progressi sul risparmio reale e un piano mensile per arrivarci, con le categorie dove recuperare margine.">
<meta property="og:image" content="https://reflexi.it/og-image.png?v=3">
<meta property="og:image:width" content="1200">
<meta property="og:image:height" content="630">
<meta property="og:locale" content="it_IT">

<!-- Twitter/X Card -->
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="Obiettivi di risparmio — Reflexi">
<meta name="twitter:description" content="Obiettivi di risparmio con importo e scadenza: progressi sul risparmio reale e un piano mensile per arrivarci, con le categorie dove recuperare margine.">
<meta name="twitter:image" content="https://reflexi.it/og-image.png?v=3">
<link rel="icon" type="image/svg+xml" href="/favicon.svg">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=IBM+Plex+Mono:wght@500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="/css/site.css?v=<?= filemtime(__DIR__ . '/css/site.css') ?>">
</head>
<body class="bg-bg text-gray-900 antialiased">

<?php
$active = 'obiettivi';
require_once __DIR__ . '/_news_db.php';
$relatedArticles = news_related(['obiettivi-risparmio-come-fissarli-e-raggiungerli', 'fondo-di-emergenza-quanto-tenere-da-parte', 'tasso-di-risparmio-cosa-e-come-calcolarlo']);
include '_nav.php';
?>

<!-- HERO -->
<section class="px-6 pb-16 pt-14 sm:pt-20">
  <div class="mx-auto grid max-w-5xl items-center gap-10 sm:grid-cols-2">
    <div>
      <span class="mb-4 inline-block rounded-full bg-income/10 text-income px-4 py-1.5 text-xs font-semibold uppercase tracking-wider">Obiettivi</span>
      <h1 class="mb-4 text-3xl font-extrabold leading-tight tracking-tight sm:text-4xl">Un traguardo, non solo un saldo</h1>
      <p class="mb-6 text-lg text-muted">Fondo di emergenza, acconto per la casa, una vacanza: dai a ogni obiettivo un importo e una scadenza, e vedi quanto ti manca calcolato sul risparmio reale. Se non sei in linea, l'app ti dice quanto mettere da parte al mese e dove trovare il margine.</p>
      <a href="/app/" class="inline-block rounded-xl bg-accent px-7 py-3.5 text-base font-semibold text-white shadow-lg shadow-accent/30 transition hover:bg-accentDark">Prova gratis</a>
    </div>
    <div class="flex justify-center">
      <div class="w-[260px] rounded-[2.5rem] border-[10px] border-gray-900 bg-gray-900 shadow-2xl shadow-gray-400/40">
        <div class="relative h-[520px] overflow-hidden rounded-[1.75rem] bg-bg">
          <div class="absolute left-1/2 top-0 z-10 h-5 w-24 -translate-x-1/2 rounded-b-2xl bg-gray-900"></div>
          <div class="h-full overflow-hidden px-3 pb-3 pt-7">
            <div class="mb-2 text-xs font-bold">Obiettivi di risparmio</div>
            <div class="rounded-xl border border-border bg-white p-3">
              <div class="flex justify-between text-[10px] font-bold"><span>🏠 Acconto casa</span><span class="font-mono">62%</span></div>
              <div class="mt-1.5 h-1.5 w-full overflow-hidden rounded-full bg-gray-200"><div class="h-full w-[62%] rounded-full bg-income"></div></div>
              <div class="mt-1 text-[8px] text-muted">€ 18.600 di € 30.000 · entro dicembre 2027</div>
            </div>
            <div class="mt-2 rounded-xl border border-border bg-white p-3">
              <div class="flex justify-between text-[10px] font-bold"><span>🛡️ Fondo emergenza</span><span class="font-mono">100%</span></div>
              <div class="mt-1.5 h-1.5 w-full overflow-hidden rounded-full bg-gray-200"><div class="h-full w-full rounded-full bg-income"></div></div>
              <div class="mt-1 text-[8px] text-income">Obiettivo raggiunto 🎉</div>
            </div>
            <div class="mt-2 rounded-xl border border-accent/30 bg-accentSoft p-3">
              <div class="text-[8px] font-semibold uppercase text-accent">Piano per l'acconto casa</div>
              <div class="mt-1 text-[9px] text-gray-700">Servono <span class="font-bold">€ 760 al mese</span> · oggi ne metti da parte € 610</div>
              <div class="mt-2 text-[8px] font-semibold uppercase text-muted">Dove recuperare margine</div>
              <div class="flex items-center justify-between border-b border-border py-1.5 text-[9px] last:border-0"><span class="text-gray-700">Ristoranti</span><span class="font-mono font-semibold ">−€ 90</span></div><div class="flex items-center justify-between border-b border-border py-1.5 text-[9px] last:border-0"><span class="text-gray-700">Abbonamenti</span><span class="font-mono font-semibold ">−€ 35</span></div><div class="flex items-center justify-between border-b border-border py-1.5 text-[9px] last:border-0"><span class="text-gray-700">Shopping</span><span class="font-mono font-semibold ">−€ 25</span></div>
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
        <div><span class="font-semibold">Importo e scadenza per ogni traguardo</span><p class="text-sm text-muted">Crea tutti gli obiettivi che vuoi, con una cifra da raggiungere e, se serve, una data entro cui arrivarci.</p></div>
      </div>
      <div class="flex gap-3">
        <span class="mt-1 text-income">✓</span>
        <div><span class="font-semibold">Progresso calcolato sul risparmio reale</span><p class="text-sm text-muted">Collega un conto dedicato, oppure lascia che l'app conti automaticamente quanto hai davvero risparmiato dalla data di inizio.</p></div>
      </div>
      <div class="flex gap-3">
        <span class="mt-1 text-income">✓</span>
        <div><span class="font-semibold">Un piano mensile per arrivarci</span><p class="text-sm text-muted">Quanto mettere da parte ogni mese per rispettare la scadenza, con un rendimento atteso che puoi modificare se i soldi sono investiti.</p></div>
      </div>
      <div class="flex gap-3">
        <span class="mt-1 text-income">✓</span>
        <div><span class="font-semibold">Dove recuperare margine</span><p class="text-sm text-muted">Se il piano richiede più di quanto risparmi oggi, l'app indica le categorie di spesa su cui intervenire e di quanto.</p></div>
      </div>
      <div class="flex gap-3">
        <span class="mt-1 text-income">✓</span>
        <div><span class="font-semibold">Collegato al resto</span><p class="text-sm text-muted">Gli obiettivi leggono le stesse transazioni di budget e analisi: nessun dato da inserire due volte.</p></div>
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
    <h2 class="mb-3 text-2xl font-bold sm:text-3xl">Dai una meta ai tuoi risparmi</h2>
    <p class="mb-8 text-white/80">6 mesi gratis, senza carta di credito.</p>
    <a href="/app/" class="inline-block rounded-xl bg-white px-8 py-3.5 text-base font-semibold text-accent shadow-lg transition hover:bg-gray-50">Crea il tuo account</a>
  </div>
</section>

<?php include '_footer.php'; ?>

</body>
</html>
