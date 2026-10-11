<?php
declare(strict_types=1);

// Su XAMPP non esistono le variabili d'ambiente che Docker Compose passerebbe
// al container: se c'è un file .env accanto a questo script (solo per SMTP/APP_URL,
// non per le credenziali DB che restano quelle di XAMPP), lo carica qui.
// Su Docker le variabili sono già impostate da docker-compose.yml e questo blocco
// non sovrascrive nulla di già presente.
$envFile = __DIR__ . '/.env';
if (is_file($envFile)) {
    foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) continue;
        [$key, $value] = explode('=', $line, 2);
        $key = trim($key);
        if (getenv($key) === false) {
            putenv($key . '=' . trim($value));
        }
    }
}

// Qualsiasi errore PHP (query fallita, eccezione non gestita, ecc.) deve sempre
// tornare come JSON valido, mai come pagina HTML con "<br />" — altrimenti il
// frontend vede un errore di parsing invece del vero messaggio.
ini_set('display_errors', '0');
error_reporting(E_ALL);

// Sessione PHP: e' cosi' che sappiamo "chi sta chiedendo" in ogni endpoint.
// 'secure' e' condizionato a HTTPS reale: in locale (XAMPP, o accesso diretto
// alla porta 8080 senza passare da Caddy) resta http puro e il cookie deve
// comunque partire, altrimenti il login smette di funzionare li'. Dietro
// Caddy il traffico arriva sempre come reverse proxy in HTTP verso questo
// container, quindi la rilevazione vera e' l'header X-Forwarded-Proto che
// Caddy imposta di default (stesso principio di client_ip() con X-Forwarded-For).
$isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
    || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');

session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'httponly' => true,
    'samesite' => 'Lax',
    'secure' => $isHttps,
]);
session_start();
// Rilascia subito il lock sul file di sessione. PHP lo tiene per tutta la
// durata della richiesta: senza questa chiamata una richiesta lenta (es.
// forecasts.php a cache fredda, decine di chiamate Yahoo) blocca in coda
// TUTTE le altre richieste dello stesso utente, e l'app sembra congelata.
// $_SESSION resta leggibile; chi deve scriverla chiama session_reopen().
// write_close (e non read_and_close) aggiorna il timestamp del file, così il
// GC delle sessioni non scollega chi è attivo.
session_write_close();

set_exception_handler(function (Throwable $e) {
    http_response_code(500);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['error' => 'Errore interno: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
    exit;
});
set_error_handler(function ($severity, $message, $file, $line) {
    // Rispetta l'operatore @: se l'errore è stato volutamente soppresso
    // (es. file_get_contents verso un'API esterna che potrebbe non rispondere),
    // non deve diventare un'eccezione fatale.
    if (!(error_reporting() & $severity)) {
        return false;
    }
    throw new ErrorException($message, 0, $severity, $file, $line);
});

header('Content-Type: application/json; charset=utf-8');

/** ID dell'utente loggato, o null se nessuna sessione attiva. */
function current_user_id(): ?int
{
    return $_SESSION['user_id'] ?? null;
}

/** Riapre la sessione in scrittura: solo per login/logout/eliminazione account. */
function session_reopen(): void
{
    if (session_status() !== PHP_SESSION_ACTIVE) session_start();
}

/** Richiede una sessione valida: se assente, interrompe con 401 JSON. */
function require_auth(): int
{
    $uid = current_user_id();
    if (!$uid) {
        respond_error('Non autenticato', 401);
    }
    return $uid;
}

/**
 * Richiede una sessione valida di un utente admin: 401 se non loggato, 403 se
 * loggato ma senza flag is_admin. Il flag è riletto dal DB a ogni richiesta
 * (non messo in sessione), così una revoca ha effetto immediato.
 */
function require_admin(): int
{
    $uid = require_auth();
    $stmt = db()->prepare("SELECT is_admin FROM users WHERE id = :id");
    $stmt->execute(['id' => $uid]);
    if ((int)$stmt->fetchColumn() !== 1) {
        respond_error('Accesso riservato agli amministratori', 403);
    }
    return $uid;
}

/**
 * Verifica una risorsa referenziata (conto, categoria, strumento, ecc.)
 * appartenga davvero all'utente loggato, prima di usarla in una scrittura.
 * Senza questo controllo, un utente autenticato potrebbe agganciare le proprie
 * transazioni/ricorrenze/mutui a un conto o una categoria di un ALTRO utente,
 * semplicemente passando un ID diverso nella richiesta.
 * $id === null o stringa vuota viene ignorato (campo opzionale non valorizzato).
 */
function assert_owned(string $table, $id, int $uid, string $label = 'risorsa'): void
{
    if ($id === null || $id === '') return;
    $stmt = db()->prepare("SELECT id FROM {$table} WHERE id = :id AND user_id = :uid");
    $stmt->execute(['id' => $id, 'uid' => $uid]);
    if (!$stmt->fetch()) {
        respond_error(ucfirst($label) . " non valido o non appartenente al tuo account", 403);
    }
}

/**
 * IP reale del client. Dietro un reverse proxy (Caddy), REMOTE_ADDR mostra
 * l'IP del container proxy, non del visitatore — va letto da X-Forwarded-For,
 * altrimenti il rate limiting per IP diventerebbe inutile (tutti condividono
 * lo stesso "IP" agli occhi di PHP).
 */
function client_ip(): string
{
    if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $parts = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
        return trim($parts[0]);
    }
    return $_SERVER['REMOTE_ADDR'] ?? 'unknown';
}

/**
 * Interrompe con 429 se l'IP corrente ha superato il numero massimo di
 * tentativi per una data azione nella finestra temporale indicata.
 */
function rate_limit_check(string $action, int $maxAttempts, int $windowSeconds): void
{
    $stmt = db()->prepare("SELECT COUNT(*) FROM rate_limit_attempts
        WHERE action = :action AND identifier = :identifier
        AND created_at >= DATE_SUB(NOW(), INTERVAL :window SECOND)");
    $stmt->execute(['action' => $action, 'identifier' => client_ip(), 'window' => $windowSeconds]);
    if ((int)$stmt->fetchColumn() >= $maxAttempts) {
        respond_error('Troppi tentativi. Riprova tra qualche minuto.', 429);
    }
}

/** Registra un tentativo per l'azione indicata, da chiamare dopo il check. */
function rate_limit_record(string $action): void
{
    db()->prepare("INSERT INTO rate_limit_attempts (action, identifier) VALUES (:action, :identifier)")
        ->execute(['action' => $action, 'identifier' => client_ip()]);
}
if (php_sapi_name() !== 'cli') {
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type');

    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        http_response_code(204);
        exit;
    }
}

function db(): PDO
{
    static $pdo = null;
    if ($pdo === null) {
        // Se le variabili d'ambiente non sono impostate (es. sviluppo locale con XAMPP
        // invece che Docker), usa i default tipici di una installazione XAMPP:
        // MySQL su localhost, utente root, nessuna password.
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

function json_input(): array
{
    $raw = file_get_contents('php://input');
    if (!$raw) return [];
    $data = json_decode($raw, true);
    return is_array($data) ? $data : [];
}

function respond($data, int $status = 200): void
{
    http_response_code($status);
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function respond_error(string $message, int $status = 400): void
{
    respond(['error' => $message], $status);
}

function method(): string
{
    return $_SERVER['REQUEST_METHOD'];
}
