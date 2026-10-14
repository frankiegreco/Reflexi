<!DOCTYPE html>
<html lang="it">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Portafoglio ETF e azioni con quotazioni reali — Reflexi</title>
<meta name="description" content="ETF, azioni e crypto con quotazioni in tempo reale, PAC automatici e tasse sulle plusvalenze calcolate da sole: il portafoglio collegato alle tue finanze.">
<meta name="robots" content="index, follow">
<link rel="canonical" href="https://reflexi.it/investimenti">
<!-- Open Graph (Facebook, WhatsApp, LinkedIn, Telegram...) -->
<meta property="og:type" content="website">
<meta property="og:url" content="https://reflexi.it/investimenti">
<meta property="og:site_name" content="Reflexi">
<meta property="og:title" content="Portafoglio ETF e azioni con quotazioni reali — Reflexi">
<meta property="og:description" content="ETF, azioni e crypto con quotazioni in tempo reale, PAC automatici e tasse sulle plusvalenze calcolate da sole: il portafoglio collegato alle tue finanze.">
<meta property="og:image" content="<?php require_once __DIR__ . '/_seo.php'; echo seo_og_image(); ?>">
<meta property="og:image:width" content="1200">
<meta property="og:image:height" content="630">
<meta property="og:locale" content="it_IT">

<!-- Twitter/X Card -->
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="Portafoglio ETF e azioni con quotazioni reali — Reflexi">
<meta name="twitter:description" content="ETF, azioni e crypto con quotazioni in tempo reale, PAC automatici e tasse sulle plusvalenze calcolate da sole: il portafoglio collegato alle tue finanze.">
<meta name="twitter:image" content="<?php require_once __DIR__ . '/_seo.php'; echo seo_og_image(); ?>">
<link rel="icon" type="image/svg+xml" href="/favicon.svg">
<link rel="preload" href="/fonts/inter-v20-latin.woff2" as="font" type="font/woff2" crossorigin>
<link rel="stylesheet" href="/css/site.css?v=<?= filemtime(__DIR__ . '/css/site.css') ?>">
<?php
require_once __DIR__ . '/_seo.php';
seo_breadcrumb([['Investimenti', '/investimenti']]);
?>
</head>
<body class="bg-bg text-gray-900 antialiased">

<?php
$active = 'investimenti';
require_once __DIR__ . '/_news_db.php';
$relatedArticles = news_related(['cosa-e-un-etf-guida-per-principianti', 'pac-piano-accumulo-etf-come-funziona', 'diversificazione-portafoglio-cosa-significa-davvero']);
include '_nav.php';
?>

