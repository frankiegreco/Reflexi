<?php
$active = 'calcolatori';
require_once __DIR__ . '/_news_db.php';
$relatedArticles = news_related(['pensione-integrativa-fondo-pensione-quanto-serve', 'indipendenza-finanziaria-fire-come-calcolare-il-numero']);
?>
<!DOCTYPE html>
<html lang="it">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Calcolatore per la pensione — Reflexi</title>
<meta name="description" content="Calcola quanto capitale potresti accumulare entro l'età obiettivo, con grafico di accumulo. Gratuito, senza registrazione.">
<meta name="robots" content="index, follow">
<link rel="canonical" href="https://reflexi.it/calcolatore-pensione.php">
<!-- Open Graph (Facebook, WhatsApp, LinkedIn, Telegram...) -->
<meta property="og:type" content="website">
<meta property="og:url" content="https://reflexi.it/calcolatore-pensione.php">
<meta property="og:site_name" content="Reflexi">
<meta property="og:title" content="Calcolatore per la pensione — Reflexi">
<meta property="og:description" content="Calcola quanto capitale potresti accumulare entro l'età obiettivo, con grafico di accumulo. Gratuito, senza registrazione.">
<meta property="og:image" content="https://reflexi.it/og-image.png?v=3">
<meta property="og:image:width" content="1200">
<meta property="og:image:height" content="630">
<meta property="og:locale" content="it_IT">

<!-- Twitter/X Card -->
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="Calcolatore per la pensione — Reflexi">
<meta name="twitter:description" content="Calcola quanto capitale potresti accumulare entro l'età obiettivo, con grafico di accumulo. Gratuito, senza registrazione.">
<meta name="twitter:image" content="https://reflexi.it/og-image.png?v=3">
<link rel="icon" type="image/svg+xml" href="/favicon.svg">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=IBM+Plex+Mono:wght@500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="/css/site.css?v=<?= filemtime(__DIR__ . '/css/site.css') ?>">
<style>
  body { font-family: 'Inter', ui-sans-serif, system-ui, sans-serif; } .font-mono { font-family: 'IBM Plex Mono', ui-monospace, monospace; }
</style>
<?php
require_once __DIR__ . '/_seo.php';
seo_breadcrumb([['Calcolatori', '/calcolatori.php'], ['Calcolatore pensione', '/calcolatore-pensione.php']]);
seo_calculator('Calcolatore pensione', '/calcolatore-pensione.php', 'Calcola quanto capitale potresti accumulare entro l\'età obiettivo, con grafico di accumulo. Gratuito, senza registrazione.');
?>
</head>
<body class="bg-bg text-gray-900 antialiased">

<?php include '_nav.php'; ?>

<section class="px-6 pb-6 pt-14 text-center sm:pt-16">
  <a href="/calcolatori.php" class="mb-4 inline-block text-sm text-muted hover:text-accent">← Tutti i calcolatori</a>
  <div class="mx-auto mb-3 flex h-14 w-14 items-center justify-center rounded-2xl bg-loan/10 text-3xl">🏖️</div>
  <h1 class="mx-auto mb-3 max-w-2xl text-3xl font-extrabold leading-tight tracking-tight sm:text-4xl">Calcolatore per la pensione</h1>
  <p class="mx-auto max-w-xl text-lg text-muted">Quanto potresti accumulare entro l'età che ti sei posto come obiettivo.</p>
</section>

