<?php $active = 'about'; ?>
<!DOCTYPE html>
<html lang="it">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Chi siamo — Reflexi, la finanza personale in un posto solo</title>
<meta name="description" content="Scopri la storia di Reflexi: la nostra missione, i nostri valori e perché stiamo cambiando il modo di gestire le finanze personali.">
<meta name="robots" content="index, follow">
<link rel="canonical" href="https://reflexi.it/about">
<!-- Open Graph (Facebook, WhatsApp, LinkedIn, Telegram...) -->
<meta property="og:type" content="website">
<meta property="og:url" content="https://reflexi.it/about">
<meta property="og:site_name" content="Reflexi">
<meta property="og:title" content="Chi siamo — Reflexi, la finanza personale in un posto solo">
<meta property="og:description" content="Scopri la storia di Reflexi: la nostra missione, i nostri valori e perché stiamo cambiando il modo di gestire le finanze personali.">
<meta property="og:image" content="<?php require_once __DIR__ . '/_seo.php'; echo seo_og_image(); ?>">
<meta property="og:image:width" content="1200">
<meta property="og:image:height" content="630">
<meta property="og:locale" content="it_IT">

<!-- Twitter/X Card -->
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="Chi siamo — Reflexi, la finanza personale in un posto solo">
<meta name="twitter:description" content="Scopri la storia di Reflexi: la nostra missione, i nostri valori e perché stiamo cambiando il modo di gestire le finanze personali.">
<meta name="twitter:image" content="<?php require_once __DIR__ . '/_seo.php'; echo seo_og_image(); ?>">
<link rel="icon" type="image/svg+xml" href="/favicon.svg">
<link rel="preload" href="/fonts/inter-v20-latin.woff2" as="font" type="font/woff2" crossorigin>
<link rel="stylesheet" href="/css/site.css?v=<?= filemtime(__DIR__ . '/css/site.css') ?>">
<?php
require_once __DIR__ . '/_seo.php';
seo_breadcrumb([['Chi siamo', '/about']]);
seo_jsonld(['@type' => 'AboutPage', 'url' => SEO_BASE_URL . '/about', 'inLanguage' => 'it-IT', 'mainEntity' => seo_organization()]);
?>
</head>
<body class="bg-bg text-gray-900 antialiased">

<?php include '_nav.php'; ?>

<!-- HERO -->
<section class="px-6 pb-14 pt-14 text-center sm:pt-20">
  <h1 class="mx-auto mb-4 max-w-2xl text-3xl font-extrabold leading-tight tracking-tight sm:text-4xl">
    Crediamo che la finanza personale debba essere chiara per tutti
  </h1>
  <p class="mx-auto mb-8 max-w-xl text-lg text-muted">
    Non importa da dove parti: Reflexi ti dà gli strumenti per avere il controllo reale delle tue finanze,
    costruire abitudini sane e raggiungere i tuoi obiettivi.
  </p>
  <a href="/app/" class="inline-block rounded-xl bg-accent px-7 py-3.5 text-base font-semibold text-white shadow-lg shadow-accent/30 transition hover:bg-accentDark">Prova Reflexi</a>
</section>

<!-- CHI SIAMO / MISSIONE -->
<section class="border-t border-border bg-white px-6 py-16">
  <div class="mx-auto grid max-w-5xl gap-10 sm:grid-cols-2">
    <div>
      <span class="mb-2 block text-xs font-semibold uppercase tracking-wider text-accent">Chi siamo</span>
      <p class="text-lg text-gray-700">Reflexi nasce dalla passione per il mondo della finanza e degli investimenti, unita a
        un'esperienza concreta nel settore del credito — mutui e prestiti in particolare. A renderlo possibile è la
        professione di chi lo ha costruito: sviluppatore informatico, capace di trasformare quella conoscenza di
        settore in uno strumento reale, non solo in un'idea.</p>
    </div>
    <div>
      <span class="mb-2 block text-xs font-semibold uppercase tracking-wider text-accent">La nostra missione</span>
      <p class="text-lg text-gray-700">Aiutare le persone a vedere la propria situazione finanziaria per quello che è
        davvero — mutuo, investimenti e spese quotidiane insieme, non su tre strumenti scollegati — così da poter
        prendere decisioni migliori, con più chiarezza e meno ansia.</p>
    </div>
  </div>
</section>

<!-- LA NOSTRA STORIA -->
<section class="px-6 py-16">
  <div class="mx-auto max-w-3xl">
    <span class="mb-2 block text-center text-xs font-semibold uppercase tracking-wider text-accent">La nostra storia</span>
    <h2 class="mb-6 text-center text-2xl font-bold sm:text-3xl">Tutto è cominciato con un nome: Reflexi</h2>
    <div class="space-y-4 text-[15px] leading-relaxed text-gray-700">
      <p>L'idea è nata da un problema molto concreto: gestire mutuo, investimenti e spese quotidiane richiede oggi
        strumenti diversi che non si parlano tra loro. Chi conosce da vicino il mondo del credito sa quanto un mutuo
        pesi sulle decisioni finanziarie di ogni mese — eppure nessuna app metteva davvero insieme quel pezzo con il
        resto.</p>
      <p>Il nome <strong>Reflexi</strong> è stato scelto ispirandosi alla <strong>Teoria della Riflessività</strong> di
        George Soros: il modo in cui percepiamo la nostra situazione finanziaria non si limita a descriverla — la
        plasma attivamente. Una percezione distorta produce decisioni sbagliate, che rinforzano quella stessa
        percezione, in un ciclo continuo. Reflexi esiste per rompere quel ciclo, restituendo un riflesso accurato
        invece di un'illusione confortante.</p>
      <p>Oggi Reflexi continua a crescere, restando fedele alla sua missione: aiutare le persone a prendere in mano
        il proprio futuro finanziario, con chiarezza.</p>
    </div>
  </div>
