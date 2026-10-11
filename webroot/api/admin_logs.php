<?php
declare(strict_types=1);
require __DIR__ . '/db.php';

// Area admin: log di sicurezza ed errori (tabella security_events, scritta da
// log_event() in db.php). Filtri opzionali: type, level, q (testo libero su
// email/IP/messaggio/endpoint), days (1-90, default 7).

const ADMIN_LOGS_LIMIT = 300;
// Tipi che indicano un possibile tentativo di accesso non autorizzato:
// usati per la classifica degli IP sospetti.
const ADMIN_LOGS_SUSPICIOUS_TYPES = ['login_failed', 'rate_limited', 'admin_denied', 'password_check_failed', 'reset_invalid'];

require_admin();
if (method() !== 'GET') respond_error('Metodo non supportato', 405);

$days = max(1, min(90, (int)($_GET['days'] ?? 7)));
$where = ['e.created_at >= NOW() - INTERVAL :days DAY'];
$params = ['days' => $days];

if (!empty($_GET['type'])) {
    $where[] = 'e.type = :type';
    $params['type'] = (string)$_GET['type'];
}
if (!empty($_GET['level']) && in_array($_GET['level'], ['info', 'warning', 'error'], true)) {
    $where[] = 'e.level = :level';
    $params['level'] = $_GET['level'];
}
$q = trim((string)($_GET['q'] ?? ''));
if ($q !== '') {
    $where[] = '(e.email LIKE :q1 OR e.ip LIKE :q2 OR e.message LIKE :q3 OR e.endpoint LIKE :q4 OR u.email LIKE :q5)';
    $like = '%' . addcslashes($q, '%_\\') . '%';
    foreach (['q1', 'q2', 'q3', 'q4', 'q5'] as $k) $params[$k] = $like;
}

$stmt = db()->prepare("SELECT e.id, e.created_at, e.level, e.type, e.user_id, e.email, e.ip, e.user_agent,
        e.endpoint, e.message, u.email AS user_email, u.name AS user_name
    FROM security_events e
    LEFT JOIN users u ON u.id = e.user_id
    WHERE " . implode(' AND ', $where) . "
    ORDER BY e.created_at DESC, e.id DESC
    LIMIT " . ADMIN_LOGS_LIMIT);
$stmt->execute($params);
$events = array_map(fn($e) => [
    'id' => (int)$e['id'],
    'created_at' => $e['created_at'],
    'level' => $e['level'],
    'type' => $e['type'],
    'user_id' => $e['user_id'] !== null ? (int)$e['user_id'] : null,
    // L'email tentata (es. login fallito) prevale; altrimenti quella dell'utente collegato.
    'email' => $e['email'] ?? $e['user_email'],
    'user_name' => $e['user_name'],
    'ip' => $e['ip'],
    'user_agent' => $e['user_agent'],
    'endpoint' => $e['endpoint'],
    'message' => $e['message'],
], $stmt->fetchAll());

$summary = db()->query("SELECT
        SUM(type = 'login_failed') AS failed_logins,
        SUM(type = 'rate_limited') AS rate_limited,
        SUM(level = 'error') AS errors,
        SUM(type IN ('admin_denied', 'password_check_failed', 'reset_invalid', 'stripe_webhook_invalid')) AS other_warnings
    FROM security_events WHERE created_at >= NOW() - INTERVAL 24 HOUR")->fetch();

$placeholders = implode(',', array_fill(0, count(ADMIN_LOGS_SUSPICIOUS_TYPES), '?'));
$ipStmt = db()->prepare("SELECT ip, COUNT(*) AS events, COUNT(DISTINCT email) AS emails, MAX(created_at) AS last_at
    FROM security_events
    WHERE ip IS NOT NULL AND type IN ($placeholders) AND created_at >= NOW() - INTERVAL 7 DAY
    GROUP BY ip ORDER BY events DESC LIMIT 10");
$ipStmt->execute(ADMIN_LOGS_SUSPICIOUS_TYPES);

$types = db()->query("SELECT DISTINCT type FROM security_events ORDER BY type")->fetchAll(PDO::FETCH_COLUMN);

respond([
    'events' => $events,
    'limit' => ADMIN_LOGS_LIMIT,
    'types' => $types,
    'summary_24h' => array_map('intval', $summary),
    'top_ips' => array_map(fn($r) => [
        'ip' => $r['ip'],
        'events' => (int)$r['events'],
        'emails' => (int)$r['emails'],
        'last_at' => $r['last_at'],
    ], $ipStmt->fetchAll()),
]);
