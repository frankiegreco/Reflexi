<?php
require_once __DIR__ . '/_news_db.php';
$recentArticles = news_db()->query(
    "SELECT slug, title, excerpt, category, cover_emoji FROM news_articles
     WHERE status = 'pubblicato' AND published_at <= NOW()
     ORDER BY published_at DESC LIMIT 3"
)->fetchAll();
$categoryLabels = [
    'mutui' => 'Mutui',
    'investimenti' => 'Investimenti',
    'budget' => 'Budget',
    'pianificazione' => 'Pianificazione',
];
?>
<!DOCTYPE html>
<html lang="it">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Reflexi — Il riflesso vero delle tue finanze</title>
<meta name="description" content="Reflexi mette insieme mutuo, investimenti, budget e analisi predittiva in un unico posto — l'unica app che fa parlare tra loro i pezzi della tua vita finanziaria.">
<meta name="robots" content="index, follow">
<link rel="canonical" href="https://reflexi.it/">

<!-- Open Graph (Facebook, WhatsApp, LinkedIn, Telegram...) -->
<meta property="og:type" content="website">
<meta property="og:url" content="https://reflexi.it/">
<meta property="og:site_name" content="Reflexi">
<meta property="og:title" content="Reflexi — Il riflesso vero delle tue finanze">
<meta property="og:description" content="Mutuo, investimenti, budget e analisi predittiva in un unico posto. L'unica app che fa parlare tra loro i pezzi della tua vita finanziaria.">
<meta property="og:image" content="https://reflexi.it/og-image.png?v=3">
<meta property="og:image:width" content="1200">
<meta property="og:image:height" content="630">
<meta property="og:locale" content="it_IT">

<!-- Twitter/X Card -->
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="Reflexi — Il riflesso vero delle tue finanze">
<meta name="twitter:description" content="Mutuo, investimenti, budget e analisi predittiva in un unico posto.">
<meta name="twitter:image" content="https://reflexi.it/og-image.png?v=3">

<link rel="icon" type="image/svg+xml" href="/favicon.svg">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=IBM+Plex+Mono:wght@500;600&display=swap" rel="stylesheet">
<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "SoftwareApplication",
  "name": "Reflexi",
  "url": "https://reflexi.it/",
  "applicationCategory": "FinanceApplication",
  "operatingSystem": "Web",
  "description": "App di finanza personale che unisce mutuo, investimenti, budget e analisi predittiva in un'unica piattaforma.",
  "offers": {
    "@type": "Offer",
    "price": "0",
    "priceCurrency": "EUR"
  },
  "inLanguage": "it-IT"
}
</script>
<link rel="stylesheet" href="/css/site.css?v=1">
<style>
  .glow { position: absolute; border-radius: 9999px; filter: blur(60px); pointer-events: none; }
</style>
<script>
  (function () {
    var GA_ID = 'G-YVZ3MKFSGP';

    window.loadGA = function () {
      var s = document.createElement('script');
      s.async = true;
      s.src = 'https://www.googletagmanager.com/gtag/js?id=' + GA_ID;
      document.head.appendChild(s);
      window.dataLayer = window.dataLayer || [];
      window.gtag = function () { dataLayer.push(arguments); };
      gtag('js', new Date());
      gtag('config', GA_ID);
    };

    window.acceptCookies = function () {
      localStorage.setItem('reflexi_cookie_consent', 'accepted');
      document.getElementById('cookie-banner').classList.add('hidden');
      window.loadGA();
    };
    window.declineCookies = function () {
      localStorage.setItem('reflexi_cookie_consent', 'declined');
      document.getElementById('cookie-banner').classList.add('hidden');
    };

    document.addEventListener('DOMContentLoaded', function () {
      var consent = localStorage.getItem('reflexi_cookie_consent');
      if (consent === 'accepted') {
        window.loadGA();
      } else if (consent === null) {
        document.getElementById('cookie-banner').classList.remove('hidden');
      }
    });
  })();
</script>
</head>
<body class="bg-bg text-gray-900 antialiased">

<!-- NAV -->
<?php $active = ''; include '_nav.php'; ?>

