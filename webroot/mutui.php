<!DOCTYPE html>
<html lang="it">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Gestione mutuo e piano di ammortamento — Reflexi</title>
<meta name="description" content="Piano di ammortamento reale, simulazione di estinzione anticipata e quota per mutui cointestati: il tuo mutuo, collegato al resto delle tue finanze.">
<meta name="robots" content="index, follow">
<link rel="canonical" href="https://reflexi.it/mutui">
<!-- Open Graph (Facebook, WhatsApp, LinkedIn, Telegram...) -->
<meta property="og:type" content="website">
<meta property="og:url" content="https://reflexi.it/mutui">
<meta property="og:site_name" content="Reflexi">
<meta property="og:title" content="Gestione mutuo e piano di ammortamento — Reflexi">
<meta property="og:description" content="Piano di ammortamento reale, simulazione di estinzione anticipata e quota per mutui cointestati: il tuo mutuo, collegato al resto delle tue finanze.">
<meta property="og:image" content="<?php require_once __DIR__ . '/_seo.php'; echo seo_og_image(); ?>">
<meta property="og:image:width" content="1200">
<meta property="og:image:height" content="630">
<meta property="og:locale" content="it_IT">

<!-- Twitter/X Card -->
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="Gestione mutuo e piano di ammortamento — Reflexi">
<meta name="twitter:description" content="Piano di ammortamento reale, simulazione di estinzione anticipata e quota per mutui cointestati: il tuo mutuo, collegato al resto delle tue finanze.">
<meta name="twitter:image" content="<?php require_once __DIR__ . '/_seo.php'; echo seo_og_image(); ?>">
<link rel="icon" type="image/svg+xml" href="/favicon.svg">
<link rel="preload" href="/fonts/inter-v20-latin.woff2" as="font" type="font/woff2" crossorigin>
<link rel="stylesheet" href="/css/site.css?v=<?= filemtime(__DIR__ . '/css/site.css') ?>">
<?php
require_once __DIR__ . '/_seo.php';
seo_breadcrumb([['Mutui', '/mutui']]);
?>
</head>
<body class="bg-bg text-gray-900 antialiased">

<?php
$active = 'mutui';
require_once __DIR__ . '/_news_db.php';
$relatedArticles = news_related(['ammortamento-alla-francese-guida-mutuo-rata-costante', 'estinzione-anticipata-mutuo-quando-conviene', 'mutuo-tasso-fisso-o-variabile-come-scegliere']);
include '_nav.php';
?>

<!-- HERO -->
<section class="px-6 pb-16 pt-14 sm:pt-20">
  <div class="mx-auto grid max-w-5xl items-center gap-10 sm:grid-cols-2">
    <div>
      <span class="mb-4 inline-block rounded-full bg-loan/10 px-4 py-1.5 text-xs font-semibold uppercase tracking-wider text-loan">Mutui</span>
      <h1 class="mb-4 text-3xl font-extrabold leading-tight tracking-tight sm:text-4xl">Il piano di ammortamento vero, non un promemoria</h1>
      <p class="mb-6 text-lg text-muted">Rata calcolata col metodo francese, simulazione di estinzione anticipata, e — se il mutuo è cointestato — la tua quota reale su capitale e interessi, non l'importo totale che confonde i conti.</p>
      <a href="/app/" class="inline-block rounded-xl bg-accent px-7 py-3.5 text-base font-semibold text-white shadow-lg shadow-accent/30 transition hover:bg-accentDark">Prova gratis</a>
    </div>
    <div class="flex justify-center">
      <div class="w-[260px] rounded-[2.5rem] border-[10px] border-gray-900 bg-gray-900 shadow-2xl shadow-gray-400/40">
        <div class="relative h-[520px] overflow-hidden rounded-[1.75rem] bg-bg">
          <div class="absolute left-1/2 top-0 z-10 h-5 w-24 -translate-x-1/2 rounded-b-2xl bg-gray-900"></div>
          <div class="h-full overflow-hidden px-3 pb-3 pt-7">
            <div class="mb-2 text-xs font-bold">Mutuo — Casa di residenza</div>
            <div class="rounded-xl border border-border bg-white p-3">
              <div class="text-[8px] uppercase text-muted">Capitale residuo (tua quota 50%)</div>
              <div class="font-mono text-lg font-semibold">€ 53.295,51</div>
              <div class="mt-2 h-1.5 w-full overflow-hidden rounded-full bg-gray-200"><div class="h-full w-[48%] rounded-full bg-loan"></div></div>
              <div class="mt-1 text-[8px] text-muted">Rata 234,44€ · 128/360 pagate</div>
            </div>
            <div class="mt-2 grid grid-cols-2 gap-2">
              <div class="rounded-lg border border-border bg-white p-2">
                <div class="text-[8px] uppercase text-muted">Capitale restituito</div>
                <div class="font-mono text-xs font-semibold text-income">€ 2.704</div>
              </div>
              <div class="rounded-lg border border-border bg-white p-2">
                <div class="text-[8px] uppercase text-muted">Interessi pagati</div>
                <div class="font-mono text-xs font-semibold text-expense">€ 3.625</div>
              </div>
            </div>
            <div class="mt-2 rounded-lg border border-accent/30 bg-accentSoft p-2">
              <div class="text-[8px] font-semibold uppercase text-accent">Simulazione estinzione anticipata</div>
              <div class="mt-1 text-[9px] text-gray-700">Estinguendo oggi risparmi <span class="font-bold">€ 8.240</span> di interessi</div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- DETTAGLIO -->
