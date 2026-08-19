<?php
declare(strict_types=1);
require __DIR__ . '/db.php';

$uid = require_auth();

if (isset($_GET['action']) && $_GET['action'] === 'overview') {
    $month = $_GET['month'] ?? date('Y-m');
    $from = $month . '-01';
    $to = date('Y-m-t', strtotime($from));

    // Storico 6 mesi entrate/uscite reali (per i due grafici in alto)
    $stmt = db()->prepare("
        SELECT DATE_FORMAT(date, '%Y-%m') AS ym,
            COALESCE(SUM(CASE WHEN type = 'entrata' THEN amount ELSE 0 END), 0) AS income,
            COALESCE(SUM(CASE WHEN type = 'uscita' THEN amount ELSE 0 END), 0) AS expense
        FROM transactions
        WHERE user_id = :uid AND date >= DATE_SUB(:to_date, INTERVAL 5 MONTH) AND date <= :to_date2 AND type IN ('entrata','uscita')
        GROUP BY ym ORDER BY ym
    ");
    $stmt->execute(['uid' => $uid, 'to_date' => $to, 'to_date2' => $to]);
    $history = $stmt->fetchAll();
    foreach ($history as &$h) { $h['income'] = (float)$h['income']; $h['expense'] = (float)$h['expense']; }

    // Totale budgetato per entrate/uscite nel mese selezionato
    $stmt = db()->prepare("
        SELECT c.type, COALESCE(SUM(b.monthly_limit), 0) AS total_budgeted
        FROM budgets b JOIN categories c ON c.id = b.category_id
        WHERE b.user_id = :uid GROUP BY c.type
    ");
    $stmt->execute(['uid' => $uid]);
    $budgeted = ['entrata' => 0, 'uscita' => 0];
    foreach ($stmt->fetchAll() as $r) { $budgeted[$r['type']] = (float)$r['total_budgeted']; }

    // Ripartizione per classificazione (necessità/desideri/risparmio), sulle
    // uscite reali del mese — solo per le categorie che l'utente ha classificato.
    $stmt = db()->prepare("
        SELECT c.classification, COALESCE(SUM(t.amount), 0) AS total
        FROM transactions t JOIN categories c ON c.id = t.category_id
        WHERE t.user_id = :uid AND t.type = 'uscita' AND t.date BETWEEN :from AND :to
        AND c.classification IS NOT NULL
        GROUP BY c.classification
    ");
    $stmt->execute(['uid' => $uid, 'from' => $from, 'to' => $to]);
    $ratios = ['necessita' => 0, 'desiderio' => 0, 'risparmio' => 0];
    foreach ($stmt->fetchAll() as $r) { $ratios[$r['classification']] = (float)$r['total']; }
    $hasClassification = array_sum($ratios) > 0;

    respond([
        'history' => $history,
        'budgeted_income' => $budgeted['entrata'],
        'budgeted_expense' => $budgeted['uscita'],
        'ratios' => $ratios,
        'has_classification' => $hasClassification,
    ]);
}

switch (method()) {
    case 'GET':
        $month = $_GET['month'] ?? date('Y-m');
        $from = $month . '-01';
        $to = date('Y-m-t', strtotime($from));

        $stmt = db()->prepare("
            SELECT b.id, b.category_id, b.monthly_limit, b.rollover, b.created_at, c.name AS category_name, c.type AS category_type, c.color,
                   COALESCE((
                       SELECT SUM(t.amount) FROM transactions t
                       WHERE t.category_id = b.category_id AND t.user_id = :uid AND t.type = 'uscita'
                       AND t.date BETWEEN :from AND :to
                   ), 0) AS spent
            FROM budgets b
            JOIN categories c ON c.id = b.category_id
            WHERE b.user_id = :uid2
            ORDER BY c.name
        ");
        $stmt->execute(['uid' => $uid, 'uid2' => $uid, 'from' => $from, 'to' => $to]);
        $rows = $stmt->fetchAll();

        foreach ($rows as &$r) {
            $r['spent'] = (float)$r['spent'];
            $r['monthly_limit'] = (float)$r['monthly_limit'];
            $r['rollover'] = (bool)$r['rollover'];

            if ($r['rollover']) {
                // Con il rollover attivo, il "disponibile" e' cumulativo dal mese di
                // creazione del budget: somma di tutti i limiti mensili passati meno
                // tutto lo speso da allora, non solo il mese corrente. Un mese in cui
                // spendi meno del limite lascia margine per il mese dopo (e viceversa,
                // se sfori un mese il margine del successivo si riduce davvero).
                $firstMonth = date('Y-m-01', strtotime($r['created_at']));
                $monthsElapsed = (int)((strtotime($from) - strtotime($firstMonth)) / (30.44 * 86400)) + 1;
                // Calcolo esatto dei mesi trascorsi (piu' affidabile della divisione sopra)
                $d1 = new DateTime($firstMonth);
                $d2 = new DateTime($from);
                $monthsElapsed = ($d2->format('Y') - $d1->format('Y')) * 12 + ($d2->format('n') - $d1->format('n')) + 1;
                $totalLimit = $r['monthly_limit'] * max(1, $monthsElapsed);

                $totalSpentStmt = db()->prepare("SELECT COALESCE(SUM(amount), 0) FROM transactions
                    WHERE category_id = :cat AND user_id = :uid AND type = 'uscita' AND date BETWEEN :from AND :to");
                $totalSpentStmt->execute(['cat' => $r['category_id'], 'uid' => $uid, 'from' => $firstMonth, 'to' => $to]);
                $totalSpent = (float)$totalSpentStmt->fetchColumn();

                $r['remaining'] = round($totalLimit - $totalSpent, 2);
                $r['available_this_month'] = round($r['remaining'] + $r['spent'], 2); // quanto avevi davvero da spendere questo mese
                $r['pct'] = $r['available_this_month'] > 0 ? round($r['spent'] / $r['available_this_month'] * 100, 1) : ($r['spent'] > 0 ? 100 : 0);
            } else {
                $r['remaining'] = round($r['monthly_limit'] - $r['spent'], 2);
                $r['available_this_month'] = $r['monthly_limit'];
                $r['pct'] = $r['monthly_limit'] > 0 ? round($r['spent'] / $r['monthly_limit'] * 100, 1) : 0;
            }
            $r['over_budget'] = $r['remaining'] < 0;
            unset($r['created_at']);
        }
        respond(['month' => $month, 'budgets' => $rows]);
        break;

    case 'POST':
        $in = json_input();
        if (empty($in['category_id']) || empty($in['monthly_limit'])) {
            respond_error('Categoria e limite mensile sono obbligatori');
        }
        assert_owned('categories', $in['category_id'], $uid, 'categoria');
        // Upsert: un budget per utente+categoria
        $stmt = db()->prepare("INSERT INTO budgets (user_id, category_id, monthly_limit, rollover)
            VALUES (:uid, :category_id, :monthly_limit, :rollover)
            ON DUPLICATE KEY UPDATE monthly_limit = :monthly_limit2, rollover = :rollover2");
        $stmt->execute([
            'uid' => $uid,
            'category_id' => $in['category_id'],
            'monthly_limit' => $in['monthly_limit'],
            'rollover' => !empty($in['rollover']) ? 1 : 0,
            'monthly_limit2' => $in['monthly_limit'],
            'rollover2' => !empty($in['rollover']) ? 1 : 0,
        ]);
        respond(['ok' => true], 201);
        break;

    case 'DELETE':
        $categoryId = $_GET['category_id'] ?? null;
        if (!$categoryId) respond_error('category_id mancante');
        db()->prepare("DELETE FROM budgets WHERE category_id = :category_id AND user_id = :uid")
            ->execute(['category_id' => $categoryId, 'uid' => $uid]);
        respond(['ok' => true]);
        break;

    default:
        respond_error('Metodo non supportato', 405);
}