<!-- HERO -->
<section class="relative overflow-hidden px-6 pb-20 pt-16 sm:pt-24">
  <div class="glow -left-32 -top-32 h-96 w-96 bg-accent/20"></div>
  <div class="glow -right-32 top-40 h-80 w-80 bg-loan/10"></div>
  <div class="mx-auto max-w-3xl text-center">
    <span class="mb-5 inline-block rounded-full bg-accentSoft px-4 py-1.5 text-xs font-semibold uppercase tracking-wider text-accent">
      Ora in beta — accesso gratuito
    </span>
    <h1 class="mb-5 text-4xl font-extrabold leading-tight tracking-tight sm:text-6xl">
      Il riflesso vero<br class="hidden sm:block"> delle tue finanze
    </h1>
    <p class="mx-auto mb-9 max-w-xl text-lg text-muted">
      Mutuo, investimenti, spese quotidiane e proiezioni future — di solito li tieni su tre app diverse
      che non si parlano. Reflexi li mette in un unico posto, con la chiarezza di uno specchio.
    </p>
    <div class="relative flex items-center justify-center">
      <a href="/app/"
         class="flex h-40 w-40 flex-shrink-0 items-center justify-center rounded-full border-[14px] border-accent bg-bg text-center text-base font-bold text-accent shadow-lg transition hover:scale-105 hover:bg-accentSoft sm:h-48 sm:w-48 sm:border-[16px] sm:text-lg">
        Inizia<br>gratis
      </a>
      <a href="#funzionalita"
         class="-ml-10 flex h-40 w-40 flex-shrink-0 items-center justify-center rounded-full border-[14px] border-accent/30 bg-bg text-center text-sm font-bold text-accent/70 shadow-lg transition hover:scale-105 hover:bg-accentSoft sm:-ml-12 sm:h-48 sm:w-48 sm:border-[16px] sm:text-base">
        Scopri come<br>funziona
      </a>
      <svg class="pointer-events-none absolute inset-0 h-full w-full" viewBox="0 0 280 160" preserveAspectRatio="xMidYMid meet" aria-hidden="true">
        <path id="ctaInfinityPath" d="M140,80 C140,40 100,20 70,35 C40,50 40,110 70,125 C100,140 140,120 140,80 C140,40 180,20 210,35 C240,50 240,110 210,125 C180,140 140,120 140,80 Z" fill="none" stroke="none"/>
        <circle r="10" fill="#f59e0b" stroke="#fff" stroke-width="2">
          <animateMotion dur="5s" repeatCount="indefinite">
            <mpath href="#ctaInfinityPath"/>
          </animateMotion>
        </circle>
        <text font-size="12" font-weight="700" fill="#fff" text-anchor="middle" dy="4">
          €
          <animateMotion dur="5s" repeatCount="indefinite">
            <mpath href="#ctaInfinityPath"/>
          </animateMotion>
        </text>
      </svg>
    </div>
  </div>

  <!-- Mockup hero: dashboard dentro una cornice di smartphone -->
  <div class="mx-auto mt-16 flex justify-center">
    <div class="w-[280px] rounded-[2.5rem] border-[10px] border-gray-900 bg-gray-900 shadow-2xl shadow-gray-400/40">
      <div class="relative h-[560px] overflow-hidden rounded-[1.75rem] bg-bg">
        <div class="absolute left-1/2 top-0 z-10 h-5 w-28 -translate-x-1/2 rounded-b-2xl bg-gray-900"></div>
        <div class="h-full overflow-hidden px-3 pb-3 pt-7">
          <div class="mb-2 flex items-center gap-1.5">
            <img src="/favicon.svg" alt="" class="h-5 w-5 rounded-md">
            <span class="text-xs font-bold">Reflexi</span>
          </div>
          <div class="rounded-xl bg-gradient-to-br from-accent via-accentDark to-indigo-900 p-4 text-white">
            <div class="text-[9px] uppercase tracking-wider opacity-70">Saldo complessivo</div>
            <div class="font-mono text-xl font-semibold">€ 12.480,32</div>
          </div>
          <div class="mt-2 grid grid-cols-2 gap-2">
            <div class="rounded-lg border border-border bg-white p-2">
              <div class="text-[8px] uppercase text-muted">Mutuo residuo</div>
              <div class="font-mono text-xs font-semibold">€ 106.789</div>
            </div>
            <div class="rounded-lg border border-border bg-white p-2">
              <div class="text-[8px] uppercase text-muted">Portafoglio</div>
              <div class="font-mono text-xs font-semibold text-income">+8,4%</div>
            </div>
          </div>
          <div class="mt-2 rounded-lg border border-border bg-white p-2">
            <div class="mb-1 text-[8px] uppercase text-muted">Andamento 6 mesi</div>
            <div class="flex h-12 items-end gap-1">
              <div class="w-full rounded-t bg-accent/30" style="height:40%"></div>
              <div class="w-full rounded-t bg-accent/40" style="height:55%"></div>
              <div class="w-full rounded-t bg-accent/50" style="height:35%"></div>
              <div class="w-full rounded-t bg-accent/70" style="height:70%"></div>
              <div class="w-full rounded-t bg-accent/85" style="height:60%"></div>
              <div class="w-full rounded-t bg-accent" style="height:85%"></div>
            </div>
          </div>
          <div class="mt-2 rounded-lg border border-border bg-white p-2">
            <div class="mb-1 text-[8px] uppercase text-muted">Budget · Ristoranti</div>
            <div class="h-1.5 w-full overflow-hidden rounded-full bg-gray-200"><div class="h-full w-[93%] rounded-full bg-loan"></div></div>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- IL PROBLEMA -->