</section>

<!-- TAPPE -->
<section class="border-t border-border bg-white px-6 py-16">
  <div class="mx-auto max-w-2xl">
    <span class="mb-2 block text-center text-xs font-semibold uppercase tracking-wider text-accent">Tappe</span>
    <h2 class="mb-8 text-center text-2xl font-bold sm:text-3xl">Il nostro percorso, finora</h2>
    <div class="space-y-5">
      <div class="flex gap-4">
        <span class="mt-0.5 flex h-6 w-6 flex-shrink-0 items-center justify-center rounded-full bg-income/15 text-income">✓</span>
        <div><span class="font-semibold">Le prime righe di codice</span><p class="text-sm text-muted">Un progetto personale per gestire mutuo, investimenti e spese in un unico posto.</p></div>
      </div>
      <div class="flex gap-4">
        <span class="mt-0.5 flex h-6 w-6 flex-shrink-0 items-center justify-center rounded-full bg-income/15 text-income">✓</span>
        <div><span class="font-semibold">Nasce Reflexi</span><p class="text-sm text-muted">Il progetto prende un nome e un'identità propria, ispirati alla Teoria della Riflessività.</p></div>
      </div>
      <div class="flex gap-4">
        <span class="mt-0.5 flex h-6 w-6 flex-shrink-0 items-center justify-center rounded-full bg-income/15 text-income">✓</span>
        <div><span class="font-semibold">Lancio della beta</span><p class="text-sm text-muted">Reflexi apre le porte ai primi utenti, con accesso gratuito e ascolto attivo dei feedback.</p></div>
      </div>
    </div>
    <p class="mt-8 text-center text-sm text-muted">La strada è ancora lunga — e continua a crescere ascoltando chi lo usa ogni giorno.</p>
  </div>
</section>

<!-- VALORI -->
<section class="px-6 py-16">
  <div class="mx-auto max-w-5xl">
    <div class="mb-12 text-center">
      <span class="mb-2 block text-xs font-semibold uppercase tracking-wider text-accent">I nostri valori</span>
      <h2 class="text-2xl font-bold sm:text-3xl">Chiarezza e fiducia, prima di tutto</h2>
    </div>
    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-4">
      <div class="rounded-2xl border border-border bg-white p-6 text-center">
        <div class="mb-3 text-3xl">🔍</div>
        <div class="mb-1 font-semibold">Trasparenza</div>
        <div class="text-sm text-muted">Onestà su cosa Reflexi sa fare bene e su cosa ancora no — mai promesse più grandi della realtà.</div>
      </div>
      <div class="rounded-2xl border border-border bg-white p-6 text-center">
        <div class="mb-3 text-3xl">❤️</div>
        <div class="mb-1 font-semibold">Attenzione all'utente</div>
        <div class="text-sm text-muted">Ogni funzionalità nasce da un bisogno reale, spesso segnalato da chi usa l'app ogni giorno.</div>
      </div>
      <div class="rounded-2xl border border-border bg-white p-6 text-center">
        <div class="mb-3 text-3xl">💡</div>
        <div class="mb-1 font-semibold">Competenza di settore</div>
        <div class="text-sm text-muted">Mutui, investimenti e finanza personale trattati con la serietà di chi li conosce da vicino.</div>
      </div>
      <div class="rounded-2xl border border-border bg-white p-6 text-center">
        <div class="mb-3 text-3xl">✨</div>
        <div class="mb-1 font-semibold">Chiarezza prima di tutto</div>
        <div class="text-sm text-muted">Ogni numero deve essere comprensibile, anche a chi non mastica gergo finanziario.</div>
      </div>
    </div>
  </div>
</section>

<!-- CTA -->
<section class="px-6 py-20">
  <div class="mx-auto max-w-2xl rounded-3xl bg-gradient-to-br from-accent via-accentDark to-indigo-900 p-10 text-center text-white sm:p-14">
    <h2 class="mb-3 text-2xl font-bold sm:text-3xl">Costruiamo insieme il futuro di Reflexi</h2>
    <p class="mb-8 text-white/80">Hai un feedback o un'idea? Scrivi a <a href="mailto:info@reflexi.it" class="underline">info@reflexi.it</a> — leggiamo davvero ogni messaggio.</p>
    <a href="/app/" class="inline-block rounded-xl bg-white px-8 py-3.5 text-base font-semibold text-accent shadow-lg transition hover:bg-gray-50">Crea il tuo account</a>
  </div>
</section>

<?php include '_footer.php'; ?>

</body>
</html>