<section class="border-t border-border bg-white px-6 py-16">
  <div class="mx-auto max-w-3xl">
    <h2 class="mb-8 text-center text-2xl font-bold">Cosa puoi fare</h2>
    <div class="space-y-5">
      <div class="flex gap-3">
        <span class="mt-1 text-income">✓</span>
        <div><span class="font-semibold">Piano di ammortamento completo</span><p class="text-sm text-muted">Ogni rata, quanto capitale e quanto interesse contiene, mese per mese fino alla fine del mutuo.</p></div>
      </div>
      <div class="flex gap-3">
        <span class="mt-1 text-income">✓</span>
        <div><span class="font-semibold">Simulazione di estinzione anticipata</span><p class="text-sm text-muted">Quanto risparmieresti in interessi se estinguessi il mutuo oggi, o a qualsiasi data futura.</p></div>
      </div>
      <div class="flex gap-3">
        <span class="mt-1 text-income">✓</span>
        <div><span class="font-semibold">Quota di proprietà per mutui cointestati</span><p class="text-sm text-muted">Se il mutuo è al 50% con il tuo partner, vedi la <em>tua</em> quota reale di debito e interessi — non l'importo totale che confonderebbe il resto dei tuoi conti.</p></div>
      </div>
      <div class="flex gap-3">
        <span class="mt-1 text-income">✓</span>
        <div><span class="font-semibold">Rata generata automaticamente</span><p class="text-sm text-muted">Collega il mutuo a un conto e una categoria: la rata si registra da sola ogni mese, senza inserimenti manuali.</p></div>
      </div>
      <div class="flex gap-3">
        <span class="mt-1 text-income">✓</span>
        <div><span class="font-semibold">Collegato al resto delle tue finanze</span><p class="text-sm text-muted">Il mutuo pesa sul tuo budget mensile e sul patrimonio netto insieme a tutto il resto — non è un calcolatore isolato.</p></div>
      </div>
    </div>
  </div>
</section>

<!-- Articoli News correlati -->
<?php if (!empty($relatedArticles)): ?>
<section class="border-t border-border bg-white px-6 py-16">
  <div class="mx-auto max-w-5xl">
    <h2 class="mb-6 text-center text-xl font-bold">Continua a leggere</h2>
    <div class="grid grid-cols-1 gap-5 sm:grid-cols-3">
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

<!-- CTA -->
<section class="px-6 py-20">
  <div class="mx-auto max-w-2xl rounded-3xl bg-gradient-to-br from-accent via-accentDark to-indigo-900 p-10 text-center text-white sm:p-14">
    <h2 class="mb-3 text-2xl font-bold sm:text-3xl">Vedi il tuo mutuo per quello che è davvero</h2>
    <p class="mb-8 text-white/80">6 mesi gratis, senza carta di credito.</p>
    <a href="/app/" class="inline-block rounded-xl bg-white px-8 py-3.5 text-base font-semibold text-accent shadow-lg transition hover:bg-gray-50">Crea il tuo account</a>
  </div>
</section>

<?php include '_footer.php'; ?>

</body>
</html>
