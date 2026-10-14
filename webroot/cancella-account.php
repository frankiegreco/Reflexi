<?php $active = 'cancella-account'; ?>
<!DOCTYPE html>
<html lang="it">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Cancellazione account — Reflexi</title>
<meta name="description" content="Come cancellare definitivamente il tuo account Reflexi e tutti i dati collegati, dall'app in modo immediato oppure con una richiesta via email.">
<meta name="robots" content="index, follow">
<link rel="canonical" href="https://reflexi.it/cancella-account">
<!-- Open Graph (Facebook, WhatsApp, LinkedIn, Telegram...) -->
<meta property="og:type" content="website">
<meta property="og:url" content="https://reflexi.it/cancella-account">
<meta property="og:site_name" content="Reflexi">
<meta property="og:title" content="Cancellazione account — Reflexi">
<meta property="og:description" content="Come cancellare definitivamente il tuo account Reflexi e tutti i dati collegati, dall'app in modo immediato oppure con una richiesta via email.">
<meta property="og:image" content="<?php require_once __DIR__ . '/_seo.php'; echo seo_og_image(); ?>">
<meta property="og:image:width" content="1200">
<meta property="og:image:height" content="630">
<meta property="og:locale" content="it_IT">

<!-- Twitter/X Card -->
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="Cancellazione account — Reflexi">
<meta name="twitter:description" content="Come cancellare definitivamente il tuo account Reflexi e tutti i dati collegati, dall'app in modo immediato oppure con una richiesta via email.">
<meta name="twitter:image" content="<?php require_once __DIR__ . '/_seo.php'; echo seo_og_image(); ?>">
<?php seo_breadcrumb([['Cancellazione account', '/cancella-account']]); ?>
<link rel="icon" type="image/svg+xml" href="/favicon.svg">
<link rel="preload" href="/fonts/inter-v20-latin.woff2" as="font" type="font/woff2" crossorigin>
<link rel="stylesheet" href="/css/site.css?v=<?= filemtime(__DIR__ . '/css/site.css') ?>">
</head>
<body class="bg-bg text-gray-900 antialiased">

<?php include '_nav.php'; ?>

<main class="mx-auto max-w-2xl px-6 py-16">
  <h1 class="mb-3 text-3xl font-extrabold tracking-tight">Cancellazione account</h1>
  <p class="mb-8 text-lg text-muted">Puoi cancellare definitivamente il tuo account Reflexi e tutti i dati collegati in qualsiasi momento, in due modi.</p>

  <div class="card mb-6 p-6">
    <h2 class="mb-2 text-lg font-bold">Opzione 1 — Dall'app (immediata)</h2>
    <ol class="ml-5 list-decimal space-y-1.5 text-[15px] text-gray-700">
      <li>Accedi a <a href="/app/" class="text-accent hover:underline">reflexi.it/app</a></li>
      <li>Vai su <strong>Profilo</strong></li>
      <li>Scorri fino a "Elimina account" in fondo alla pagina</li>
      <li>Conferma con la tua password</li>
    </ol>
    <p class="mt-3 text-sm text-muted">La cancellazione è immediata e definitiva.</p>
  </div>

  <div class="card mb-6 p-6">
    <h2 class="mb-2 text-lg font-bold">Opzione 2 — Via email (se non riesci ad accedere)</h2>
    <p class="text-[15px] text-gray-700">
      Scrivi a <a href="mailto:privacy@reflexi.it" class="text-accent hover:underline">privacy@reflexi.it</a>
      dall'indirizzo email associato al tuo account, richiedendo la cancellazione. Rispondiamo ed eseguiamo la
      richiesta entro pochi giorni lavorativi.
    </p>
  </div>

  <div class="rounded-xl border border-border bg-white p-6">
    <h2 class="mb-2 text-lg font-bold">Cosa viene cancellato</h2>
    <p class="mb-3 text-[15px] text-gray-700">La cancellazione dell'account rimuove <strong>definitivamente e senza possibilità di recupero</strong>:</p>
    <ul class="ml-5 list-disc space-y-1 text-[15px] text-gray-700">
      <li>Dati di accesso (email, password)</li>
      <li>Tutti i conti, transazioni e categorie</li>
      <li>Mutui, investimenti, obiettivi di risparmio, fondi pensione</li>
      <li>Qualsiasi altro dato collegato al tuo account</li>
      <li>L'abbonamento, se ne hai uno: viene annullato subito e non riceverai altri addebiti</li>
    </ul>
    <p class="mt-3 text-sm text-muted">
      Per i dettagli completi su come trattiamo i tuoi dati, leggi la
      <a href="/privacy" class="text-accent hover:underline">Privacy Policy</a>.
    </p>
  </div>
</main>

<?php include '_footer.php'; ?>

</body>
</html>
