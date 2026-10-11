<?php
declare(strict_types=1);
require_once __DIR__ . '/_news_db.php';

header('Content-Type: application/xml; charset=utf-8');

// Pagine statiche del sito, stesse URL/priorità già mantenute finora a mano
// in sitemap.xml (ora rimosso: questo file lo sostituisce).
$staticUrls = [
    ['/', '1.0', 'monthly'],
    ['/mutui.php', '0.8', 'monthly'],
    ['/investimenti.php', '0.8', 'monthly'],
    ['/mercati.php', '0.8', 'daily'],
    ['/budget.php', '0.8', 'monthly'],
    ['/analisi.php', '0.8', 'monthly'],
    ['/previsioni.php', '0.8', 'monthly'],
    ['/simulazione-trading.php', '0.8', 'monthly'],
    ['/fire-pensione.php', '0.8', 'monthly'],
    ['/obiettivi.php', '0.8', 'monthly'],
    ['/abbonamenti.php', '0.8', 'monthly'],
    ['/patrimonio.php', '0.8', 'monthly'],
    ['/calcolatori.php', '0.9', 'monthly'],
    ['/calcolatore-interesse-composto.php', '0.7', 'monthly'],
    ['/calcolatore-piano-investimento.php', '0.7', 'monthly'],
    ['/calcolatore-obiettivo-risparmio.php', '0.7', 'monthly'],
    ['/calcolatore-rendimento.php', '0.7', 'monthly'],
    ['/calcolatore-recupero-perdite.php', '0.7', 'monthly'],
    ['/calcolatore-inflazione.php', '0.7', 'monthly'],
    ['/calcolatore-budget-503020.php', '0.7', 'monthly'],
    ['/calcolatore-patrimonio-netto.php', '0.7', 'monthly'],
    ['/calcolatore-pensione.php', '0.7', 'monthly'],
    ['/about.php', '0.6', 'monthly'],
    ['/faq.php', '0.7', 'monthly'],
    ['/piani.php', '0.9', 'monthly'],
    ['/news', '0.8', 'daily'],
    ['/cancella-account.php', '0.4', 'yearly'],
];

echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

foreach ($staticUrls as [$path, $priority, $changefreq]) {
    echo '  <url><loc>https://reflexi.it' . $path . '</loc>'
        . '<changefreq>' . $changefreq . '</changefreq>'
        . '<priority>' . $priority . '</priority></url>' . "\n";
}

$stmt = news_db()->query(
    "SELECT slug, updated_at FROM news_articles
     WHERE status = 'pubblicato' AND published_at <= NOW()
     ORDER BY published_at DESC"
);
foreach ($stmt as $row) {
    $lastmod = date('Y-m-d', strtotime($row['updated_at']));
    echo '  <url><loc>https://reflexi.it/news/' . htmlspecialchars($row['slug']) . '</loc>'
        . '<lastmod>' . $lastmod . '</lastmod>'
        . '<changefreq>monthly</changefreq><priority>0.6</priority></url>' . "\n";
}

echo '</urlset>' . "\n";
