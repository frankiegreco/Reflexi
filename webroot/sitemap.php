<?php
declare(strict_types=1);
require_once __DIR__ . '/_news_db.php';

header('Content-Type: application/xml; charset=utf-8');

// Pagine statiche del sito, stesse URL/priorità già mantenute finora a mano
// in sitemap.xml (ora rimosso: questo file lo sostituisce).
$staticUrls = [
    ['/', '1.0', 'monthly'],
    ['/mutui', '0.8', 'monthly'],
    ['/investimenti', '0.8', 'monthly'],
    ['/mercati', '0.8', 'daily'],
    ['/budget', '0.8', 'monthly'],
    ['/analisi', '0.8', 'monthly'],
    ['/previsioni', '0.8', 'monthly'],
    ['/simulazione-trading', '0.8', 'monthly'],
    ['/fire-pensione', '0.8', 'monthly'],
    ['/obiettivi', '0.8', 'monthly'],
    ['/abbonamenti', '0.8', 'monthly'],
    ['/patrimonio', '0.8', 'monthly'],
    ['/calcolatori', '0.9', 'monthly'],
    ['/calcolatore-interesse-composto', '0.7', 'monthly'],
    ['/calcolatore-piano-investimento', '0.7', 'monthly'],
    ['/calcolatore-obiettivo-risparmio', '0.7', 'monthly'],
    ['/calcolatore-rendimento', '0.7', 'monthly'],
    ['/calcolatore-recupero-perdite', '0.7', 'monthly'],
    ['/calcolatore-inflazione', '0.7', 'monthly'],
    ['/calcolatore-budget-503020', '0.7', 'monthly'],
    ['/calcolatore-patrimonio-netto', '0.7', 'monthly'],
    ['/calcolatore-pensione', '0.7', 'monthly'],
    ['/about', '0.6', 'monthly'],
    ['/faq', '0.7', 'monthly'],
    ['/piani', '0.9', 'monthly'],
    ['/news', '0.8', 'daily'],
    ['/cancella-account', '0.4', 'yearly'],
    ['/privacy', '0.3', 'yearly'],
    ['/termini', '0.3', 'yearly'],
];

echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

$stmt = news_db()->query(
    "SELECT slug, updated_at FROM news_articles
     WHERE status = 'pubblicato' AND published_at <= NOW()
     ORDER BY published_at DESC"
);
$articles = $stmt->fetchAll();
// /news cambia quando cambia un articolo, non quando cambia news.php.
$newsLastmod = $articles ? max(array_map(fn($a) => strtotime($a['updated_at']), $articles)) : null;

foreach ($staticUrls as [$path, $priority, $changefreq]) {
    // lastmod = ultima modifica del file PHP della pagina (URL senza estensione):
    // dice a Google quando ricontrollarla. Mercati cambia ogni giorno coi
    // prezzi, ma il file no: lì conta changefreq=daily.
    $file = __DIR__ . ($path === '/' ? '/index.php' : $path . '.php');
    $time = is_file($file) ? filemtime($file) : null;
    if ($path === '/news' && $newsLastmod) {
        $time = max($time ?? 0, $newsLastmod);
    }
    $lastmod = $time ? '<lastmod>' . date('Y-m-d', $time) . '</lastmod>' : '';
    echo '  <url><loc>https://reflexi.it' . $path . '</loc>' . $lastmod
        . '<changefreq>' . $changefreq . '</changefreq>'
        . '<priority>' . $priority . '</priority></url>' . "\n";
}

foreach ($articles as $row) {
    $lastmod = date('Y-m-d', strtotime($row['updated_at']));
    echo '  <url><loc>https://reflexi.it/news/' . htmlspecialchars($row['slug']) . '</loc>'
        . '<lastmod>' . $lastmod . '</lastmod>'
        . '<changefreq>monthly</changefreq><priority>0.6</priority></url>' . "\n";
}

echo '</urlset>' . "\n";
