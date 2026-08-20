<?php
$active = 'calcolatori';
require_once __DIR__ . '/_news_db.php';
$relatedArticles = news_related(['patrimonio-netto-cos-e-perche-seguirlo-nel-tempo', 'tasso-di-risparmio-cosa-e-come-calcolarlo']);
?>
<!DOCTYPE html>
<html lang="it">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Calcolatore di patrimonio netto — Reflexi</title>
<meta name="description" content="Calcola il tuo patrimonio netto: tutto quello che possiedi meno tutto quello che devi. Gratuito, senza registrazione.">
<meta name="robots" content="index, follow">
<link rel="canonical" href="https://reflexi.it/calcolatore-patrimonio-netto.php">
<!-- Open Graph (Facebook, WhatsApp, LinkedIn, Telegram...) -->
<meta property="og:type" content="website">
<meta property="og:url" content="https://reflexi.it/calcolatore-patrimonio-netto.php">
<meta property="og:site_name" content="Reflexi">
<meta property="og:title" content="Calcolatore di patrimonio netto — Reflexi">
<meta property="og:description" content="Calcola il tuo patrimonio netto: tutto quello che possiedi meno tutto quello che devi. Gratuito, senza registrazione.">
<meta property="og:image" content="https://reflexi.it/og-image.png?v=3">
<meta property="og:image:width" content="1200">
<meta property="og:image:height" content="630">
<meta property="og:locale" content="it_IT">

<!-- Twitter/X Card -->
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="Calcolatore di patrimonio netto — Reflexi">
<meta name="twitter:description" content="Calcola il tuo patrimonio netto: tutto quello che possiedi meno tutto quello che devi. Gratuito, senza registrazione.">
<meta name="twitter:image" content="https://reflexi.it/og-image.png?v=3">
<link rel="icon" type="image/svg+xml" href="/favicon.svg">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=IBM+Plex+Mono:wght@500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="/css/site.css?v=1">
<style>
  body { font-family: 'Inter', ui-sans-serif, system-ui, sans-serif; } .font-mono { font-family: 'IBM Plex Mono', ui-monospace, monospace; }
</style>
</head>
<body class="bg-bg text-gray-900 antialiased">

<?php include '_nav.php'; ?>

<section class="px-6 pb-6 pt-14 text-center sm:pt-16">
  <a href="/calcolatori.php" class="mb-4 inline-block text-sm text-muted hover:text-accent">← Tutti i calcolatori</a>
  <div class="mx-auto mb-3 flex h-14 w-14 items-center justify-center rounded-2xl bg-accentSoft text-3xl">🏦</div>
  <h1 class="mx-auto mb-3 max-w-2xl text-3xl font-extrabold leading-tight tracking-tight sm:text-4xl">Calcolatore di patrimonio netto</h1>
  <p class="mx-auto max-w-xl text-lg text-muted">Tutto quello che possiedi, meno tutto quello che devi.</p>
</section>

<main class="mx-auto max-w-3xl px-6 py-8">
  <div class="rounded-2xl border border-border bg-white p-6 shadow-sm sm:p-8">
    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
      <div>
        <div class="mb-3 text-xs font-semibold uppercase tracking-wide text-income">Attività (cosa possiedi)</div>
        <div class="space-y-3">
          <div><label class="mb-1 block text-xs text-muted">Liquidità (conti, contanti)</label><input id="nw-liquid" type="number" value="0" class="w-full rounded-xl border border-border px-3.5 py-2.5 text-sm outline-none focus:border-accent focus:ring-4 focus:ring-accent/10"></div>
          <div><label class="mb-1 block text-xs text-muted">Investimenti (ETF, azioni...)</label><input id="nw-invest" type="number" value="0" class="w-full rounded-xl border border-border px-3.5 py-2.5 text-sm outline-none focus:border-accent focus:ring-4 focus:ring-accent/10"></div>
          <div><label class="mb-1 block text-xs text-muted">Immobili</label><input id="nw-realestate" type="number" value="0" class="w-full rounded-xl border border-border px-3.5 py-2.5 text-sm outline-none focus:border-accent focus:ring-4 focus:ring-accent/10"></div>
        </div>
      </div>
      <div>
        <div class="mb-3 text-xs font-semibold uppercase tracking-wide text-expense">Passività (cosa devi)</div>
        <div class="space-y-3">
          <div><label class="mb-1 block text-xs text-muted">Mutuo residuo</label><input id="nw-mortgage" type="number" value="0" class="w-full rounded-xl border border-border px-3.5 py-2.5 text-sm outline-none focus:border-accent focus:ring-4 focus:ring-accent/10"></div>
          <div><label class="mb-1 block text-xs text-muted">Altri prestiti/debiti</label><input id="nw-loans" type="number" value="0" class="w-full rounded-xl border border-border px-3.5 py-2.5 text-sm outline-none focus:border-accent focus:ring-4 focus:ring-accent/10"></div>
        </div>
      </div>
    </div>

    <div class="mt-8 rounded-xl border-2 border-accent bg-accentSoft p-5 text-center">
      <div class="text-[11px] uppercase text-accent">Patrimonio netto</div>
      <div id="nw-out-total" class="font-mono text-3xl font-bold text-accent">—</div>
    </div>
  </div>

  <div class="mt-10 space-y-4 text-[15px] leading-relaxed text-gray-700">
    <h2 class="text-xl font-bold text-gray-900">Perché il patrimonio netto conta più del solo saldo del conto</h2>
    <p>Il saldo del conto corrente è solo un pezzo della tua situazione finanziaria reale. Il patrimonio netto (attività meno passività) dà un quadro molto più completo: puoi avere pochi soldi liquidi ma un patrimonio netto solido grazie a una casa senza mutuo, oppure un conto pieno ma un patrimonio netto basso a causa di debiti importanti.</p>
    <p>Se hai un mutuo cointestato con un'altra persona, ricordati di inserire solo la <strong>tua quota</strong> di debito residuo, non l'importo totale — altrimenti il calcolo risulterebbe sballato rispetto alla tua situazione reale.</p>
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
  <a href="/calcolatori.php" class="mb-8 inline-block text-sm font-semibold text-accent hover:underline">Vedi tutti i calcolatori →</a>
  <h2 class="mb-2 text-xl font-bold">Vuoi che il calcolo si aggiorni da solo, quota mutuo inclusa?</h2>
  <p class="mb-6 text-muted">Reflexi calcola il patrimonio netto in automatico, gestendo da solo le quote di proprietà sui mutui cointestati.</p>
  <a href="/app/" class="inline-block rounded-xl bg-accent px-7 py-3 text-sm font-semibold text-white shadow-lg shadow-accent/25 transition hover:bg-accentDark">Prova Reflexi gratis</a>
</section>

<?php include '_footer.php'; ?>

<script>
function euro(n) {
  if (!isFinite(n)) return '—';
  return new Intl.NumberFormat('it-IT', { style: 'currency', currency: 'EUR', maximumFractionDigits: 0 }).format(n);
}
function calc() {
  const assets = ['nw-liquid', 'nw-invest', 'nw-realestate'].reduce((s, id) => s + (parseFloat(document.getElementById(id).value) || 0), 0);
  const liabilities = ['nw-mortgage', 'nw-loans'].reduce((s, id) => s + (parseFloat(document.getElementById(id).value) || 0), 0);
  document.getElementById('nw-out-total').textContent = euro(assets - liabilities);
}
['nw-liquid', 'nw-invest', 'nw-realestate', 'nw-mortgage', 'nw-loans'].forEach((id) => document.getElementById(id).addEventListener('input', calc));
calc();
</script>

</body>
</html>
