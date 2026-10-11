<?php
declare(strict_types=1);
require __DIR__ . '/../db.php';

$uid = require_auth();

if (method() !== 'POST') respond_error('Metodo non supportato', 405);

$in = json_input();
if (empty($in['password'])) respond_error('Inserisci la password per confermare');

$stmt = db()->prepare("SELECT password_hash, email FROM users WHERE id = :id");
$stmt->execute(['id' => $uid]);
$user = $stmt->fetch();
if (!$user || !password_verify($in['password'], $user['password_hash'])) {
    log_event('warning', 'password_check_failed', "Password errata nell'eliminazione account", $uid);
    respond_error('Password non corretta', 401);
}

// Il cascade sulle foreign key elimina automaticamente tutti i dati collegati
// (conti, transazioni, mutui, investimenti, ecc.) — non serve cancellarli uno per uno.
db()->prepare("DELETE FROM users WHERE id = :id")->execute(['id' => $uid]);
// Dopo il DELETE: user_id resterebbe NULL comunque (ON DELETE SET NULL), l'email identifica chi era.
log_event('info', 'account_deleted', 'Account eliminato dall\'utente', null, $user['email']);

session_reopen();
session_destroy();
respond(['ok' => true]);