<section class="border-t border-border bg-white px-6 py-20">
  <div class="mx-auto max-w-4xl text-center">
    <h2 class="mb-4 text-2xl font-bold sm:text-3xl">Oggi la tua vita finanziaria vive in tre posti diversi</h2>
    <p class="mx-auto mb-14 max-w-xl text-muted">
      Un'app per il budget, un foglio Excel per il mutuo, l'app della banca per gli investimenti —
      e nessuno dei tre sa cosa fanno gli altri due.
    </p>
    <div class="grid grid-cols-1 gap-6 sm:grid-cols-3">
      <div class="rounded-2xl border border-border p-6">
        <div class="mb-3 text-3xl">📱</div>
        <div class="font-semibold">App di budgeting</div>
        <div class="mt-1 text-sm text-muted">Traccia le spese, ma non sa nulla del tuo mutuo</div>
      </div>
      <div class="rounded-2xl border border-border p-6">
        <div class="mb-3 text-3xl">📊</div>
        <div class="font-semibold">Foglio del mutuo</div>
        <div class="mt-1 text-sm text-muted">Calcola la rata, isolato da tutto il resto</div>
      </div>
      <div class="rounded-2xl border border-border p-6">
        <div class="mb-3 text-3xl">💹</div>
        <div class="font-semibold">App della banca</div>
        <div class="mt-1 text-sm text-muted">Segue gli investimenti, ignora le tue spese</div>
      </div>
    </div>
    <div class="mt-12 flex flex-col items-center gap-3">
      <div class="flex flex-wrap items-center justify-center gap-3 text-3xl">
        <span>📱</span><span class="text-muted">+</span><span>📊</span><span class="text-muted">+</span><span>💹</span>
      </div>
      <p class="text-center text-sm font-semibold uppercase tracking-wide text-muted">
        3 app diverse, 3 abbonamenti, zero collegamento tra i tuoi dati
      </p>
      <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" class="text-accent"><line x1="12" y1="5" x2="12" y2="19"/><polyline points="19 12 12 19 5 12"/></svg>
      <div class="flex items-center gap-3 rounded-2xl bg-gradient-to-br from-accent to-accentDark px-6 py-4 text-white shadow-lg shadow-accent/30 sm:px-8">
        <img src="/favicon.svg" alt="" class="h-10 w-10 flex-shrink-0 rounded-xl">
        <div class="text-left">
          <div class="text-lg font-bold leading-tight">Reflexi</div>
          <div class="text-xs text-white/80">Tutto quello che hai visto sopra, in un'unica app</div>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- LA TEORIA -->
<section class="border-t border-border bg-sidebar px-6 py-20 text-white">
  <div class="mx-auto max-w-3xl text-center">
    <span class="mb-5 inline-block rounded-full bg-white/10 px-4 py-1.5 text-xs font-semibold uppercase tracking-wider text-white/70">
      Da dove viene il nome
    </span>
    <h2 class="mb-6 text-2xl font-bold sm:text-3xl">La Teoria della Riflessività</h2>
    <p class="mb-4 text-white/80">
      George Soros la descrive così: il modo in cui percepiamo una situazione non si limita a
      descriverla — la <span class="font-semibold text-white">plasma attivamente</span>. Una percezione
      distorta produce decisioni sbagliate, che rinforzano quella stessa percezione distorta, in un
      ciclo continuo.
    </p>
    <p class="text-white/80">
      Vale anche per i tuoi soldi. Se non vedi chiaramente dove vanno, decidi sulla base di
      un'illusione — e quell'illusione diventa la tua realtà finanziaria. Reflexi rompe questo ciclo:
      ti restituisce un riflesso accurato, così le tue decisioni si basano sui fatti, non sulla percezione.
    </p>
  </div>
