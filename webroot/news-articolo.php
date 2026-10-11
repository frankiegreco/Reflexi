<?php
$active = 'news';
require_once __DIR__ . '/_news_db.php';

$categoryLabels = [
    'mutui' => 'Mutui',
    'investimenti' => 'Investimenti',
    'budget' => 'Budget',
    'pianificazione' => 'Pianificazione',
];

$categoryCta = [
    'mutui' => ['label' => 'Scopri i mutui su Reflexi', 'href' => '/mutui', 'text' => 'Piano di ammortamento reale, simulazione di estinzione anticipata, gestione della quota di proprietà.'],
    'investimenti' => ['label' => 'Scopri gli investimenti su Reflexi', 'href' => '/investimenti', 'text' => 'ETF, azioni e crypto con quotazioni live, PAC automatici, tassazione sulle plusvalenze calcolata da sola.'],
    'budget' => ['label' => 'Scopri il budget su Reflexi', 'href' => '/budget', 'text' => 'Un limite per categoria, con rollover e avviso prima di sforare, non dopo.'],
    'pianificazione' => ['label' => 'Scopri l\'analisi predittiva su Reflexi', 'href' => '/analisi', 'text' => 'Simulazione Monte Carlo sul tuo risparmio futuro, basata sulla tua storia finanziaria reale.'],
];

$slug = $_GET['slug'] ?? '';
if (!is_string($slug) || $slug === '' || !preg_match('/^[a-z0-9-]+$/', $slug)) {
    http_response_code(404);
    $slug = '';
}

$article = null;
if ($slug !== '') {
    $stmt = news_db()->prepare(
        "SELECT * FROM news_articles WHERE slug = :slug AND status = 'pubblicato' AND published_at <= NOW() LIMIT 1"
    );
    $stmt->execute(['slug' => $slug]);
    $article = $stmt->fetch() ?: null;
}

if ($article === null) {
    http_response_code(404);
}

$related = [];
if ($article !== null) {
    $stmt = news_db()->prepare(
        "SELECT slug, title, excerpt, cover_emoji FROM news_articles
         WHERE status = 'pubblicato' AND published_at <= NOW()
           AND category = :category AND id != :id
         ORDER BY published_at DESC LIMIT 3"
    );
    $stmt->execute(['category' => $article['category'], 'id' => $article['id']]);
    $related = $stmt->fetchAll();
}

$canonicalUrl = 'https://reflexi.it/news/' . htmlspecialchars($slug, ENT_QUOTES);
?>
<!DOCTYPE html>
<html lang="it">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<?php if ($article === null): ?>
<title>Articolo non trovato — Reflexi</title>
<meta name="robots" content="noindex, follow">
<?php else: ?>
<?php
// Title SEO (max 60 caratteri, colonna seo_title) in <title> e anteprime social;
// il titolo completo resta l'H1. Il suffisso del brand solo se ci sta nei 60.
$seoTitle = $article['seo_title'] ?: $article['title'];
$titleTag = mb_strlen($seoTitle) + 10 <= 60 ? $seoTitle . ' — Reflexi' : $seoTitle;
?>
<title><?= htmlspecialchars($titleTag) ?></title>
<meta name="description" content="<?= htmlspecialchars($article['meta_description']) ?>">
<meta name="robots" content="index, follow">
<link rel="canonical" href="<?= $canonicalUrl ?>">
<!-- Open Graph (Facebook, WhatsApp, LinkedIn, Telegram...) -->
<meta property="og:type" content="article">
<meta property="og:url" content="<?= $canonicalUrl ?>">
<meta property="og:site_name" content="Reflexi">
<meta property="og:title" content="<?= htmlspecialchars($seoTitle) ?>">
<meta property="og:description" content="<?= htmlspecialchars($article['meta_description']) ?>">
<meta property="og:image" content="<?php require_once __DIR__ . '/_seo.php'; echo seo_og_image('news/' . $article['slug']); ?>">
<meta property="og:image:width" content="1200">
<meta property="og:image:height" content="630">
<meta property="og:locale" content="it_IT">
<meta property="article:published_time" content="<?= date('c', strtotime($article['published_at'])) ?>">
<meta property="article:modified_time" content="<?= date('c', strtotime($article['updated_at'])) ?>">

