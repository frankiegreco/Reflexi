<?php
declare(strict_types=1);
require __DIR__ . '/db.php';
require __DIR__ . '/loan_math.php';

$uid = require_auth();

// ---- Piano di ammortamento / simulazione estinzione anticipata di un mutuo ----
if (isset($_GET['action']) && $_GET['action'] === 'schedule') {
    $id = $_GET['id'] ?? null;
    if (!$id) respond_error('ID mutuo mancante');
    $stmt = db()->prepare("SELECT * FROM loans WHERE id = :id AND user_id = :uid");
    $stmt->execute(['id' => $id, 'uid' => $uid]);
    $loan = $stmt->fetch();
    if (!$loan) respond_error('Mutuo non trovato', 404);

    $n = (int)$loan['duration_years'] * 12;
    $rateHistory = loan_rate_history_for($loan);
    // Piano completo fino a scadenza: i mesi gia' trascorsi usano i tassi
    // storici reali, quelli futuri assumono costante l'ultimo tasso noto
    // (o eventuali variazioni gia' inserite con data futura).
    $schedule = simulate_amortization((float)$loan['principal'], $rateHistory, $loan['start_date'], $n, '9999-12-31');
    $summary = loan_summary($loan);
    $actual = loan_actual_breakdown($loan);

    // Le prime N rate del piano (in ordine cronologico) corrispondono alle N
    // transazioni effettivamente registrate, assumendo pagamenti mensili senza salti.
    foreach ($schedule as &$row) {
        $row['paid'] = $actual['count'] !== null ? ($row['month_number'] <= $actual['count']) : null;
    }
    unset($row);

    $payoff = null;
    if (!empty($_GET['payoff_date'])) {
        $k = loan_elapsed_months($loan['start_date'], $_GET['payoff_date']);
        $k = max(0, min($k, $n));
        $residual = $k > 0 ? $schedule[$k - 1]['balance_after'] : (float)$loan['principal'];
        $interestPaidSoFar = array_sum(array_column(array_slice($schedule, 0, $k), 'interest_portion'));
        $interestIfCompleted = array_sum(array_column($schedule, 'interest_portion'));
        $interestSaved = $interestIfCompleted - $interestPaidSoFar;
        $payoff = [
            'payments_made' => $k,
            'residual_capital' => round($residual, 2),
            'interest_paid_so_far' => round($interestPaidSoFar, 2),
            'interest_saved_if_paid_now' => round(max(0, $interestSaved), 2),
        ];
    }

    respond(['loan' => $loan, 'summary' => $summary, 'actual' => $actual, 'schedule' => $schedule, 'payoff_simulation' => $payoff]);
}

