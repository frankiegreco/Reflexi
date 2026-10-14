<?php
$active = 'piani';
require_once __DIR__ . '/_pricing.php';
// Prezzi da Stripe: null se non disponibili (mai cifre scritte a mano).
$pricing = reflexi_pricing();
$tax = $pricing['tax_behavior'];
$monthly = $pricing['monthly'];
$annual = $pricing['annual'];
$monthsSaved = $pricing['months_saved'];
?>
<!DOCTYPE html>
<html lang="it">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Prezzi di Reflexi: 6 mesi gratis, poi mensile o annuale</title>
<meta name="description" content="6 mesi (180 giorni) di prova gratuita, poi scegli il piano mensile o annuale. Tutte le funzionalità incluse in entrambi, nessuna sorpresa.">
<meta name="robots" content="index, follow">
<link rel="canonical" href="https://reflexi.it/piani">
<!-- Open Graph (Facebook, WhatsApp, LinkedIn, Telegram...) -->
<meta property="og:type" content="website">
<meta property="og:url" content="https://reflexi.it/piani">
<meta property="og:site_name" content="Reflexi">
<meta property="og:title" content="Prezzi di Reflexi: 6 mesi gratis, poi mensile o annuale">
<meta property="og:description" content="6 mesi (180 giorni) di prova gratuita, poi scegli il piano mensile o annuale. Tutte le funzionalità incluse in entrambi, nessuna sorpresa.">
<meta property="og:image" content="<?php require_once __DIR__ . '/_seo.php'; echo seo_og_image(); ?>">
<meta property="og:image:width" content="1200">
<meta property="og:image:height" content="630">
<meta property="og:locale" content="it_IT">

<!-- Twitter/X Card -->
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="Prezzi di Reflexi: 6 mesi gratis, poi mensile o annuale">
<meta name="twitter:description" content="6 mesi (180 giorni) di prova gratuita, poi scegli il piano mensile o annuale. Tutte le funzionalità incluse in entrambi, nessuna sorpresa.">
<meta name="twitter:image" content="<?php require_once __DIR__ . '/_seo.php'; echo seo_og_image(); ?>">
<link rel="icon" type="image/svg+xml" href="/favicon.svg">
<link rel="preload" href="/fonts/inter-v20-latin.woff2" as="font" type="font/woff2" crossorigin>
<link rel="stylesheet" href="/css/site.css?v=<?= filemtime(__DIR__ . '/css/site.css') ?>">
<style>
  body { font-family: 'Inter', ui-sans-serif, system-ui, sans-serif; } .font-mono { font-family: 'IBM Plex Mono', ui-monospace, monospace; }
</style>
<?php
require_once __DIR__ . '/_seo.php';
seo_breadcrumb([['Piani e prezzi', '/piani']]);
seo_jsonld(seo_software_application());
?>
</head>
<body class="bg-bg text-gray-900 antialiased">

<?php include '_nav.php'; ?>

<section class="px-6 pb-8 pt-14 text-center sm:pt-20">
  <span class="mb-4 inline-block rounded-full bg-accentSoft px-4 py-1.5 text-xs font-semibold uppercase tracking-wider text-accent">Piani e prezzi</span>
  <h1 class="mx-auto mb-3 max-w-2xl text-3xl font-extrabold leading-tight tracking-tight sm:text-4xl">6 mesi (180 giorni) gratis, poi un prezzo semplice</h1>
  <p class="mx-auto max-w-xl text-lg text-muted">Nessuna carta richiesta per iniziare. Tutte le funzionalità incluse in entrambi i piani — cambia solo la frequenza di fatturazione.</p>
</section>

