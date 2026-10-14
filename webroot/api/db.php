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
    log_event('error', 'server_error', get_class($e) . ': ' . $e->getMessage()
        . ' @ ' . basename($e->getFile()) . ':' . $e->getLine(), current_user_id());
    http_response_code(500);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['error' => 'Errore interno: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
    exit;
});
// Errori fatali (memoria, timeout, ...) non passano dal gestore eccezioni:
// li registra comunque nel log di sicurezza, a fine richiesta.
register_shutdown_function(function () {
    $err = error_get_last();
    if ($err && in_array($err['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true)) {
        log_event('error', 'server_error', 'Errore fatale: ' . $err['message'] . ' @ ' . basename($err['file']) . ':' . $err['line'], current_user_id());
    }
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

/**
 * Registra un evento nel log di sicurezza (sezione "Admin · Log"): login
 * falliti, rate limit, accessi negati, errori server, ... Non deve MAI far
 * fallire la richiesta che lo chiama (né ricorrere nel gestore eccezioni):
 * qualunque errore di scrittura finisce solo nell'error_log di PHP. Mai
 * passare password o token in $message.
 */
function log_event(string $level, string $type, string $message = '', ?int $userId = null, ?string $email = null): void
{
    try {
        db()->prepare("INSERT INTO security_events (level, type, user_id, email, ip, user_agent, endpoint, message)
            VALUES (:level, :type, :uid, :email, :ip, :ua, :endpoint, :message)")
            ->execute([
                'level' => $level,
                'type' => $type,
                'uid' => $userId,
                'email' => $email !== null ? mb_substr($email, 0, 190) : null,
                'ip' => PHP_SAPI === 'cli' ? null : client_ip(),
                'ua' => mb_substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255) ?: null,
                'endpoint' => PHP_SAPI === 'cli' ? ($_SERVER['SCRIPT_NAME'] ?? 'cli')
                    : mb_substr(($_SERVER['REQUEST_METHOD'] ?? '') . ' ' . strtok($_SERVER['REQUEST_URI'] ?? '', '?'), 0, 255),
                'message' => mb_substr($message, 0, 2000),
            ]);
        // Pulizia occasionale (circa 1 scrittura su 100): log oltre 90 giorni.
        if (random_int(1, 100) === 1) {
            db()->exec("DELETE FROM security_events WHERE created_at < NOW() - INTERVAL 90 DAY");
        }
    } catch (Throwable $e) {
        error_log('log_event fallito: ' . $e->getMessage());
    }
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
    // Utente eliminato (dall'area admin) con la sessione ancora aperta: 401,
    // il frontend torna al login invece di mostrare il Paywall.
    $exists = db()->prepare("SELECT 1 FROM users WHERE id = :id");
    $exists->execute(['id' => $uid]);
    if (!$exists->fetchColumn()) respond_error('Sessione non più valida', 401);
    touch_login_session($uid);
    // Trial scaduto / abbonamento non valido: 402, il frontend mostra il Paywall.
    // Restano aperti solo gli endpoint che servono per pagare o uscire.
    if (!billing_exempt_endpoint() && !user_has_access($uid)) {
        respond(['error' => 'Abbonamento richiesto', 'code' => 'payment_required'], 402);
    }
    return $uid;
}

/**
 * Endpoint raggiungibili anche senza accesso valido: tutto auth/* (me, logout,
 * delete_account, ...), checkout e portale Stripe. Il webhook non usa
 * require_auth (si autentica con la firma Stripe).
 */
function billing_exempt_endpoint(): bool
{
    $script = str_replace('\\', '/', $_SERVER['SCRIPT_FILENAME'] ?? '');
    $apiDir = str_replace('\\', '/', __DIR__);
    if (str_starts_with($script, $apiDir . '/auth/')) return true;
    return in_array($script, [
        $apiDir . '/stripe_checkout.php',
        $apiDir . '/stripe_portal.php',
        $apiDir . '/stripe_webhook.php',
    ], true);
}

/** Giorni di accesso concessi dopo un pagamento fallito (stato past_due). */
const PAST_DUE_GRACE_DAYS = 7;

/**
 * True se l'utente ha accesso valido in questo momento:
 * - abbonamento attivo;
 * - trial con abbonamento Stripe già attivato (stripe_subscription_id): la fine
 *   del trial la gestisce Stripe, che ci avvisa via webhook, quindi resta
 *   valido anche oltre trial_ends_at;
 * - trial senza abbonamento, finché trial_ends_at non è passato;
 * - past_due per al massimo PAST_DUE_GRACE_DAYS giorni da past_due_since.
 */
function has_active_access(array $user): bool
{
    switch ($user['subscription_status']) {
        case 'active':
            return true;
        case 'trialing':
            if (!empty($user['stripe_subscription_id'])) return true;
            return $user['trial_ends_at'] && strtotime($user['trial_ends_at']) > time();
        case 'past_due':
            $since = $user['past_due_since'] ?? null;
            return $since && strtotime($since) + PAST_DUE_GRACE_DAYS * 86400 > time();
        default:
            return false;
    }
}

/**
 * has_active_access() riletto dal DB. SELECT * di proposito: past_due_since
 * arriva con la migrazione 059 e il codice deve funzionare anche prima.
 */
function user_has_access(int $uid): bool
{
    $stmt = db()->prepare("SELECT * FROM users WHERE id = :id");
    $stmt->execute(['id' => $uid]);
    $user = $stmt->fetch();
    return $user ? has_active_access($user) : false;
}

/** True se la colonna esiste (cache per richiesta). Per codice che precede una migrazione. */
function db_has_column(string $table, string $column): bool
{
    static $cache = [];
    $key = "$table.$column";
    if (!isset($cache[$key])) {
        $stmt = db()->prepare("SELECT COUNT(*) FROM information_schema.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :t AND COLUMN_NAME = :c");
        $stmt->execute(['t' => $table, 'c' => $column]);
        $cache[$key] = (int)$stmt->fetchColumn() > 0;
    }
    return $cache[$key];
}

/**
 * Aggiorna l'ultima attività della sessione di login corrente (tabella
 * user_sessions, vedi login.php), da cui l'area admin ricava la durata delle
 * sessioni. Al massimo una scrittura al minuto per sessione: la condizione su
 * last_seen_at evita un UPDATE effettivo a ogni richiesta.
 */
function touch_login_session(int $uid): void
{
    $sid = $_SESSION['login_session_id'] ?? null;
    if (!$sid) return; // sessioni aperte prima dell'introduzione dello storico
    db()->prepare("UPDATE user_sessions SET last_seen_at = NOW()
        WHERE id = :id AND user_id = :uid AND ended_at IS NULL
          AND last_seen_at < NOW() - INTERVAL 60 SECOND")
        ->execute(['id' => $sid, 'uid' => $uid]);
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
        log_event('warning', 'admin_denied', 'Tentativo di accesso a ' . basename($_SERVER['SCRIPT_NAME'] ?? ''), $uid);
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
        log_event('warning', 'rate_limited', "Superato il limite per '{$action}' ({$maxAttempts} in {$windowSeconds} s)", current_user_id());
        respond_error('Troppi tentativi. Riprova tra qualche minuto.', 429);
    }
}

/** Per quante ore si tengono i tentativi registrati: ben oltre la finestra più lunga usata (1 ora). */
const RATE_LIMIT_RETENTION_HOURS = 24;

/** Registra un tentativo per l'azione indicata, da chiamare dopo il check. */
function rate_limit_record(string $action): void
{
    db()->prepare("INSERT INTO rate_limit_attempts (action, identifier) VALUES (:action, :identifier)")
        ->execute(['action' => $action, 'identifier' => client_ip()]);
    // Pulizia occasionale (circa 1 scrittura su 100): l'IP non serve oltre la
    // finestra del limite, e la Privacy Policy dichiara 24 ore.
    if (random_int(1, 100) === 1) {
        db()->exec("DELETE FROM rate_limit_attempts WHERE created_at < NOW() - INTERVAL " . RATE_LIMIT_RETENTION_HOURS . " HOUR");
    }
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
    if ($status >= 500) log_event('error', 'server_error', "HTTP {$status}: {$message}", current_user_id());
    respond(['error' => $message], $status);
}

function method(): string
{
    return $_SERVER['REQUEST_METHOD'];
}
