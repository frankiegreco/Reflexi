<?php
declare(strict_types=1);

/**
 * Capitale finale dopo $months mesi di crescita composta con versamento
 * mensile costante. Condivisa da pension_funds.php e goal_plan.php.
 */
function compound_growth_project(float $balance, float $monthlyContribution, float $annualReturn, int $months): float
{
    $monthlyRate = ($annualReturn / 100) / 12;
    for ($i = 0; $i < $months; $i++) {
        $balance = $balance * (1 + $monthlyRate) + $monthlyContribution;
    }
    return $balance;
}

/**
 * Mesi necessari per raggiungere $target con crescita composta e versamento
 * mensile costante, o null se non raggiungibile entro $maxMonths (limite di
 * sicurezza contro loop infiniti quando contributo e rendimento sono entrambi
 * nulli/negativi). Condivisa da fire.php e goal_plan.php.
 */
function compound_growth_months_to_target(float $balance, float $monthlyContribution, float $annualReturn, float $target, int $maxMonths = 1200): ?int
{
    if ($balance >= $target) return 0;
    $monthlyRate = ($annualReturn / 100) / 12;
    $months = 0;
    while ($balance < $target && $months < $maxMonths) {
        $balance = $balance * (1 + $monthlyRate) + $monthlyContribution;
        $months++;
        if ($monthlyContribution <= 0 && $monthlyRate <= 0) break;
    }
    return $balance >= $target ? $months : null;
}
