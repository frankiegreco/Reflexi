<?php
declare(strict_types=1);
require __DIR__ . '/db.php';
require_once __DIR__ . '/lib_networth.php';

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
$excludedCategories = ['Investimenti (acquisto)', 'Investimenti (vendita)'];
$placeholders = implode(',', array_fill(0, count($excludedCategories), '?'));
$stmt = db()->prepare("
    SELECT
        COALESCE(SUM(CASE WHEN t.type = 'uscita' THEN t.amount ELSE 0 END), 0) AS total_expense,
        COALESCE(SUM(CASE WHEN t.type = 'entrata' THEN t.amount ELSE 0 END), 0) AS total_income,
        COUNT(DISTINCT DATE_FORMAT(t.date, '%Y-%m')) AS months_with_data
    FROM transactions t
    JOIN categories c ON c.id = t.category_id
    WHERE t.user_id = ? AND t.date >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH) AND t.type IN ('entrata','uscita')
    AND c.name NOT IN ($placeholders)
");
$stmt->execute(array_merge([$uid], $excludedCategories));
$row = $stmt->fetch();
$monthsWithData = max(1, (int)$row['months_with_data']);
$monthlyExpense = (float)$row['total_expense'] / $monthsWithData;
$monthlyIncome = (float)$row['total_income'] / $monthsWithData;
$monthlySavings = max(0, $monthlyIncome - $monthlyExpense);
$annualExpense = $monthlyExpense * 12;

$fireNumber = $withdrawalRate > 0 ? $annualExpense * (100 / $withdrawalRate) : 0;

$netWorth = compute_net_worth($uid);
$investableAssets = max(0, $netWorth['liquid_balance'] + $netWorth['investments_value']);

$progressPct = $fireNumber > 0 ? min(100, ($investableAssets / $fireNumber) * 100) : 0;

// Proiezione: quanti anni mancano al numero FIRE, dato il capitale attuale,
// il risparmio mensile attuale, e il rendimento atteso configurato.
$monthlyRate = ($expectedReturn / 100) / 12;
$years = null;
if ($investableAssets < $fireNumber && $fireNumber > 0) {
    $balance = $investableAssets;
    $months = 0;
    $maxMonths = 100 * 12; // limite di sicurezza: non proiettare oltre 100 anni
    while ($balance < $fireNumber && $months < $maxMonths) {
        $balance = $balance * (1 + $monthlyRate) + $monthlySavings;
        $months++;
        if ($monthlySavings <= 0 && $monthlyRate <= 0) break; // non arriverà mai, evita loop infinito
    }
    $years = $balance >= $fireNumber ? round($months / 12, 1) : null;
} else {
    $years = 0;
}

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
