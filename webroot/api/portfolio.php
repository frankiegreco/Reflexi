<?php
declare(strict_types=1);
require __DIR__ . '/db.php';
require_once __DIR__ . '/price_fetch.php';

if (method() !== 'GET') respond_error('Metodo non supportato', 405);

$uid = require_auth();
$forceRefresh = ($_GET['refresh'] ?? '0') === '1';

$stmt = db()->prepare("SELECT * FROM instruments WHERE user_id = :uid ORDER BY ticker");
$stmt->execute(['uid' => $uid]);
$instruments = $stmt->fetchAll();

$positions = [];
$totalsByCurrency = [];
$realizedTotal = 0.0;
$taxPaidTotal = 0.0;

foreach ($instruments as $instr) {
    $stmt = db()->prepare("SELECT * FROM investment_transactions WHERE instrument_id = :id AND user_id = :uid ORDER BY date, id");
    $stmt->execute(['id' => $instr['id'], 'uid' => $uid]);
    $txs = $stmt->fetchAll();

    $qty = 0.0;
    $cost = 0.0;
    $realized = 0.0;
    $taxPaid = 0.0;

    foreach ($txs as $t) {
        if ($t['type'] === 'acquisto') {
            $cost += (float)$t['quantity'] * (float)$t['price_per_unit'] + (float)$t['fees'];
            $qty += (float)$t['quantity'];
        } else {
            $avgCost = $qty > 0 ? $cost / $qty : 0;
            $costRemoved = $avgCost * (float)$t['quantity'];
            $proceeds = (float)$t['quantity'] * (float)$t['price_per_unit'] - (float)$t['fees'];
            $realized += $proceeds - $costRemoved;
            $taxPaid += (float)$t['tax_amount'];
            $cost -= $costRemoved;
            $qty -= (float)$t['quantity'];
        }
    }
    $qty = round($qty, 6);
    $cost = max(0, $cost);
    $realizedTotal += $realized;
    $taxPaidTotal += $taxPaid;

    $hasHoldings = $qty > 0.000001;
    $priceInfo = $hasHoldings ? fetch_live_price($instr['ticker'], $forceRefresh ? 0 : 900) : null;

    $currentValue = $priceInfo ? $qty * $priceInfo['price'] : null;
    $unrealizedGain = ($currentValue !== null) ? $currentValue - $cost : null;
    $currency = $priceInfo['currency'] ?? $instr['currency'];

    if ($hasHoldings) {
        if (!isset($totalsByCurrency[$currency])) {
            $totalsByCurrency[$currency] = ['invested' => 0, 'current_value' => 0, 'unrealized_gain' => 0, 'has_missing_price' => false];
        }
        $totalsByCurrency[$currency]['invested'] += $cost;
        if ($currentValue !== null) {
            $totalsByCurrency[$currency]['current_value'] += $currentValue;
            $totalsByCurrency[$currency]['unrealized_gain'] += $unrealizedGain;
        } else {
            $totalsByCurrency[$currency]['has_missing_price'] = true;
        }
    }

    $positions[] = [
        'instrument' => $instr,
        'quantity' => $qty,
        'avg_cost' => $qty > 0 ? round($cost / $qty, 4) : 0,
        'cost_basis' => round($cost, 2),
        'realized_gain' => round($realized, 2),
        'live_price' => $priceInfo['price'] ?? null,
        'price_currency' => $currency,
        'price_fetched_at' => $priceInfo['fetched_at'] ?? null,
        'price_stale' => $priceInfo['stale'] ?? null,
        'current_value' => $currentValue !== null ? round($currentValue, 2) : null,
        'unrealized_gain' => $unrealizedGain !== null ? round($unrealizedGain, 2) : null,
        'unrealized_gain_pct' => ($currentValue !== null && $cost > 0) ? round($unrealizedGain / $cost * 100, 2) : null,
    ];
}

respond([
    'positions' => $positions,
    'totals_by_currency' => $totalsByCurrency,
    'realized_gain_total' => round($realizedTotal, 2),
    'tax_paid_total' => round($taxPaidTotal, 2),
    'realized_gain_net' => round($realizedTotal - $taxPaidTotal, 2),
]);
