<?php
declare(strict_types=1);

/**
 * Saldo reale di un conto (saldo iniziale ± transazioni), stessa formula
 * usata in accounts.php (BALANCE_SQL) e lib_networth.php. Qui per un singolo
 * conto alla volta, con verifica di appartenenza inclusa: torna 0.0 se il
 * conto non esiste o non è dell'utente. Condivisa da lib_goals.php.
 */
function account_balance(int $accountId, int $uid): float
{
    $stmt = db()->prepare("
        SELECT
            a.initial_balance
            + COALESCE((SELECT SUM(amount) FROM transactions WHERE account_id = a.id AND type = 'entrata'), 0)
            - COALESCE((SELECT SUM(amount) FROM transactions WHERE account_id = a.id AND type = 'uscita'), 0)
            - COALESCE((SELECT SUM(amount) FROM transactions WHERE account_id = a.id AND type = 'trasferimento'), 0)
            + COALESCE((SELECT SUM(amount) FROM transactions WHERE transfer_account_id = a.id AND type = 'trasferimento'), 0)
            AS balance
        FROM accounts a WHERE a.id = :id AND a.user_id = :uid
    ");
    $stmt->execute(['id' => $accountId, 'uid' => $uid]);
    $balance = $stmt->fetchColumn();
    return $balance === false ? 0.0 : (float)$balance;
}
