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
    exit('Firma non valida');
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

/**
 * Annullamento programmato. Il portale lo esprime in due forme: cancel_at
 * (timestamp esplicito) oppure cancel_at_period_end = true, che vale la fine
 * del periodo corrente. Senza nessuna delle due: NULL.
 */
function subscription_cancel_at(object $subscription, ?int $periodEndTs): ?int
{
    if (!empty($subscription->cancel_at)) return (int)$subscription->cancel_at;
    if (!empty($subscription->cancel_at_period_end)) return $periodEndTs;
    return null;
}

function sync_subscription(string $customerId, object $subscription): void
{
    $user = find_user_by_customer($customerId);
    if (!$user) return;

    $status = map_stripe_status($subscription->status);
    $periodEndTs = subscription_period_end($subscription);
    $cancelAtTs = subscription_cancel_at($subscription, $periodEndTs);

    $set = ['subscription_status = :status', 'stripe_subscription_id = :sub_id'];
    $params = ['status' => $status, 'sub_id' => $subscription->id, 'id' => $user['id']];

    // subscription_cancel_at (migrazione 060) prima di period_end: con
    // cancel_at_period_end e scadenza mancante nell'evento si usa quella già
    // salvata, che la riga sotto non cambia.
    if (db_has_column('users', 'subscription_cancel_at')) {
        if ($cancelAtTs) {
            $set[] = 'subscription_cancel_at = :cancel_at';
            $params['cancel_at'] = date('Y-m-d H:i:s', $cancelAtTs);
        } elseif (!empty($subscription->cancel_at_period_end)) {
            $set[] = 'subscription_cancel_at = subscription_current_period_end';
        } else {
            $set[] = 'subscription_cancel_at = NULL';
        }
    }

    // Una scadenza mancante non cancella quella nota: si tiene la vecchia.
    if ($periodEndTs) {
        $set[] = 'subscription_current_period_end = :period_end';
        $params['period_end'] = date('Y-m-d H:i:s', $periodEndTs);
    } else {
        log_event('warning', 'stripe_period_end_missing',
            "Abbonamento {$subscription->id} ({$subscription->status}) senza current_period_end: tengo la scadenza salvata", (int)$user['id']);
    }

    // past_due_since: inizio del ritardo (conta per i giorni di grazia in
    // has_active_access), conservato finché il ritardo dura, azzerato dopo.
    // La colonna arriva con la migrazione 059.
    if (db_has_column('users', 'past_due_since')) {
        $set[] = 'past_due_since = ' . ($status === 'past_due' ? 'COALESCE(past_due_since, NOW())' : 'NULL');
    }

    db()->prepare('UPDATE users SET ' . implode(', ', $set) . ' WHERE id = :id')->execute($params);
}

/**
 * Abbonamento chiuso. Se la prova gratuita non è ancora finita (es. annullato
 * durante la prova) l'utente torna in prova senza abbonamento, con accesso
 * fino a trial_ends_at; altrimenti canceled. stripe_customer_id resta.
 */
function subscription_deleted(object $subscription): void
{
    $stmt = db()->prepare("SELECT id, stripe_subscription_id, trial_ends_at FROM users WHERE stripe_customer_id = :cid");
    $stmt->execute(['cid' => $subscription->customer]);
    $user = $stmt->fetch();
    if (!$user) return;

    // Un abbonamento diverso da quello registrato (es. un vecchio doppione)
    // non deve chiudere quello in corso.
    if ($user['stripe_subscription_id'] && $user['stripe_subscription_id'] !== $subscription->id) {
        log_event('warning', 'stripe_subscription_mismatch',
            "Chiuso {$subscription->id}, ma l'utente ha {$user['stripe_subscription_id']}: stato invariato", (int)$user['id']);
        return;
    }

    $set = [];
    if ($user['trial_ends_at'] && strtotime($user['trial_ends_at']) > time()) {
        $set[] = "subscription_status = 'trialing'";
        $set[] = 'stripe_subscription_id = NULL';
    } else {
        $set[] = "subscription_status = 'canceled'";
    }
    // Annullamento eseguito: non è più "programmato".
    if (db_has_column('users', 'subscription_cancel_at')) $set[] = 'subscription_cancel_at = NULL';
    if (db_has_column('users', 'past_due_since')) $set[] = 'past_due_since = NULL';

    db()->prepare('UPDATE users SET ' . implode(', ', $set) . ' WHERE id = :id')->execute(['id' => $user['id']]);
}

try {
    switch ($event->type) {
        case 'checkout.session.completed':
            $session = $event->data->object;
            if ($session->mode === 'subscription' && $session->subscription) {
                // Cliente non associato a nessun utente (es. UPDATE di
                // stripe_customer_id perso): lo si ricollega tramite il
                // reflexi_user_id messo nei metadata dal nostro checkout.
                $metaUid = (int)($session->metadata->reflexi_user_id ?? 0);
                if ($session->customer && $metaUid > 0 && !find_user_by_customer($session->customer)) {
                    $stmt = db()->prepare("SELECT stripe_customer_id FROM users WHERE id = :id");
                    $stmt->execute(['id' => $metaUid]);
                    $previous = $stmt->fetchColumn();
                    if ($previous !== false) {
                        db()->prepare("UPDATE users SET stripe_customer_id = :cid WHERE id = :id")
                            ->execute(['cid' => $session->customer, 'id' => $metaUid]);
                        log_event('warning', 'stripe_customer_relinked',
                            "Checkout {$session->id}: cliente {$session->customer} non associato, collegato all'utente dai metadata (cliente precedente: " . ($previous ?: 'nessuno') . ')', $metaUid);
                    }
                }
                sync_subscription($session->customer, \Stripe\Subscription::retrieve($session->subscription));
            }
            break;

        case 'customer.subscription.updated':
            $subscription = $event->data->object;
            sync_subscription($subscription->customer, $subscription);
            break;

        case 'customer.subscription.deleted':
            subscription_deleted($event->data->object);
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
                sync_subscription($invoice->customer, $subscription);
                $user = find_user_by_customer($invoice->customer);
                log_event('warning', 'stripe_payment_failed', "Pagamento fallito, abbonamento {$subscription->status}", $user ? (int)$user['id'] : null);
            }
            break;
    }
} catch (\Exception $e) {
    // 500: Stripe ritenta la consegna. Il dettaglio resta nel log.
    log_stripe_error("Webhook {$event->type} {$event->id}", $e);
    http_response_code(500);
    exit('Errore interno');
}

http_response_code(200);
echo json_encode(['received' => true]);
