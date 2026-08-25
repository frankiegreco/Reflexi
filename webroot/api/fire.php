<?php
declare(strict_types=1);
require __DIR__ . '/db.php';
require_once __DIR__ . '/lib_networth.php';
require_once __DIR__ . '/lib_savings.php';
require_once __DIR__ . '/lib_projection.php';

$uid = require_auth();

if (method() === 'PUT') {
    $in = json_input();
    $fields = [];
    $params = ['id' => $uid];
    if (isset($in['fire_withdrawal_rate'])) { $fields[] = 'fire_withdrawal_rate = :rate'; $params['rate'] = $in['fire_withdrawal_rate']; }
    if (isset($in['fire_expected_return'])) { $fields[] = 'fire_expected_return = :ret'; $params['ret'] = $in['fire_expected_return']; }
    if ($fields) {
        db()->prepare("UPDATE users SET " . implode(', ', $fields) . " WHERE id = :id")->execute($params);
    }
}

if (method() !== 'GET' && method() !== 'PUT') respond_error('Metodo non supportato', 405);

$stmt = db()->prepare("SELECT fire_withdrawal_rate, fire_expected_return FROM users WHERE id = :id");
$stmt->execute(['id' => $uid]);
$user = $stmt->fetch();
$withdrawalRate = (float)$user['fire_withdrawal_rate'];
$expectedReturn = (float)$user['fire_expected_return'];

// Spesa media mensile reale, sugli ultimi 12 mesi di storico disponibili
// (esclusi investimenti e trasferimenti, che non sono vero "consumo").
$savings = compute_avg_monthly_savings($uid, 12);
$monthsWithData = $savings['months_with_data'];
$monthlyExpense = $savings['monthly_expense'];
$monthlyIncome = $savings['monthly_income'];
$monthlySavings = max(0, $savings['monthly_savings']);
$annualExpense = $monthlyExpense * 12;

$fireNumber = $withdrawalRate > 0 ? $annualExpense * (100 / $withdrawalRate) : 0;

$netWorth = compute_net_worth($uid);
$investableAssets = max(0, $netWorth['liquid_balance'] + $netWorth['investments_value']);

$progressPct = $fireNumber > 0 ? min(100, ($investableAssets / $fireNumber) * 100) : 0;

// Proiezione: quanti anni mancano al numero FIRE, dato il capitale attuale,
// il risparmio mensile attuale, e il rendimento atteso configurato.
$monthsToFire = compound_growth_months_to_target($investableAssets, $monthlySavings, $expectedReturn, $fireNumber);
$years = $monthsToFire === null ? null : round($monthsToFire / 12, 1);

respond([
    'withdrawal_rate' => $withdrawalRate,
    'expected_return' => $expectedReturn,
    'monthly_expense' => round($monthlyExpense, 2),
    'monthly_income' => round($monthlyIncome, 2),
    'monthly_savings' => round($monthlySavings, 2),
    'annual_expense' => round($annualExpense, 2),
    'fire_number' => round($fireNumber, 2),
    'investable_assets' => round($investableAssets, 2),
    'progress_pct' => round($progressPct, 1),
    'years_to_fire' => $years,
    'months_with_data' => $monthsWithData,
]);
