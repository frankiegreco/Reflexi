<?php
declare(strict_types=1);
require __DIR__ . '/db.php';
require_once __DIR__ . '/price_fetch.php';

if (method() !== 'GET') respond_error('Metodo non supportato', 405);

$uid = require_auth();
$months = min((int)($_GET['months'] ?? 12), 60);
$catFrom = $_GET['date_from'] ?? date('Y-m-01');
$catTo = $_GET['date_to'] ?? date('Y-m-d');

// Saldo totale e per conto (solo conti non archiviati)
$balanceSql = "
    a.initial_balance
    + COALESCE((SELECT SUM(amount) FROM transactions WHERE account_id = a.id AND type = 'entrata'), 0)
    - COALESCE((SELECT SUM(amount) FROM transactions WHERE account_id = a.id AND type = 'uscita'), 0)
    - COALESCE((SELECT SUM(amount) FROM transactions WHERE account_id = a.id AND type = 'trasferimento'), 0)
    + COALESCE((SELECT SUM(amount) FROM transactions WHERE transfer_account_id = a.id AND type = 'trasferimento'), 0)
    AS balance
";
$stmt = db()->prepare("SELECT a.id, a.name, a.color, a.currency, {$balanceSql} FROM accounts a WHERE a.user_id = :uid AND a.archived = 0 ORDER BY a.sort_order");
$stmt->execute(['uid' => $uid]);
$accounts = $stmt->fetchAll();

$totalBalance = 0.0;
foreach ($accounts as &$acc) {
    $balance = (float)$acc['balance'];
    if ($acc['currency'] !== 'EUR') {
        $fx = fx_rate_to_eur($acc['currency']);
        $acc['fx_rate'] = $fx['rate'];
        $acc['balance_eur'] = $fx['rate'] !== null ? round($balance * $fx['rate'], 2) : null;
    } else {
        $acc['fx_rate'] = 1.0;
        $acc['balance_eur'] = $balance;
    }
    $totalBalance += $acc['balance_eur'] ?? 0;
}
unset($acc);

// Andamento entrate/uscite mensile (ultimi N mesi)
$stmt = db()->prepare("
    SELECT DATE_FORMAT(date, '%Y-%m') AS month,
           SUM(CASE WHEN type = 'entrata' THEN amount ELSE 0 END) AS entrate,
           SUM(CASE WHEN type = 'uscita' THEN amount ELSE 0 END) AS uscite
    FROM transactions
    WHERE user_id = :uid AND date >= DATE_SUB(CURDATE(), INTERVAL :months MONTH)
    GROUP BY month
    ORDER BY month
");
$stmt->execute(['uid' => $uid, 'months' => $months]);
$monthlyRaw = $stmt->fetchAll();
$monthlyByKey = [];
foreach ($monthlyRaw as $row) {
    $monthlyByKey[$row['month']] = $row;
}

// Riempie i mesi senza movimenti con zero, cosi il grafico non ha buchi
$monthly = [];
$cursor = new DateTime("first day of -" . ($months - 1) . " months");
for ($i = 0; $i < $months; $i++) {
    $key = $cursor->format('Y-m');
    $monthly[] = [
        'month' => $key,
        'entrate' => (float)($monthlyByKey[$key]['entrate'] ?? 0),
        'uscite' => (float)($monthlyByKey[$key]['uscite'] ?? 0),
    ];
    $cursor->modify('+1 month');
}

// Ripartizione per categoria nel periodo richiesto (default: mese corrente)
$stmt = db()->prepare("
    SELECT c.name, c.color, c.type, SUM(t.amount) AS total
    FROM transactions t
    JOIN categories c ON c.id = t.category_id
    WHERE t.user_id = :uid AND t.date BETWEEN :from AND :to AND t.type IN ('entrata','uscita')
    GROUP BY c.id
    ORDER BY total DESC
");
$stmt->execute(['uid' => $uid, 'from' => $catFrom, 'to' => $catTo]);
$categoryBreakdown = $stmt->fetchAll();

respond([
    'total_balance' => $totalBalance,
    'accounts' => $accounts,
    'monthly' => $monthly,
    'category_breakdown' => $categoryBreakdown,
    'period' => ['from' => $catFrom, 'to' => $catTo],
]);
