<?php $active = 'calcolatori'; ?>
<!DOCTYPE html>
<html lang="it">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Calcolatori finanziari gratuiti — Reflexi</title>
<meta name="description" content="9 calcolatori finanziari gratuiti e senza registrazione: interesse composto, PAC, rendimento, inflazione, regola 50/30/20, patrimonio netto, pensione e altri.">
<meta name="robots" content="index, follow">
<link rel="canonical" href="https://reflexi.it/calcolatori">
<!-- Open Graph (Facebook, WhatsApp, LinkedIn, Telegram...) -->
<meta property="og:type" content="website">
<meta property="og:url" content="https://reflexi.it/calcolatori">
<meta property="og:site_name" content="Reflexi">
<meta property="og:title" content="Calcolatori finanziari gratuiti — Reflexi">
<meta property="og:description" content="9 calcolatori finanziari gratuiti e senza registrazione: interesse composto, PAC, rendimento, inflazione, regola 50/30/20, patrimonio netto, pensione e altri.">
<meta property="og:image" content="https://reflexi.it/og-image.png?v=3">
<meta property="og:image:width" content="1200">
<meta property="og:image:height" content="630">
<meta property="og:locale" content="it_IT">

<!-- Twitter/X Card -->
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="Calcolatori finanziari gratuiti — Reflexi">
<meta name="twitter:description" content="9 calcolatori finanziari gratuiti e senza registrazione: interesse composto, PAC, rendimento, inflazione, regola 50/30/20, patrimonio netto, pensione e altri.">
<meta name="twitter:image" content="https://reflexi.it/og-image.png?v=3">
<link rel="icon" type="image/svg+xml" href="/favicon.svg">
<link rel="preload" href="/fonts/inter-v20-latin.woff2" as="font" type="font/woff2" crossorigin>
<link rel="stylesheet" href="/css/site.css?v=<?= filemtime(__DIR__ . '/css/site.css') ?>">
<?php
require_once __DIR__ . '/_seo.php';
seo_breadcrumb([['Calcolatori', '/calcolatori']]);
?>
</head>
<body class="bg-bg text-gray-900 antialiased">

<?php include '_nav.php'; ?>

<section class="px-6 pb-8 pt-14 text-center sm:pt-20">
  <h1 class="mx-auto mb-3 max-w-2xl text-3xl font-extrabold leading-tight tracking-tight sm:text-4xl">Calcolatori finanziari gratuiti</h1>
  <p class="mx-auto max-w-xl text-lg text-muted">Nessuna registrazione richiesta. Scegli lo strumento che ti serve.</p>
</section>

