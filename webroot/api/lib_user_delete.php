<?php
declare(strict_types=1);

/**
 * Elimina definitivamente un utente: dall'utente stesso (auth/delete_account.php)
 * o dall'area admin (admin_users.php, action=delete).
 *
 * Prima l'abbonamento: cancellato l'utente, nessuno potrebbe più gestirlo e
 * Stripe continuerebbe ad addebitarlo. Se Stripe non risponde l'account resta
 * com'è e la funzione ritorna false (dettaglio nel log): meglio un'eliminazione
 * da ripetere che addebiti a un utente sparito. Stesso lock del checkout, così
 * un checkout in corso non crea un abbonamento subito dopo l'annullamento
 * (rilasciato con la connessione a fine richiesta).
 *
 * Il cascade sulle foreign key elimina tutti i dati collegati (conti,
 * transazioni, mutui, investimenti, sessioni, ...). I log di sicurezza restano
 * (user_id a NULL): l'email nell'evento dice chi era.
 */
function delete_user_account(int $uid, string $eventType, string $reason): bool
{
    $stmt = db()->prepare("SELECT email, stripe_customer_id FROM users WHERE id = :id");
    $stmt->execute(['id' => $uid]);
    $user = $stmt->fetch();
    if (!$user) return true;

    if ($user['stripe_customer_id']) {
        require_once __DIR__ . '/lib_stripe.php';
        db()->query('SELECT GET_LOCK(' . db()->quote('reflexi_checkout_' . $uid) . ', 10)');
        try {
            $canceled = stripe_cancel_open_subscriptions($user['stripe_customer_id']);
        } catch (\Exception $e) {
            log_stripe_error("{$reason}: annullamento abbonamento non riuscito, account non eliminato", $e, $uid);
            return false;
        }
        if ($canceled) {
            log_event('info', 'stripe_subscription_canceled', "{$reason}: annullati " . implode(', ', $canceled), $uid, $user['email']);
        }
    }

    db()->prepare("DELETE FROM users WHERE id = :id")->execute(['id' => $uid]);
    log_event('info', $eventType, $reason, null, $user['email']);
    return true;
}
