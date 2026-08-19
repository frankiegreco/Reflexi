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
    $session = \Stripe\Checkout\Session::create([
        'customer' => $customerId,
        'mode' => 'subscription',
        'line_items' => [['price' => $priceId, 'quantity' => 1]],
        'success_url' => $appUrl . '/app/?checkout=success',
        'cancel_url' => $appUrl . '/app/?checkout=cancel',
        'metadata' => ['reflexi_user_id' => $uid],
    ]);

    respond(['url' => $session->url]);
} catch (\Exception $e) {
    respond_error('Errore Stripe: ' . $e->getMessage(), 500);
}