</section>

<!-- FUNZIONALITA' -->
<section id="funzionalita" class="px-6 py-20">
  <div class="mx-auto max-w-5xl">
    <div class="mb-16 text-center">
      <h2 class="mb-3 text-2xl font-bold sm:text-3xl">Tutto quello che serve, collegato</h2>
      <p class="mx-auto max-w-xl text-muted">Quattro moduli che oggi nessun'altra app mette davvero insieme.</p>
    </div>

    <!-- Mutui -->
    <div class="mb-16 grid items-center gap-10 sm:grid-cols-2">
      <div>
        <span class="mb-3 inline-block rounded-full bg-loan/10 px-3 py-1 text-xs font-semibold uppercase tracking-wider text-loan">Mutui</span>
        <h3 class="mb-3 text-xl font-bold">Il piano di ammortamento vero, non un promemoria</h3>
        <p class="mb-4 text-muted">Rata calcolata col metodo francese, simulazione di estinzione anticipata, e — se il mutuo è
          cointestato — la tua quota reale su capitale e interessi, non l'importo totale che confonde i conti.</p>
        <a href="/mutui.php" class="text-sm font-semibold text-accent hover:underline">Scopri di più →</a>
      </div>
      <div class="rounded-2xl border border-border bg-white p-5 shadow-lg shadow-gray-200/50">
        <div class="mb-3 text-xs uppercase tracking-wide text-muted">Capitale residuo</div>
        <div class="mb-3 font-mono text-2xl font-semibold">€ 106.789,45</div>
        <div class="h-2 w-full overflow-hidden rounded-full bg-gray-200">
          <div class="h-full rounded-full bg-loan" style="width:34%"></div>
        </div>
        <div class="mt-2 text-xs text-muted">Rata € 468,58 · 122/360 pagate</div>
      </div>
    </div>

    <!-- Investimenti -->
    <div class="mb-16 grid items-center gap-10 sm:grid-cols-2">
      <div class="order-2 rounded-2xl border border-border bg-white p-5 shadow-lg shadow-gray-200/50 sm:order-1">
        <div class="mb-3 text-xs uppercase tracking-wide text-muted">Portafoglio</div>
        <div class="flex items-center gap-4">
          <div class="h-24 w-24 flex-shrink-0 rounded-full" style="background: conic-gradient(#4f46e5 0% 45%, #10b981 45% 75%, #f59e0b 75% 100%)"></div>
          <div class="space-y-1.5 text-sm">
            <div class="flex items-center gap-2"><span class="h-2 w-2 rounded-full bg-accent"></span>VWCE.MI <span class="text-muted">45%</span></div>
            <div class="flex items-center gap-2"><span class="h-2 w-2 rounded-full bg-income"></span>SXR8.DE <span class="text-muted">30%</span></div>
            <div class="flex items-center gap-2"><span class="h-2 w-2 rounded-full bg-loan"></span>AAPL <span class="text-muted">25%</span></div>
          </div>
        </div>
      </div>
      <div class="order-1 sm:order-2">
        <span class="mb-3 inline-block rounded-full bg-accentSoft px-3 py-1 text-xs font-semibold uppercase tracking-wider text-accent">Investimenti</span>
        <h3 class="mb-3 text-xl font-bold">ETF e azioni con quotazioni vere, non stimate</h3>
        <p class="mb-4 text-muted">Prezzi live, costo medio di carico, plus e minusvalenze realizzate e non — collegati allo
          stesso quadro delle tue spese quotidiane, non un'app separata da controllare a parte.</p>
        <a href="/investimenti.php" class="text-sm font-semibold text-accent hover:underline">Scopri di più →</a>
      </div>
    </div>

    <!-- Budget -->
    <div class="mb-16 grid items-center gap-10 sm:grid-cols-2">
      <div>
        <span class="mb-3 inline-block rounded-full bg-income/10 px-3 py-1 text-xs font-semibold uppercase tracking-wider text-income">Budget</span>
        <h3 class="mb-3 text-xl font-bold">Un limite per categoria, un avviso prima di sforare</h3>
        <p class="mb-4 text-muted">Imposti quanto vuoi spendere in ristoranti, spesa, abbonamenti — Reflexi ti avvisa quando
          il ritmo del mese ti porterebbe a superarlo, non dopo che è già successo.</p>
        <a href="/budget.php" class="text-sm font-semibold text-accent hover:underline">Scopri di più →</a>
      </div>
      <div class="space-y-3 rounded-2xl border border-border bg-white p-5 shadow-lg shadow-gray-200/50">
        <div>
          <div class="mb-1 flex justify-between text-sm"><span class="font-medium">Ristoranti</span><span class="font-mono text-expense">€ 187 / 200</span></div>
          <div class="h-2 w-full overflow-hidden rounded-full bg-gray-200"><div class="h-full rounded-full bg-loan" style="width:93%"></div></div>
        </div>
        <div>
          <div class="mb-1 flex justify-between text-sm"><span class="font-medium">Spesa</span><span class="font-mono text-muted">€ 240 / 400</span></div>
          <div class="h-2 w-full overflow-hidden rounded-full bg-gray-200"><div class="h-full rounded-full bg-income" style="width:60%"></div></div>
        </div>
      </div>
    </div>

    <!-- Analisi predittiva -->
    <div class="grid items-center gap-10 sm:grid-cols-2">
      <div class="order-2 rounded-2xl border border-border bg-white p-5 shadow-lg shadow-gray-200/50 sm:order-1">
        <div class="mb-3 text-xs uppercase tracking-wide text-muted">Proiezione a 6 mesi</div>
        <div class="flex h-24 items-end gap-2">
          <div class="w-full rounded-t bg-accent/20" style="height:40%"></div>
          <div class="w-full rounded-t bg-accent/30" style="height:55%"></div>
          <div class="w-full rounded-t bg-accent/40" style="height:50%"></div>
          <div class="w-full rounded-t bg-accent/60" style="height:70%"></div>
          <div class="w-full rounded-t bg-accent/80" style="height:65%"></div>
          <div class="w-full rounded-t bg-accent" style="height:85%"></div>
        </div>
        <div class="mt-2 text-xs text-muted">78% probabilità di risparmio cumulato positivo</div>
      </div>
      <div class="order-1 sm:order-2">
        <span class="mb-3 inline-block rounded-full bg-accentSoft px-3 py-1 text-xs font-semibold uppercase tracking-wider text-accent">Analisi predittiva</span>
        <h3 class="mb-3 text-xl font-bold">Non una media, una simulazione vera</h3>
        <p class="mb-4 text-muted">Migliaia di scenari calcolati sulla tua reale variabilità storica, più il rilevamento
          automatico di abbonamenti che paghi senza nemmeno saperlo di aver dichiarato come ricorrenti.</p>
        <a href="/analisi.php" class="text-sm font-semibold text-accent hover:underline">Scopri di più →</a>
      </div>
    </div>
  </div>
