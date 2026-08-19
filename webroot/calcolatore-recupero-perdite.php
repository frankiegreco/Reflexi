<?php $active = 'calcolatori'; ?>
<!DOCTYPE html>
<html lang="it">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Calcolatore di recupero perdite — Reflexi</title>
<meta name="description" content="Scopri quanto guadagno serve per tornare in pari dopo una perdita negli investimenti. Gratuito, senza registrazione.">
<meta name="robots" content="index, follow">
<link rel="canonical" href="https://reflexi.it/calcolatore-recupero-perdite.php">
<!-- Open Graph (Facebook, WhatsApp, LinkedIn, Telegram...) -->
<meta property="og:type" content="website">
<meta property="og:url" content="https://reflexi.it/calcolatore-recupero-perdite.php">
<meta property="og:site_name" content="Reflexi">
<meta property="og:title" content="Calcolatore di recupero perdite — Reflexi">
<meta property="og:description" content="Scopri quanto guadagno serve per tornare in pari dopo una perdita negli investimenti. Gratuito, senza registrazione.">
<meta property="og:image" content="https://reflexi.it/og-image.png?v=3">
<meta property="og:image:width" content="1200">
<meta property="og:image:height" content="630">
<meta property="og:locale" content="it_IT">

<!-- Twitter/X Card -->
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="Calcolatore di recupero perdite — Reflexi">
<meta name="twitter:description" content="Scopri quanto guadagno serve per tornare in pari dopo una perdita negli investimenti. Gratuito, senza registrazione.">
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
  <div class="mx-auto mb-3 flex h-14 w-14 items-center justify-center rounded-2xl bg-expense/10 text-3xl">🩹</div>
  <h1 class="mx-auto mb-3 max-w-2xl text-3xl font-extrabold leading-tight tracking-tight sm:text-4xl">Calcolatore di recupero perdite</h1>
  <p class="mx-auto max-w-xl text-lg text-muted">Dopo un calo, il guadagno necessario per tornare al capitale iniziale è più alto di quanto sembri.</p>
</section>

<main class="mx-auto max-w-3xl px-6 py-8">
  <div class="rounded-2xl border border-border bg-white p-6 shadow-sm sm:p-8">
    <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-muted">Perdita subita (%)</label>
    <input id="lr-loss" type="range" value="20" min="1" max="95" class="w-full">
    <div class="mt-1 text-center font-mono text-2xl font-bold text-expense" id="lr-loss-display">-20%</div>

    <div class="mt-6 rounded-xl border-2 border-accent bg-accentSoft p-5 text-center">
      <div class="text-[11px] uppercase text-accent">Guadagno necessario per tornare in pari</div>
      <div id="lr-out" class="font-mono text-3xl font-bold text-accent">—</div>
    </div>

    <div class="mt-6 overflow-x-auto">
      <table class="w-full text-sm">
        <thead><tr class="border-b border-border text-left text-[11px] uppercase tracking-wide text-muted"><th class="py-2">Perdita subita</th><th class="py-2 text-right">Guadagno necessario</th></tr></thead>
        <tbody>
          <tr class="border-b border-border"><td class="py-1.5">-10%</td><td class="py-1.5 text-right font-mono">+11,1%</td></tr>
          <tr class="border-b border-border"><td class="py-1.5">-20%</td><td class="py-1.5 text-right font-mono">+25,0%</td></tr>
          <tr class="border-b border-border"><td class="py-1.5">-30%</td><td class="py-1.5 text-right font-mono">+42,9%</td></tr>
          <tr class="border-b border-border"><td class="py-1.5">-50%</td><td class="py-1.5 text-right font-mono font-semibold text-expense">+100,0%</td></tr>
          <tr><td class="py-1.5">-70%</td><td class="py-1.5 text-right font-mono font-semibold text-expense">+233,3%</td></tr>
        </tbody>
      </table>
    </div>
  </div>

  <div class="mt-10 space-y-4 text-[15px] leading-relaxed text-gray-700">
    <h2 class="text-xl font-bold text-gray-900">Perché le perdite pesano più dei guadagni</h2>
    <p>La matematica delle percentuali è asimmetrica: se un capitale di 100€ scende del 50%, resta a 50€. Ma per tornare a 100€ da 50€, serve un guadagno del 100%, non del 50% — perché la base di calcolo è cambiata. Più grande è la perdita, più questa asimmetria si amplifica in modo drastico: una perdita del 90% richiede un guadagno del 900% per tornare in pari.</p>
    <p>Questo è uno dei motivi principali per cui la gestione del rischio (diversificazione, dimensionamento delle posizioni) conta più di quanto sembri: evitare le perdite grandi è spesso più importante che inseguire i guadagni grandi.</p>
  </div>
</main>

<section class="px-6 py-16 text-center">
  <p class="mb-4 text-sm text-muted">Cerchi anche un altro calcolatore?</p>
  <a href="/calcolatori.php" class="mb-8 inline-block text-sm font-semibold text-accent hover:underline">Vedi tutti i calcolatori →</a>
  <h2 class="mb-2 text-xl font-bold">Tieni sotto controllo plus e minusvalenze reali</h2>
  <p class="mb-6 text-muted">Reflexi calcola il costo medio di carico e la tassazione automaticamente, ad ogni vendita.</p>
  <a href="/app/" class="inline-block rounded-xl bg-accent px-7 py-3 text-sm font-semibold text-white shadow-lg shadow-accent/25 transition hover:bg-accentDark">Prova Reflexi gratis</a>
</section>

<?php include '_footer.php'; ?>

<script>
function calc() {
  const loss = parseFloat(document.getElementById('lr-loss').value) || 0;
  document.getElementById('lr-loss-display').textContent = '-' + loss + '%';
  const needed = loss < 100 ? (loss / (100 - loss)) * 100 : Infinity;
  document.getElementById('lr-out').textContent = isFinite(needed) ? '+' + needed.toFixed(1) + '%' : '—';
}
document.getElementById('lr-loss').addEventListener('input', calc);
calc();
</script>

</body>
</html>
