<!DOCTYPE html>
<html lang="it">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Previsioni sugli investimenti — Reflexi</title>
<meta name="description" content="Stime di rendimento a 12 mesi da un modello statistico su prezzi reali, indicatori di rischio e una proposta di portafoglio, con i limiti dichiarati.">
<meta name="robots" content="index, follow">
<link rel="canonical" href="https://reflexi.it/previsioni">
<!-- Open Graph (Facebook, WhatsApp, LinkedIn, Telegram...) -->
<meta property="og:type" content="website">
<meta property="og:url" content="https://reflexi.it/previsioni">
<meta property="og:site_name" content="Reflexi">
<meta property="og:title" content="Previsioni sugli investimenti — Reflexi">
<meta property="og:description" content="Stime di rendimento a 12 mesi da un modello statistico su prezzi reali, indicatori di rischio e una proposta di portafoglio, con i limiti dichiarati.">
<meta property="og:image" content="https://reflexi.it/og-image.png?v=3">
<meta property="og:image:width" content="1200">
<meta property="og:image:height" content="630">
<meta property="og:locale" content="it_IT">

<!-- Twitter/X Card -->
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="Previsioni sugli investimenti — Reflexi">
<meta name="twitter:description" content="Stime di rendimento a 12 mesi da un modello statistico su prezzi reali, indicatori di rischio e una proposta di portafoglio, con i limiti dichiarati.">
<meta name="twitter:image" content="https://reflexi.it/og-image.png?v=3">
<link rel="icon" type="image/svg+xml" href="/favicon.svg">
<link rel="preload" href="/fonts/inter-v20-latin.woff2" as="font" type="font/woff2" crossorigin>
<link rel="stylesheet" href="/css/site.css?v=<?= filemtime(__DIR__ . '/css/site.css') ?>">
<?php
require_once __DIR__ . '/_seo.php';
seo_breadcrumb([['Previsioni', '/previsioni']]);
?>
</head>
<body class="bg-bg text-gray-900 antialiased">

<?php
$active = 'previsioni';
require_once __DIR__ . '/_news_db.php';
$relatedArticles = news_related(['diversificazione-portafoglio-cosa-significa-davvero', 'rendimento-totale-vs-cagr-come-leggere-la-performance', 'ter-costi-etf-quanto-incidono-sul-rendimento']);
include '_nav.php';
?>

