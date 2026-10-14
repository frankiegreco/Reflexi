<?php
declare(strict_types=1);
require __DIR__ . '/../db.php';
require __DIR__ . '/../lib_stripe.php';

if (method() !== 'POST') respond_error('Metodo non supportato', 405);

// Max 10 tentativi (riusciti o falliti) ogni 15 minuti per IP: generoso per un
// utente reale che sbaglia la password un paio di volte, stretto per un brute force.
rate_limit_check('login', 10, 900);
rate_limit_record('login');

$in = json_input();
$email = strtolower(trim($in['email'] ?? ''));
$password = $in['password'] ?? '';

$stmt = db()->prepare("SELECT * FROM users WHERE email = :email");
$stmt->execute(['email' => $email]);
$user = $stmt->fetch();

if (!$user || !password_verify($password, $user['password_hash'])) {
    log_event('warning', 'login_failed', $user ? 'Password errata' : 'Email non registrata', $user ? (int)$user['id'] : null, $email);
    respond_error('Email o password non corretti', 401);
}
if (!$user['email_verified_at']) {
    log_event('info', 'login_unverified', 'Login con email non ancora verificata', (int)$user['id'], $email);
    respond(['error' => 'Devi prima confermare la tua email. Controlla la posta in arrivo.', 'code' => 'email_not_verified'], 403);
}

session_reopen();
session_regenerate_id(true);
$_SESSION['user_id'] = (int)$user['id'];

// Storico accessi per l'area admin (durata sessioni, IP, dispositivo). Le
// righe oltre 180 giorni si cancellano qui, al login successivo dell'utente.
db()->prepare("DELETE FROM user_sessions WHERE user_id = :uid AND started_at < NOW() - INTERVAL 180 DAY")
    ->execute(['uid' => $user['id']]);
db()->prepare("INSERT INTO user_sessions (user_id, started_at, last_seen_at, ip, user_agent)
    VALUES (:uid, NOW(), NOW(), :ip, :ua)")
    ->execute([
        'uid' => $user['id'],
        'ip' => client_ip(),
        'ua' => mb_substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255) ?: null,
    ]);
$_SESSION['login_session_id'] = (int)db()->lastInsertId();

$trialDaysLeft = null;
if ($user['subscription_status'] === 'trialing' && $user['trial_ends_at']) {
    $trialDaysLeft = max(0, (int)ceil((strtotime($user['trial_ends_at']) - time()) / 86400));
}

respond([
    'id' => (int)$user['id'],
    'email' => $user['email'],
    'name' => $user['name'],
    'digest_frequency' => $user['digest_frequency'],
    'subscription_status' => $user['subscription_status'],
    'trial_ends_at' => $user['trial_ends_at'],
    'trial_days_left' => $trialDaysLeft,
    'subscription_current_period_end' => $user['subscription_current_period_end'],
    'subscription_cancel_at' => $user['subscription_cancel_at'] ?? null,
    'has_subscription' => !empty($user['stripe_subscription_id']),
    'has_access' => user_has_access((int)$user['id']),
    'is_admin' => (int)$user['is_admin'] === 1,
]);
