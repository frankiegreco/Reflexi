<?php
declare(strict_types=1);
require __DIR__ . '/db.php';
require __DIR__ . '/loan_math.php';

if (method() !== 'GET') respond_error('Metodo non supportato', 405);

$uid = require_auth();
$historyMonths = max(3, min((int)($_GET['history_months'] ?? 6), 24));
$forecastMonths = max(1, min((int)($_GET['forecast_months'] ?? 6), 24));

const EXCLUDED_CATEGORIES = ['Investimenti (acquisto)', 'Investimenti (vendita)'];

function normalize_monthly_amount(string $frequency, int $intervalCount, float $amount): float
{
    $intervalCount = max(1, $intervalCount);
    switch ($frequency) {
        case 'giornaliera': return $amount * 30.44 / $intervalCount;
        case 'settimanale': return $amount * (52 / 12) / $intervalCount;
        case 'annuale': return $amount / (12 * $intervalCount);
        case 'mensile':
        default: return $amount / $intervalCount;
    }
}

function rule_active_in_month(string $startDate, ?string $endDate, string $monthStart, string $monthEnd): bool
{
    if ($startDate > $monthEnd) return false;
    if ($endDate !== null && $endDate < $monthStart) return false;
    return true;
}

/**
 * Rileva spese ricorrenti non ancora dichiarate come tali (solo per l'utente
 * indicato): raggruppa le uscite non collegate a una ricorrenza per categoria +
 * importo simile (tolleranza 10%), poi verifica se gli intervalli tra le
 * occorrenze sono regolari (coefficiente di variazione basso).
 */
