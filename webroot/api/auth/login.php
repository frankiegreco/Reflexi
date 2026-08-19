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
    respond_error('Email o password non corretti', 401);
}
if (!$user['email_verified_at']) {
    respond(['error' => 'Devi prima confermare la tua email. Controlla la posta in arrivo.', 'code' => 'email_not_verified'], 403);
}

session_regenerate_id(true);
$_SESSION['user_id'] = (int)$user['id'];

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
    'has_access' => has_active_access($user),
]);
