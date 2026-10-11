<?php
declare(strict_types=1);
require __DIR__ . '/../db.php';

$uid = require_auth();

if (method() !== 'POST') respond_error('Metodo non supportato', 405);

rate_limit_check('change_password', 10, 900);
rate_limit_record('change_password');

$in = json_input();
$currentPassword = $in['current_password'] ?? '';
$newPassword = $in['new_password'] ?? '';

if (strlen($newPassword) < 8) respond_error('La nuova password deve avere almeno 8 caratteri');

$stmt = db()->prepare("SELECT password_hash FROM users WHERE id = :id");
$stmt->execute(['id' => $uid]);
$user = $stmt->fetch();

if (!$user || !password_verify($currentPassword, $user['password_hash'])) {
    log_event('warning', 'password_check_failed', 'Password attuale errata nel cambio password', $uid);
    respond_error('La password attuale non è corretta', 401);
}

$hash = password_hash($newPassword, PASSWORD_DEFAULT);
db()->prepare("UPDATE users SET password_hash = :hash WHERE id = :id")
    ->execute(['hash' => $hash, 'id' => $uid]);
log_event('info', 'password_changed', 'Password cambiata dal profilo', $uid);

respond(['ok' => true]);