<main class="mx-auto max-w-3xl px-6 py-8">
  <div class="rounded-2xl border border-border bg-white p-6 shadow-sm sm:p-8">
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
      <div>
        <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-muted">Età attuale</label>
        <input id="ret-age" type="number" value="35" min="18" max="80" class="w-full rounded-xl border border-border px-3.5 py-2.5 text-sm outline-none focus:border-accent focus:ring-4 focus:ring-accent/10">
      </div>
      <div>
        <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-muted">Età obiettivo</label>
        <input id="ret-target" type="number" value="67" min="19" max="90" class="w-full rounded-xl border border-border px-3.5 py-2.5 text-sm outline-none focus:border-accent focus:ring-4 focus:ring-accent/10">
      </div>
      <div>
        <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-muted">Risparmi attuali (€)</label>
        <input id="ret-current" type="number" value="15000" min="0" class="w-full rounded-xl border border-border px-3.5 py-2.5 text-sm outline-none focus:border-accent focus:ring-4 focus:ring-accent/10">
      </div>
      <div>
        <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-muted">Versamento mensile (€)</label>
        <input id="ret-monthly" type="number" value="300" min="0" class="w-full rounded-xl border border-border px-3.5 py-2.5 text-sm outline-none focus:border-accent focus:ring-4 focus:ring-accent/10">
      </div>
      <div class="sm:col-span-2">
        <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-muted">Rendimento annuo atteso (%)</label>
        <input id="ret-rate" type="number" value="5" step="0.1" min="0" class="w-full rounded-xl border border-border px-3.5 py-2.5 text-sm outline-none focus:border-accent focus:ring-4 focus:ring-accent/10">
      </div>
    </div>

    <div class="mt-6 grid grid-cols-1 gap-3 sm:grid-cols-2">
      <div class="rounded-xl border-2 border-accent bg-accentSoft p-4 text-center">
        <div class="text-[11px] uppercase text-accent">Capitale stimato all'età obiettivo</div>
        <div id="ret-out-total" class="font-mono text-2xl font-bold text-accent">—</div>
      </div>
      <div class="rounded-xl border border-border p-4 text-center">
        <div class="text-[11px] uppercase text-muted">Rendita mensile sostenibile stimata*</div>
        <div id="ret-out-monthly" class="font-mono text-2xl font-bold">—</div>
      </div>
    </div>

    <div class="mt-6">
      <div class="mb-2 flex items-center gap-4 text-xs text-muted">
        <span class="flex items-center gap-1.5"><span class="h-2.5 w-2.5 rounded-full bg-accent/30"></span>Versato</span>
        <span class="flex items-center gap-1.5"><span class="h-2.5 w-2.5 rounded-full bg-income"></span>Crescita</span>
      </div>
      <div id="ret-chart" class="flex h-48 items-end gap-1"></div>
      <div id="ret-chart-labels" class="mt-1 flex justify-between text-[10px] text-muted"></div>
    </div>

    <p class="mt-4 text-xs text-muted">*Stima approssimativa (regola del 4% annuo), non tiene conto di inflazione, tasse o longevità reale — non sostituisce una consulenza previdenziale professionale.</p>
  </div>

  <div class="mt-10 space-y-4 text-[15px] leading-relaxed text-gray-700">
    <h2 class="text-xl font-bold text-gray-900">Perché iniziare presto conta più dell'importo versato</h2>
    <p>Grazie all'interesse composto, chi inizia a versare anche solo poche decine di euro al mese vent'anni prima di un altro spesso arriva a un capitale finale più alto, pur versando in totale meno soldi — perché ha lasciato più tempo alla crescita di agire. Questo calcolatore ti dà una stima di dove potresti arrivare, in base a quanto versi oggi e per quanti anni.</p>
    <p>La "rendita mensile sostenibile" usa la cosiddetta regola del 4%: un principio approssimativo secondo cui prelevare il 4% del capitale all'anno (circa) permette storicamente al capitale di durare a lungo senza esaurirsi rapidamente. È una semplificazione, non una garanzia — la pianificazione pensionistica reale è più complessa e merita una consulenza dedicata.</p>
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
  <h2 class="mb-2 text-xl font-bold">Tieni traccia del percorso verso la pensione, non solo la proiezione</h2>
  <p class="mb-6 text-muted">Con gli Obiettivi di risparmio di Reflexi, il progresso si aggiorna da solo insieme alle tue transazioni reali.</p>
  <a href="/app/" class="inline-block rounded-xl bg-accent px-7 py-3 text-sm font-semibold text-white shadow-lg shadow-accent/25 transition hover:bg-accentDark">Prova Reflexi gratis</a>
</section>

<?php include '_footer.php'; ?>

<script>
function euro(n) {
  if (!isFinite(n)) return '—';
  return new Intl.NumberFormat('it-IT', { style: 'currency', currency: 'EUR', maximumFractionDigits: 0 }).format(n);
}
function calc() {
  const age = parseInt(document.getElementById('ret-age').value) || 0;
  const target = parseInt(document.getElementById('ret-target').value) || 0;
  const current = parseFloat(document.getElementById('ret-current').value) || 0;
  const monthly = parseFloat(document.getElementById('ret-monthly').value) || 0;
  const rate = parseFloat(document.getElementById('ret-rate').value) || 0;

  const years = Math.max(0, target - age);
  const monthlyRate = (rate / 100) / 12;

  const chart = document.getElementById('ret-chart');
  const labels = document.getElementById('ret-chart-labels');
  chart.innerHTML = ''; labels.innerHTML = '';

  let total = current;
  let invested = current;
  const yearVals = [{ total, invested }];
  for (let y = 1; y <= years; y++) {
    for (let m = 0; m < 12; m++) {
      total = total * (1 + monthlyRate) + monthly;
      invested += monthly;
    }
    yearVals.push({ total, invested });
  }
  const maxVal = Math.max(...yearVals.map((v) => v.total), 1);
  const step = Math.max(1, Math.ceil(years / 10));

  yearVals.forEach((v, y) => {
    const col = document.createElement('div');
    col.className = 'flex h-full w-full flex-col justify-end';
    const gain = v.total - v.invested;
    const investedPct = Math.max(1, (v.invested / maxVal) * 100);
    const gainPct = Math.max(0, (gain / maxVal) * 100);
    col.innerHTML = `<div class="w-full rounded-t bg-income" style="height:${gainPct}%"></div><div class="w-full bg-accent/30" style="height:${investedPct}%"></div>`;
    chart.appendChild(col);
    if (y % step === 0 || y === years) {
      const lbl = document.createElement('span');
      lbl.textContent = age + y;
      labels.appendChild(lbl);
    }
  });

  const final = yearVals[yearVals.length - 1].total;
  const sustainableMonthly = (final * 0.04) / 12;
  document.getElementById('ret-out-total').textContent = euro(final);
  document.getElementById('ret-out-monthly').textContent = euro(sustainableMonthly);
}
['ret-age', 'ret-target', 'ret-current', 'ret-monthly', 'ret-rate'].forEach((id) => document.getElementById(id).addEventListener('input', calc));
calc();
</script>

</body>
</html>
