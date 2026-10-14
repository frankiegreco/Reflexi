<!DOCTYPE html>
<html lang="it">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Gestione mutuo e piano di ammortamento — Reflexi</title>
<meta name="description" content="Piano di ammortamento reale, rientro anticipato con versamenti extra, risparmio netto al netto della detrazione fiscale e quota per mutui cointestati.">
<meta name="robots" content="index, follow">
<link rel="canonical" href="https://reflexi.it/mutui">
<!-- Open Graph (Facebook, WhatsApp, LinkedIn, Telegram...) -->
<meta property="og:type" content="website">
<meta property="og:url" content="https://reflexi.it/mutui">
<meta property="og:site_name" content="Reflexi">
<meta property="og:title" content="Gestione mutuo e piano di ammortamento — Reflexi">
<meta property="og:description" content="Piano di ammortamento reale, rientro anticipato con versamenti extra, risparmio netto al netto della detrazione fiscale e quota per mutui cointestati.">
<meta property="og:image" content="<?php require_once __DIR__ . '/_seo.php'; echo seo_og_image(); ?>">
<meta property="og:image:width" content="1200">
<meta property="og:image:height" content="630">
<meta property="og:locale" content="it_IT">

<!-- Twitter/X Card -->
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="Gestione mutuo e piano di ammortamento — Reflexi">
<meta name="twitter:description" content="Piano di ammortamento reale, rientro anticipato con versamenti extra, risparmio netto al netto della detrazione fiscale e quota per mutui cointestati.">
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
$relatedArticles = news_related(['ammortamento-alla-francese-guida-mutuo-rata-costante', 'rientro-anticipato-mutuo-ridurre-durata-o-rata', 'estinzione-anticipata-mutuo-quando-conviene']);
include '_nav.php';
?>

<!-- HERO -->
<section class="px-6 pb-16 pt-14 sm:pt-20">
  <div class="mx-auto grid max-w-5xl items-center gap-10 sm:grid-cols-2">
    <div>
      <span class="mb-4 inline-block rounded-full bg-loan/10 px-4 py-1.5 text-xs font-semibold uppercase tracking-wider text-loan">Mutui</span>
      <h1 class="mb-4 text-3xl font-extrabold leading-tight tracking-tight sm:text-4xl">Il piano di ammortamento vero, non un promemoria</h1>
      <p class="mb-6 text-lg text-muted">Rata calcolata col metodo francese, simulazione di rientro ed estinzione anticipata con il risparmio netto al netto della detrazione fiscale, e — se il mutuo è cointestato — la tua quota reale su capitale e interessi, non l'importo totale che confonde i conti.</p>
      <a href="/app/?mode=register" class="inline-block rounded-xl bg-accent px-7 py-3.5 text-base font-semibold text-white shadow-lg shadow-accent/30 transition hover:bg-accentDark">Prova gratis</a>
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
            <div class="mt-2 rounded-lg border border-border bg-white p-2">
              <div class="text-[8px] font-semibold uppercase text-loan">Rientro anticipato</div>
              <div class="mt-1 text-[9px] text-gray-700">Versi <span class="font-bold">€ 10.000</span>: finisci 2 anni prima</div>
              <div class="mt-1 flex justify-between text-[9px]"><span class="text-muted">Interessi risparmiati</span><span class="font-mono">€ 9.343</span></div>
              <div class="flex justify-between text-[9px]"><span class="text-muted">Detrazione persa</span><span class="font-mono">− € 1.631</span></div>
              <div class="mt-0.5 flex justify-between border-t border-border pt-0.5 text-[9px] font-bold"><span>Risparmio netto</span><span class="font-mono text-income">€ 7.712</span></div>
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
        <div><span class="font-semibold">Simulazione di rientro anticipato</span><p class="text-sm text-muted">Ipotizzi versamenti extra in conto capitale, sporadici (una data e un importo) o fissi nel tempo (ogni mese, trimestre, semestre o anno), e scegli se usarli per accorciare la durata o abbassare la rata. Vedi subito nuova scadenza o nuova rata e il grafico del debito con e senza versamenti.</p></div>
      </div>
      <div class="flex gap-3">
        <span class="mt-1 text-income">✓</span>
        <div><span class="font-semibold">Risparmio netto, detrazione fiscale inclusa</span><p class="text-sm text-muted">Meno interessi significa anche meno detrazione IRPEF del 19%: Reflexi la sottrae anno per anno, rispettando il tetto di 4.000 € di interessi, insieme all'eventuale penale. Il dettaglio del calcolo è sempre visibile.</p></div>
      </div>
      <div class="flex gap-3">
        <span class="mt-1 text-income">✓</span>
        <div><span class="font-semibold">Versamenti registrati nel piano reale</span><p class="text-sm text-muted">Quando il versamento lo fai davvero, lo registri con un clic: debito residuo, piano, patrimonio netto e rata ricorrente si ricalcolano, e se vuoi l'uscita finisce anche sul tuo conto.</p></div>
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

