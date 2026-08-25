<?php
declare(strict_types=1);

// Categorie che non rappresentano vero consumo/risparmio: spostamenti di
// capitale (acquisto/vendita investimenti), non entrate o uscite reali.
// Condivisa da fire.php, savings_goals.php (via lib_goals.php) e goal_plan.php.
const SPENDING_EXCLUDED_CATEGORIES = ['Investimenti (acquisto)', 'Investimenti (vendita)'];

/**
 * Entrata/uscita/risparmio medio mensile reale sugli ultimi $months mesi di
 * storico disponibili (esclusi investimenti e trasferimenti). Se l'utente ha
 * meno storico di $months mesi, usa quello disponibile invece di richiederne
 * esattamente $months — months_with_data segnala quanti mesi ha davvero usato.
 */
function compute_avg_monthly_savings(int $uid, int $months = 12): array
{
    $placeholders = implode(',', array_fill(0, count(SPENDING_EXCLUDED_CATEGORIES), '?'));
    $stmt = db()->prepare("
        SELECT
            COALESCE(SUM(CASE WHEN t.type = 'uscita' THEN t.amount ELSE 0 END), 0) AS total_expense,
            COALESCE(SUM(CASE WHEN t.type = 'entrata' THEN t.amount ELSE 0 END), 0) AS total_income,
            COUNT(DISTINCT DATE_FORMAT(t.date, '%Y-%m')) AS months_with_data
        FROM transactions t
        JOIN categories c ON c.id = t.category_id
        WHERE t.user_id = ? AND t.date >= DATE_SUB(CURDATE(), INTERVAL ? MONTH) AND t.type IN ('entrata','uscita')
        AND c.name NOT IN ($placeholders)
    ");
    $stmt->execute(array_merge([$uid, $months], SPENDING_EXCLUDED_CATEGORIES));
    $row = $stmt->fetch();
    $monthsWithData = max(1, (int)$row['months_with_data']);
    $monthlyExpense = (float)$row['total_expense'] / $monthsWithData;
    $monthlyIncome = (float)$row['total_income'] / $monthsWithData;

    return [
        'monthly_expense' => $monthlyExpense,
        'monthly_income' => $monthlyIncome,
        'monthly_savings' => $monthlyIncome - $monthlyExpense,
        'months_with_data' => $monthsWithData,
    ];
}
