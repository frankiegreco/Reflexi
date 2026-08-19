<?php
declare(strict_types=1);
require __DIR__ . '/../db.php';

$token = $_GET['token'] ?? '';

$stmt = db()->prepare("SELECT id FROM users WHERE verification_token = :token AND verification_token_expires >= NOW()");
$stmt->execute(['token' => $token]);
$user = $stmt->fetch();

if (!$user) {
    header('Location: /app/?verify=invalid');
    exit;
}

db()->prepare("UPDATE users SET email_verified_at = NOW(), verification_token = NULL, verification_token_expires = NULL WHERE id = :id")
    ->execute(['id' => $user['id']]);

header('Location: /app/?verify=success');
exit;