<!-- Twitter/X Card -->
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="<?= htmlspecialchars($seoTitle) ?>">
<meta name="twitter:description" content="<?= htmlspecialchars($article['meta_description']) ?>">
<meta name="twitter:image" content="<?php require_once __DIR__ . '/_seo.php'; echo seo_og_image('news/' . $article['slug']); ?>">

<script type="application/ld+json">
<?= json_encode([
    '@context' => 'https://schema.org',
    '@type' => 'Article',
    'headline' => $article['title'],
    'description' => $article['meta_description'],
    'image' => seo_og_image('news/' . $article['slug']),
    'datePublished' => date('c', strtotime($article['published_at'])),
    'dateModified' => date('c', strtotime($article['updated_at'])),
    'author' => ['@type' => 'Organization', 'name' => 'Reflexi', 'url' => 'https://reflexi.it/'],
    'publisher' => ['@type' => 'Organization', 'name' => 'Reflexi', 'url' => 'https://reflexi.it/', 'logo' => ['@type' => 'ImageObject', 'url' => 'https://reflexi.it/favicon.svg']],
    'mainEntityOfPage' => ['@type' => 'WebPage', '@id' => $canonicalUrl],
    'inLanguage' => 'it-IT',
    'articleSection' => $categoryLabels[$article['category']] ?? $article['category'],
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>
</script>
<?php
require_once __DIR__ . '/_seo.php';
seo_breadcrumb([
    ['News', '/news'],
    [$categoryLabels[$article['category']] ?? $article['category'], '/news?categoria=' . $article['category']],
    [$seoTitle, '/news/' . $article['slug']],
]);
?>
<?php endif; ?>
<link rel="icon" type="image/svg+xml" href="/favicon.svg">
<link rel="preload" href="/fonts/inter-v20-latin.woff2" as="font" type="font/woff2" crossorigin>
<link rel="stylesheet" href="/css/site.css?v=<?= filemtime(__DIR__ . '/css/site.css') ?>">
</head>
<body class="bg-bg text-gray-900 antialiased">

<?php include '_nav.php'; ?>

<?php if ($article === null): ?>

<section class="px-6 py-24 text-center">
  <h1 class="mb-3 text-3xl font-extrabold">Articolo non trovato</h1>
  <p class="mb-8 text-muted">Potrebbe essere stato spostato o rimosso.</p>
  <a href="/news" class="inline-block rounded-xl bg-accent px-7 py-3 text-sm font-semibold text-white shadow-lg shadow-accent/25 transition hover:bg-accentDark">Vai alla sezione News</a>
</section>

<?php else: ?>

<section class="px-6 pb-6 pt-14 text-center sm:pt-16">
  <a href="/news" class="mb-4 inline-block text-sm text-muted hover:text-accent">&larr; Tutti gli articoli</a>
  <div class="mx-auto mb-3 flex h-14 w-14 items-center justify-center rounded-2xl bg-accentSoft text-3xl"><?= htmlspecialchars($article['cover_emoji']) ?></div>
  <span class="mb-2 block text-xs font-semibold uppercase tracking-wide text-accent"><?= htmlspecialchars($categoryLabels[$article['category']] ?? $article['category']) ?></span>
  <h1 class="mx-auto mb-3 max-w-2xl text-3xl font-extrabold leading-tight tracking-tight sm:text-4xl"><?= htmlspecialchars($article['title']) ?></h1>
  <p class="mx-auto max-w-xl text-lg text-muted"><?= htmlspecialchars($article['excerpt']) ?></p>
</section>

<main class="mx-auto max-w-3xl px-6 py-8">
  <article><?= $article['body'] ?></article>
</main>

<?php $cta = $categoryCta[$article['category']] ?? null; if ($cta): ?>
<section class="px-6 py-16 text-center">
  <h2 class="mb-2 text-xl font-bold"><?= htmlspecialchars($cta['label']) ?></h2>
  <p class="mb-6 text-muted"><?= htmlspecialchars($cta['text']) ?></p>
  <a href="<?= htmlspecialchars($cta['href']) ?>" class="inline-block rounded-xl bg-accent px-7 py-3 text-sm font-semibold text-white shadow-lg shadow-accent/25 transition hover:bg-accentDark"><?= htmlspecialchars($cta['label']) ?></a>
</section>
<?php endif; ?>

<?php
// "Mettilo in pratica": dall'articolo alla funzione dell'app e ai calcolatori
// pertinenti. Gli articoli sono le pagine che portano traffico dalla ricerca,
// e questi link interni passano visite e rilevanza alle pagine che portano
// alle iscrizioni. Regole sullo slug (prima quelle più specifiche), poi i
// predefiniti della categoria; al massimo 3 collegamenti.
$practiceTargets = [
    '/mutui' => ['Funzionalità', 'Mutui', 'Piano di ammortamento ed estinzione anticipata'],
    '/investimenti' => ['Funzionalità', 'Investimenti', 'Portafoglio con quotazioni reali'],
    '/budget' => ['Funzionalità', 'Budget', 'Limiti per categoria e avvisi'],
    '/analisi' => ['Funzionalità', 'Analisi predittiva', 'Proiezioni e simulazione Monte Carlo'],
    '/previsioni' => ['Funzionalità', 'Previsioni', 'Stime a 12 mesi e proposte di investimento'],
    '/simulazione-trading' => ['Funzionalità', 'Simulazione trading', 'Fai pratica con capitale virtuale'],
    '/fire-pensione' => ['Funzionalità', 'FIRE e pensione', 'Indipendenza finanziaria e fondi pensione'],
    '/obiettivi' => ['Funzionalità', 'Obiettivi', 'Traguardi di risparmio con un piano'],
    '/abbonamenti' => ['Funzionalità', 'Abbonamenti', 'Spese ricorrenti, anche quelle nascoste'],
    '/patrimonio' => ['Funzionalità', 'Patrimonio e report', 'Patrimonio netto, immobili e report'],
    '/calcolatore-interesse-composto' => ['Calcolatore gratuito', 'Interesse composto', 'Quanto cresce un capitale nel tempo'],
    '/calcolatore-piano-investimento' => ['Calcolatore gratuito', 'Piano di investimento', 'Quanto diventa un PAC mensile'],
    '/calcolatore-obiettivo-risparmio' => ['Calcolatore gratuito', 'Obiettivo di risparmio', 'Quanto mettere da parte al mese'],
    '/calcolatore-rendimento' => ['Calcolatore gratuito', 'Rendimento (ROI)', 'Rendimento totale e CAGR'],
    '/calcolatore-recupero-perdite' => ['Calcolatore gratuito', 'Recupero perdite', 'Quanto serve per tornare in pari'],
    '/calcolatore-inflazione' => ['Calcolatore gratuito', 'Inflazione', "Quanto perde valore il denaro"],
    '/calcolatore-budget-503020' => ['Calcolatore gratuito', 'Regola 50/30/20', 'Dividi lo stipendio in tre parti'],
    '/calcolatore-patrimonio-netto' => ['Calcolatore gratuito', 'Patrimonio netto', 'Quanto possiedi davvero'],
    '/calcolatore-pensione' => ['Calcolatore gratuito', 'Pensione', 'Quanto potresti avere al pensionamento'],
];
$practiceRules = [
    'fire' => ['/fire-pensione', '/calcolatore-pensione'],
    'pension' => ['/fire-pensione', '/calcolatore-pensione'],
    'tfr' => ['/fire-pensione', '/calcolatore-pensione'],
    'interesse-composto' => ['/calcolatore-interesse-composto', '/investimenti'],
    'pac' => ['/calcolatore-piano-investimento', '/investimenti'],
    'recupero-perdite' => ['/calcolatore-recupero-perdite', '/simulazione-trading'],
    'cagr' => ['/calcolatore-rendimento', '/investimenti'],
    'inflazione' => ['/calcolatore-inflazione', '/patrimonio'],
    'tasso-di-risparmio' => ['/analisi', '/calcolatore-obiettivo-risparmio'],
    'obiettiv' => ['/obiettivi', '/calcolatore-obiettivo-risparmio'],
    'emergenza' => ['/obiettivi', '/calcolatore-obiettivo-risparmio'],
    'figlio' => ['/obiettivi', '/budget'],
    'abbonament' => ['/abbonamenti', '/budget'],
    'spese-fisse' => ['/abbonamenti', '/budget'],
    'impulsiv' => ['/budget', '/abbonamenti'],
    '50-30-20' => ['/calcolatore-budget-503020', '/budget'],
    'budget' => ['/budget', '/calcolatore-budget-503020'],
    'buste' => ['/budget', '/calcolatore-budget-503020'],
    'tracciare' => ['/budget', '/analisi'],
    'patrimonio' => ['/patrimonio', '/calcolatore-patrimonio-netto'],
    'gestire-i-soldi' => ['/patrimonio', '/calcolatore-patrimonio-netto'],
    'coppia' => ['/budget', '/patrimonio'],
    'debito-reddito' => ['/mutui', '/analisi'],
    'etf' => ['/investimenti', '/previsioni'],
    'btp' => ['/investimenti', '/calcolatore-rendimento'],
    'diversificazione' => ['/investimenti', '/previsioni'],
    'ribilanciamento' => ['/investimenti', '/patrimonio'],
    'tassazione' => ['/investimenti', '/calcolatore-rendimento'],
    'mutuo' => ['/mutui', '/patrimonio'],
];
$practiceDefaults = [
    'mutui' => ['/mutui', '/patrimonio'],
    'investimenti' => ['/investimenti', '/calcolatore-interesse-composto'],
    'budget' => ['/budget', '/calcolatore-budget-503020'],
    'pianificazione' => ['/patrimonio', '/fire-pensione'],
];
$practiceLinks = [];
if ($article !== null) {
    foreach ($practiceRules as $needle => $paths) {
        if (str_contains($article['slug'], $needle)) $practiceLinks = array_merge($practiceLinks, $paths);
    }
    $practiceLinks = array_slice(array_values(array_unique(array_merge($practiceLinks, $practiceDefaults[$article['category']] ?? []))), 0, 3);
}
?>
<?php if (!empty($practiceLinks)): ?>
<section class="border-t border-border px-6 py-14">
  <div class="mx-auto max-w-5xl">
    <h2 class="mb-2 text-center text-xl font-bold">Mettilo in pratica</h2>
    <p class="mb-6 text-center text-sm text-muted">Dalla teoria ai tuoi numeri, con gli strumenti di Reflexi.</p>
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
      <?php foreach ($practiceLinks as $href): [$kind, $label, $desc] = $practiceTargets[$href]; ?>
      <a href="<?= $href ?>" class="flex flex-col rounded-2xl border border-border bg-white p-5 transition hover:border-accent hover:bg-accentSoft">
        <span class="mb-1 text-[11px] font-semibold uppercase tracking-wide text-accent"><?= $kind ?></span>
        <span class="font-bold"><?= htmlspecialchars($label) ?> →</span>
        <span class="mt-1 text-sm text-muted"><?= htmlspecialchars($desc) ?></span>
      </a>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<?php if (!empty($related)): ?>
<section class="border-t border-border bg-white px-6 py-16">
  <div class="mx-auto max-w-5xl">
    <h2 class="mb-6 text-center text-xl font-bold">Continua a leggere</h2>
    <div class="grid grid-cols-1 gap-5 sm:grid-cols-3">
      <?php foreach ($related as $r): ?>
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

<?php endif; ?>

<?php include '_footer.php'; ?>

</body>
</html>
