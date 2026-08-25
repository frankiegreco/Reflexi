<?php
declare(strict_types=1);
require_once __DIR__ . '/lib_savings.php';
require_once __DIR__ . '/lib_accounts.php';

/**
 * Progresso reale verso un obiettivo di risparmio: somma di entrate-uscite
 * (esclusi investimenti e trasferimenti) dalla data di inizio ad oggi.
 * Condivisa da savings_goals.php e goal_plan.php.
 */
function goal_progress(int $uid, string $startDate): float
{
    $placeholders = implode(',', array_fill(0, count(SPENDING_EXCLUDED_CATEGORIES), '?'));
    $stmt = db()->prepare("
        SELECT COALESCE(SUM(CASE WHEN t.type = 'entrata' THEN t.amount ELSE -t.amount END), 0) AS net
        FROM transactions t
        JOIN categories c ON c.id = t.category_id
        WHERE t.user_id = ? AND t.date >= ? AND t.type IN ('entrata','uscita')
        AND c.name NOT IN ($placeholders)
    ");
    $stmt->execute(array_merge([$uid, $startDate], SPENDING_EXCLUDED_CATEGORIES));
    return (float)$stmt->fetchColumn();
}

/**
 * Importo attuale verso un obiettivo: se l'obiettivo è collegato a un conto
 * dedicato (l'utente vi trasferisce i soldi che vuole accantonare), il
 * progresso è il saldo reale di quel conto; altrimenti resta il calcolo
 * automatico entrate-uscite dalla data di inizio (comportamento originale,
 * per tutti gli obiettivi non collegati a un conto). Condivisa da
 * savings_goals.php e goal_plan.php.
 */
function goal_current_amount(int $uid, array $goal): float
{
    if (!empty($goal['account_id'])) {
        return account_balance((int)$goal['account_id'], $uid);
    }
    return goal_progress($uid, $goal['start_date']);
}
