<?php
declare(strict_types=1);
require __DIR__ . '/db.php';
require __DIR__ . '/lib_mailer.php';
require __DIR__ . '/lib_stripe.php';

$uid = require_auth();

if (method() !== 'POST') respond_error('Metodo non supportato', 405);

$stmt = db()->prepare("SELECT stripe_customer_id FROM users WHERE id = :id");
$stmt->execute(['id' => $uid]);
$customerId = $stmt->fetchColumn();

if (!$customerId) respond_error('Nessun abbonamento associato a questo account', 400);

try {
    $session = \Stripe\BillingPortal\Session::create([
        'customer' => $customerId,
        'return_url' => app_base_url() . '/app/',
    ]);
    respond(['url' => $session->url]);
} catch (\Exception $e) {
    respond_error('Errore Stripe: ' . $e->getMessage(), 500);
}
