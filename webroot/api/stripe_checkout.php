<?php
declare(strict_types=1);
require __DIR__ . '/db.php';
require __DIR__ . '/lib_mailer.php';
require __DIR__ . '/lib_stripe.php';

$uid = require_auth();

if (method() !== 'POST') respond_error('Metodo non supportato', 405);

$in = json_input();
$plan = ($in['plan'] ?? 'mensile') === 'annuale' ? 'annuale' : 'mensile';
$priceId = stripe_price_id($plan);
if (!$priceId) respond_error('Configurazione Stripe mancante lato server', 500);

$stmt = db()->prepare("SELECT email, stripe_customer_id FROM users WHERE id = :id");
$stmt->execute(['id' => $uid]);
$user = $stmt->fetch();

try {
    $customerId = $user['stripe_customer_id'];
    if (!$customerId) {
        $customer = \Stripe\Customer::create(['email' => $user['email'], 'metadata' => ['reflexi_user_id' => $uid]]);
        $customerId = $customer->id;
        db()->prepare("UPDATE users SET stripe_customer_id = :cid WHERE id = :id")->execute(['cid' => $customerId, 'id' => $uid]);
    }

    $appUrl = app_base_url();
    $params = [
        'customer' => $customerId,
        'mode' => 'subscription',
        'line_items' => [['price' => $priceId, 'quantity' => 1]],
        'metadata' => ['reflexi_user_id' => $uid],
    ];

    // STRIPE_CHECKOUT_UI=form: embedded form di Stripe dentro l'app (risponde
    // con client_secret). Default "hosted": redirect alla pagina Stripe (url).
    if (getenv('STRIPE_CHECKOUT_UI') === 'form') {
        $params += [
            'ui_mode' => 'form',
            'return_url' => $appUrl . '/app/?checkout=return&session_id={CHECKOUT_SESSION_ID}',
            'billing_address_collection' => 'auto',
            'phone_number_collection' => ['enabled' => false],
            'automatic_tax' => ['enabled' => false],
            'payment_method_collection' => 'always',
            'name_collection' => ['individual' => ['enabled' => true]],
        ];
        // Versione API solo per questa chiamata (quella della doc dell'embedded
        // form); la versione globale dell'account e il webhook restano invariati.
        $session = \Stripe\Checkout\Session::create($params, ['stripe_version' => '2026-09-30.endive']);
        respond(['client_secret' => $session->client_secret]);
    }

    $session = \Stripe\Checkout\Session::create($params + [
        'success_url' => $appUrl . '/app/?checkout=success',
        'cancel_url' => $appUrl . '/app/?checkout=cancel',
    ]);

    respond(['url' => $session->url]);
} catch (\Exception $e) {
    respond_error('Errore Stripe: ' . $e->getMessage(), 500);
}
