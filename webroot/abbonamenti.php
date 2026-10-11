<!DOCTYPE html>
<html lang="it">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Abbonamenti e spese ricorrenti — Reflexi</title>
<meta name="description" content="Abbonamenti e spese ricorrenti sotto controllo: totale mensile, addebiti automatici e rilevamento delle spese ricorrenti nascoste mai dichiarate.">
<meta name="robots" content="index, follow">
<link rel="canonical" href="https://reflexi.it/abbonamenti.php">
<!-- Open Graph (Facebook, WhatsApp, LinkedIn, Telegram...) -->
<meta property="og:type" content="website">
<meta property="og:url" content="https://reflexi.it/abbonamenti.php">
<meta property="og:site_name" content="Reflexi">
<meta property="og:title" content="Abbonamenti e spese ricorrenti — Reflexi">
<meta property="og:description" content="Abbonamenti e spese ricorrenti sotto controllo: totale mensile, addebiti automatici e rilevamento delle spese ricorrenti nascoste mai dichiarate.">
<meta property="og:image" content="https://reflexi.it/og-image.png?v=3">
<meta property="og:image:width" content="1200">
<meta property="og:image:height" content="630">
<meta property="og:locale" content="it_IT">

<!-- Twitter/X Card -->
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="Abbonamenti e spese ricorrenti — Reflexi">
<meta name="twitter:description" content="Abbonamenti e spese ricorrenti sotto controllo: totale mensile, addebiti automatici e rilevamento delle spese ricorrenti nascoste mai dichiarate.">
<meta name="twitter:image" content="https://reflexi.it/og-image.png?v=3">
<link rel="icon" type="image/svg+xml" href="/favicon.svg">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=IBM+Plex+Mono:wght@500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="/css/site.css?v=<?= filemtime(__DIR__ . '/css/site.css') ?>">
<?php
require_once __DIR__ . '/_seo.php';
seo_breadcrumb([['Abbonamenti', '/abbonamenti.php']]);
?>
</head>
<body class="bg-bg text-gray-900 antialiased">

<?php
$active = 'abbonamenti';
require_once __DIR__ . '/_news_db.php';
$relatedArticles = news_related(['abbonamenti-dimenticati-come-scovare-spese-ricorrenti', 'spese-fisse-e-variabili-differenza', 'metodo-delle-buste-budget-digitale']);
include '_nav.php';
?>