<!-- HERO -->
<section class="px-6 pb-16 pt-14 sm:pt-20">
  <div class="mx-auto grid max-w-5xl items-center gap-10 sm:grid-cols-2">
    <div>
      <span class="mb-4 inline-block rounded-full bg-accent/10 text-accent px-4 py-1.5 text-xs font-semibold uppercase tracking-wider">Previsioni</span>
      <h1 class="mb-4 text-3xl font-extrabold leading-tight tracking-tight sm:text-4xl">Una stima a 12 mesi, con i suoi limiti scritti chiaro</h1>
      <p class="mb-6 text-lg text-muted">Per ogni strumento che possiedi, un modello statistico addestrato sullo storico prezzi stima il rendimento a 12 mesi, insieme a trend, volatilità e drawdown. Nessuna promessa: quanto il modello spiega davvero lo vedi accanto a ogni numero.</p>
      <a href="/app/" class="inline-block rounded-xl bg-accent px-7 py-3.5 text-base font-semibold text-white shadow-lg shadow-accent/30 transition hover:bg-accentDark">Prova gratis</a>
    </div>
    <div class="flex justify-center">
      <div class="w-[260px] rounded-[2.5rem] border-[10px] border-gray-900 bg-gray-900 shadow-2xl shadow-gray-400/40">
        <div class="relative h-[520px] overflow-hidden rounded-[1.75rem] bg-bg">
          <div class="absolute left-1/2 top-0 z-10 h-5 w-24 -translate-x-1/2 rounded-b-2xl bg-gray-900"></div>
          <div class="h-full overflow-hidden px-3 pb-3 pt-7">
            <div class="mb-2 text-xs font-bold">Previsioni</div>
            <div class="rounded-xl border border-border bg-white p-3">
              <div class="flex items-center justify-between"><span class="text-[10px] font-bold">VWCE.MI</span><span class="rounded-full bg-income/10 px-1.5 py-0.5 text-[8px] font-semibold text-income">+6,8% a 12 mesi</span></div>
              <div class="mt-0.5 text-[8px] text-muted">ETF azionario globale · R² 0,12</div>
              <div class="mt-2 grid grid-cols-2 gap-1.5">
                <div class="rounded bg-bg p-1.5"><div class="text-[7px] uppercase text-muted">Trend storico</div><div class="font-mono text-[10px] font-semibold">+9,1%</div></div>
                <div class="rounded bg-bg p-1.5"><div class="text-[7px] uppercase text-muted">Volatilità</div><div class="font-mono text-[10px] font-semibold">14,2%</div></div>
                <div class="rounded bg-bg p-1.5"><div class="text-[7px] uppercase text-muted">Momentum</div><div class="font-mono text-[10px] font-semibold text-income">+3,4%</div></div>
                <div class="rounded bg-bg p-1.5"><div class="text-[7px] uppercase text-muted">Max drawdown</div><div class="font-mono text-[10px] font-semibold text-expense">−33,8%</div></div>
              </div>
            </div>
            <div class="mt-2 rounded-xl border border-accent/30 bg-accentSoft p-3">
              <div class="text-[8px] font-semibold uppercase text-accent">Voglio investire</div>
              <div class="mt-1 text-[9px] text-gray-700">10.000 € · 12 mesi · profilo <span class="font-bold">bilanciato</span></div>
              <div class="mt-2 space-y-1 text-[9px]">
                <div class="flex justify-between"><span>3 ETF azionari</span><span class="font-mono font-semibold">55%</span></div>
                <div class="flex justify-between"><span>2 ETF obbligazionari</span><span class="font-mono font-semibold">30%</span></div>
                <div class="flex justify-between"><span>2 azioni</span><span class="font-mono font-semibold">15%</span></div>
              </div>
            </div>
            <div class="mt-2 rounded-lg border border-amber-200 bg-amber-50 p-2 text-[8px] text-amber-900"><span class="font-semibold">Non è consulenza finanziaria.</span> Stima di un modello, non una garanzia.</div>
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
        <div><span class="font-semibold">Stima di rendimento a 12 mesi</span><p class="text-sm text-muted">Un modello di regressione addestrato sullo storico prezzi reale, separato per azioni, obbligazioni e crypto, stima il rendimento atteso di ogni strumento che possiedi.</p></div>
      </div>
      <div class="flex gap-3">
        <span class="mt-1 text-income">✓</span>
        <div><span class="font-semibold">Indicatori di rischio, non solo rendimento</span><p class="text-sm text-muted">Trend storico, momentum, volatilità annualizzata e massimo drawdown: per capire quanto uno strumento può oscillare prima di guardare quanto potrebbe rendere.</p></div>
      </div>
      <div class="flex gap-3">
        <span class="mt-1 text-income">✓</span>
        <div><span class="font-semibold">Classifiche dei migliori e dei peggiori</span><p class="text-sm text-muted">Gli strumenti con l'andamento storico migliore e peggiore, e quelli con la previsione più alta e più bassa, su un universo di titoli, ETF e crypto.</p></div>
      </div>
      <div class="flex gap-3">
        <span class="mt-1 text-income">✓</span>
        <div><span class="font-semibold">«Voglio investire»: una proposta per il tuo profilo</span><p class="text-sm text-muted">Indichi importo, orizzonte, numero di strumenti e profilo di rischio (conservativo, bilanciato o aggressivo): ricevi una ripartizione che tiene conto anche delle commissioni di acquisto.</p></div>
      </div>
      <div class="flex gap-3">
        <span class="mt-1 text-income">✓</span>
        <div><span class="font-semibold">Onestà sui limiti</span><p class="text-sm text-muted">Accanto a ogni stima vedi l'R², cioè quanto il modello spiega davvero i rendimenti passati: più è basso, meno peso dare al numero.</p></div>
      </div>
    </div>
    <div class="mt-10 rounded-2xl border border-amber-200 bg-amber-50 p-5 text-sm text-amber-900">
      <span class="font-semibold">Non è consulenza finanziaria.</span> Nessun modello può prevedere in modo affidabile i mercati: le previsioni sono un elemento di valutazione in più, mai l'unico riferimento per una decisione di investimento.
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
    <h2 class="mb-3 text-2xl font-bold sm:text-3xl">Guarda i tuoi investimenti con occhi più critici</h2>
    <p class="mb-8 text-white/80">6 mesi gratis, senza carta di credito.</p>
    <a href="/app/" class="inline-block rounded-xl bg-white px-8 py-3.5 text-base font-semibold text-accent shadow-lg transition hover:bg-gray-50">Crea il tuo account</a>
  </div>
</section>

<?php include '_footer.php'; ?>

</body>
</html>
