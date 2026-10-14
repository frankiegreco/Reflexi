<?php
declare(strict_types=1);
require __DIR__ . '/db.php';
require __DIR__ . '/lib_mailer.php';
require __DIR__ . '/lib_stripe.php';

$uid = require_auth();

if (method() !== 'POST') respond_error('Metodo non supportato', 405);

$stmt = db()->prepare("SELECT email, stripe_customer_id FROM users WHERE id = :id");
$stmt->execute(['id' => $uid]);
$user = $stmt->fetch();
$customerId = $user['stripe_customer_id'];

if (!$customerId) respond_error('Nessun abbonamento associato a questo account', 400);

function create_portal_session(string $customerId): string
{
    return \Stripe\BillingPortal\Session::create([
        'customer' => $customerId,
        'return_url' => app_base_url() . '/app/',
    ])->url;
}

try {
    respond(['url' => create_portal_session($customerId)]);
} catch (\Exception $e) {
    if (!stripe_is_missing_customer($e)) {
        log_stripe_error('Portale', $e, $uid);
        respond_error(STRIPE_USER_ERROR, 500);
    }
    stripe_reset_missing_customer($uid, 'Portale', $e);
}

// Cliente inesistente su Stripe: scollegato sopra, se ne crea uno nuovo e si
// riprova una sola volta.
try {
    respond(['url' => create_portal_session(stripe_create_customer($uid, $user['email']))]);
} catch (\Exception $e) {
    log_stripe_error('Portale (secondo tentativo, cliente ricreato)', $e, $uid);
    respond_error(STRIPE_USER_ERROR, 500);
}
