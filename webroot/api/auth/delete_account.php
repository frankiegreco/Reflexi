<?php
declare(strict_types=1);
require __DIR__ . '/../db.php';

$uid = require_auth();

if (method() !== 'POST') respond_error('Metodo non supportato', 405);

$in = json_input();
if (empty($in['password'])) respond_error('Inserisci la password per confermare');

$stmt = db()->prepare("SELECT password_hash FROM users WHERE id = :id");
$stmt->execute(['id' => $uid]);
$user = $stmt->fetch();
if (!$user || !password_verify($in['password'], $user['password_hash'])) {
    respond_error('Password non corretta', 401);
}

// Il cascade sulle foreign key elimina automaticamente tutti i dati collegati
// (conti, transazioni, mutui, investimenti, ecc.) — non serve cancellarli uno per uno.
db()->prepare("DELETE FROM users WHERE id = :id")->execute(['id' => $uid]);

session_destroy();
respond(['ok' => true]);
