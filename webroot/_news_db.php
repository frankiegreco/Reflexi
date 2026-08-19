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
