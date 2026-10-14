<?php
declare(strict_types=1);
require __DIR__ . '/db.php';

// Area admin: elenco utenti registrati (e dettaglio di un utente con ?id=N)
// con i soli dati anagrafici, di account e di accesso. Di proposito NON legge
// nessuna tabella dati (conti, transazioni, investimenti, ...): i dati
// finanziari degli utenti restano privati anche per l'admin. Niente token,
// hash password o ID Stripe in uscita.
//
// Scritture (vedi admin_update_user e admin_user_action più sotto):
// PUT ?id=N modifica i dati anagrafici; POST ?id=N&action=... per verifica
// email, link di reset password e fine della prova. Il flag is_admin non si
// tocca da qui (solo via SQL), lo stato dell'abbonamento lo governa Stripe.
// Ogni modifica finisce nel log di sicurezza con l'admin che l'ha fatta.

// Oltre questa inattività una sessione non chiusa con logout è considerata
// scaduta: è il session.gc_maxlifetime di PHP (default 1440 s).
const ADMIN_SESSION_IDLE_SECONDS = 1440;

$adminId = require_admin();

function admin_user_row(array $u): array
{
    return [
        'id' => (int)$u['id'],
        'email' => $u['email'],
        'name' => $u['name'],
        // Dati del modulo di registrazione (migrazioni 061-062): NULL per gli
        // account creati prima.
        'first_name' => $u['first_name'] ?? null,
        'last_name' => $u['last_name'] ?? null,
        'phone' => $u['phone'] ?? null,
        'finance_experience' => $u['finance_experience'] ?? null,
        'preferred_plan' => $u['preferred_plan'] ?? null,
        // Solo il fatto che esista, non l'ID: serve a sapere se la prova è
        // modificabile (con un abbonamento la gestisce Stripe).
        'has_subscription' => !empty($u['stripe_subscription_id']),
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

function admin_user_columns(): string
{
    $extra = db_has_column('users', 'preferred_plan') ? ', u.preferred_plan' : '';
    if (db_has_column('users', 'first_name')) $extra .= ', u.first_name, u.last_name, u.phone, u.finance_experience';
    return "u.id, u.email, u.name, u.is_admin, u.email_verified_at, u.created_at,
        u.subscription_status, u.trial_ends_at, u.subscription_current_period_end, u.digest_frequency,
        u.stripe_subscription_id{$extra},
        (SELECT MAX(s.started_at) FROM user_sessions s WHERE s.user_id = u.id) AS last_login_at,
        (SELECT MAX(s.last_seen_at) FROM user_sessions s WHERE s.user_id = u.id) AS last_seen_at";
}

function admin_load_user(int $id): array
{
    $stmt = db()->prepare("SELECT " . admin_user_columns() . " FROM users u WHERE u.id = :id");
    $stmt->execute(['id' => $id]);
    $u = $stmt->fetch();
    if (!$u) respond_error('Utente non trovato', 404);
    return $u;
}

/** PUT ?id=N: nome, cognome, email, telefono, esperienza, riepilogo email. */
function admin_update_user(int $adminId, int $id): void
{
    require_once __DIR__ . '/lib_user_profile.php';
    if (!db_has_column('users', 'first_name')) respond_error('Database da aggiornare: manca la migrazione 062', 500);
    $before = admin_load_user($id);
    $in = json_input();

    [$first, $last] = profile_names($in['first_name'] ?? null, $in['last_name'] ?? null);
    $email = strtolower(trim((string)($in['email'] ?? '')));
    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 190) respond_error('Email non valida');
    $stmt = db()->prepare("SELECT id FROM users WHERE email = :email AND id <> :id");
    $stmt->execute(['email' => $email, 'id' => $id]);
    if ($stmt->fetch()) respond_error('Esiste già un altro account con questa email');
    $digest = $in['digest_frequency'] ?? $before['digest_frequency'];
    if (!in_array($digest, ['nessuno', 'settimanale', 'mensile'], true)) respond_error('Frequenza del riepilogo non valida');

    $new = [
        'first_name' => $first,
        'last_name' => $last,
        'name' => $first . ' ' . $last,
        'email' => $email,
        'phone' => profile_phone($in['phone'] ?? null),
        'finance_experience' => profile_experience($in['finance_experience'] ?? null),
        'digest_frequency' => $digest,
    ];
    $changed = array_keys(array_filter($new, fn($v, $k) => ($before[$k] ?? null) !== $v, ARRAY_FILTER_USE_BOTH));
    if (!$changed) respond(['user' => admin_user_row($before), 'message' => 'Nessuna modifica da salvare.']);

    $set = implode(', ', array_map(fn($c) => "{$c} = :{$c}", $changed));
    $params = array_intersect_key($new, array_flip($changed)) + ['id' => $id];
    db()->prepare("UPDATE users SET {$set} WHERE id = :id")->execute($params);
    // Solo i nomi dei campi: i valori (telefono, email) restano fuori dal log.
    log_event('info', 'admin_user_updated', "Admin #{$adminId} ha modificato: " . implode(', ', $changed), $id);

    // Le ricevute Stripe vanno all'email del cliente Stripe: va tenuta allineata.
    $warning = null;
    if (in_array('email', $changed, true)) {
        $stmt = db()->prepare("SELECT stripe_customer_id FROM users WHERE id = :id");
        $stmt->execute(['id' => $id]);
        $customerId = $stmt->fetchColumn();
        if ($customerId) {
            require_once __DIR__ . '/lib_stripe.php';
            try {
                \Stripe\Customer::update($customerId, ['email' => $email]);
            } catch (\Exception $e) {
                if (!stripe_is_missing_customer($e)) {
                    log_stripe_error("Admin #{$adminId}: email del cliente Stripe non aggiornata", $e, $id);
                    $warning = 'Email salvata, ma non aggiornata su Stripe: le ricevute di pagamento andranno ancora al vecchio indirizzo. Riprova più tardi.';
                }
            }
        }
    }

    respond(['user' => admin_user_row(admin_load_user($id)), 'message' => 'Dati aggiornati.', 'warning' => $warning]);
}

/** POST ?id=N&action=verify_email|send_password_reset|set_trial_end */
function admin_user_action(int $adminId, int $id, string $action): void
{
    $u = admin_load_user($id);
    $in = json_input();

    if ($action === 'verify_email') {
        if ($u['email_verified_at'] !== null) respond_error('Email già verificata');
        db()->prepare("UPDATE users SET email_verified_at = NOW(), verification_token = NULL, verification_token_expires = NULL WHERE id = :id")
            ->execute(['id' => $id]);
        log_event('info', 'admin_user_updated', "Admin #{$adminId} ha segnato l'email come verificata", $id);
        respond(['user' => admin_user_row(admin_load_user($id)), 'message' => 'Email segnata come verificata: ora l\'utente può accedere.']);
    }

    if ($action === 'send_password_reset') {
        require_once __DIR__ . '/lib_mailer.php';
        if (!send_password_reset_email($id, $u['email'], 24)) {
            respond_error('Email non inviata: controlla la configurazione SMTP del server.', 502);
        }
        log_event('info', 'admin_password_reset_sent', "Admin #{$adminId} ha inviato un link di reset password", $id);
        respond(['user' => admin_user_row($u), 'message' => "Link per reimpostare la password inviato a {$u['email']} (valido 24 ore)."]);
    }

    if ($action === 'set_trial_end') {
        // Con un abbonamento Stripe la prova la gestisce Stripe (e il webhook
        // riscriverebbe la data): qui solo per chi non ha abbonamenti attivi.
        if (!empty($u['stripe_subscription_id']) || in_array($u['subscription_status'], ['active', 'past_due'], true)) {
            respond_error('Questo utente ha un abbonamento: la prova si gestisce da Stripe.');
        }
        $date = (string)($in['trial_ends_on'] ?? '');
        $d = DateTime::createFromFormat('!Y-m-d', $date);
        if (!$d || $d->format('Y-m-d') !== $date) respond_error('Data non valida');
        if ($d > new DateTime('+2 years')) respond_error('La prova può durare al massimo fino a due anni da oggi');
        $endsAt = $date . ' 23:59:59';
        db()->prepare("UPDATE users SET trial_ends_at = :ends, subscription_status = 'trialing' WHERE id = :id")
            ->execute(['ends' => $endsAt, 'id' => $id]);
        log_event('info', 'admin_user_updated', "Admin #{$adminId} ha impostato la fine della prova al {$date} (prima: " . ($u['trial_ends_at'] ?? 'nessuna') . ", stato: {$u['subscription_status']})", $id);
        respond(['user' => admin_user_row(admin_load_user($id)), 'message' => 'Fine della prova aggiornata.']);
    }

    respond_error('Azione non valida');
}

$method = method();
if ($method === 'PUT' || $method === 'POST') {
    $id = (int)($_GET['id'] ?? 0);
    if ($id <= 0) respond_error('Utente non indicato');
    if ($method === 'PUT') admin_update_user($adminId, $id);
    admin_user_action($adminId, $id, (string)($_GET['action'] ?? ''));
}
if ($method !== 'GET') respond_error('Metodo non supportato', 405);

if (isset($_GET['id'])) {
    $stmt = db()->prepare("SELECT " . admin_user_columns() . " FROM users u WHERE u.id = :id");
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
    SELECT " . admin_user_columns() . "
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
