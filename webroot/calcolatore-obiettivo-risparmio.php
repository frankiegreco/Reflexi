<?php
$active = 'calcolatori';
require_once __DIR__ . '/_news_db.php';
$relatedArticles = news_related(['obiettivi-risparmio-come-fissarli-e-raggiungerli', 'fondo-di-emergenza-quanto-tenere-da-parte']);
?>
<!DOCTYPE html>
<html lang="it">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Calcolatore obiettivo di risparmio — Reflexi</title>
<meta name="description" content="Calcola quanto versare ogni mese per raggiungere un obiettivo di risparmio entro una data specifica. Gratuito, senza registrazione.">
<meta name="robots" content="index, follow">
<link rel="canonical" href="https://reflexi.it/calcolatore-obiettivo-risparmio">
<!-- Open Graph (Facebook, WhatsApp, LinkedIn, Telegram...) -->
<meta property="og:type" content="website">
<meta property="og:url" content="https://reflexi.it/calcolatore-obiettivo-risparmio">
<meta property="og:site_name" content="Reflexi">
<meta property="og:title" content="Calcolatore obiettivo di risparmio — Reflexi">
<meta property="og:description" content="Calcola quanto versare ogni mese per raggiungere un obiettivo di risparmio entro una data specifica. Gratuito, senza registrazione.">
<meta property="og:image" content="<?php require_once __DIR__ . '/_seo.php'; echo seo_og_image(); ?>">
<meta property="og:image:width" content="1200">
<meta property="og:image:height" content="630">
<meta property="og:locale" content="it_IT">

<!-- Twitter/X Card -->
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="Calcolatore obiettivo di risparmio — Reflexi">
<meta name="twitter:description" content="Calcola quanto versare ogni mese per raggiungere un obiettivo di risparmio entro una data specifica. Gratuito, senza registrazione.">
<meta name="twitter:image" content="<?php require_once __DIR__ . '/_seo.php'; echo seo_og_image(); ?>">
<link rel="icon" type="image/svg+xml" href="/favicon.svg">
<link rel="preload" href="/fonts/inter-v20-latin.woff2" as="font" type="font/woff2" crossorigin>
<link rel="stylesheet" href="/css/site.css?v=<?= filemtime(__DIR__ . '/css/site.css') ?>">
<style>
  body { font-family: 'Inter', ui-sans-serif, system-ui, sans-serif; } .font-mono { font-family: 'IBM Plex Mono', ui-monospace, monospace; }
</style>
<?php
require_once __DIR__ . '/_seo.php';
seo_breadcrumb([['Calcolatori', '/calcolatori'], ['Calcolatore obiettivo di risparmio', '/calcolatore-obiettivo-risparmio']]);
seo_calculator('Calcolatore obiettivo di risparmio', '/calcolatore-obiettivo-risparmio', 'Calcola quanto versare ogni mese per raggiungere un obiettivo di risparmio entro una data specifica. Gratuito, senza registrazione.');
?>
</head>
<body class="bg-bg text-gray-900 antialiased">

<?php include '_nav.php'; ?>

<section class="px-6 pb-6 pt-14 text-center sm:pt-16">
  <a href="/calcolatori" class="mb-4 inline-block text-sm text-muted hover:text-accent">← Tutti i calcolatori</a>
  <div class="mx-auto mb-3 flex h-14 w-14 items-center justify-center rounded-2xl bg-accentSoft text-3xl">🎯</div>
  <h1 class="mx-auto mb-3 max-w-2xl text-3xl font-extrabold leading-tight tracking-tight sm:text-4xl">Calcolatore obiettivo di risparmio</h1>
  <p class="mx-auto max-w-xl text-lg text-muted">Quanto devi versare ogni mese per raggiungere una cifra specifica entro una data.</p>
</section>

