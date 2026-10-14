<?php
declare(strict_types=1);
require __DIR__ . '/db.php';
require __DIR__ . '/lib_stripe.php';

// Endpoint pubblico chiamato dai server di Stripe, non da un browser con
// sessione: l'autenticazione qui e' la firma crittografica della richiesta,
// verificata con il webhook secret — non usare require_auth() qui.

$payload = @file_get_contents('php://input');
$sigHeader = $_SERVER['HTTP_STRIPE_SIGNATURE'] ?? '';
$webhookSecret = getenv('STRIPE_WEBHOOK_SECRET') ?: '';

try {
    $event = \Stripe\Webhook::constructEvent($payload, $sigHeader, $webhookSecret);
} catch (\Exception $e) {
    log_event('warning', 'stripe_webhook_invalid', 'Firma webhook non valida: ' . $e->getMessage());
    http_response_code(400);
    exit('Firma non valida: ' . $e->getMessage());
}

function find_user_by_customer(string $customerId): ?array
{
    $stmt = db()->prepare("SELECT id FROM users WHERE stripe_customer_id = :cid");
    $stmt->execute(['cid' => $customerId]);
    $row = $stmt->fetch();
    return $row ?: null;
}

function map_stripe_status(string $stripeStatus): string
{
    return match ($stripeStatus) {
        'active' => 'active',
        'trialing' => 'trialing',
        'past_due', 'unpaid' => 'past_due',
        'canceled', 'incomplete_expired' => 'canceled',
        default => 'none',
    };
}

/**
 * Fine del periodo corrente. Dalle API 2025-03-31.basil current_period_end sta
 * sugli item dell'abbonamento; prima era sull'abbonamento stesso.
 */
function subscription_period_end(object $subscription): ?int
{
    return $subscription->items->data[0]->current_period_end ?? $subscription->current_period_end ?? null;
}

function sync_subscription(string $customerId, string $subscriptionId, string $stripeStatus, ?int $periodEndTs): void
{
    $user = find_user_by_customer($customerId);
    if (!$user) return;

    $status = map_stripe_status($stripeStatus);
    $periodEnd = $periodEndTs ? date('Y-m-d H:i:s', $periodEndTs) : null;

    // past_due_since: inizio del ritardo (conta per i giorni di grazia in
    // has_active_access), conservato finché il ritardo dura, azzerato dopo.
    // La colonna arriva con la migrazione 059.
    $pastDue = db_has_column('users', 'past_due_since')
        ? ', past_due_since = ' . ($status === 'past_due' ? 'COALESCE(past_due_since, NOW())' : 'NULL')
        : '';

    db()->prepare("UPDATE users SET subscription_status = :status, stripe_subscription_id = :sub_id, subscription_current_period_end = :period_end{$pastDue} WHERE id = :id")
        ->execute(['status' => $status, 'sub_id' => $subscriptionId, 'period_end' => $periodEnd, 'id' => $user['id']]);
}

switch ($event->type) {
    case 'checkout.session.completed':
        $session = $event->data->object;
        if ($session->mode === 'subscription' && $session->subscription) {
            $subscription = \Stripe\Subscription::retrieve($session->subscription);
            sync_subscription($session->customer, $subscription->id, $subscription->status, subscription_period_end($subscription));
        }
        break;

    case 'customer.subscription.updated':
        $subscription = $event->data->object;
        sync_subscription($subscription->customer, $subscription->id, $subscription->status, subscription_period_end($subscription));
        break;

    case 'customer.subscription.deleted':
        $subscription = $event->data->object;
        $user = find_user_by_customer($subscription->customer);
        if ($user) {
            $pastDue = db_has_column('users', 'past_due_since') ? ', past_due_since = NULL' : '';
            db()->prepare("UPDATE users SET subscription_status = 'canceled'{$pastDue} WHERE id = :id")->execute(['id' => $user['id']]);
        }
        break;

    case 'invoice.payment_failed':
        // Rilegge l'abbonamento invece di dedurre lo stato dalla fattura: se
        // Stripe lo ha già messo in past_due, parte il conteggio dei giorni di
        // grazia. Il campo subscription della fattura dalle API basil è sotto
        // parent.subscription_details.
        $invoice = $event->data->object;
        $subscriptionId = $invoice->parent->subscription_details->subscription ?? $invoice->subscription ?? null;
        if ($subscriptionId && $invoice->customer) {
            $subscription = \Stripe\Subscription::retrieve($subscriptionId);
            sync_subscription($invoice->customer, $subscription->id, $subscription->status, subscription_period_end($subscription));
            $user = find_user_by_customer($invoice->customer);
            log_event('warning', 'stripe_payment_failed', "Pagamento fallito, abbonamento {$subscription->status}", $user ? (int)$user['id'] : null);
        }
        break;
}

http_response_code(200);
echo json_encode(['received' => true]);