<!-- HERO -->
<section class="px-6 pb-16 pt-14 sm:pt-20">
  <div class="mx-auto grid max-w-5xl items-center gap-10 sm:grid-cols-2">
    <div>
      <span class="mb-4 inline-block rounded-full bg-expense/10 text-expense px-4 py-1.5 text-xs font-semibold uppercase tracking-wider">Abbonamenti</span>
      <h1 class="mb-4 text-3xl font-extrabold leading-tight tracking-tight sm:text-4xl">Tutti i tuoi abbonamenti, anche quelli che hai dimenticato</h1>
      <p class="mb-6 text-lg text-muted">Streaming, palestra, cloud, telefono: quanto spendi davvero ogni mese in abbonamenti, in un'unica vista. E l'app cerca da sola tra le tue spese quelle che si ripetono con regolarità ma che non hai mai segnato come ricorrenti.</p>
      <a href="/app/" class="inline-block rounded-xl bg-accent px-7 py-3.5 text-base font-semibold text-white shadow-lg shadow-accent/30 transition hover:bg-accentDark">Prova gratis</a>
    </div>
    <div class="flex justify-center">
      <div class="w-[260px] rounded-[2.5rem] border-[10px] border-gray-900 bg-gray-900 shadow-2xl shadow-gray-400/40">
        <div class="relative h-[520px] overflow-hidden rounded-[1.75rem] bg-bg">
          <div class="absolute left-1/2 top-0 z-10 h-5 w-24 -translate-x-1/2 rounded-b-2xl bg-gray-900"></div>
          <div class="h-full overflow-hidden px-3 pb-3 pt-7">
            <div class="mb-2 text-xs font-bold">Abbonamenti</div>
            <div class="rounded-xl border border-border bg-white p-3">
              <div class="text-[8px] uppercase text-muted">Spesa mensile in abbonamenti</div>
              <div class="font-mono text-lg font-semibold text-expense">€ 87,96</div>
              <div class="text-[8px] text-muted">8 abbonamenti attivi · € 1.055 l'anno</div>
            </div>
            <div class="mt-2 rounded-xl border border-border bg-white px-3 py-1.5">
              <div class="flex items-center justify-between border-b border-border py-1.5 text-[9px] last:border-0"><span class="text-gray-700">Streaming video</span><span class="font-mono font-semibold ">€ 13,99</span></div><div class="flex items-center justify-between border-b border-border py-1.5 text-[9px] last:border-0"><span class="text-gray-700">Musica</span><span class="font-mono font-semibold ">€ 10,99</span></div><div class="flex items-center justify-between border-b border-border py-1.5 text-[9px] last:border-0"><span class="text-gray-700">Palestra</span><span class="font-mono font-semibold ">€ 39,00</span></div><div class="flex items-center justify-between border-b border-border py-1.5 text-[9px] last:border-0"><span class="text-gray-700">Cloud storage</span><span class="font-mono font-semibold ">€ 2,99</span></div><div class="flex items-center justify-between border-b border-border py-1.5 text-[9px] last:border-0"><span class="text-gray-700">Telefono</span><span class="font-mono font-semibold ">€ 9,99</span></div>
            </div>
            <div class="mt-2 rounded-xl border border-amber-200 bg-amber-50 p-3">
              <div class="text-[8px] font-semibold uppercase text-amber-800">Spesa ricorrente rilevata</div>
              <div class="mt-1 text-[9px] text-gray-700">€ 7,99 ogni mese da 5 mesi, mai dichiarata</div>
              <div class="mt-1.5 inline-block rounded-md bg-accent px-2 py-0.5 text-[8px] font-semibold text-white">Registra come ricorrente</div>
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
        <div><span class="font-semibold">Il totale vero degli abbonamenti</span><p class="text-sm text-muted">Quanto pesano al mese e all'anno tutti i servizi a cui sei iscritto, con un catalogo dei servizi più noti per inserirli in un attimo.</p></div>
      </div>
      <div class="flex gap-3">
        <span class="mt-1 text-income">✓</span>
        <div><span class="font-semibold">Spese ricorrenti nascoste</span><p class="text-sm text-muted">L'app analizza le uscite e segnala quelle che si ripetono con cadenza regolare (almeno tre volte) ma che non hai mai dichiarato, da registrare con un clic.</p></div>
      </div>
      <div class="flex gap-3">
        <span class="mt-1 text-income">✓</span>
        <div><span class="font-semibold">Addebiti che si registrano da soli</span><p class="text-sm text-muted">Abbonamenti, affitto, stipendio e rate: ogni ricorrenza genera automaticamente la sua transazione alla data giusta, anche con una data di fine.</p></div>
      </div>
      <div class="flex gap-3">
        <span class="mt-1 text-income">✓</span>
        <div><span class="font-semibold">Nelle previsioni di cassa</span><p class="text-sm text-muted">Le ricorrenze attive entrano nella parte certa delle proiezioni dei mesi futuri, e smettono di contare quando scadono.</p></div>
      </div>
    </div>
    <div class="mt-10 rounded-2xl border border-amber-200 bg-amber-50 p-5 text-sm text-amber-900">
      <span class="font-semibold">Un limite onesto:</span> il rilevamento si basa su importo e cadenza, non sul nome dell'esercente. Due spese diverse con importo e ritmo simili possono essere segnalate insieme: decidi sempre tu cosa registrare.
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
    <h2 class="mb-3 text-2xl font-bold sm:text-3xl">Scopri quanto spendi davvero in abbonamenti</h2>
    <p class="mb-8 text-white/80">6 mesi gratis, senza carta di credito.</p>
    <a href="/app/" class="inline-block rounded-xl bg-white px-8 py-3.5 text-base font-semibold text-accent shadow-lg transition hover:bg-gray-50">Crea il tuo account</a>
  </div>
</section>

<?php include '_footer.php'; ?>

</body>
</html>
