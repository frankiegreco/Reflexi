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
    log_event('warning', 'password_check_failed', "Password errata nell'eliminazione account", $uid);
    respond_error('Password non corretta', 401);
}

require __DIR__ . '/../lib_user_delete.php';
if (!delete_user_account($uid, 'account_deleted', "Account eliminato dall'utente")) {
    respond_error('Non è stato possibile annullare il tuo abbonamento, quindi l\'account non è stato eliminato. Riprova tra qualche minuto; se il problema continua scrivi a info@reflexi.it.', 502);
}

session_reopen();
session_destroy();
respond(['ok' => true]);
