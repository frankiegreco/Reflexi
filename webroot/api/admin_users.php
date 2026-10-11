<?php
declare(strict_types=1);
require __DIR__ . '/db.php';

// Area admin: elenco utenti registrati (e dettaglio di un utente con ?id=N)
// con i soli dati anagrafici, di account e di accesso. Di proposito NON legge
// nessuna tabella dati (conti, transazioni, investimenti, ...): i dati
// finanziari degli utenti restano privati anche per l'admin. Niente token,
// hash password o ID Stripe in uscita.

// Oltre questa inattività una sessione non chiusa con logout è considerata
// scaduta: è il session.gc_maxlifetime di PHP (default 1440 s).
const ADMIN_SESSION_IDLE_SECONDS = 1440;

require_admin();
if (method() !== 'GET') respond_error('Metodo non supportato', 405);

function admin_user_row(array $u): array
{
    return [
        'id' => (int)$u['id'],
        'email' => $u['email'],
        'name' => $u['name'],
        'is_admin' => (int)$u['is_admin'] === 1,
        'email_verified' => $u['email_verified_at'] !== null,
        'email_verified_at' => $u['email_verified_at'],
        'created_at' => $u['created_at'],
        'subscription_status' => $u['subscription_status'],
        'trial_ends_at' => $u['trial_ends_at'],
        'subscription_current_period_end' => $u['subscription_current_period_end'],
        'digest_frequency' => $u['digest_frequency'],
        'last_login_at' => $u['last_login_at'],
        'last_seen_at' => $u['last_seen_at'],
    ];
}

const ADMIN_USER_COLUMNS = "u.id, u.email, u.name, u.is_admin, u.email_verified_at, u.created_at,
    u.subscription_status, u.trial_ends_at, u.subscription_current_period_end, u.digest_frequency,
    (SELECT MAX(s.started_at) FROM user_sessions s WHERE s.user_id = u.id) AS last_login_at,
    (SELECT MAX(s.last_seen_at) FROM user_sessions s WHERE s.user_id = u.id) AS last_seen_at";

if (isset($_GET['id'])) {
    $stmt = db()->prepare("SELECT " . ADMIN_USER_COLUMNS . " FROM users u WHERE u.id = :id");
    $stmt->execute(['id' => (int)$_GET['id']]);
    $u = $stmt->fetch();
    if (!$u) respond_error('Utente non trovato', 404);

    $stmt = db()->prepare("SELECT id, started_at, last_seen_at, ended_at, ip, user_agent,
            TIMESTAMPDIFF(SECOND, started_at, COALESCE(ended_at, last_seen_at)) AS duration_seconds,
            (ended_at IS NULL AND last_seen_at >= NOW() - INTERVAL " . ADMIN_SESSION_IDLE_SECONDS . " SECOND) AS is_active
        FROM user_sessions WHERE user_id = :uid ORDER BY started_at DESC");
    $stmt->execute(['uid' => $u['id']]);
    $sessions = array_map(fn($s) => [
        'id' => (int)$s['id'],
        'started_at' => $s['started_at'],
        'last_seen_at' => $s['last_seen_at'],
        'ended_at' => $s['ended_at'],
        'ip' => $s['ip'],
        'user_agent' => $s['user_agent'],
        'duration_seconds' => (int)$s['duration_seconds'],
        'status' => $s['ended_at'] !== null ? 'logout' : ((int)$s['is_active'] === 1 ? 'attiva' : 'scaduta'),
    ], $stmt->fetchAll());

    // Login falliti (password errata) e controlli password falliti, dal log di sicurezza.
    $stmt = db()->prepare("SELECT COUNT(*) FROM security_events
        WHERE user_id = :uid AND type IN ('login_failed', 'password_check_failed')
          AND created_at >= NOW() - INTERVAL 30 DAY");
    $stmt->execute(['uid' => $u['id']]);
    $failed30 = (int)$stmt->fetchColumn();

    $durations = array_column($sessions, 'duration_seconds');
    $since30 = strtotime('-30 days');
    respond([
        'user' => admin_user_row($u),
        'stats' => [
            'logins_total' => count($sessions),
            'logins_30d' => count(array_filter($sessions, fn($s) => strtotime($s['started_at']) >= $since30)),
            'avg_session_seconds' => $durations ? (int)round(array_sum($durations) / count($durations)) : null,
            'total_session_seconds' => $durations ? array_sum($durations) : null,
            'failed_logins_30d' => $failed30,
            'distinct_ips' => count(array_unique(array_filter(array_column($sessions, 'ip')))),
        ],
        // Le ultime 100 bastano per il dettaglio; le statistiche sopra usano tutto lo storico (max 180 giorni).
        'sessions' => array_slice($sessions, 0, 100),
    ]);
}

$users = array_map('admin_user_row', db()->query("
    SELECT " . ADMIN_USER_COLUMNS . "
    FROM users u
    ORDER BY u.created_at DESC, u.id DESC
")->fetchAll());

$since30 = strtotime('-30 days');
respond([
    'users' => $users,
    'summary' => [
        'total' => count($users),
        'verified' => count(array_filter($users, fn($u) => $u['email_verified'])),
        'active' => count(array_filter($users, fn($u) => $u['subscription_status'] === 'active')),
        'trialing' => count(array_filter($users, fn($u) => $u['subscription_status'] === 'trialing')),
        'new_30d' => count(array_filter($users, fn($u) => strtotime($u['created_at']) >= $since30)),
    ],
]);
