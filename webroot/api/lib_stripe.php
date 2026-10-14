<?php
declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

\Stripe\Stripe::setApiKey(getenv('STRIPE_SECRET_KEY') ?: '');

/** ID dei prezzi Stripe, configurati via variabili d'ambiente. */
function stripe_price_id(string $plan): ?string
{
    return $plan === 'annuale' ? (getenv('STRIPE_PRICE_ANNUAL') ?: null) : (getenv('STRIPE_PRICE_MONTHLY') ?: null);
}

/** Testo mostrato all'utente per qualunque errore Stripe: il dettaglio va solo nel log. */
const STRIPE_USER_ERROR = 'Non è stato possibile completare l\'operazione di pagamento. Riprova tra qualche minuto; se il problema continua scrivi a info@reflexi.it.';

/**
 * Registra un errore Stripe completo nel log del server (security_events),
 * con le eventuali chiavi oscurate: i messaggi di Stripe possono citarne una
 * parte ("Invalid API Key provided: sk_live_...").
 */
function log_stripe_error(string $context, \Throwable $e, ?int $userId = null): void
{
    $detail = get_class($e) . ': ' . $e->getMessage();
    if ($e instanceof \Stripe\Exception\ApiErrorException) {
        $detail .= ' [http ' . ($e->getHttpStatus() ?? '-') . ', code ' . ($e->getStripeCode() ?? '-')
            . ', request ' . ($e->getRequestId() ?? '-') . ']';
    }
    $detail = preg_replace('/\b(sk|rk|pk)_(live|test)_[A-Za-z0-9*]+|\bwhsec_[A-Za-z0-9*]+/', '[chiave oscurata]', $detail);
    log_event('error', 'stripe_error', "{$context}: {$detail}", $userId);
}

/**
 * True se Stripe ha risposto "No such customer": lo stripe_customer_id salvato
 * non esiste più (cliente cancellato dalla dashboard, oppure creato con l'altra
 * modalità test/live prima di cambiare chiavi).
 */
function stripe_is_missing_customer(\Throwable $e): bool
{
    return $e instanceof \Stripe\Exception\InvalidRequestException
        && $e->getStripeCode() === 'resource_missing'
        && $e->getStripeParam() === 'customer';
}

/**
 * Scollega dall'utente il cliente Stripe inesistente e l'abbonamento che vi era
 * legato. Se la prova gratuita non è finita l'utente torna in trial, altrimenti
 * l'abbonamento risulta cancellato: senza cliente non c'è nulla che lo paghi.
 */
function stripe_reset_missing_customer(int $uid, string $context, \Throwable $e): void
{
    log_stripe_error("{$context}: cliente Stripe inesistente, scollegato e ricreato", $e, $uid);
    $set = [
        'stripe_customer_id = NULL',
        'stripe_subscription_id = NULL',
        'subscription_current_period_end = NULL',
        "subscription_status = IF(trial_ends_at > NOW(), 'trialing', 'canceled')",
    ];
    if (db_has_column('users', 'subscription_cancel_at')) $set[] = 'subscription_cancel_at = NULL';
    db()->prepare('UPDATE users SET ' . implode(', ', $set) . ' WHERE id = :id')->execute(['id' => $uid]);
}

/** Crea il cliente Stripe dell'utente e lo salva in users.stripe_customer_id. */
function stripe_create_customer(int $uid, string $email): string
{
    $customer = \Stripe\Customer::create(['email' => $email, 'metadata' => ['reflexi_user_id' => $uid]]);
    db()->prepare("UPDATE users SET stripe_customer_id = :cid WHERE id = :id")->execute(['cid' => $customer->id, 'id' => $uid]);
    return $customer->id;
}
