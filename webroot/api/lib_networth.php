<?php
declare(strict_types=1);

require_once __DIR__ . '/loan_math.php';
require_once __DIR__ . '/price_fetch.php';

/**
 * Calcola il patrimonio netto reale dell'utente, con lo stesso metodo già
 * usato lato frontend nel Riepilogo: liquidità (saldo conti, convertita in EUR
 * per i conti in valuta estera) + investimenti (convertiti in EUR per gli
 * strumenti in valuta estera, stesso tasso di cambio usato per i conti) +
 * immobili (per la sola quota di proprietà se collegati a un mutuo) meno il
 * debito residuo dei mutui (sempre per la propria quota).
 */
function compute_net_worth(int $uid): array
{
    // Liquidità: somma saldi di tutti i conti attivi, convertendo in EUR i conti in valuta estera
    $stmt = db()->prepare("
        SELECT a.currency,
            a.initial_balance
            + COALESCE((SELECT SUM(amount) FROM transactions WHERE account_id = a.id AND type = 'entrata'), 0)
            - COALESCE((SELECT SUM(amount) FROM transactions WHERE account_id = a.id AND type = 'uscita'), 0)
            - COALESCE((SELECT SUM(amount) FROM transactions WHERE account_id = a.id AND type = 'trasferimento'), 0)
            + COALESCE((SELECT SUM(amount) FROM transactions WHERE transfer_account_id = a.id AND type = 'trasferimento'), 0)
            AS balance
        FROM accounts a WHERE a.user_id = :uid AND a.archived = 0
    ");
    $stmt->execute(['uid' => $uid]);
    $liquidBalance = 0.0;
    foreach ($stmt->fetchAll() as $acc) {
        $balance = (float)$acc['balance'];
        if ($acc['currency'] !== 'EUR') {
            $fx = fx_rate_to_eur($acc['currency']);
            if ($fx['rate'] !== null) $balance *= $fx['rate'];
        }
        $liquidBalance += $balance;
    }

    // Investimenti: valore corrente delle posizioni in EUR (costo medio + quantità attuale)
    $stmt = db()->prepare("SELECT id, currency FROM instruments WHERE user_id = :uid");
    $stmt->execute(['uid' => $uid]);
    $investmentsValue = 0.0;
    foreach ($stmt->fetchAll() as $instr) {
        $txStmt = db()->prepare("SELECT type, quantity, price_per_unit, fees FROM investment_transactions WHERE instrument_id = :id AND user_id = :uid ORDER BY date, id");
        $txStmt->execute(['id' => $instr['id'], 'uid' => $uid]);
        $qty = 0.0;
        foreach ($txStmt->fetchAll() as $t) {
            $qty += $t['type'] === 'acquisto' ? (float)$t['quantity'] : -(float)$t['quantity'];
        }
        if ($qty <= 0) continue;
        $priceStmt = db()->prepare("SELECT price FROM price_cache WHERE ticker = (SELECT ticker FROM instruments WHERE id = :id)");
        $priceStmt->execute(['id' => $instr['id']]);
        $price = (float)($priceStmt->fetchColumn() ?: 0);
        $value = $qty * $price;
        if ($instr['currency'] !== 'EUR') {
            $fx = fx_rate_to_eur($instr['currency']);
            if ($fx['rate'] !== null) $value *= $fx['rate'];
        }
        $investmentsValue += $value;
    }

    // Immobili, per la quota di proprietà se collegati a un mutuo
    $stmt = db()->prepare("SELECT * FROM real_estate_assets WHERE user_id = :uid");
    $stmt->execute(['uid' => $uid]);
    $realEstateValue = 0.0;
    $loanCache = [];
    foreach ($stmt->fetchAll() as $re) {
        $value = (float)$re['estimated_value'];
        if ($re['linked_loan_id']) {
            if (!isset($loanCache[$re['linked_loan_id']])) {
                $loanStmt = db()->prepare("SELECT ownership_pct FROM loans WHERE id = :id AND user_id = :uid");
                $loanStmt->execute(['id' => $re['linked_loan_id'], 'uid' => $uid]);
                $loanCache[$re['linked_loan_id']] = (float)($loanStmt->fetchColumn() ?: 100);
            }
            $value *= $loanCache[$re['linked_loan_id']] / 100;
        }
        $realEstateValue += $value;
    }

    // Debito residuo mutui, per la propria quota
    $stmt = db()->prepare("SELECT * FROM loans WHERE user_id = :uid AND status = 'attivo'");
    $stmt->execute(['uid' => $uid]);
    $debtTotal = 0.0;
    foreach ($stmt->fetchAll() as $loan) {
        $summary = loan_summary($loan);
        $debtTotal += $summary['personal_residual_today'];
    }

    $netWorth = $liquidBalance + $investmentsValue + $realEstateValue - $debtTotal;

    return [
        'net_worth' => round($netWorth, 2),
        'liquid_balance' => round($liquidBalance, 2),
        'investments_value' => round($investmentsValue, 2),
        'real_estate_value' => round($realEstateValue, 2),
        'debt_total' => round($debtTotal, 2),
    ];
}

/** Salva (o aggiorna) lo snapshot di oggi, per costruire lo storico nel tempo. */
function snapshot_net_worth(int $uid): void
{
    $nw = compute_net_worth($uid);
    db()->prepare("INSERT INTO net_worth_history (user_id, date, net_worth, liquid_balance, investments_value, real_estate_value, debt_total)
        VALUES (:uid, CURDATE(), :net_worth, :liquid, :invest, :re, :debt)
        ON DUPLICATE KEY UPDATE net_worth = :net_worth2, liquid_balance = :liquid2, investments_value = :invest2, real_estate_value = :re2, debt_total = :debt2")
        ->execute([
            'uid' => $uid,
            'net_worth' => $nw['net_worth'], 'liquid' => $nw['liquid_balance'], 'invest' => $nw['investments_value'], 're' => $nw['real_estate_value'], 'debt' => $nw['debt_total'],
            'net_worth2' => $nw['net_worth'], 'liquid2' => $nw['liquid_balance'], 'invest2' => $nw['investments_value'], 're2' => $nw['real_estate_value'], 'debt2' => $nw['debt_total'],
        ]);
}
