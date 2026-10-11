<?php
// Pagina 404 del sito pubblico (ErrorDocument in .htaccess). Al posto della
// pagina predefinita di Apache in inglese: stessa navbar del sito e link alle
// sezioni principali, così chi arriva da un link rotto non se ne va. noindex:
// non deve mai finire nei risultati di ricerca.
http_response_code(404);
$active = '';
?>
<!DOCTYPE html>
<html lang="it">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Pagina non trovata — Reflexi</title>
<meta name="robots" content="noindex, follow">
<link rel="icon" type="image/svg+xml" href="/favicon.svg">
<link rel="stylesheet" href="/css/site.css?v=<?= filemtime(__DIR__ . '/css/site.css') ?>">
</head>
<body class="bg-bg text-gray-900 antialiased">

<?php include __DIR__ . '/_nav.php'; ?>

<section class="px-6 py-20 text-center sm:py-28">
  <div class="mx-auto max-w-2xl">
    <div class="mb-4 font-mono text-5xl font-semibold text-accent">404</div>
    <h1 class="mb-3 text-3xl font-extrabold tracking-tight">Questa pagina non esiste</h1>
    <p class="mb-10 text-lg text-muted">Il link potrebbe essere sbagliato o la pagina è stata spostata. Ecco da dove puoi ripartire:</p>
    <div class="grid grid-cols-1 gap-3 text-left sm:grid-cols-2">
      <a href="/" class="rounded-2xl border border-border bg-white p-5 transition hover:border-accent hover:bg-accentSoft">
        <span class="block font-semibold">Home</span>
        <span class="block text-sm text-muted">Cos'è Reflexi e come funziona</span>
      </a>
      <a href="/calcolatori" class="rounded-2xl border border-border bg-white p-5 transition hover:border-accent hover:bg-accentSoft">
        <span class="block font-semibold">Calcolatori gratuiti</span>
        <span class="block text-sm text-muted">Interesse composto, pensione, inflazione e altri</span>
      </a>
      <a href="/news" class="rounded-2xl border border-border bg-white p-5 transition hover:border-accent hover:bg-accentSoft">
        <span class="block font-semibold">News e guide</span>
        <span class="block text-sm text-muted">Mutui, investimenti, budget e pianificazione</span>
      </a>
      <a href="/piani" class="rounded-2xl border border-border bg-white p-5 transition hover:border-accent hover:bg-accentSoft">
        <span class="block font-semibold">Piani e prezzi</span>
        <span class="block text-sm text-muted">6 mesi gratis, senza carta di credito</span>
      </a>
    </div>
  </div>
</section>

<?php include __DIR__ . '/_footer.php'; ?>

</body>
</html>