<!-- ESEMPIO RIENTRO ANTICIPATO -->
<section class="border-t border-border px-6 py-16">
  <div class="mx-auto max-w-3xl">
    <h2 class="mb-3 text-center text-2xl font-bold">Rientro anticipato: quanto risparmi davvero</h2>
    <p class="mx-auto mb-8 max-w-2xl text-center text-muted">Un esempio calcolato con il simulatore di Reflexi: mutuo prima casa da 150.000 € al 3,5% in 25 anni, dopo 5 anni (debito residuo circa 129.500 €, rata 750,94 €). Versi una volta 10.000 €.</p>
    <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
      <div class="rounded-2xl border border-border bg-white p-6">
        <h3 class="mb-1 text-base font-bold">Riduci la durata</h3>
        <p class="mb-4 text-sm text-muted">La rata resta 750,94 €, il mutuo finisce 2 anni e 1 mese prima.</p>
        <div class="space-y-1.5 text-sm">
          <div class="flex justify-between"><span class="text-muted">Interessi risparmiati</span><span class="font-mono">€ 9.343</span></div>
          <div class="flex justify-between"><span class="text-muted">Detrazione IRPEF persa</span><span class="font-mono">− € 1.631</span></div>
          <div class="flex justify-between border-t border-border pt-1.5 font-semibold"><span>Risparmio netto</span><span class="font-mono text-income">€ 7.712</span></div>
        </div>
      </div>
      <div class="rounded-2xl border border-border bg-white p-6">
        <h3 class="mb-1 text-base font-bold">Riduci la rata</h3>
        <p class="mb-4 text-sm text-muted">La scadenza resta uguale, la rata scende a 692,77 € (−58 € al mese).</p>
        <div class="space-y-1.5 text-sm">
          <div class="flex justify-between"><span class="text-muted">Interessi risparmiati</span><span class="font-mono">€ 3.901</span></div>
          <div class="flex justify-between"><span class="text-muted">Detrazione IRPEF persa</span><span class="font-mono">− € 600</span></div>
          <div class="flex justify-between border-t border-border pt-1.5 font-semibold"><span>Risparmio netto</span><span class="font-mono text-income">€ 3.301</span></div>
        </div>
      </div>
    </div>
    <div class="mt-8 space-y-3 text-sm text-muted">
      <p><span class="font-semibold text-gray-900">Perché ridurre la durata fa risparmiare di più?</span> Con la rata invariata il debito scende più in fretta, e su un debito più basso maturano meno interessi. Ridurre la rata, invece, libera liquidità ogni mese: dipende da cosa ti serve.</p>
      <p><span class="font-semibold text-gray-900">Perché la detrazione persa non è sempre il 19% del risparmio?</span> La detrazione si calcola solo sui primi 4.000 € di interessi pagati ogni anno. Se oggi ne paghi di più, la parte di interessi risparmiata sopra il tetto non ti fa perdere nulla. Reflexi fa il conto anno per anno, contando anche gli interessi già pagati nell'anno in corso.</p>
      <p class="text-xs">Esempio indicativo: la detrazione spetta solo per il mutuo sull'abitazione principale e nei limiti dell'IRPEF dovuta. Nel simulatore puoi cambiare percentuale e tetto, disattivarla o aggiungere una penale.</p>
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
    <a href="/app/?mode=register" class="inline-block rounded-xl bg-white px-8 py-3.5 text-base font-semibold text-accent shadow-lg transition hover:bg-gray-50">Crea il tuo account</a>
  </div>
</section>

<?php include '_footer.php'; ?>

</body>
</html>