</section>

<!-- ALTRE FUNZIONALITA' -->
<section class="border-t border-border bg-white px-6 py-20">
  <div class="mx-auto max-w-5xl">
    <div class="mb-14 text-center">
      <h2 class="mb-3 text-2xl font-bold sm:text-3xl">E non è tutto</h2>
      <p class="mx-auto max-w-xl text-muted">Cresciuto ascoltando chi lo usa ogni giorno in beta.</p>
    </div>
    <div class="grid grid-cols-1 gap-6 sm:grid-cols-3">

      <!-- Abbonamenti -->
      <div class="rounded-2xl border border-border p-6">
        <span class="mb-3 inline-block rounded-full bg-loan/10 px-3 py-1 text-xs font-semibold uppercase tracking-wider text-loan">Abbonamenti</span>
        <h3 class="mb-2 text-lg font-bold">Quanto stai davvero pagando ogni mese</h3>
        <p class="mb-4 text-sm text-muted">Netflix, Spotify, palestra — e quelli che l'app scova da sola perché li avevi dimenticati.</p>
        <div class="space-y-2 rounded-xl bg-gray-50 p-3">
          <div class="flex items-center gap-2">
            <span class="flex h-7 w-7 items-center justify-center rounded-lg bg-red-600 text-xs font-bold text-white">N</span>
            <span class="text-sm">Netflix</span><span class="ml-auto font-mono text-xs text-muted">13,99€</span>
          </div>
          <div class="flex items-center gap-2">
            <span class="flex h-7 w-7 items-center justify-center rounded-lg bg-green-600 text-xs font-bold text-white">S</span>
            <span class="text-sm">Spotify</span><span class="ml-auto font-mono text-xs text-muted">10,99€</span>
          </div>
        </div>
      </div>

      <!-- Obiettivi di risparmio -->
      <div class="rounded-2xl border border-border p-6">
        <span class="mb-3 inline-block rounded-full bg-income/10 px-3 py-1 text-xs font-semibold uppercase tracking-wider text-income">Obiettivi</span>
        <h3 class="mb-2 text-lg font-bold">Un traguardo, non solo un saldo</h3>
        <p class="mb-4 text-sm text-muted">Imposti quanto vuoi risparmiare ed entro quando — il progresso si muove da solo insieme alle tue transazioni reali.</p>
        <div class="rounded-xl bg-gray-50 p-3">
          <div class="mb-1 flex justify-between text-sm"><span class="font-medium">Vacanza estate</span><span class="font-mono text-xs text-muted">3.200€ / 5.000€</span></div>
          <div class="h-2 w-full overflow-hidden rounded-full bg-gray-200"><div class="h-full rounded-full bg-income" style="width:64%"></div></div>
        </div>
      </div>

      <!-- Patrimonio netto -->
      <div class="rounded-2xl border border-border p-6">
        <span class="mb-3 inline-block rounded-full bg-accentSoft px-3 py-1 text-xs font-semibold uppercase tracking-wider text-accent">Patrimonio netto</span>
        <h3 class="mb-2 text-lg font-bold">Il quadro intero, immobili inclusi</h3>
        <p class="mb-4 text-sm text-muted">Liquidità, investimenti e casa di proprietà meno il mutuo residuo — anche cointestato, per la sola tua quota reale.</p>
        <div class="rounded-xl bg-gray-50 p-3 font-mono text-xs text-muted">
          <div class="flex justify-between"><span>Liquidità + Investimenti + Immobili</span></div>
          <div class="flex justify-between"><span>− Mutui residui (tua quota)</span></div>
          <div class="mt-1 flex justify-between border-t border-gray-200 pt-1 font-bold text-gray-900"><span>= Patrimonio netto</span></div>
        </div>
      </div>

    </div>
  </div>
