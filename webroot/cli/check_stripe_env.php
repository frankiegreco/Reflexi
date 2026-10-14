<?php
declare(strict_types=1);

// Controllo della configurazione Stripe del container web, da riga di comando:
//   docker exec finanze_web php /var/www/html/cli/check_stripe_env.php
// Non stampa mai chiavi o secret: solo tipo (test/live) e presenza.
// Exit code 0 se tutto è a posto, 1 se c'è almeno un errore.

if (php_sapi_name() !== 'cli') {
    http_response_code(404);
    exit;
}

require __DIR__ . '/../_pricing.php';

$errors = 0;
$ok = fn(string $msg) => print("  OK      $msg\n");
$fail = function (string $msg) use (&$errors) {
    $errors++;
    print("  ERRORE  $msg\n");
};

echo "Configurazione Stripe\n";

// Chiave API: sk_ (segreta) o rk_ (con restrizioni), test o live.
$key = getenv('STRIPE_SECRET_KEY') ?: '';
$keyMode = null;
if ($key === '') {
    $fail('STRIPE_SECRET_KEY mancante');
} elseif (preg_match('/^(sk|rk)_(test|live)_/', $key, $m)) {
    $keyMode = $m[2];
    $ok('STRIPE_SECRET_KEY: ' . ($m[1] === 'rk' ? 'restricted key' : 'secret key') . " $keyMode");
} else {
    $fail('STRIPE_SECRET_KEY presente ma con formato non riconosciuto (attesi sk_/rk_ test o live)');
}

$appUrl = rtrim(getenv('APP_URL') ?: '', '/');
if ($keyMode === 'test' && $appUrl === 'https://reflexi.it') {
    echo "\n  !!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!\n";
    echo "  !!  CHIAVE DI TEST con APP_URL di produzione (https://reflexi.it)\n";
    echo "  !!  I pagamenti NON sono reali.\n";
    echo "  !!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!\n\n";
    $errors++;
}

$whsec = getenv('STRIPE_WEBHOOK_SECRET') ?: '';
if ($whsec === '') {
    $fail('STRIPE_WEBHOOK_SECRET mancante');
} elseif (!str_starts_with($whsec, 'whsec_')) {
    $fail('STRIPE_WEBHOOK_SECRET presente ma non inizia con whsec_');
} else {
    $ok('STRIPE_WEBHOOK_SECRET presente');
}

// Prezzi: esistono, attivi, ricorrenti con l'intervallo giusto, prodotto attivo
// (un prodotto archiviato blocca il checkout anche se il prezzo è attivo).
$prices = ['STRIPE_PRICE_MONTHLY' => PRICING_PLAN_INTERVALS['monthly'], 'STRIPE_PRICE_ANNUAL' => PRICING_PLAN_INTERVALS['annual']];
foreach ($prices as $var => $interval) {
    $id = getenv($var) ?: '';
    if ($id === '') { $fail("$var mancante"); continue; }
    if ($keyMode === null) { $fail("$var = $id non verificabile (chiave API non valida)"); continue; }
    try {
        $price = \Stripe\Price::retrieve(['id' => $id, 'expand' => ['product']], ['api_key' => $key]);
    } catch (\Throwable $e) {
        $fail("$var = $id: " . $e->getMessage());
        continue;
    }
    // Stessi controlli di _pricing.php (che nasconde il piano), più la modalità.
    $problems = pricing_price_problems($price, $interval);
    if ($price->livemode !== ($keyMode === 'live')) $problems[] = 'modalità diversa dalla chiave';
    $amount = $price->unit_amount !== null ? number_format($price->unit_amount / 100, 2, ',', '.') . ' ' . strtoupper($price->currency) : 'importo non fisso';
    $desc = "$var = $id ($amount/" . ($price->recurring->interval ?? "una tantum") . ", tax_behavior {$price->tax_behavior})";
    $problems ? $fail("$desc: " . implode(', ', $problems)) : $ok($desc);
}

$ui = getenv('STRIPE_CHECKOUT_UI') ?: '';
if ($ui === '' || $ui === 'hosted') {
    $ok('STRIPE_CHECKOUT_UI: hosted' . ($ui === '' ? ' (default, variabile non impostata)' : ''));
} elseif ($ui === 'form') {
    $ok('STRIPE_CHECKOUT_UI: form (serve VITE_STRIPE_PUBLISHABLE_KEY della stessa modalità nel build del frontend)');
} else {
    $fail("STRIPE_CHECKOUT_UI = '$ui' non valido (hosted|form)");
}

echo $errors ? "\n$errors problema/i trovati.\n" : "\nTutto a posto.\n";
exit($errors ? 1 : 0);
