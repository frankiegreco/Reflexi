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