</section>

<!-- SICUREZZA -->
<section class="border-t border-border px-6 py-20">
  <div class="mx-auto max-w-4xl">
    <div class="mb-12 text-center">
      <h2 class="mb-3 text-2xl font-bold sm:text-3xl">I tuoi dati, trattati con serietà</h2>
      <p class="mx-auto max-w-xl text-muted">Sono numeri della tua vita finanziaria. Ecco esattamente come li proteggiamo — senza giri di parole.</p>
    </div>
    <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
      <div class="flex gap-3 rounded-2xl border border-border bg-white p-5">
        <span class="text-2xl">🔒</span>
        <div>
          <div class="font-semibold">Connessione sempre cifrata</div>
          <div class="text-sm text-muted">Ogni pagina viaggia in HTTPS, mai in chiaro — dal tuo dispositivo al server e ritorno.</div>
        </div>
      </div>
      <div class="flex gap-3 rounded-2xl border border-border bg-white p-5">
        <span class="text-2xl">🔑</span>
        <div>
          <div class="font-semibold">Password mai salvate in chiaro</div>
          <div class="text-sm text-muted">Solo un hash crittografico irreversibile — nemmeno noi possiamo vedere la tua password.</div>
        </div>
      </div>
      <div class="flex gap-3 rounded-2xl border border-border bg-white p-5">
        <span class="text-2xl">🧱</span>
        <div>
          <div class="font-semibold">I tuoi dati sono visibili solo a te</div>
          <div class="text-sm text-muted">Ogni account è isolato dagli altri a livello di sistema — nessun altro utente può vederli o toccarli.</div>
        </div>
      </div>
      <div class="flex gap-3 rounded-2xl border border-border bg-white p-5">
        <span class="text-2xl">🇮🇹</span>
        <div>
          <div class="font-semibold">Server in Italia</div>
          <div class="text-sm text-muted">Nessun trasferimento inutile dei tuoi dati fuori dall'Unione Europea.</div>
        </div>
      </div>
      <div class="flex gap-3 rounded-2xl border border-border bg-white p-5">
        <span class="text-2xl">🚫</span>
        <div>
          <div class="font-semibold">Nessuna pubblicità, nessuna vendita dei dati</div>
          <div class="text-sm text-muted">Non condividiamo né vendiamo i tuoi dati a terzi per scopi pubblicitari. Mai.</div>
        </div>
      </div>
      <div class="flex gap-3 rounded-2xl border border-border bg-white p-5">
        <span class="text-2xl">🗑️</span>
        <div>
          <div class="font-semibold">Cancellazione su richiesta</div>
          <div class="text-sm text-muted">Puoi chiedere la cancellazione completa del tuo account e dei tuoi dati in ogni momento.</div>
        </div>
      </div>
    </div>
    <p class="mt-8 text-center text-sm text-muted">
      Per i dettagli completi, leggi la <a href="/privacy.php" class="text-accent hover:underline">Privacy Policy</a>.
    </p>
  </div>