<main class="mx-auto max-w-3xl px-6 py-8">
  <div class="rounded-2xl border border-border bg-white p-6 shadow-sm sm:p-8">
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
      <div>
        <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-muted">Obiettivo (€)</label>
        <input id="og-target" type="number" value="20000" min="0" class="w-full rounded-xl border border-border px-3.5 py-2.5 text-sm outline-none focus:border-accent focus:ring-4 focus:ring-accent/10">
      </div>
      <div>
        <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-muted">Hai già (€)</label>
        <input id="og-current" type="number" value="0" min="0" class="w-full rounded-xl border border-border px-3.5 py-2.5 text-sm outline-none focus:border-accent focus:ring-4 focus:ring-accent/10">
      </div>
      <div>
        <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-muted">Entro quanti anni</label>
        <input id="og-years" type="number" value="5" min="1" max="50" class="w-full rounded-xl border border-border px-3.5 py-2.5 text-sm outline-none focus:border-accent focus:ring-4 focus:ring-accent/10">
      </div>
      <div>
        <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-muted">Rendimento annuo atteso (%)</label>
        <input id="og-rate" type="number" value="3" step="0.1" min="0" class="w-full rounded-xl border border-border px-3.5 py-2.5 text-sm outline-none focus:border-accent focus:ring-4 focus:ring-accent/10">
      </div>
    </div>

    <div class="mt-6 rounded-xl border-2 border-accent bg-accentSoft p-5 text-center">
      <div class="text-[11px] uppercase text-accent">Versamento mensile necessario</div>
      <div id="og-out-monthly" class="font-mono text-3xl font-bold text-accent">—</div>
    </div>

    <div class="mt-6">
      <div class="mb-1 flex justify-between text-xs text-muted"><span>Hai già</span><span>Obiettivo</span></div>
      <div class="h-3 w-full overflow-hidden rounded-full bg-gray-200">
        <div id="og-progress-bar" class="h-full rounded-full bg-accent" style="width:0%"></div>
      </div>
      <div class="mt-1 flex justify-between text-xs font-semibold"><span id="og-progress-current">—</span><span id="og-progress-target">—</span></div>
    </div>
  </div>

  <div class="mt-10 space-y-4 text-[15px] leading-relaxed text-gray-700">
    <h2 class="text-xl font-bold text-gray-900">Come impostare un obiettivo di risparmio realistico</h2>
    <p>Che si tratti di un fondo di emergenza, una vacanza, l'acconto per una casa o qualsiasi altro obiettivo, avere una cifra e una data chiare trasforma un desiderio vago in un piano concreto. Questo calcolatore parte dal risultato che vuoi ottenere e lavora a ritroso, dicendoti esattamente quanto mettere da parte ogni mese.</p>
    <p>Se il versamento richiesto sembra troppo alto, hai tre leve su cui agire: allungare i tempi, ridurre l'obiettivo, oppure cercare un rendimento atteso più alto (accettando però più rischio) — non esiste una risposta giusta in assoluto, dipende da quanto è urgente per te quel traguardo.</p>
  </div>
</main>

<!-- Articoli News correlati -->
<?php if (!empty($relatedArticles)): ?>
<section class="border-t border-border bg-white px-6 py-16">
  <div class="mx-auto max-w-5xl">
    <h2 class="mb-6 text-center text-xl font-bold">Continua a leggere</h2>
    <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
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

<section class="px-6 py-16 text-center">
  <p class="mb-4 text-sm text-muted">Cerchi anche un altro calcolatore?</p>
  <a href="/calcolatori" class="mb-8 inline-block text-sm font-semibold text-accent hover:underline">Vedi tutti i calcolatori →</a>
  <h2 class="mb-2 text-xl font-bold">Vuoi tracciare il progresso reale verso il tuo obiettivo?</h2>
  <p class="mb-6 text-muted">Reflexi collega l'obiettivo al tuo risparmio reale, aggiornato automaticamente ogni volta che registri una transazione.</p>
  <a href="/app/" class="inline-block rounded-xl bg-accent px-7 py-3 text-sm font-semibold text-white shadow-lg shadow-accent/25 transition hover:bg-accentDark">Prova Reflexi gratis</a>
  <p class="mt-4 text-sm text-muted">Come funziona in Reflexi: <a href="/obiettivi" class="font-semibold text-accent hover:underline">Obiettivi →</a></p>
</section>

<?php include '_footer.php'; ?>

<script>
function euro(n) {
  if (!isFinite(n)) return '—';
  return new Intl.NumberFormat('it-IT', { style: 'currency', currency: 'EUR', maximumFractionDigits: 0 }).format(n);
}

function calc() {
  const target = parseFloat(document.getElementById('og-target').value) || 0;
  const current = parseFloat(document.getElementById('og-current').value) || 0;
  const years = parseFloat(document.getElementById('og-years').value) || 0;
  const rate = parseFloat(document.getElementById('og-rate').value) || 0;

  const months = years * 12;
  const monthlyRate = (rate / 100) / 12;
  const futureValueOfCurrent = current * Math.pow(1 + monthlyRate, months);
  const remaining = target - futureValueOfCurrent;

  let monthly;
  if (monthlyRate === 0) {
    monthly = months > 0 ? remaining / months : 0;
  } else {
    monthly = remaining * monthlyRate / (Math.pow(1 + monthlyRate, months) - 1);
  }
  document.getElementById('og-out-monthly').textContent = monthly > 0 ? euro(monthly) : 'Obiettivo già raggiunto';

  const pct = target > 0 ? Math.min(100, (current / target) * 100) : 0;
  document.getElementById('og-progress-bar').style.width = pct + '%';
  document.getElementById('og-progress-current').textContent = euro(current);
  document.getElementById('og-progress-target').textContent = euro(target);
}
['og-target', 'og-current', 'og-years', 'og-rate'].forEach((id) => document.getElementById(id).addEventListener('input', calc));
calc();
</script>

</body>
</html>
