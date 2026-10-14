<?php
declare(strict_types=1);
require __DIR__ . '/../db.php';

$uid = require_auth();

if (method() !== 'POST') respond_error('Metodo non supportato', 405);

$in = json_input();
if (empty($in['password'])) respond_error('Inserisci la password per confermare');

$stmt = db()->prepare("SELECT password_hash, email, stripe_customer_id FROM users WHERE id = :id");
$stmt->execute(['id' => $uid]);
$user = $stmt->fetch();
if (!$user || !password_verify($in['password'], $user['password_hash'])) {
    log_event('warning', 'password_check_failed', "Password errata nell'eliminazione account", $uid);
    respond_error('Password non corretta', 401);
}

// Prima l'abbonamento: cancellato l'utente, nessuno potrebbe più gestirlo e
// Stripe continuerebbe ad addebitarlo. Se Stripe non risponde l'account resta
// com'è: meglio un'eliminazione da ripetere che addebiti a un utente sparito.
// Stesso lock del checkout, così un checkout in corso non crea un abbonamento
// subito dopo l'annullamento (rilasciato con la connessione a fine richiesta).
if ($user['stripe_customer_id']) {
    require __DIR__ . '/../lib_stripe.php';
    db()->query('SELECT GET_LOCK(' . db()->quote('reflexi_checkout_' . $uid) . ', 10)');
    try {
        $canceled = stripe_cancel_open_subscriptions($user['stripe_customer_id']);
    } catch (\Exception $e) {
        log_stripe_error('Eliminazione account: annullamento abbonamento non riuscito, account non eliminato', $e, $uid);
        respond_error('Non è stato possibile annullare il tuo abbonamento, quindi l\'account non è stato eliminato. Riprova tra qualche minuto; se il problema continua scrivi a info@reflexi.it.', 502);
    }
    if ($canceled) {
        log_event('info', 'stripe_subscription_canceled', 'Eliminazione account: annullati ' . implode(', ', $canceled), $uid);
    }
}

// Il cascade sulle foreign key elimina automaticamente tutti i dati collegati
// (conti, transazioni, mutui, investimenti, ecc.) — non serve cancellarli uno per uno.
db()->prepare("DELETE FROM users WHERE id = :id")->execute(['id' => $uid]);
// Dopo il DELETE: user_id resterebbe NULL comunque (ON DELETE SET NULL), l'email identifica chi era.
log_event('info', 'account_deleted', 'Account eliminato dall\'utente', null, $user['email']);

session_reopen();
session_destroy();
respond(['ok' => true]);