</section>

<!-- DIFFERENZIAZIONE -->
<section class="border-t border-border bg-white px-6 py-20">
  <div class="mx-auto max-w-3xl text-center">
    <h2 class="mb-10 text-2xl font-bold sm:text-3xl">Perché non è "un'altra app di risparmio"</h2>
    <div class="grid grid-cols-1 gap-6 text-left sm:grid-cols-2">
      <div class="flex gap-3">
        <span class="mt-0.5 text-income">✓</span>
        <p class="text-sm text-muted"><span class="font-semibold text-gray-900">Mutuo e budget nello stesso posto</span> — vedi davvero quanto pesa la rata sul resto del mese</p>
      </div>
      <div class="flex gap-3">
        <span class="mt-0.5 text-income">✓</span>
        <p class="text-sm text-muted"><span class="font-semibold text-gray-900">Investimenti reali</span> — quotazioni vive, non un numero inserito a mano</p>
      </div>
      <div class="flex gap-3">
        <span class="mt-0.5 text-income">✓</span>
        <p class="text-sm text-muted"><span class="font-semibold text-gray-900">Pensato per l'Italia</span> — ticker di Borsa Italiana, terminologia mutui italiana</p>
      </div>
      <div class="flex gap-3">
        <span class="mt-0.5 text-income">✓</span>
        <p class="text-sm text-muted"><span class="font-semibold text-gray-900">Proiezioni probabilistiche</span> — non una singola cifra ottimista, un ventaglio di scenari reali</p>
      </div>
    </div>
  </div>
</section>

<!-- NEWS: articoli recenti -->
<?php if (!empty($recentArticles)): ?>
<section class="border-t border-border bg-white px-6 py-20">
  <div class="mx-auto max-w-5xl">
    <div class="mb-12 text-center">
      <h2 class="mb-3 text-2xl font-bold sm:text-3xl">Dal blog</h2>
      <p class="mx-auto max-w-xl text-muted">Guide pratiche di finanza personale: mutui, investimenti, budget e pianificazione.</p>
    </div>
    <div class="grid grid-cols-1 gap-5 sm:grid-cols-3">
      <?php foreach ($recentArticles as $article): ?>
      <a href="/news/<?= htmlspecialchars($article['slug']) ?>" class="group flex flex-col rounded-2xl border border-border bg-bg p-6 transition hover:-translate-y-1 hover:border-accent hover:bg-accentSoft hover:shadow-lg">
        <div class="mb-3 flex h-10 w-10 items-center justify-center rounded-xl bg-white text-xl"><?= htmlspecialchars($article['cover_emoji']) ?></div>
        <span class="mb-1.5 text-xs font-semibold uppercase tracking-wide text-accent"><?= htmlspecialchars($categoryLabels[$article['category']] ?? $article['category']) ?></span>
        <h3 class="mb-1.5 text-base font-bold"><?= htmlspecialchars($article['title']) ?></h3>
        <p class="text-sm text-muted"><?= htmlspecialchars($article['excerpt']) ?></p>
      </a>
      <?php endforeach; ?>
    </div>
    <div class="mt-10 text-center">
      <a href="/news" class="inline-block rounded-xl border border-border bg-white px-6 py-2.5 text-sm font-semibold text-gray-700 transition hover:border-accent hover:text-accent">Leggi tutti gli articoli &rarr;</a>
    </div>
  </div>
</section>
<?php endif; ?>

