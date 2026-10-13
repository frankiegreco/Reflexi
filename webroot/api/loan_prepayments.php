<?php
declare(strict_types=1);
require __DIR__ . '/db.php';
require __DIR__ . '/loan_math.php';

$uid = require_auth();

function load_owned_loan($id, int $uid): array
{
    $stmt = db()->prepare("SELECT * FROM loans WHERE id = :id AND user_id = :uid");
    $stmt->execute(['id' => $id, 'uid' => $uid]);
    $loan = $stmt->fetch();
    if (!$loan) respond_error('Mutuo non trovato', 404);
    return $loan;
}

switch (method()) {
    case 'GET':
        $loanId = $_GET['loan_id'] ?? null;
        if (!$loanId) respond_error('ID mutuo mancante');
        load_owned_loan($loanId, $uid);
        respond(loan_load_prepayments((int)$loanId));
        break;

    case 'POST':
        $in = json_input();
        foreach (['loan_id', 'payment_date', 'amount'] as $f) {
            if (empty($in[$f])) respond_error("Campo obbligatorio mancante: $f");
        }
        $loan = load_owned_loan($in['loan_id'], $uid);
        if ($loan['status'] === 'estinto') respond_error('Il mutuo risulta già estinto');

        $amount = round((float)$in['amount'], 2);
        $mode = ($in['mode'] ?? 'durata') === 'rata' ? 'rata' : 'durata';
        $date = (string)$in['payment_date'];
        if ($amount <= 0) respond_error("L'importo deve essere maggiore di zero");
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) respond_error('Data non valida');
        // Si registra un versamento gia' fatto: per ipotesi future c'e' il simulatore.
        if ($date > date('Y-m-d')) respond_error('La data del versamento non può essere nel futuro: per le ipotesi usa la simulazione');
        if ($date < $loan['start_date']) respond_error("La data è precedente all'erogazione del mutuo");

        // Residuo alla data del versamento, compresi i versamenti gia' registrati fino a quel giorno.
        $n = (int)$loan['duration_years'] * 12;
        $before = simulate_amortization((float)$loan['principal'], loan_rate_history_for($loan), $loan['start_date'], $n, $date, loan_load_prepayments((int)$loan['id']));
        $residual = $before ? end($before)['balance_after'] : (float)$loan['principal'];
        if ($amount >= $residual) {
            respond_error('Il versamento estinguerebbe tutto il debito residuo (' . number_format($residual, 2, ',', '.') . ' €): usa "Estinzione anticipata"');
        }

        $createTx = !empty($in['create_transaction']);
        if ($createTx) {
            if (empty($in['account_id']) || empty($in['category_id'])) respond_error('Per registrare l\'uscita servono conto e categoria');
            assert_owned('accounts', $in['account_id'], $uid, 'conto');
            assert_owned('categories', $in['category_id'], $uid, 'categoria');
        }

        db()->beginTransaction();
        $txId = null;
        if ($createTx) {
            // Come per la rata ricorrente, l'uscita e' la quota a proprio carico.
            $ownershipPct = (float)$loan['ownership_pct'];
            db()->prepare("INSERT INTO transactions (user_id, account_id, category_id, amount, type, description, date)
                VALUES (:uid, :account_id, :category_id, :amount, 'uscita', :description, :date)")
                ->execute([
                    'uid' => $uid,
                    'account_id' => $in['account_id'],
                    'category_id' => $in['category_id'],
                    'amount' => round($amount * $ownershipPct / 100, 2),
                    'description' => 'Rimborso anticipato mutuo: ' . $loan['name'] . ($ownershipPct < 100 ? " (quota {$ownershipPct}%)" : ''),
                    'date' => $date,
                ]);
            $txId = (int)db()->lastInsertId();
        }
        db()->prepare("INSERT INTO loan_prepayments (user_id, loan_id, payment_date, amount, mode, transaction_id)
            VALUES (:uid, :loan_id, :payment_date, :amount, :mode, :transaction_id)")
            ->execute(['uid' => $uid, 'loan_id' => $loan['id'], 'payment_date' => $date, 'amount' => $amount, 'mode' => $mode, 'transaction_id' => $txId]);
        $id = (int)db()->lastInsertId();
        loan_sync_recurring_with_plan($loan);
        db()->commit();

        respond(['id' => $id, 'transaction_id' => $txId], 201);
        break;

    case 'DELETE':
        $id = $_GET['id'] ?? null;
        if (!$id) respond_error('ID mancante');
        $stmt = db()->prepare("SELECT * FROM loan_prepayments WHERE id = :id AND user_id = :uid");
        $stmt->execute(['id' => $id, 'uid' => $uid]);
        $row = $stmt->fetch();
        if (!$row) respond_error('Versamento non trovato', 404);
        $loan = load_owned_loan($row['loan_id'], $uid);

        db()->beginTransaction();
        db()->prepare("DELETE FROM loan_prepayments WHERE id = :id")->execute(['id' => $id]);
        if (($_GET['delete_transaction'] ?? '0') === '1' && $row['transaction_id']) {
            db()->prepare("DELETE FROM transactions WHERE id = :id AND user_id = :uid")->execute(['id' => $row['transaction_id'], 'uid' => $uid]);
        }
        if (loan_load_prepayments((int)$loan['id'])) {
            loan_sync_recurring_with_plan($loan);
        } elseif ($loan['recurring_id'] && $loan['status'] !== 'estinto') {
            // Nessun versamento rimasto: torna alla rata e alla scadenza originali.
            $rata = loan_monthly_payment((float)$loan['principal'], (float)$loan['annual_rate'], (int)$loan['duration_years']);
            $endDate = (new DateTime($loan['start_date']))->modify('+' . ((int)$loan['duration_years'] * 12) . ' months')->format('Y-m-d');
            db()->prepare("UPDATE recurring_transactions SET amount = :amount, end_date = :end_date WHERE id = :rid AND user_id = :uid")
                ->execute(['amount' => round($rata * (float)$loan['ownership_pct'] / 100, 2), 'end_date' => $endDate, 'rid' => $loan['recurring_id'], 'uid' => $uid]);
        }
        db()->commit();
        respond(['ok' => true]);
        break;

    default:
        respond_error('Metodo non supportato', 405);
}
