<?php
declare(strict_types=1);
require __DIR__ . '/../db.php';

if (method() !== 'POST') respond_error('Metodo non supportato', 405);

$in = json_input();
$token = $in['token'] ?? '';
$password = $in['password'] ?? '';

if (strlen($password) < 8) respond_error('La password deve avere almeno 8 caratteri');

$stmt = db()->prepare("SELECT id FROM users WHERE reset_token = :token AND reset_token_expires >= NOW()");
$stmt->execute(['token' => $token]);
$user = $stmt->fetch();
if (!$user) respond_error('Link di reset non valido o scaduto', 400);

$hash = password_hash($password, PASSWORD_DEFAULT);
db()->prepare("UPDATE users SET password_hash = :hash, reset_token = NULL, reset_token_expires = NULL WHERE id = :id")
    ->execute(['hash' => $hash, 'id' => $user['id']]);

respond(['ok' => true]);