<!-- INTERMEZZO -->
<div class="flex justify-center px-6 pt-16" aria-hidden="true">
  <svg width="131" height="95" viewBox="0 0 131 95" xmlns="http://www.w3.org/2000/svg">
    <defs>
      <linearGradient id="midRingL" x1="0%" y1="0%" x2="100%" y2="100%">
        <stop offset="0%" stop-color="#6366f1"/><stop offset="100%" stop-color="#4f46e5"/>
      </linearGradient>
      <linearGradient id="midRingR" x1="0%" y1="0%" x2="100%" y2="100%">
        <stop offset="0%" stop-color="#c7c9f5"/><stop offset="100%" stop-color="#a5a8ee"/>
      </linearGradient>
    </defs>
    <path fill-rule="evenodd" fill="url(#midRingL)" d="M5,47 A35,35 0 1,0 75,47 A35,35 0 1,0 5,47 Z M27,47 A13,13 0 1,0 53,47 A13,13 0 1,0 27,47 Z"/>
    <path fill-rule="evenodd" fill="url(#midRingR)" d="M56,47 A35,35 0 1,0 126,47 A35,35 0 1,0 56,47 Z M78,47 A13,13 0 1,0 104,47 A13,13 0 1,0 78,47 Z"/>
    <path id="midInfinityPath" d="M65,47 C65,20 45,10 30,20 C15,30 15,64 30,74 C45,84 65,74 65,47 C65,20 85,10 100,20 C118,30 118,64 100,74 C85,84 65,74 65,47 Z" fill="none" stroke="none"/>
    <circle r="7.5" fill="#f59e0b" stroke="#fff" stroke-width="1.5">
      <animateMotion dur="4.5s" repeatCount="indefinite">
        <mpath href="#midInfinityPath"/>
      </animateMotion>
    </circle>
    <text font-size="9" font-weight="700" fill="#fff" text-anchor="middle" dy="3">
      €
      <animateMotion dur="4.5s" repeatCount="indefinite">
        <mpath href="#midInfinityPath"/>
      </animateMotion>
    </text>
  </svg>
</div>

<!-- CTA finale -->
<section class="px-6 py-20">
  <div class="mx-auto max-w-2xl rounded-3xl bg-gradient-to-br from-accent via-accentDark to-indigo-900 p-10 text-center text-white sm:p-14">
    <h2 class="mb-3 text-2xl font-bold sm:text-3xl">Guarda le tue finanze come sono davvero</h2>
    <p class="mb-8 text-white/80">Gratuito in questa fase beta. Nessuna carta di credito richiesta.</p>
    <a href="/app/" class="inline-block rounded-xl bg-white px-8 py-3.5 text-base font-semibold text-accent shadow-lg transition hover:bg-gray-50">
      Crea il tuo account
    </a>
    <div class="mt-5 flex items-center justify-center gap-2 text-xs text-white/60">
      <span>Quando sceglierai un piano, i pagamenti saranno gestiti in sicurezza da</span>
      <a href="https://stripe.com" target="_blank" rel="noopener" class="inline-flex items-center">
        <img src="/stripe-badge.svg" alt="Stripe" class="h-4" onerror="this.replaceWith(Object.assign(document.createElement('span'),{textContent:'Stripe',className:'font-semibold text-white/80'}))">
      </a>
    </div>
  </div>
</section>

<!-- FOOTER -->
<?php include '_footer.php'; ?>

<!-- Banner consenso cookie -->
<div id="cookie-banner" class="hidden fixed inset-x-0 bottom-0 z-50 border-t border-border bg-white p-4 shadow-2xl sm:p-5">
  <div class="mx-auto flex max-w-4xl flex-col items-center gap-3 sm:flex-row sm:justify-between">
    <p class="text-sm text-muted">
      Usiamo cookie tecnici necessari al funzionamento del sito e, solo con il tuo consenso,
      Google Analytics per capire come viene usata la pagina. Nessun dato viene condiviso con terzi a scopo pubblicitario.
      <a href="/privacy.php" class="text-accent hover:underline">Maggiori informazioni</a>.
    </p>
    <div class="flex flex-shrink-0 gap-2">
      <button onclick="declineCookies()" class="rounded-lg border border-border px-4 py-2 text-sm font-semibold text-gray-700 transition hover:bg-gray-50">Rifiuta</button>
      <button onclick="acceptCookies()" class="rounded-lg bg-accent px-4 py-2 text-sm font-semibold text-white transition hover:bg-accentDark">Accetta</button>
    </div>
  </div>
</div>

</body>
</html>
