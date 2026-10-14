<?php
declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

\Stripe\Stripe::setApiKey(getenv('STRIPE_SECRET_KEY') ?: '');

/** ID dei prezzi Stripe, configurati via variabili d'ambiente. */
function stripe_price_id(string $plan): ?string
{
    return $plan === 'annuale' ? (getenv('STRIPE_PRICE_ANNUAL') ?: null) : (getenv('STRIPE_PRICE_MONTHLY') ?: null);
}
