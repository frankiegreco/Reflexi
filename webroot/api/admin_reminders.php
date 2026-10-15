<?php
declare(strict_types=1);
require __DIR__ . '/db.php';
require __DIR__ . '/lib_reminders.php';

// Area admin: promemoria per tornare a usare l'app (vedi lib_reminders.php).
// GET ?days=N[&include_unknown=1]: anteprima dei destinatari dell'invio di massa.
// GET: storico degli invii, con chi è tornato ad accedere dopo il promemoria.
// POST {days, include_unknown, message}: invio a tutti i destinatari.
// L'invio al singolo utente è in admin_users.php (action=send_reminder).

$adminId = require_admin();

function reminder_params(array $src): array
{
    $days = (int)($src['days'] ?? 0);
    if ($days < 1 || $days > 365) respond_error('Numero di giorni non valido');
    return [$days, filter_var($src['include_unknown'] ?? false, FILTER_VALIDATE_BOOLEAN)];
}

if (method() === 'POST') {
    $in = json_input();
    [$days, $includeUnknown] = reminder_params($in);
    $note = (string)($in['message'] ?? '');
    if (mb_strlen(trim($note)) > 1000) respond_error('Il messaggio può avere al massimo 1000 caratteri');
    $targets = array_slice(reminder_candidates($days, $includeUnknown), 0, REMINDER_BULK_MAX);
    if (!$targets) respond_error('Nessun utente da avvisare con questi criteri.');
    // Le email partono una per una: con molti destinatari serve più tempo.
    set_time_limit(max(60, count($targets) * 10));
    $sent = 0; $failed = [];
    foreach ($targets as $u) {
        if (send_reminder($adminId, $u, $note)) $sent++;
        else $failed[] = $u['email'];
    }
    log_event($failed ? 'warning' : 'info', 'admin_reminder_bulk',
        "Admin #{$adminId}: promemoria a inattivi da {$days} giorni, {$sent} inviati, " . count($failed) . ' non riusciti', $adminId);
    if ($sent === 0) respond_error('Nessun promemoria inviato: controlla la configurazione SMTP del server.', 502);
    respond([
        'sent' => $sent,
        'failed' => $failed,
        'message' => "Promemoria inviati: {$sent}" . ($failed ? '. Non riusciti: ' . implode(', ', $failed) : '.'),
    ]);
}
if (method() !== 'GET') respond_error('Metodo non supportato', 405);

if (isset($_GET['days'])) {
    [$days, $includeUnknown] = reminder_params($_GET);
    $all = reminder_candidates($days, $includeUnknown);
    respond([
        'count' => min(count($all), REMINDER_BULK_MAX),
        'capped' => count($all) > REMINDER_BULK_MAX,
        'users' => array_map(fn($u) => [
            'id' => (int)$u['id'],
            'email' => $u['email'],
            'name' => $u['name'],
            'last_activity' => reminder_last_activity($u),
        ], array_slice($all, 0, REMINDER_BULK_MAX)),
        'bulk_gap_days' => REMINDER_BULK_GAP_DAYS,
        'history_start' => REMINDER_HISTORY_START,
    ]);
}

// Storico: ultimi 100 invii; "tornato" = attività registrata dopo il promemoria.
$rows = db()->query("
    SELECT r.id, r.sent_at, u.id AS user_id, u.email, u.name, a.name AS sent_by_name,
        (SELECT MAX(s.last_seen_at) FROM user_sessions s WHERE s.user_id = u.id) AS last_seen_at
    FROM user_reminders r
    JOIN users u ON u.id = r.user_id
    LEFT JOIN users a ON a.id = r.sent_by
    ORDER BY r.sent_at DESC, r.id DESC
    LIMIT 100
")->fetchAll();
respond(['reminders' => array_map(fn($r) => [
    'id' => (int)$r['id'],
    'sent_at' => $r['sent_at'],
    'user_id' => (int)$r['user_id'],
    'email' => $r['email'],
    'name' => $r['name'],
    'sent_by_name' => $r['sent_by_name'],
    'returned' => $r['last_seen_at'] !== null && $r['last_seen_at'] > $r['sent_at'],
    'last_seen_at' => $r['last_seen_at'],
], $rows)]);
