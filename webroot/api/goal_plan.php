<?php
declare(strict_types=1);
require __DIR__ . '/db.php';
require_once __DIR__ . '/lib_goals.php';
require_once __DIR__ . '/lib_savings.php';
require_once __DIR__ . '/lib_projection.php';

$uid = require_auth();

if (method() !== 'GET') respond_error('Metodo non supportato', 405);

$goalId = (int)($_GET['goal_id'] ?? 0);
if (!$goalId) respond_error('goal_id mancante');

$expectedReturn = isset($_GET['expected_return']) ? (float)$_GET['expected_return'] : 5.0;

$stmt = db()->prepare("SELECT * FROM savings_goals WHERE id = :id AND user_id = :uid");
$stmt->execute(['id' => $goalId, 'uid' => $uid]);
$goal = $stmt->fetch();
if (!$goal) respond_error('Obiettivo non trovato', 404);

$targetAmount = (float)$goal['target_amount'];
$currentAmount = round(goal_progress($uid, $goal['start_date']), 2);
$remainingAmount = round($targetAmount - $currentAmount, 2);

// Obiettivo già raggiunto: niente proiezioni, solo il messaggio di congratulazioni.
if ($remainingAmount <= 0) {
    respond([
        'target_amount' => $targetAmount,
        'current_amount' => $currentAmount,
        'remaining_amount' => 0,
        'reached' => true,
    ]);
}

// Risparmio medio mensile reale sugli ultimi 6 mesi (o meno, se non c'è
// ancora abbastanza storico — compute_avg_monthly_savings si adatta da sola).
$savings = compute_avg_monthly_savings($uid, 6);
$avgMonthlySavings = round($savings['monthly_savings'], 2);
$monthsWithData = $savings['months_with_data'];

$response = [
    'target_amount' => $targetAmount,
    'current_amount' => $currentAmount,
    'remaining_amount' => $remainingAmount,
    'reached' => false,
    'avg_monthly_savings' => $avgMonthlySavings,
    'months_with_data' => $monthsWithData,
    'low_data' => $monthsWithData < 6,
    'unreachable' => $avgMonthlySavings <= 0,
];

if ($avgMonthlySavings <= 0) {
    // L'utente spende in media più di quanto guadagna: nessuna proiezione
    // temporale ha senso, si passa direttamente alle categorie di margine.
    $response['scenario_savings_only'] = null;
    $response['scenario_invested'] = null;
} else {
    $response['scenario_savings_only'] = [
        'months_to_goal' => round($remainingAmount / $avgMonthlySavings, 1),
    ];
    $response['scenario_invested'] = [
        'expected_return' => $expectedReturn,
        'months_to_goal' => compound_growth_months_to_target($currentAmount, $avgMonthlySavings, $expectedReturn, $targetAmount),
    ];
}

// Categorie dove la spesa recente (ultimi 2 mesi) supera di almeno il 15% la
// norma dei 6 mesi precedenti — confronto sempre contro lo storico dello
// stesso utente, mai contro medie di popolazione.
$placeholders = implode(',', array_fill(0, count(SPENDING_EXCLUDED_CATEGORIES), '?'));
$recentStart = date('Y-m-d', strtotime('-2 months'));
$baselineStart = date('Y-m-d', strtotime('-8 months'));
$stmt = db()->prepare("
    SELECT c.name AS category_name,
        COALESCE(SUM(CASE WHEN t.date >= ? THEN t.amount ELSE 0 END), 0) AS recent_total,
        COALESCE(SUM(CASE WHEN t.date >= ? AND t.date < ? THEN t.amount ELSE 0 END), 0) AS baseline_total
    FROM transactions t
    JOIN categories c ON c.id = t.category_id
    WHERE t.user_id = ? AND t.type = 'uscita' AND t.date >= ?
    AND c.name NOT IN ($placeholders)
    GROUP BY c.id, c.name
");
$stmt->execute(array_merge([$recentStart, $baselineStart, $recentStart, $uid, $baselineStart], SPENDING_EXCLUDED_CATEGORIES));

$opportunities = [];
foreach ($stmt->fetchAll() as $r) {
    $recentAvg = (float)$r['recent_total'] / 2;
    $baselineAvg = (float)$r['baseline_total'] / 6;
    if ($baselineAvg > 0 && $recentAvg > $baselineAvg * 1.15) {
        $opportunities[] = [
            'category_name' => $r['category_name'],
            'recent_avg' => round($recentAvg, 2),
            'baseline_avg' => round($baselineAvg, 2),
            'potential_monthly_saving' => round($recentAvg - $baselineAvg, 2),
        ];
    }
}
usort($opportunities, fn($a, $b) => $b['potential_monthly_saving'] <=> $a['potential_monthly_saving']);
$opportunities = array_slice($opportunities, 0, 3);
$response['category_opportunities'] = $opportunities;

// Terzo scenario: recuperare metà del margine trovato nelle categorie sopra
// e investirlo insieme al risparmio medio attuale.
$response['scenario_with_adjustment'] = null;
if ($opportunities) {
    $extraMonthly = round(array_sum(array_column($opportunities, 'potential_monthly_saving')) * 0.5, 2);
    $adjustedMonthly = $avgMonthlySavings + $extraMonthly;
    if ($adjustedMonthly > 0) {
        $response['scenario_with_adjustment'] = [
            'extra_monthly' => $extraMonthly,
            'months_to_goal' => compound_growth_months_to_target($currentAmount, $adjustedMonthly, $expectedReturn, $targetAmount),
        ];
    }
}

respond($response);
