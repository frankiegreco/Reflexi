<?php
declare(strict_types=1);

/** Storico dei tassi di un conto, ordinato per data di decorrenza. */
function account_load_rate_history(int $accountId): array
{
    $stmt = db()->prepare("SELECT effective_date, rate FROM account_interest_rate_history WHERE account_id = :id ORDER BY effective_date ASC");
    $stmt->execute(['id' => $accountId]);
    return $stmt->fetchAll();
}

/** Tasso in vigore a una certa data, secondo lo storico (l'ultima variazione con effective_date <= $date). */
function account_rate_at(array $rateHistory, string $date): float
{
    $rate = (float)$rateHistory[0]['rate'];
    foreach ($rateHistory as $r) {
        if ($r['effective_date'] > $date) break;
        $rate = (float)$r['rate'];
    }
    return $rate;
}

/**
 * Tasso realmente in vigore oggi per un conto, secondo lo storico — mai la
 * colonna accounts.interest_rate presa da sola, che rappresenta solo il
 * valore inserito l'ultima volta dal form principale e puo' disallinearsi
 * dallo storico quando un periodo si aggiunge/rimuove dalla sezione dedicata
 * (es. un periodo futuro che nel frattempo e' entrato in vigore). Fallback
 * sulla colonna solo se per qualche motivo lo storico e' vuoto.
 */
function account_current_rate(array $account): float
{
    $history = account_load_rate_history((int)$account['id']);
    if (!$history) return (float)$account['interest_rate'];
    return account_rate_at($history, date('Y-m-d'));
}

/**
 * Saldo reale del conto a fine di una certa data (saldo iniziale ± transazioni
 * con data <= $date). Usato per calcolare l'interesse di un periodo arretrato
 * sul saldo che il conto aveva davvero in quel momento, non su quello di oggi
 * — coerente col fatto che anche il tasso è storicizzato per periodo.
 */
function account_balance_at(int $accountId, string $date): float
{
    $stmt = db()->prepare("
        SELECT a.initial_balance
            + COALESCE((SELECT SUM(amount) FROM transactions WHERE account_id = a.id AND type = 'entrata' AND date <= :d1), 0)
            - COALESCE((SELECT SUM(amount) FROM transactions WHERE account_id = a.id AND type = 'uscita' AND date <= :d2), 0)
            - COALESCE((SELECT SUM(amount) FROM transactions WHERE account_id = a.id AND type = 'trasferimento' AND date <= :d3), 0)
            + COALESCE((SELECT SUM(amount) FROM transactions WHERE transfer_account_id = a.id AND type = 'trasferimento' AND date <= :d4), 0)
            AS balance
        FROM accounts a WHERE a.id = :id");
    $stmt->execute(['id' => $accountId, 'd1' => $date, 'd2' => $date, 'd3' => $date, 'd4' => $date]);
    return (float)$stmt->fetchColumn();
}
