<!DOCTYPE html>
<html lang="it">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Budget mensile per categoria con avvisi — Reflexi</title>
<meta name="description" content="Un limite di spesa per categoria, con rollover: l'eccedenza non spesa si riporta al mese successivo. Un avviso prima di sforare, non dopo.">
<meta name="robots" content="index, follow">
<link rel="canonical" href="https://reflexi.it/budget">
<!-- Open Graph (Facebook, WhatsApp, LinkedIn, Telegram...) -->
<meta property="og:type" content="website">
<meta property="og:url" content="https://reflexi.it/budget">
<meta property="og:site_name" content="Reflexi">
<meta property="og:title" content="Budget mensile per categoria con avvisi — Reflexi">
<meta property="og:description" content="Un limite di spesa per categoria, con rollover: l'eccedenza non spesa si riporta al mese successivo. Un avviso prima di sforare, non dopo.">
<meta property="og:image" content="<?php require_once __DIR__ . '/_seo.php'; echo seo_og_image(); ?>">
<meta property="og:image:width" content="1200">
<meta property="og:image:height" content="630">
<meta property="og:locale" content="it_IT">

<!-- Twitter/X Card -->
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="Budget mensile per categoria con avvisi — Reflexi">
<meta name="twitter:description" content="Un limite di spesa per categoria, con rollover: l'eccedenza non spesa si riporta al mese successivo. Un avviso prima di sforare, non dopo.">
<meta name="twitter:image" content="<?php require_once __DIR__ . '/_seo.php'; echo seo_og_image(); ?>">
<link rel="icon" type="image/svg+xml" href="/favicon.svg">
<link rel="preload" href="/fonts/inter-v20-latin.woff2" as="font" type="font/woff2" crossorigin>
<link rel="stylesheet" href="/css/site.css?v=<?= filemtime(__DIR__ . '/css/site.css') ?>">
<?php
require_once __DIR__ . '/_seo.php';
seo_breadcrumb([['Budget', '/budget']]);
?>
</head>
<body class="bg-bg text-gray-900 antialiased">

<?php
$active = 'budget';
require_once __DIR__ . '/_news_db.php';
$relatedArticles = news_related(['regola-50-30-20-vs-budget-per-categoria', 'metodo-delle-buste-budget-digitale', 'fondo-di-emergenza-quanto-tenere-da-parte']);
include '_nav.php';
?>

<!-- HERO -->
<section class="px-6 pb-16 pt-14 sm:pt-20">
  <div class="mx-auto grid max-w-5xl items-center gap-10 sm:grid-cols-2">
    <div>
      <span class="mb-4 inline-block rounded-full bg-income/10 px-4 py-1.5 text-xs font-semibold uppercase tracking-wider text-income">Budget</span>
      <h1 class="mb-4 text-3xl font-extrabold leading-tight tracking-tight sm:text-4xl">Un avviso prima di sforare, non dopo che è già successo</h1>
      <p class="mb-6 text-lg text-muted">Imposti un limite per categoria — con il rollover, se non lo spendi tutto, l'eccedenza si riporta al mese dopo invece di sparire.</p>
      <a href="/app/" class="inline-block rounded-xl bg-accent px-7 py-3.5 text-base font-semibold text-white shadow-lg shadow-accent/30 transition hover:bg-accentDark">Prova gratis</a>
    </div>
    <div class="flex justify-center">
      <div class="w-[260px] rounded-[2.5rem] border-[10px] border-gray-900 bg-gray-900 shadow-2xl shadow-gray-400/40">
        <div class="relative h-[520px] overflow-hidden rounded-[1.75rem] bg-bg">
          <div class="absolute left-1/2 top-0 z-10 h-5 w-24 -translate-x-1/2 rounded-b-2xl bg-gray-900"></div>
          <div class="h-full overflow-hidden px-3 pb-3 pt-7">
            <div class="mb-2 text-xs font-bold">Budget mensile</div>
            <div class="space-y-2">
              <div class="rounded-xl border border-border bg-white p-3">
                <div class="mb-1 flex justify-between text-[10px]"><span class="font-semibold">Ristoranti</span><span class="font-mono text-expense">187 / 200€</span></div>
                <div class="h-1.5 w-full overflow-hidden rounded-full bg-gray-200"><div class="h-full w-[93%] rounded-full bg-loan"></div></div>
              </div>
              <div class="rounded-xl border border-border bg-white p-3">
                <div class="mb-1 flex justify-between text-[10px]"><span class="font-semibold">Spesa</span><span class="font-mono text-muted">240 / 400€</span></div>
                <div class="h-1.5 w-full overflow-hidden rounded-full bg-gray-200"><div class="h-full w-[60%] rounded-full bg-income"></div></div>
              </div>
              <div class="rounded-xl border border-accent/30 bg-accentSoft p-3">
                <div class="mb-1 flex items-center justify-between text-[10px]">
                  <span class="font-semibold">Regali <span class="rounded bg-accent px-1 py-0.5 text-[7px] font-bold text-white">ROLLOVER</span></span>
                  <span class="font-mono text-income">Disponibile: 340€</span>
                </div>
                <div class="text-[8px] text-muted">Non speso a giugno: +180€ riportati</div>
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
        <div><span class="font-semibold">Un limite per ogni categoria</span><p class="text-sm text-muted">Ristoranti, spesa, abbonamenti — ognuna con il proprio tetto di spesa mensile.</p></div>
      </div>
      <div class="flex gap-3">
        <span class="mt-1 text-income">✓</span>
        <div><span class="font-semibold">Rollover dell'eccedenza</span><p class="text-sm text-muted">Attivabile categoria per categoria: se non spendi tutto, il margine si accumula per il mese dopo invece di azzerarsi.</p></div>
      </div>
      <div class="flex gap-3">
        <span class="mt-1 text-income">✓</span>
        <div><span class="font-semibold">Avviso prima di sforare</span><p class="text-sm text-muted">L'app proietta la spesa a fine mese in base al ritmo attuale, non solo a quanto hai già speso oggi.</p></div>
      </div>
      <div class="flex gap-3">
        <span class="mt-1 text-income">✓</span>
        <div><span class="font-semibold">Collegato alle categorie che usi già</span><p class="text-sm text-muted">Nessuna configurazione separata — il budget segue le stesse categorie delle tue transazioni quotidiane.</p></div>
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
    <h2 class="mb-3 text-2xl font-bold sm:text-3xl">Un budget che si ricorda del mese scorso</h2>
    <p class="mb-8 text-white/80">6 mesi gratis, senza carta di credito.</p>
    <a href="/app/" class="inline-block rounded-xl bg-white px-8 py-3.5 text-base font-semibold text-accent shadow-lg transition hover:bg-gray-50">Crea il tuo account</a>
  </div>
</section>

<?php include '_footer.php'; ?>

</body>
</html>
