<?php
$active = 'news';
require_once __DIR__ . '/_news_db.php';

$categoryLabels = [
    'mutui' => 'Mutui',
    'investimenti' => 'Investimenti',
    'budget' => 'Budget',
    'pianificazione' => 'Pianificazione',
];

$categoria = $_GET['categoria'] ?? '';
$categoria = is_string($categoria) && isset($categoryLabels[$categoria]) ? $categoria : '';

$page = isset($_GET['page']) ? max(1, (int) $_GET['page']) : 1;
$perPage = 6;
$offset = ($page - 1) * $perPage;

$where = "status = 'pubblicato' AND published_at <= NOW()";
$params = [];
if ($categoria !== '') {
    $where .= " AND category = :categoria";
    $params['categoria'] = $categoria;
}

$countStmt = news_db()->prepare("SELECT COUNT(*) FROM news_articles WHERE {$where}");
$countStmt->execute($params);
$total = (int) $countStmt->fetchColumn();
$totalPages = max(1, (int) ceil($total / $perPage));

$stmt = news_db()->prepare(
    "SELECT slug, title, excerpt, category, cover_emoji, published_at
     FROM news_articles WHERE {$where}
     ORDER BY published_at DESC LIMIT :limit OFFSET :offset"
);
foreach ($params as $k => $v) {
    $stmt->bindValue($k, $v);
}
$stmt->bindValue('limit', $perPage, PDO::PARAM_INT);
$stmt->bindValue('offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$articles = $stmt->fetchAll();

$pageTitle = $categoria !== ''
    ? ($categoryLabels[$categoria] . ': guide e news — Reflexi')
    : 'News e guide di finanza personale — Reflexi';
if ($page > 1) {
    $pageTitle .= " (pagina {$page})";
}
$pageDescription = $categoria !== ''
    ? "Articoli su {$categoryLabels[$categoria]}: guide pratiche di finanza personale scritte da Reflexi."
    : 'Guide pratiche di finanza personale su mutui, investimenti, budget e pianificazione: spiegazioni chiare, esempi concreti e calcolatori gratuiti.';
// Ogni pagina della paginazione si auto-canonicalizza (include ?page=N se >1):
// puntare tutte le pagine al canonical di pagina 1 farebbe perdere agli
// articoli presenti solo in pagina 2+ il loro segnale di indicizzazione.
$canonicalParams = array_filter(['categoria' => $categoria ?: null, 'page' => $page > 1 ? $page : null]);
$canonicalUrl = 'https://reflexi.it/news' . (!empty($canonicalParams) ? '?' . http_build_query($canonicalParams) : '');

function news_fmt_date(string $datetime): string
{
    $months = ['gennaio','febbraio','marzo','aprile','maggio','giugno','luglio','agosto','settembre','ottobre','novembre','dicembre'];
    $ts = strtotime($datetime);
    return (int) date('j', $ts) . ' ' . $months[(int) date('n', $ts) - 1] . ' ' . date('Y', $ts);
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= htmlspecialchars($pageTitle) ?></title>
<meta name="description" content="<?= htmlspecialchars($pageDescription) ?>">
<meta name="robots" content="index, follow">
<link rel="canonical" href="<?= htmlspecialchars($canonicalUrl) ?>">
<!-- Open Graph (Facebook, WhatsApp, LinkedIn, Telegram...) -->
<meta property="og:type" content="website">
<meta property="og:url" content="<?= htmlspecialchars($canonicalUrl) ?>">
<meta property="og:site_name" content="Reflexi">
<meta property="og:title" content="<?= htmlspecialchars($pageTitle) ?>">
<meta property="og:description" content="<?= htmlspecialchars($pageDescription) ?>">
<meta property="og:image" content="https://reflexi.it/og-image.png?v=3">
<meta property="og:image:width" content="1200">
<meta property="og:image:height" content="630">
<meta property="og:locale" content="it_IT">

<!-- Twitter/X Card -->
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="<?= htmlspecialchars($pageTitle) ?>">
<meta name="twitter:description" content="<?= htmlspecialchars($pageDescription) ?>">
<meta name="twitter:image" content="https://reflexi.it/og-image.png?v=3">
<link rel="icon" type="image/svg+xml" href="/favicon.svg">
<link rel="preload" href="/fonts/inter-v20-latin.woff2" as="font" type="font/woff2" crossorigin>
<link rel="stylesheet" href="/css/site.css?v=<?= filemtime(__DIR__ . '/css/site.css') ?>">
<?php
require_once __DIR__ . '/_seo.php';
seo_breadcrumb($categoria !== ''
    ? [['News', '/news'], [$categoryLabels[$categoria], '/news?categoria=' . $categoria]]
    : [['News', '/news']]);
?>
</head>
<body class="bg-bg text-gray-900 antialiased">

<?php include '_nav.php'; ?>

<section class="px-6 pb-8 pt-14 text-center sm:pt-20">
  <h1 class="mx-auto mb-3 max-w-2xl text-3xl font-extrabold leading-tight tracking-tight sm:text-4xl">News</h1>
  <p class="mx-auto max-w-xl text-lg text-muted">Guide pratiche di finanza personale: mutui, investimenti, budget e pianificazione.</p>
</section>

<div class="mx-auto mb-8 flex max-w-5xl flex-wrap justify-center gap-2 px-6">
  <a href="/news" class="rounded-full border px-4 py-1.5 text-sm font-medium <?= $categoria === '' ? 'border-accent bg-accentSoft text-accent' : 'border-border bg-white text-gray-600 hover:border-accent hover:text-accent' ?>">Tutti</a>
  <?php foreach ($categoryLabels as $key => $label): ?>
  <a href="/news?categoria=<?= urlencode($key) ?>" class="rounded-full border px-4 py-1.5 text-sm font-medium <?= $categoria === $key ? 'border-accent bg-accentSoft text-accent' : 'border-border bg-white text-gray-600 hover:border-accent hover:text-accent' ?>"><?= htmlspecialchars($label) ?></a>
  <?php endforeach; ?>
</div>

<main class="mx-auto max-w-5xl px-6 py-8">
<?php if (empty($articles)): ?>
  <p class="py-16 text-center text-muted">Nessun articolo trovato<?= $categoria !== '' ? ' in questa categoria' : '' ?>.</p>
<?php else: ?>
  <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-3">
    <?php foreach ($articles as $article): ?>
    <a href="/news/<?= htmlspecialchars($article['slug']) ?>" class="group flex flex-col rounded-2xl border border-border bg-white p-6 shadow-sm transition hover:-translate-y-1 hover:border-accent hover:bg-accentSoft hover:shadow-lg">
      <div class="mb-4 flex h-12 w-12 items-center justify-center rounded-xl bg-accentSoft text-2xl"><?= htmlspecialchars($article['cover_emoji']) ?></div>
      <span class="mb-1.5 text-xs font-semibold uppercase tracking-wide text-accent"><?= htmlspecialchars($categoryLabels[$article['category']] ?? $article['category']) ?></span>
      <h2 class="mb-1.5 text-lg font-bold"><?= htmlspecialchars($article['title']) ?></h2>
      <p class="mb-4 flex-1 text-sm text-muted"><?= htmlspecialchars($article['excerpt']) ?></p>
      <div class="flex items-center justify-between">
        <span class="text-xs text-muted"><?= news_fmt_date($article['published_at']) ?></span>
        <span class="text-sm font-semibold text-accent group-hover:underline">Leggi &rarr;</span>
      </div>
    </a>
    <?php endforeach; ?>
  </div>

  <?php if ($totalPages > 1): ?>
  <div class="mt-10 flex items-center justify-center gap-3 text-sm">
    <?php if ($page > 1): ?>
      <a href="?<?= http_build_query(array_filter(['categoria' => $categoria ?: null, 'page' => $page - 1 > 1 ? $page - 1 : null])) ?>" class="rounded-xl border border-border bg-white px-4 py-2 font-medium text-gray-700 hover:border-accent hover:text-accent">&larr; Precedente</a>
    <?php endif; ?>
    <span class="text-muted">Pagina <?= $page ?> di <?= $totalPages ?></span>
    <?php if ($page < $totalPages): ?>
      <a href="?<?= http_build_query(array_filter(['categoria' => $categoria ?: null, 'page' => $page + 1])) ?>" class="rounded-xl border border-border bg-white px-4 py-2 font-medium text-gray-700 hover:border-accent hover:text-accent">Successiva &rarr;</a>
    <?php endif; ?>
  </div>
  <?php endif; ?>
<?php endif; ?>
</main>

<section class="px-6 py-16 text-center">
  <h2 class="mb-2 text-xl font-bold">Vuoi vedere questi principi applicati ai tuoi numeri veri?</h2>
  <p class="mb-6 text-muted">Reflexi mette insieme mutuo, investimenti, budget e analisi predittiva in un unico posto.</p>
  <a href="/app/" class="inline-block rounded-xl bg-accent px-7 py-3 text-sm font-semibold text-white shadow-lg shadow-accent/25 transition hover:bg-accentDark">Prova Reflexi</a>
</section>

<?php include '_footer.php'; ?>

</body>
</html>
