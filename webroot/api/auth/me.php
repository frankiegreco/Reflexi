<?php
declare(strict_types=1);
require __DIR__ . '/../db.php';
require __DIR__ . '/../lib_stripe.php';

$uid = current_user_id();
if (!$uid) respond_error('Non autenticato', 401);
touch_login_session($uid);

if (method() === 'PUT') {
    $in = json_input();
    $fields = [];
    $params = ['id' => $uid];
    if (array_key_exists('name', $in)) {
        $fields[] = 'name = :name';
        $params['name'] = trim($in['name'] ?? '') ?: null;
    }
    if (array_key_exists('digest_frequency', $in) && in_array($in['digest_frequency'], ['nessuno', 'settimanale', 'mensile'], true)) {
        $fields[] = 'digest_frequency = :digest_frequency';
        $params['digest_frequency'] = $in['digest_frequency'];
    }
    if ($fields) {
        db()->prepare("UPDATE users SET " . implode(', ', $fields) . " WHERE id = :id")->execute($params);
    }
}

// subscription_cancel_at e preferred_plan arrivano con le migrazioni 060 e 061.
$cancelAtCol = db_has_column('users', 'subscription_cancel_at') ? ', subscription_cancel_at' : '';
$planCol = db_has_column('users', 'preferred_plan') ? ', preferred_plan' : '';
$stmt = db()->prepare("SELECT id, email, name, is_admin, digest_frequency, subscription_status, trial_ends_at, subscription_current_period_end, stripe_subscription_id{$cancelAtCol}{$planCol} FROM users WHERE id = :id");
$stmt->execute(['id' => $uid]);
$user = $stmt->fetch();
if (!$user) respond_error('Utente non trovato', 404);

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
    'preferred_plan' => $user['preferred_plan'] ?? null,
    'has_subscription' => !empty($user['stripe_subscription_id']),
    'has_access' => user_has_access((int)$user['id']),
    'is_admin' => (int)$user['is_admin'] === 1,
]);
