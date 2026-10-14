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
if (!$priceId) {
    log_event('error', 'stripe_error', "Checkout: STRIPE_PRICE per il piano {$plan} non configurato", $uid);
    respond_error(STRIPE_USER_ERROR, 500);
}

// Prezzo configurato ma inutilizzabile (archiviato, non ricorrente, intervallo
// sbagliato, ID inesistente): niente checkout destinato a fallire su Stripe.
// Stessa verifica, con la stessa cache, di api/pricing.php; se Stripe non è
// raggiungibile available resta true e si prova comunque.
require __DIR__ . '/../_pricing.php';
$pricingKey = $plan === 'annuale' ? 'annual' : 'monthly';
if ((reflexi_pricing()['available'][$pricingKey] ?? true) === false) {
    log_event('error', 'stripe_error', "Checkout: prezzo del piano {$plan} ({$priceId}) non utilizzabile, vedi cli/check_stripe_env.php", $uid);
    respond(['error' => 'Questo piano non è disponibile al momento. Riprova più tardi o scrivi a info@reflexi.it.', 'code' => 'plan_unavailable'], 503);
}

// code: il frontend apre il portale invece di mostrare solo l'errore.
const CHECKOUT_SUBSCRIPTION_EXISTS = ['error' => 'Hai già un abbonamento attivo: ti portiamo al portale per gestirlo, senza nuovi addebiti.', 'code' => 'subscription_exists'];

/**
 * Crea cliente e sessione di checkout; ritorna [risposta, status HTTP].
 * Va chiamata col lock per utente preso: i dati sono riletti qui dal DB,
 * dopo il lock, così una richiesta concorrente appena conclusa (che ha
 * creato il cliente) è già visibile. Niente respond()/exit qui dentro:
 * impedirebbero al finally di rilasciare il lock.
 */
function create_checkout(int $uid, string $plan, string $priceId): array
{
    $stmt = db()->prepare("SELECT email, stripe_customer_id, stripe_subscription_id, subscription_status, trial_ends_at FROM users WHERE id = :id");
    $stmt->execute(['id' => $uid]);
    $user = $stmt->fetch();

    // Un abbonamento già in corso (anche in prova) si gestisce dal portale: un
    // secondo checkout creerebbe un secondo abbonamento con doppio addebito.
    if ($user['stripe_subscription_id'] && in_array($user['subscription_status'], ['trialing', 'active', 'past_due'], true)) {
        return [CHECKOUT_SUBSCRIPTION_EXISTS, 409];
    }

    try {
        $customerId = $user['stripe_customer_id'];
        if (!$customerId) {
            $customer = \Stripe\Customer::create(['email' => $user['email'], 'metadata' => ['reflexi_user_id' => $uid]]);
            $customerId = $customer->id;
            db()->prepare("UPDATE users SET stripe_customer_id = :cid WHERE id = :id")->execute(['cid' => $customerId, 'id' => $uid]);
        } else {
            // Controllo anche su Stripe, non solo sul DB: se il webhook del primo
            // checkout non è ancora arrivato, il DB non sa dell'abbonamento e un
            // secondo click ne creerebbe un altro. 'unpaid' qui è past_due.
            $existing = \Stripe\Subscription::all(['customer' => $customerId, 'status' => 'all', 'limit' => 100]);
            foreach ($existing->data as $sub) {
                if (in_array($sub->status, ['trialing', 'active', 'past_due', 'unpaid'], true)) {
                    log_event('info', 'stripe_checkout_blocked', "Checkout bloccato: il cliente ha già {$sub->id} ({$sub->status})", $uid);
                    return [CHECKOUT_SUBSCRIPTION_EXISTS, 409];
                }
            }
        }

        $appUrl = app_base_url();
        $params = [
            'customer' => $customerId,
            'mode' => 'subscription',
            'line_items' => [['price' => $priceId, 'quantity' => 1]],
            'metadata' => ['reflexi_user_id' => $uid],
        ];

        // Chi si abbona durante la prova non perde i giorni rimasti: l'abbonamento
        // parte in trial fino a trial_ends_at (oggi si paga 0€). Stripe vuole un
        // trial_end ad almeno 48 ore nel futuro: sotto quella soglia si paga subito.
        $trialEndTs = $user['trial_ends_at'] ? strtotime($user['trial_ends_at']) : false;
        if ($trialEndTs && $trialEndTs > time() + 48 * 3600) {
            $params['subscription_data'] = ['trial_end' => $trialEndTs];
        }

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
            return [['client_secret' => $session->client_secret], 200];
        }

        $session = \Stripe\Checkout\Session::create($params + [
            'success_url' => $appUrl . '/app/?checkout=success',
            'cancel_url' => $appUrl . '/app/?checkout=cancel',
        ]);

        return [['url' => $session->url], 200];
    } catch (\Exception $e) {
        log_stripe_error("Checkout piano {$plan}", $e, $uid);
        return [['error' => STRIPE_USER_ERROR], 500];
    }
}

// Una richiesta alla volta per utente: due click ravvicinati (o due schede)
// creerebbero due clienti Stripe o due sessioni. Il lock è della connessione
// MariaDB; il finally lo rilascia anche in caso di eccezione.
$lockName = 'reflexi_checkout_' . $uid;
$locked = (int)db()->query('SELECT GET_LOCK(' . db()->quote($lockName) . ', 10)')->fetchColumn() === 1;
if (!$locked) {
    log_event('warning', 'stripe_checkout_lock_timeout', 'Lock del checkout non ottenuto entro 10 secondi', $uid);
    respond(['error' => 'Una richiesta di pagamento è già in corso: attendi qualche secondo e riprova.', 'code' => 'checkout_in_progress'], 409);
}

try {
    [$response, $status] = create_checkout($uid, $plan, $priceId);
} finally {
    try {
        db()->query('SELECT RELEASE_LOCK(' . db()->quote($lockName) . ')');
    } catch (\Throwable $e) {
        // La connessione chiusa a fine richiesta lo rilascia comunque.
    }
}

if ($status >= 500) respond_error($response['error'], $status);
respond($response, $status);