switch (method()) {
    case 'GET':
        $stmt = db()->prepare("SELECT l.*, a.name AS account_name, c.name AS category_name
            FROM loans l
            LEFT JOIN accounts a ON a.id = l.account_id
            LEFT JOIN categories c ON c.id = l.category_id
            WHERE l.user_id = :uid
            ORDER BY l.status, l.start_date DESC");
        $stmt->execute(['uid' => $uid]);
        $loans = $stmt->fetchAll();
        foreach ($loans as &$loan) {
            $loan['calc'] = loan_summary($loan);
            $loan['actual'] = loan_actual_breakdown($loan);
        }
        respond($loans);
        break;

    case 'POST':
        $in = json_input();
        foreach (['name', 'principal', 'annual_rate', 'duration_years', 'start_date'] as $f) {
            if (empty($in[$f]) && $in[$f] !== 0) respond_error("Campo obbligatorio mancante: $f");
        }

        $recurringId = null;
        if (!empty($in['create_recurring'])) {
            if (empty($in['account_id']) || empty($in['category_id'])) {
                respond_error('Per creare la rata ricorrente servono conto e categoria');
            }
            assert_owned('accounts', $in['account_id'], $uid, 'conto');
            assert_owned('categories', $in['category_id'], $uid, 'categoria');
            $ownershipPct = (float)($in['ownership_pct'] ?? 100);
            $rata = loan_monthly_payment((float)$in['principal'], (float)$in['annual_rate'], (int)$in['duration_years']);
            $personalRata = $rata * $ownershipPct / 100; // quota realmente a tuo carico, non la rata intera
            $endDate = (new DateTime($in['start_date']))->modify('+' . ((int)$in['duration_years'] * 12) . ' months')->format('Y-m-d');

            $stmt = db()->prepare("INSERT INTO recurring_transactions
                (user_id, account_id, category_id, transfer_account_id, amount, type, description, frequency, interval_count, start_date, end_date, next_run, active)
                VALUES (:uid, :account_id, :category_id, NULL, :amount, 'uscita', :description, 'mensile', 1, :start_date, :end_date, :next_run, 1)");
            $stmt->execute([
                'uid' => $uid,
                'account_id' => $in['account_id'],
                'category_id' => $in['category_id'],
                'amount' => round($personalRata, 2),
                'description' => 'Rata mutuo: ' . $in['name'] . ($ownershipPct < 100 ? " (quota {$ownershipPct}%)" : ''),
                'start_date' => $in['start_date'],
                'end_date' => $endDate,
                'next_run' => $in['start_date'],
            ]);
            $recurringId = (int)db()->lastInsertId();
        }

        $rateType = ($in['rate_type'] ?? 'fisso') === 'variabile' ? 'variabile' : 'fisso';

        $stmt = db()->prepare("INSERT INTO loans
            (user_id, name, principal, annual_rate, duration_years, ownership_pct, start_date, account_id, category_id, recurring_id, notes, rate_type)
            VALUES (:uid, :name, :principal, :annual_rate, :duration_years, :ownership_pct, :start_date, :account_id, :category_id, :recurring_id, :notes, :rate_type)");
        $stmt->execute([
            'uid' => $uid,
            'name' => $in['name'],
            'principal' => $in['principal'],
            'annual_rate' => $in['annual_rate'],
            'duration_years' => $in['duration_years'],
            'ownership_pct' => $in['ownership_pct'] ?? 100,
            'start_date' => $in['start_date'],
            'account_id' => $in['account_id'] ?? null,
            'category_id' => $in['category_id'] ?? null,
            'recurring_id' => $recurringId,
            'notes' => $in['notes'] ?? null,
            'rate_type' => $rateType,
        ]);
        $loanId = (int)db()->lastInsertId();

        db()->prepare("INSERT INTO loan_rate_history (loan_id, effective_date, rate) VALUES (:loan_id, :effective_date, :rate)")
            ->execute(['loan_id' => $loanId, 'effective_date' => $in['start_date'], 'rate' => $in['annual_rate']]);

        respond(['id' => $loanId, 'recurring_id' => $recurringId], 201);
        break;

    case 'PUT':
        $in = json_input();
        if (empty($in['id'])) respond_error('ID mancante');

        $fields = [];
        $params = ['id' => $in['id'], 'uid' => $uid];
        foreach (['name', 'principal', 'annual_rate', 'duration_years', 'ownership_pct', 'start_date', 'account_id',
                   'category_id', 'status', 'early_payoff_date', 'early_payoff_amount', 'notes', 'rate_type'] as $f) {
            if (array_key_exists($f, $in)) {
                $fields[] = "$f = :$f";
                $params[$f] = $in[$f];
            }
        }
        if (!$fields) respond_error('Nessun campo da aggiornare');

        if (array_key_exists('account_id', $in)) assert_owned('accounts', $in['account_id'], $uid, 'conto');
        if (array_key_exists('category_id', $in)) assert_owned('categories', $in['category_id'], $uid, 'categoria');

        $stmt = db()->prepare("UPDATE loans SET " . implode(', ', $fields) . " WHERE id = :id AND user_id = :uid");
        $stmt->execute($params);

        // Se sono cambiati capitale, tasso, durata o quota di proprietà, e c'è una
        // rata ricorrente collegata, ne risincronizza l'importo: la ricorrenza deve
        // sempre riflettere la quota realmente a proprio carico, non la rata intera.
        $affectsAmount = array_intersect(['principal', 'annual_rate', 'duration_years', 'ownership_pct'], array_keys($in));
        if ($affectsAmount) {
            $loanStmt = db()->prepare("SELECT * FROM loans WHERE id = :id AND user_id = :uid");
            $loanStmt->execute(['id' => $in['id'], 'uid' => $uid]);
            $updatedLoan = $loanStmt->fetch();
            if ($updatedLoan && $updatedLoan['recurring_id']) {
                $rata = loan_monthly_payment((float)$updatedLoan['principal'], (float)$updatedLoan['annual_rate'], (int)$updatedLoan['duration_years']);
                $personalRata = $rata * (float)$updatedLoan['ownership_pct'] / 100;
                db()->prepare("UPDATE recurring_transactions SET amount = :amount WHERE id = :rid AND user_id = :uid")
                    ->execute(['amount' => round($personalRata, 2), 'rid' => $updatedLoan['recurring_id'], 'uid' => $uid]);
            }
        }

        // Se e' cambiato il tasso di partenza o la data di erogazione, tiene
        // sincronizzata la prima riga di loan_rate_history: e' l'unico modo
        // per correggere il tasso di partenza di un mutuo a tasso fisso, dato
        // che per quelli non esiste una sezione storico separata in UI.
        if (array_key_exists('annual_rate', $in) || array_key_exists('start_date', $in)) {
            $loanStmt = db()->prepare("SELECT annual_rate, start_date FROM loans WHERE id = :id AND user_id = :uid");
            $loanStmt->execute(['id' => $in['id'], 'uid' => $uid]);
            $updatedLoan = $loanStmt->fetch();
            if ($updatedLoan) {
                $firstRateStmt = db()->prepare("SELECT id FROM loan_rate_history WHERE loan_id = :id ORDER BY effective_date ASC LIMIT 1");
                $firstRateStmt->execute(['id' => $in['id']]);
                $firstRateId = $firstRateStmt->fetchColumn();
                if ($firstRateId) {
                    db()->prepare("UPDATE loan_rate_history SET effective_date = :date, rate = :rate WHERE id = :id")
                        ->execute(['date' => $updatedLoan['start_date'], 'rate' => $updatedLoan['annual_rate'], 'id' => $firstRateId]);
                }
            }
        }

        // Se il mutuo viene segnato come estinto, disattiva anche la rata ricorrente collegata
        if (($in['status'] ?? null) === 'estinto') {
            $loanStmt = db()->prepare("SELECT recurring_id FROM loans WHERE id = :id AND user_id = :uid");
            $loanStmt->execute(['id' => $in['id'], 'uid' => $uid]);
            $recId = $loanStmt->fetchColumn();
            if ($recId) {
                $deact = db()->prepare("UPDATE recurring_transactions SET active = 0 WHERE id = :id AND user_id = :uid");
                $deact->execute(['id' => $recId, 'uid' => $uid]);
            }
        }
        respond(['ok' => true]);
        break;

    case 'DELETE':
        $id = $_GET['id'] ?? null;
        if (!$id) respond_error('ID mancante');
        $deleteRecurring = ($_GET['delete_recurring'] ?? '0') === '1';

        if ($deleteRecurring) {
            $loanStmt = db()->prepare("SELECT recurring_id FROM loans WHERE id = :id AND user_id = :uid");
            $loanStmt->execute(['id' => $id, 'uid' => $uid]);
            $recId = $loanStmt->fetchColumn();
            if ($recId) {
                // Cancella prima le transazioni generate da questa rata ricorrente,
                // poi la ricorrenza stessa: altrimenti resterebbero movimenti "orfani"
                // che continuano a comparire nel ledger di un mutuo ormai cancellato.
                db()->prepare("DELETE FROM transactions WHERE recurring_id = :id AND user_id = :uid")->execute(['id' => $recId, 'uid' => $uid]);
                db()->prepare("DELETE FROM recurring_transactions WHERE id = :id AND user_id = :uid")->execute(['id' => $recId, 'uid' => $uid]);
            }
        }
        db()->prepare("DELETE FROM loans WHERE id = :id AND user_id = :uid")->execute(['id' => $id, 'uid' => $uid]);
        respond(['ok' => true]);
        break;

    default:
        respond_error('Metodo non supportato', 405);
}
