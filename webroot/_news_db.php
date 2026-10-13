<?php
declare(strict_types=1);

// Connessione DB minimale per le pagine pubbliche (news.php, news-articolo.php,
// sitemap.php). A differenza di api/db.php, qui NON tocchiamo sessione o
// header: api/db.php imposta Content-Type JSON e un exception handler che
// risponde sempre in JSON, adatto agli endpoint API ma sbagliato per una
// pagina HTML (un errore diventerebbe un blob JSON invece di una pagina
// leggibile). Stessa logica di connessione e stesse env var di db().
function news_db(): PDO
{
    static $pdo = null;
    if ($pdo === null) {
        $host = getenv('DB_HOST') ?: 'localhost';
        $name = getenv('DB_NAME') ?: 'finanze';
        $user = getenv('DB_USER') ?: 'root';
        $pass = getenv('DB_PASSWORD') ?: '';

        $dsn = "mysql:host={$host};dbname={$name};charset=utf8mb4";
        $pdo = new PDO($dsn, $user, $pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
    }
    return $pdo;
}

// Data in italiano per le pagine News, es. "13 ottobre 2026".
function news_fmt_date(string $datetime): string
{
    $months = ['gennaio','febbraio','marzo','aprile','maggio','giugno','luglio','agosto','settembre','ottobre','novembre','dicembre'];
    $ts = strtotime($datetime);
    return (int) date('j', $ts) . ' ' . $months[(int) date('n', $ts) - 1] . ' ' . date('Y', $ts);
}

// Recupera un set scelto a mano di articoli News (slug -> titolo/estratto/emoji),
// usato dalle pagine calcolatore per linkare gli approfondimenti pertinenti.
// Filtra su stato/data come le altre query pubbliche e preserva l'ordine di
// $slugs (il più rilevante per primo), non l'ordine restituito dal DB.
function news_related(array $slugs): array
{
    if ($slugs === []) {
        return [];
    }
    $placeholders = implode(',', array_fill(0, count($slugs), '?'));
    $stmt = news_db()->prepare(
        "SELECT slug, title, excerpt, cover_emoji FROM news_articles
         WHERE status = 'pubblicato' AND published_at <= NOW() AND slug IN ($placeholders)"
    );
    $stmt->execute($slugs);
    $bySlug = array_column($stmt->fetchAll(), null, 'slug');
    return array_values(array_filter(array_map(
        static fn($slug) => $bySlug[$slug] ?? null,
        $slugs
    )));
}