<!-- HERO -->
<section class="px-6 pb-16 pt-14 sm:pt-20">
  <div class="mx-auto grid max-w-5xl items-center gap-10 sm:grid-cols-2">
    <div>
      <span class="mb-4 inline-block rounded-full bg-accentSoft px-4 py-1.5 text-xs font-semibold uppercase tracking-wider text-accent">Investimenti</span>
      <h1 class="mb-4 text-3xl font-extrabold leading-tight tracking-tight sm:text-4xl">ETF, azioni e crypto con quotazioni vere, non stimate</h1>
      <p class="mb-6 text-lg text-muted">Prezzi live da Yahoo Finance, costo medio di carico, plus e minusvalenze — e un PAC che compra da solo, con il prezzo reale del giorno.</p>
      <a href="/app/?mode=register" class="inline-block rounded-xl bg-accent px-7 py-3.5 text-base font-semibold text-white shadow-lg shadow-accent/30 transition hover:bg-accentDark">Prova gratis</a>
    </div>
    <div class="flex justify-center">
      <div class="w-[260px] rounded-[2.5rem] border-[10px] border-gray-900 bg-gray-900 shadow-2xl shadow-gray-400/40">
        <div class="relative h-[520px] overflow-hidden rounded-[1.75rem] bg-bg">
          <div class="absolute left-1/2 top-0 z-10 h-5 w-24 -translate-x-1/2 rounded-b-2xl bg-gray-900"></div>
          <div class="h-full overflow-hidden px-3 pb-3 pt-7">
            <div class="mb-2 text-xs font-bold">Portafoglio</div>
            <div class="rounded-xl border border-border bg-white p-3">
              <div class="flex items-center gap-3">
                <div class="h-16 w-16 flex-shrink-0 rounded-full" style="background: conic-gradient(#4f46e5 0% 45%, #10b981 45% 75%, #f59e0b 75% 100%)"></div>
                <div class="space-y-1 text-[9px]">
                  <div class="flex items-center gap-1.5"><span class="h-1.5 w-1.5 rounded-full bg-accent"></span>VWCE.MI 45%</div>
                  <div class="flex items-center gap-1.5"><span class="h-1.5 w-1.5 rounded-full bg-income"></span>SXR8.DE 30%</div>
                  <div class="flex items-center gap-1.5"><span class="h-1.5 w-1.5 rounded-full bg-loan"></span>AAPL 25%</div>
                </div>
              </div>
            </div>
            <div class="mt-2 grid grid-cols-2 gap-2">
              <div class="rounded-lg border border-border bg-white p-2">
                <div class="text-[8px] uppercase text-muted">Valore</div>
                <div class="font-mono text-xs font-semibold">€ 20.531</div>
              </div>
              <div class="rounded-lg border border-border bg-white p-2">
                <div class="text-[8px] uppercase text-muted">Plus/minus</div>
                <div class="font-mono text-xs font-semibold text-income">+€ 1.240</div>
              </div>
            </div>
            <div class="mt-2 rounded-lg border border-accent/30 bg-accentSoft p-2">
              <div class="text-[8px] font-semibold uppercase text-accent">PAC attivo</div>
              <div class="mt-1 text-[9px] text-gray-700">€ 200/mese su VWCE.MI — prossimo il 2 del mese</div>
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
        <div><span class="font-semibold">Quotazioni in tempo reale</span><p class="text-sm text-muted">ETF, azioni e crypto (formato BTC-USD) con prezzi live, non inseriti a mano.</p></div>
      </div>
      <div class="flex gap-3">
        <span class="mt-1 text-income">✓</span>
        <div><span class="font-semibold">Ricerca per nome</span><p class="text-sm text-muted">Cerchi "Vanguard All World" o "Apple", scegli il risultato giusto — non serve conoscere il ticker esatto.</p></div>
      </div>
      <div class="flex gap-3">
        <span class="mt-1 text-income">✓</span>
        <div><span class="font-semibold">Piani di accumulo (PAC) automatici</span><p class="text-sm text-muted">Imposti un importo fisso a intervalli regolari — l'app calcola da sola quante quote comprare al prezzo del giorno.</p></div>
      </div>
      <div class="flex gap-3">
        <span class="mt-1 text-income">✓</span>
        <div><span class="font-semibold">Storico prezzi nel tempo</span><p class="text-sm text-muted">Un grafico dell'andamento di ogni titolo, non solo il valore attuale.</p></div>
      </div>
      <div class="flex gap-3">
        <span class="mt-1 text-income">✓</span>
        <div><span class="font-semibold">Tassazione sulle plusvalenze</span><p class="text-sm text-muted">Su ogni vendita, l'imposta viene stimata e il netto realmente accreditato registrato in automatico — con aliquota configurabile per strumento.</p></div>
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
    <h2 class="mb-3 text-2xl font-bold sm:text-3xl">Il tuo portafoglio, sempre aggiornato</h2>
    <p class="mb-8 text-white/80">6 mesi gratis, senza carta di credito.</p>
    <a href="/app/?mode=register" class="inline-block rounded-xl bg-white px-8 py-3.5 text-base font-semibold text-accent shadow-lg transition hover:bg-gray-50">Crea il tuo account</a>
  </div>
</section>

<?php include '_footer.php'; ?>

</body>
</html>