<main class="mx-auto max-w-5xl px-6 py-8">
  <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-3">

    <a href="/calcolatore-interesse-composto" class="group flex flex-col rounded-2xl border border-border bg-white p-6 shadow-sm transition hover:-translate-y-1 hover:border-accent hover:bg-accentSoft hover:shadow-lg">
      <div class="mb-4 flex h-12 w-12 items-center justify-center rounded-xl bg-accentSoft text-2xl">📈</div>
      <h2 class="mb-1.5 text-lg font-bold">Interesse composto</h2>
      <p class="mb-4 flex-1 text-sm text-muted">Quanto cresce un capitale nel tempo, reinvestendo gli interessi maturati.</p>
      <span class="text-sm font-semibold text-accent group-hover:underline">Usa questo calcolatore →</span>
    </a>

    <a href="/calcolatore-piano-investimento" class="group flex flex-col rounded-2xl border border-border bg-white p-6 shadow-sm transition hover:-translate-y-1 hover:border-accent hover:bg-accentSoft hover:shadow-lg">
      <div class="mb-4 flex h-12 w-12 items-center justify-center rounded-xl bg-income/10 text-2xl">💰</div>
      <h2 class="mb-1.5 text-lg font-bold">Piano di investimento</h2>
      <p class="mb-4 flex-1 text-sm text-muted">Capitale iniziale + versamenti mensili: quanto puoi accumulare nel tempo.</p>
      <span class="text-sm font-semibold text-income group-hover:underline">Usa questo calcolatore →</span>
    </a>

    <a href="/calcolatore-obiettivo-risparmio" class="group flex flex-col rounded-2xl border border-border bg-white p-6 shadow-sm transition hover:-translate-y-1 hover:border-accent hover:bg-accentSoft hover:shadow-lg">
      <div class="mb-4 flex h-12 w-12 items-center justify-center rounded-xl bg-accentSoft text-2xl">🎯</div>
      <h2 class="mb-1.5 text-lg font-bold">Obiettivo di risparmio</h2>
      <p class="mb-4 flex-1 text-sm text-muted">Quanto versare ogni mese per raggiungere una cifra entro una data.</p>
      <span class="text-sm font-semibold text-accent group-hover:underline">Usa questo calcolatore →</span>
    </a>

    <a href="/calcolatore-rendimento" class="group flex flex-col rounded-2xl border border-border bg-white p-6 shadow-sm transition hover:-translate-y-1 hover:border-accent hover:bg-accentSoft hover:shadow-lg">
      <div class="mb-4 flex h-12 w-12 items-center justify-center rounded-xl bg-income/10 text-2xl">📊</div>
      <h2 class="mb-1.5 text-lg font-bold">Rendimento (ROI)</h2>
      <p class="mb-4 flex-1 text-sm text-muted">Quanto ha reso davvero un investimento, in totale e su base annua.</p>
      <span class="text-sm font-semibold text-income group-hover:underline">Usa questo calcolatore →</span>
    </a>

    <a href="/calcolatore-recupero-perdite" class="group flex flex-col rounded-2xl border border-border bg-white p-6 shadow-sm transition hover:-translate-y-1 hover:border-accent hover:bg-accentSoft hover:shadow-lg">
      <div class="mb-4 flex h-12 w-12 items-center justify-center rounded-xl bg-expense/10 text-2xl">🩹</div>
      <h2 class="mb-1.5 text-lg font-bold">Recupero perdite</h2>
      <p class="mb-4 flex-1 text-sm text-muted">Il guadagno necessario per tornare in pari dopo un calo — è più alto di quanto pensi.</p>
      <span class="text-sm font-semibold text-expense group-hover:underline">Usa questo calcolatore →</span>
    </a>

    <a href="/calcolatore-inflazione" class="group flex flex-col rounded-2xl border border-border bg-white p-6 shadow-sm transition hover:-translate-y-1 hover:border-accent hover:bg-accentSoft hover:shadow-lg">
      <div class="mb-4 flex h-12 w-12 items-center justify-center rounded-xl bg-loan/10 text-2xl">📉</div>
      <h2 class="mb-1.5 text-lg font-bold">Inflazione</h2>
      <p class="mb-4 flex-1 text-sm text-muted">Quanto varrà davvero una somma tra qualche anno, al netto del carovita.</p>
      <span class="text-sm font-semibold text-loan group-hover:underline">Usa questo calcolatore →</span>
    </a>

    <a href="/calcolatore-budget-503020" class="group flex flex-col rounded-2xl border border-border bg-white p-6 shadow-sm transition hover:-translate-y-1 hover:border-accent hover:bg-accentSoft hover:shadow-lg">
      <div class="mb-4 flex h-12 w-12 items-center justify-center rounded-xl bg-income/10 text-2xl">🥧</div>
      <h2 class="mb-1.5 text-lg font-bold">Regola 50/30/20</h2>
      <p class="mb-4 flex-1 text-sm text-muted">Come suddividere il reddito tra necessità, desideri e risparmio.</p>
      <span class="text-sm font-semibold text-income group-hover:underline">Usa questo calcolatore →</span>
    </a>

    <a href="/calcolatore-patrimonio-netto" class="group flex flex-col rounded-2xl border border-border bg-white p-6 shadow-sm transition hover:-translate-y-1 hover:border-accent hover:bg-accentSoft hover:shadow-lg">
      <div class="mb-4 flex h-12 w-12 items-center justify-center rounded-xl bg-accentSoft text-2xl">🏦</div>
      <h2 class="mb-1.5 text-lg font-bold">Patrimonio netto</h2>
      <p class="mb-4 flex-1 text-sm text-muted">Tutto quello che possiedi, meno tutto quello che devi.</p>
      <span class="text-sm font-semibold text-accent group-hover:underline">Usa questo calcolatore →</span>
    </a>

    <a href="/calcolatore-pensione" class="group flex flex-col rounded-2xl border border-border bg-white p-6 shadow-sm transition hover:-translate-y-1 hover:border-accent hover:bg-accentSoft hover:shadow-lg">
      <div class="mb-4 flex h-12 w-12 items-center justify-center rounded-xl bg-loan/10 text-2xl">🏖️</div>
      <h2 class="mb-1.5 text-lg font-bold">Pensione</h2>
      <p class="mb-4 flex-1 text-sm text-muted">Quanto potresti accumulare entro l'età che ti sei posto come obiettivo.</p>
      <span class="text-sm font-semibold text-loan group-hover:underline">Usa questo calcolatore →</span>
    </a>

  </div>
</main>

<section class="px-6 py-16 text-center">
  <h2 class="mb-2 text-xl font-bold">Vuoi che questi calcoli si aggiornino da soli col tempo?</h2>
  <p class="mb-6 text-muted">Reflexi collega mutuo, investimenti e patrimonio netto ai tuoi dati reali, senza doverli reinserire ogni volta.</p>
  <a href="/app/" class="inline-block rounded-xl bg-accent px-7 py-3 text-sm font-semibold text-white shadow-lg shadow-accent/25 transition hover:bg-accentDark">Prova Reflexi gratis</a>
</section>

<?php include '_footer.php'; ?>

</body>
</html>