function detect_hidden_recurring(PDO $pdo, int $uid): array
{
    $stmt = $pdo->prepare("
        SELECT t.id, t.date, t.amount, t.category_id, c.name AS category_name, c.color
        FROM transactions t JOIN categories c ON c.id = t.category_id
        WHERE t.user_id = :uid AND t.type = 'uscita' AND t.recurring_id IS NULL
        ORDER BY t.category_id, t.date
    ");
    $stmt->execute(['uid' => $uid]);
    $rows = $stmt->fetchAll();

    // Raggruppamento per categoria + importo simile (tolleranza 10%)
    $groups = [];
    foreach ($rows as $r) {
        $catId = $r['category_id'];
        $amount = (float)$r['amount'];
        if (!isset($groups[$catId])) $groups[$catId] = [];
        $foundKey = null;
        foreach ($groups[$catId] as $key => $g) {
            $avg = $g['amount_sum'] / count($g['items']);
            if ($avg > 0 && abs($amount - $avg) / $avg <= 0.10) { $foundKey = $key; break; }
        }
        if ($foundKey === null) {
            $foundKey = count($groups[$catId]);
            $groups[$catId][$foundKey] = ['amount_sum' => 0.0, 'items' => []];
        }
        $groups[$catId][$foundKey]['amount_sum'] += $amount;
        $groups[$catId][$foundKey]['items'][] = $r;
    }

    $detected = [];
    foreach ($groups as $catGroups) {
        foreach ($catGroups as $g) {
            if (count($g['items']) < 3) continue; // servono almeno 3 occorrenze per parlare di regolarità

            $items = $g['items'];
            $dates = array_map(fn($i) => strtotime($i['date']), $items);
            sort($dates);
            $intervals = [];
            for ($k = 1; $k < count($dates); $k++) $intervals[] = ($dates[$k] - $dates[$k - 1]) / 86400;

            $meanInterval = array_sum($intervals) / count($intervals);
            $variance = 0.0;
            foreach ($intervals as $iv) $variance += ($iv - $meanInterval) ** 2;
            $variance /= count($intervals);
            $cv = $meanInterval > 0 ? sqrt($variance) / $meanInterval : 999;

            $freqLabel = match (true) {
                $meanInterval >= 5 && $meanInterval <= 9 => 'settimanale',
                $meanInterval >= 25 && $meanInterval <= 35 => 'mensile',
                $meanInterval >= 80 && $meanInterval <= 100 => 'trimestrale',
                $meanInterval >= 170 && $meanInterval <= 195 => 'semestrale',
                $meanInterval >= 350 && $meanInterval <= 380 => 'annuale',
                default => null,
            };
            if ($freqLabel === null || $cv > 0.35) continue; // intervalli troppo irregolari per essere una ricorrenza

            $lastItem = $items[array_key_last($items)];
            $detected[] = [
                'category_id' => $lastItem['category_id'],
                'category_name' => $lastItem['category_name'],
                'color' => $lastItem['color'],
                'occurrences' => count($items),
                'avg_amount' => round($g['amount_sum'] / count($items), 2),
                'frequency_detected' => $freqLabel,
                'regularity_pct' => round((1 - min($cv, 1)) * 100),
                'last_date' => date('Y-m-d', max($dates)),
                'suggested_start_date' => date('Y-m-d', (int)(max($dates) + $meanInterval * 86400)),
            ];
        }
    }
    usort($detected, fn($a, $b) => $b['occurrences'] <=> $a['occurrences']);
    return $detected;
}

// ---------- Finestra storica: solo mesi completi, esclude il mese in corso ----------
$currentMonthStart = date('Y-m-01');
$historyFrom = date('Y-m-01', strtotime("-{$historyMonths} months", strtotime($currentMonthStart)));
$historyTo = date('Y-m-d', strtotime('-1 day', strtotime($currentMonthStart)));

$placeholders = implode(',', array_fill(0, count(EXCLUDED_CATEGORIES), '?'));
$stmt = db()->prepare("
    SELECT t.date, t.type, t.amount, t.recurring_id, t.category_id, c.name AS category_name, c.color
    FROM transactions t
    JOIN categories c ON c.id = t.category_id
    WHERE t.user_id = ? AND t.date BETWEEN ? AND ?
    AND t.type IN ('entrata','uscita')
    AND c.name NOT IN ($placeholders)
");
$stmt->execute(array_merge([$uid, $historyFrom, $historyTo], EXCLUDED_CATEGORIES));
$historyTxs = $stmt->fetchAll();

$earliestStmt = db()->prepare("SELECT MIN(date) AS earliest FROM transactions WHERE user_id = :uid");
$earliestStmt->execute(['uid' => $uid]);
$earliestDate = $earliestStmt->fetchColumn();
$daysOfData = $earliestDate ? (int)((strtotime(date('Y-m-d')) - strtotime($earliestDate)) / 86400) : 0;
$dataSufficient = $daysOfData >= 90;

// ---------- Aggregazione mensile storica (reale, dato certo) ----------
$monthlyHistory = [];
$cursor = strtotime($historyFrom);
for ($i = 0; $i < $historyMonths; $i++) {
    $key = date('Y-m', $cursor);
    $monthlyHistory[$key] = [
        'month' => $key, 'entrate' => 0.0, 'uscite' => 0.0,
        'entrate_ricorrenti' => 0.0, 'entrate_variabili' => 0.0,
        'uscite_ricorrenti' => 0.0, 'uscite_variabili' => 0.0,
    ];
    $cursor = strtotime('+1 month', $cursor);
}

$categoryMonthly = []; // category_id => [month => totale uscite]
$categoryMeta = [];    // category_id => ['name'=>, 'color'=>]

foreach ($historyTxs as $t) {
    $month = substr($t['date'], 0, 7);
    if (!isset($monthlyHistory[$month])) continue;
    $isRecurring = $t['recurring_id'] !== null;
    $amount = (float)$t['amount'];

    if ($t['type'] === 'entrata') {
        $monthlyHistory[$month]['entrate'] += $amount;
        $monthlyHistory[$month][$isRecurring ? 'entrate_ricorrenti' : 'entrate_variabili'] += $amount;
    } else {
        $monthlyHistory[$month]['uscite'] += $amount;
        $monthlyHistory[$month][$isRecurring ? 'uscite_ricorrenti' : 'uscite_variabili'] += $amount;

        $categoryMonthly[$t['category_id']][$month] = ($categoryMonthly[$t['category_id']][$month] ?? 0) + $amount;
        $categoryMeta[$t['category_id']] = ['name' => $t['category_name'], 'color' => $t['color']];
    }
}
$monthlyHistory = array_values($monthlyHistory);

$avg = function (array $values) use ($historyMonths) {
    return $historyMonths > 0 ? array_sum($values) / $historyMonths : 0;
};
$avgIncome = $avg(array_column($monthlyHistory, 'entrate'));
$avgExpense = $avg(array_column($monthlyHistory, 'uscite'));
$avgRecurringIncome = $avg(array_column($monthlyHistory, 'entrate_ricorrenti'));
$avgVariableIncome = $avg(array_column($monthlyHistory, 'entrate_variabili'));
$avgRecurringExpense = $avg(array_column($monthlyHistory, 'uscite_ricorrenti'));
$avgVariableExpense = $avg(array_column($monthlyHistory, 'uscite_variabili'));
$avgNet = $avgIncome - $avgExpense;
$savingsRatePct = $avgIncome > 0 ? round($avgNet / $avgIncome * 100, 1) : 0;

// Media storica per categoria (uscite), usata come riferimento per gli alert
// quando la categoria non ha un budget esplicito impostato
$categoryAverages = [];
foreach ($categoryMonthly as $catId => $months) {
    $total = array_sum($months);
    $categoryAverages[$catId] = $total / $historyMonths;
}

// ---------- Regole ricorrenti attive + mutui non collegati a una ricorrenza ----------
$rules = [];
$recStmt = db()->prepare("SELECT * FROM recurring_transactions WHERE user_id = :uid AND active = 1 AND type IN ('entrata','uscita')");
$recStmt->execute(['uid' => $uid]);
foreach ($recStmt->fetchAll() as $r) {
    $rules[] = [
        'type' => $r['type'],
        'monthly_amount' => normalize_monthly_amount($r['frequency'], (int)$r['interval_count'], (float)$r['amount']),
        'start_date' => $r['start_date'],
        'end_date' => $r['end_date'],
    ];
}
$loanStmt = db()->prepare("SELECT * FROM loans WHERE user_id = :uid AND status = 'attivo' AND recurring_id IS NULL");
$loanStmt->execute(['uid' => $uid]);
foreach ($loanStmt->fetchAll() as $ln) {
    $rata = loan_monthly_payment((float)$ln['principal'], (float)$ln['annual_rate'], (int)$ln['duration_years']);
    $personalRata = $rata * (float)$ln['ownership_pct'] / 100;
    $endDate = (new DateTime($ln['start_date']))->modify('+' . ((int)$ln['duration_years'] * 12) . ' months')->format('Y-m-d');
    $rules[] = ['type' => 'uscita', 'monthly_amount' => $personalRata, 'start_date' => $ln['start_date'], 'end_date' => $endDate];
}

function rules_total_for_month(array $rules, string $type, string $monthStart, string $monthEnd): float
{
    $total = 0.0;
    foreach ($rules as $rule) {
        if ($rule['type'] !== $type) continue;
        if (rule_active_in_month($rule['start_date'], $rule['end_date'], $monthStart, $monthEnd)) {
            $total += $rule['monthly_amount'];
        }
    }
    return $total;
}

// ---------- Proiezione mesi futuri (lineare, come riferimento centrale) ----------
$forecast = [];
$recurringNetByMonth = [];
$cumulative = 0.0;
$cursor = strtotime('+1 month', strtotime($currentMonthStart));
for ($i = 0; $i < $forecastMonths; $i++) {
    $monthStart = date('Y-m-01', $cursor);
    $monthEnd = date('Y-m-t', $cursor);
    $recIncome = rules_total_for_month($rules, 'entrata', $monthStart, $monthEnd);
    $recExpense = rules_total_for_month($rules, 'uscita', $monthStart, $monthEnd);
    $projIncome = $recIncome + $avgVariableIncome;
    $projExpense = $recExpense + $avgVariableExpense;
    $projNet = $projIncome - $projExpense;
    $cumulative += $projNet;
    $recurringNetByMonth[] = $recIncome - $recExpense;
    $forecast[] = [
        'month' => date('Y-m', $cursor),
        'projected_income' => round($projIncome, 2),
        'projected_expense' => round($projExpense, 2),
        'projected_net' => round($projNet, 2),
        'cumulative_savings' => round($cumulative, 2),
    ];
    $cursor = strtotime('+1 month', $cursor);
}

// ---------- Simulazione Monte Carlo: proiezione probabilistica, non un singolo numero ----------
$historicalVariableNet = array_map(
    fn($m) => $m['entrate_variabili'] - $m['uscite_variabili'],
    $monthlyHistory
);

$numSimulations = 2000;
$monteCarlo = null;
if (count($historicalVariableNet) >= 3) {
    $simFinals = [];
    $simByMonth = array_fill(0, $forecastMonths, []);
    for ($s = 0; $s < $numSimulations; $s++) {
        $cum = 0.0;
        for ($m = 0; $m < $forecastMonths; $m++) {
            $sampledVariableNet = $historicalVariableNet[array_rand($historicalVariableNet)];
            $cum += $recurringNetByMonth[$m] + $sampledVariableNet;
            $simByMonth[$m][] = $cum;
        }
        $simFinals[] = $cum;
    }

    $percentile = function (array $values, float $p) {
        sort($values);
        $idx = (int)floor($p * (count($values) - 1));
        return $values[$idx];
    };

    $bands = [];
    for ($m = 0; $m < $forecastMonths; $m++) {
        $bands[] = [
            'month' => $forecast[$m]['month'],
            'p10' => round($percentile($simByMonth[$m], 0.10), 2),
            'p50' => round($percentile($simByMonth[$m], 0.50), 2),
            'p90' => round($percentile($simByMonth[$m], 0.90), 2),
        ];
    }
    $probPositiveFinal = round(count(array_filter($simFinals, fn($v) => $v >= 0)) / $numSimulations * 100, 1);

    $monteCarlo = [
        'simulations' => $numSimulations,
        'percentile_bands' => $bands,
        'probability_final_positive_pct' => $probPositiveFinal,
    ];
}

// ---------- Mese in corso: dato reale finora + proiezione a fine mese ----------
$today = date('Y-m-d');
$curMonthStart = $currentMonthStart;
$curMonthEnd = date('Y-m-t');
$daysElapsed = max(1, (int)date('j'));
$daysTotal = (int)date('t');

$stmt = db()->prepare("
    SELECT t.type, t.amount, t.recurring_id, t.category_id
    FROM transactions t JOIN categories c ON c.id = t.category_id
    WHERE t.user_id = ? AND t.date BETWEEN ? AND ? AND t.type IN ('entrata','uscita')
    AND c.name NOT IN ($placeholders)
");
$stmt->execute(array_merge([$uid, $curMonthStart, $today], EXCLUDED_CATEGORIES));
$curTxs = $stmt->fetchAll();

$incomeVarSoFar = 0.0; $expenseVarSoFar = 0.0; $incomeSoFar = 0.0; $expenseSoFar = 0.0;
$categorySpentSoFar = [];
foreach ($curTxs as $t) {
    $amount = (float)$t['amount'];
    $isRecurring = $t['recurring_id'] !== null;
    if ($t['type'] === 'entrata') {
        $incomeSoFar += $amount;
        if (!$isRecurring) $incomeVarSoFar += $amount;
    } else {
        $expenseSoFar += $amount;
        if (!$isRecurring) $expenseVarSoFar += $amount;
        $categorySpentSoFar[$t['category_id']] = ($categorySpentSoFar[$t['category_id']] ?? 0) + $amount;
    }
}

$recIncomeThisMonth = rules_total_for_month($rules, 'entrata', $curMonthStart, $curMonthEnd);
$recExpenseThisMonth = rules_total_for_month($rules, 'uscita', $curMonthStart, $curMonthEnd);
$projectedVarIncome = $incomeVarSoFar / $daysElapsed * $daysTotal;
$projectedVarExpense = $expenseVarSoFar / $daysElapsed * $daysTotal;
$projectedIncomeMonth = $recIncomeThisMonth + $projectedVarIncome;
$projectedExpenseMonth = $recExpenseThisMonth + $projectedVarExpense;

$currentMonth = [
    'month' => date('Y-m'),
    'days_elapsed' => $daysElapsed,
    'days_total' => $daysTotal,
    'income_so_far' => round($incomeSoFar, 2),
    'expense_so_far' => round($expenseSoFar, 2),
    'projected_income' => round($projectedIncomeMonth, 2),
    'projected_expense' => round($projectedExpenseMonth, 2),
    'projected_net' => round($projectedIncomeMonth - $projectedExpenseMonth, 2),
    'discretionary_remaining' => round(max(0, $avgVariableExpense - $expenseVarSoFar), 2),
    'discretionary_budget' => round($avgVariableExpense, 2),
];

// ---------- Alert per categoria: proiezione a fine mese vs budget o media storica ----------
$budgetStmt = db()->prepare("SELECT category_id, monthly_limit FROM budgets WHERE user_id = :uid");
$budgetStmt->execute(['uid' => $uid]);
$budgetsByCategory = [];
foreach ($budgetStmt->fetchAll() as $b) {
    $budgetsByCategory[$b['category_id']] = (float)$b['monthly_limit'];
}

$categoryAlerts = [];
$allCategoryIds = array_unique(array_merge(array_keys($categorySpentSoFar), array_keys($categoryAverages), array_keys($budgetsByCategory)));
foreach ($allCategoryIds as $catId) {
    $spentSoFar = $categorySpentSoFar[$catId] ?? 0.0;
    $projected = $spentSoFar / $daysElapsed * $daysTotal;
    $hasBudget = isset($budgetsByCategory[$catId]);
    $reference = $hasBudget ? $budgetsByCategory[$catId] : ($categoryAverages[$catId] ?? null);
    if ($reference === null || $reference <= 0) continue;

    $pct = round($projected / $reference * 100, 1);
    if ($pct < 90) continue;

    $severity = $pct >= 130 ? 'danger' : ($pct >= 100 ? 'warning' : 'ok');
    $meta = $categoryMeta[$catId] ?? null;
    if (!$meta) {
        $nameStmt = db()->prepare("SELECT name, color FROM categories WHERE id = :id AND user_id = :uid");
        $nameStmt->execute(['id' => $catId, 'uid' => $uid]);
        $meta = $nameStmt->fetch();
    }
    $categoryAlerts[] = [
        'category_id' => $catId,
        'category_name' => $meta['name'] ?? '?',
        'color' => $meta['color'] ?? '#8b95a1',
        'spent_so_far' => round($spentSoFar, 2),
        'projected_month_end' => round($projected, 2),
        'reference' => round($reference, 2),
        'reference_type' => $hasBudget ? 'budget' : 'storico',
        'pct' => $pct,
        'severity' => $severity,
    ];
}
usort($categoryAlerts, fn($a, $b) => $b['pct'] <=> $a['pct']);

respond([
    'data_sufficient' => $dataSufficient,
    'days_of_data' => $daysOfData,
    'history_months' => $historyMonths,
    'monthly_history' => $monthlyHistory,
    'averages' => [
        'avg_income' => round($avgIncome, 2),
        'avg_expense' => round($avgExpense, 2),
        'avg_net' => round($avgNet, 2),
        'savings_rate_pct' => $savingsRatePct,
        'avg_recurring_income' => round($avgRecurringIncome, 2),
        'avg_variable_income' => round($avgVariableIncome, 2),
        'avg_recurring_expense' => round($avgRecurringExpense, 2),
        'avg_variable_expense' => round($avgVariableExpense, 2),
    ],
    'forecast' => $forecast,
    'monte_carlo' => $monteCarlo,
    'current_month' => $currentMonth,
    'category_alerts' => $categoryAlerts,
    'detected_recurring' => detect_hidden_recurring(db(), $uid),
]);
