<?php
declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

\Stripe\Stripe::setApiKey(getenv('STRIPE_SECRET_KEY') ?: '');

/**
 * True se l'utente ha accesso valido in questo momento: trial non scaduto,
 * oppure abbonamento attivo (o in past_due, a cui diamo comunque accesso per
 * qualche giorno di margine prima di bloccare per un pagamento in ritardo).
 */
function has_active_access(array $user): bool
{
    if ($user['subscription_status'] === 'active') return true;
    if ($user['subscription_status'] === 'past_due') return true;
    if ($user['subscription_status'] === 'trialing') {
        return $user['trial_ends_at'] && strtotime($user['trial_ends_at']) > time();
    }
    return false;
}

/** ID dei prezzi Stripe, configurati via variabili d'ambiente. */
function stripe_price_id(string $plan): ?string
{
    return $plan === 'annuale' ? (getenv('STRIPE_PRICE_ANNUAL') ?: null) : (getenv('STRIPE_PRICE_MONTHLY') ?: null);
}