<main class="mx-auto max-w-4xl px-6 py-8">
  <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">

    <div class="rounded-2xl border-2 border-border bg-white p-8">
      <div class="mb-1 text-sm font-semibold uppercase tracking-wide text-muted">Mensile</div>
      <div class="mb-1 flex items-baseline gap-1">
        <?php if ($monthly): ?>
        <span class="font-mono text-4xl font-bold"><?= htmlspecialchars(pricing_format($monthly['amount'], $monthly['currency'], $tax)) ?></span>
        <span class="text-muted">/mese</span>
        <?php else: ?>
        <span class="text-lg font-semibold">Prezzo visibile al checkout</span>
        <?php endif; ?>
      </div>
      <p class="mb-6 text-sm text-muted">Fatturato ogni mese. Annulla quando vuoi.</p>
      <a href="/app/" class="block rounded-xl border-2 border-accent bg-white px-6 py-3 text-center text-sm font-semibold text-accent transition hover:bg-accentSoft">Inizia la prova gratuita</a>
    </div>

    <div class="relative rounded-2xl border-2 border-accent bg-accentSoft p-8">
      <?php if ($monthsSaved !== null && $monthsSaved >= 1): ?>
      <span class="absolute -top-3 left-1/2 -translate-x-1/2 rounded-full bg-income px-3 py-1 text-[11px] font-bold uppercase tracking-wide text-white">Risparmi <?= $monthsSaved ?> <?= $monthsSaved === 1 ? 'mese' : 'mesi' ?></span>
      <?php endif; ?>
      <div class="mb-1 text-sm font-semibold uppercase tracking-wide text-accent">Annuale</div>
      <div class="mb-1 flex items-baseline gap-1">
        <?php if ($annual): ?>
        <span class="font-mono text-4xl font-bold text-accent"><?= htmlspecialchars(pricing_format($annual['amount'], $annual['currency'], $tax)) ?></span>
        <span class="text-muted">/anno</span>
        <?php else: ?>
        <span class="text-lg font-semibold">Prezzo visibile al checkout</span>
        <?php endif; ?>
      </div>
      <p class="mb-6 text-sm text-muted"><?php if ($pricing['annual_per_month'] !== null): ?>Circa <?= htmlspecialchars(pricing_format($pricing['annual_per_month'], $annual['currency'], $tax)) ?>/mese, fatturato<?php else: ?>Fatturato<?php endif; ?> una volta l'anno.</p>
      <a href="/app/" class="block rounded-xl bg-accent px-6 py-3 text-center text-sm font-semibold text-white shadow-lg shadow-accent/25 transition hover:bg-accentDark">Inizia la prova gratuita</a>
    </div>

  </div>

  <div class="mt-4 flex items-center justify-center gap-2 text-xs text-muted">
    <span>Pagamenti sicuri gestiti da</span>
    <img src="/stripe-badge.svg" alt="Stripe" class="h-4" onerror="this.replaceWith(Object.assign(document.createElement('span'),{textContent:'Stripe',className:'font-semibold'}))">
  </div>

  <!-- COSA E' INCLUSO -->
  <div class="mt-16">
    <h2 class="mb-8 text-center text-2xl font-bold">Tutto incluso, in entrambi i piani</h2>
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
      <div class="flex gap-3"><span class="mt-0.5 text-income">✓</span><span class="text-sm text-gray-700"><strong>Mutui</strong> — piano di ammortamento, rientro ed estinzione anticipata, quote di proprietà</span></div>
      <div class="flex gap-3"><span class="mt-0.5 text-income">✓</span><span class="text-sm text-gray-700"><strong>Investimenti</strong> — quotazioni live, PAC automatici, storico prezzi, tassazione plusvalenze</span></div>
      <div class="flex gap-3"><span class="mt-0.5 text-income">✓</span><span class="text-sm text-gray-700"><strong>Budget con rollover</strong> — limiti per categoria che si riportano al mese successivo</span></div>
      <div class="flex gap-3"><span class="mt-0.5 text-income">✓</span><span class="text-sm text-gray-700"><strong>Analisi predittiva</strong> — simulazione Monte Carlo, rilevamento abbonamenti dimenticati</span></div>
      <div class="flex gap-3"><span class="mt-0.5 text-income">✓</span><span class="text-sm text-gray-700"><strong>Obiettivi di risparmio</strong> — progresso calcolato sul tuo risparmio reale</span></div>
      <div class="flex gap-3"><span class="mt-0.5 text-income">✓</span><span class="text-sm text-gray-700"><strong>Patrimonio netto</strong> — liquidità, investimenti e immobili, mutui inclusi</span></div>
      <div class="flex gap-3"><span class="mt-0.5 text-income">✓</span><span class="text-sm text-gray-700"><strong>Interessi sui conti</strong> — conti deposito e vincolati, accredito automatico</span></div>
      <div class="flex gap-3"><span class="mt-0.5 text-income">✓</span><span class="text-sm text-gray-700"><strong>Riepilogo periodico via email</strong> — settimanale o mensile, opzionale</span></div>
    </div>
  </div>

  <!-- FAQ BREVI -->
  <div class="mt-16">
    <h2 class="mb-6 text-center text-2xl font-bold">Domande frequenti sui piani</h2>
    <div class="space-y-2">
      <details class="group rounded-xl border border-border bg-white p-0">
        <summary class="flex cursor-pointer list-none items-center justify-between px-5 py-4 text-sm font-semibold">
          Cosa succede dopo il periodo di prova?
          <svg aria-hidden="true" class="h-4 w-4 flex-shrink-0 text-muted transition-transform group-open:rotate-180" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"/></svg>
        </summary>
        <div class="border-t border-border px-5 py-4 text-sm text-gray-700">Puoi scegliere il piano mensile o annuale dal tuo Profilo. Se non scegli nulla, l'accesso si blocca fino a quando non attivi un abbonamento — i tuoi dati restano al sicuro e intatti.</div>
      </details>
      <details class="group rounded-xl border border-border bg-white p-0">
        <summary class="flex cursor-pointer list-none items-center justify-between px-5 py-4 text-sm font-semibold">
          Posso annullare quando voglio?
          <svg aria-hidden="true" class="h-4 w-4 flex-shrink-0 text-muted transition-transform group-open:rotate-180" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"/></svg>
        </summary>
        <div class="border-t border-border px-5 py-4 text-sm text-gray-700">Sì, in autonomia dal Profilo tramite il portale sicuro di Stripe — nessuna email da scrivere, nessun vincolo minimo.</div>
      </details>
      <details class="group rounded-xl border border-border bg-white p-0">
        <summary class="flex cursor-pointer list-none items-center justify-between px-5 py-4 text-sm font-semibold">
          Posso passare da mensile ad annuale (o viceversa)?
          <svg aria-hidden="true" class="h-4 w-4 flex-shrink-0 text-muted transition-transform group-open:rotate-180" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"/></svg>
        </summary>
        <div class="border-t border-border px-5 py-4 text-sm text-gray-700">Sì: scrivici a <a href="mailto:info@reflexi.it" class="text-accent hover:underline">info@reflexi.it</a> e ti aiutiamo a cambiare piano.</div>
      </details>
    </div>
    <p class="mt-4 text-center text-sm text-muted">Altre domande? Vedi tutte le <a href="/faq" class="text-accent hover:underline">FAQ</a>.</p>
  </div>
</main>

<section class="px-6 py-16 text-center">
  <h2 class="mb-2 text-xl font-bold">Prova Reflexi senza rischi</h2>
  <p class="mb-6 text-muted">6 mesi (180 giorni) completi, gratis, senza carta di credito.</p>
  <a href="/app/" class="inline-block rounded-xl bg-accent px-8 py-3.5 text-base font-semibold text-white shadow-lg shadow-accent/25 transition hover:bg-accentDark">Crea il tuo account</a>
</section>

<?php include '_footer.php'; ?>

</body>
</html>
