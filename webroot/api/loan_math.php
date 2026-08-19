<?php
declare(strict_types=1);

/**
 * Calcola la rata mensile con il metodo di ammortamento "alla francese"
 * (rata costante), lo standard usato dai mutui in Italia. $years puo' essere
 * frazionario: e' cosi' che simulate_amortization() ricalcola la rata su un
 * numero di mesi residui che non e' un multiplo esatto di 12.
 */
function loan_monthly_payment(float $principal, float $annualRatePct, float $years): float
{
    $i = $annualRatePct / 100 / 12;
    $n = (int)round($years * 12);
    if ($n <= 0) return 0.0;
    if ($i == 0.0) return $principal / $n;
    return $principal * $i / (1 - ($i + 1) ** (-$n));
}

/** Numero di rate mensili intere trascorse tra start_date e una data di riferimento. */
function loan_elapsed_months(string $startDate, string $refDate): int
{
    $start = new DateTime($startDate);
    $ref = new DateTime($refDate);
    if ($ref <= $start) return 0;
    $diff = $start->diff($ref);
    return $diff->y * 12 + $diff->m;
}

/** Storico dei tassi di un mutuo, ordinato per data di decorrenza. */
function loan_load_rate_history(int $loanId): array
{
    $stmt = db()->prepare("SELECT effective_date, rate FROM loan_rate_history WHERE loan_id = :id ORDER BY effective_date ASC");
    $stmt->execute(['id' => $loanId]);
    return $stmt->fetchAll();
}

/** Tasso in vigore a una certa data, secondo lo storico (l'ultima variazione con effective_date <= $date). */
function loan_rate_at(array $rateHistory, string $date): float
{
    $rate = (float)$rateHistory[0]['rate'];
    foreach ($rateHistory as $r) {
        if ($r['effective_date'] > $date) break;
        $rate = (float)$r['rate'];
    }
    return $rate;
}

/**
 * Simulazione mese per mese dell'ammortamento, usando il tasso realmente in
 * vigore in ciascun mese secondo $rateHistory. Per un mutuo a tasso fisso
 * (un solo tasso in tutto lo storico) il risultato e' identico a una formula
 * chiusa; per uno variabile, quando il tasso cambia la rata viene ricalcolata
 * sul capitale residuo e sui mesi rimanenti, mantenendo fissa la data di
 * scadenza originale (stesso comportamento delle banche italiane).
 *
 * Simula al massimo fino al mese $totalMonths, fermandosi prima se si supera
 * $asOfDate (usare una data lontanissima per ottenere il piano completo fino
 * a scadenza, comprese le proiezioni future su eventuali variazioni di tasso
 * gia' inserite con data futura).
 */
function simulate_amortization(float $principal, array $rateHistory, string $startDate, int $totalMonths, string $asOfDate): array
{
    $balance = $principal;
    $date = new DateTime($startDate);
    $schedule = [];
    $payment = 0.0;
    $prevRate = null;

    for ($month = 1; $month <= $totalMonths; $month++) {
        $date->modify('+1 month');
        $dateStr = $date->format('Y-m-d');
        if ($dateStr > $asOfDate) break;

        $rate = loan_rate_at($rateHistory, $dateStr);
        if ($prevRate === null || $rate !== $prevRate) {
            $remainingMonths = $totalMonths - $month + 1;
            $payment = loan_monthly_payment($balance, $rate, $remainingMonths / 12);
        }
        $prevRate = $rate;

        $interest = $balance * ($rate / 100 / 12);
        $principalPortion = min($balance, $payment - $interest);
        $balance = max(0.0, $balance - $principalPortion);

        $schedule[] = [
            'month_number' => $month,
            'date' => $dateStr,
            'rate_used' => $rate,
            'payment' => round($payment, 2),
            'principal_portion' => round($principalPortion, 2),
            'interest_portion' => round($interest, 2),
            'balance_after' => round($balance, 2),
        ];
    }

    return $schedule;
}

/**
 * Rate effettivamente registrate come transazioni (non calcolate dal calendario):
 * conta le transazioni collegate alla rata ricorrente di questo mutuo, se presente.
 * E' il "dato certo" da confrontare con la proiezione teorica.
 */
function loan_actual_payments(array $loan): array
{
    if (empty($loan['recurring_id'])) {
        return ['count' => null, 'amount_paid' => null];
    }
    $stmt = db()->prepare("SELECT COUNT(*) AS cnt, COALESCE(SUM(amount), 0) AS total
        FROM transactions WHERE recurring_id = :rid AND user_id = :uid AND type = 'uscita'");
    $stmt->execute(['rid' => $loan['recurring_id'], 'uid' => $loan['user_id']]);
    $row = $stmt->fetch();
    return ['count' => (int)$row['cnt'], 'amount_paid' => round((float)$row['total'], 2)];
}

/** Storico tassi del mutuo, con un fallback su annual_rate se per qualche motivo e' vuoto. */
function loan_rate_history_for(array $loan): array
{
    $history = loan_load_rate_history((int)$loan['id']);
    if (!$history) {
        $history = [['effective_date' => $loan['start_date'], 'rate' => $loan['annual_rate']]];
    }
    return $history;
}

/**
 * Scompone il "dato certo" (rate effettivamente registrate) in quota capitale
 * e quota interessi cumulati, con le relative percentuali sul totale del mutuo.
 * Usa i tassi realmente in vigore mese per mese (loan_rate_history), non un
 * tasso costante. Riutilizzata sia dalla lista mutui (per la dashboard) sia
 * dal dettaglio.
 */
function loan_actual_breakdown(array $loan): array
{
    $actual = loan_actual_payments($loan);
    if ($actual['count'] === null) {
        return $actual + ['interest_paid' => null, 'capital_paid' => null, 'interest_pct_of_total' => null, 'capital_pct_of_total' => null];
    }

    $n = (int)$loan['duration_years'] * 12;
    $rateHistory = loan_rate_history_for($loan);
    $fullSchedule = simulate_amortization((float)$loan['principal'], $rateHistory, $loan['start_date'], $n, '9999-12-31');

    $paidRows = array_slice($fullSchedule, 0, min($actual['count'], $n));
    $interestPaid = array_sum(array_column($paidRows, 'interest_portion'));
    $capitalPaid = array_sum(array_column($paidRows, 'principal_portion'));
    $totalInterest = array_sum(array_column($fullSchedule, 'interest_portion'));

    $actual['interest_paid'] = round($interestPaid, 2);
    $actual['capital_paid'] = round($capitalPaid, 2);
    $actual['interest_pct_of_total'] = $totalInterest > 0 ? round($interestPaid / $totalInterest * 100, 1) : 0;
    $actual['capital_pct_of_total'] = (float)$loan['principal'] > 0 ? round($capitalPaid / (float)$loan['principal'] * 100, 1) : 0;

    $ownershipPct = isset($loan['ownership_pct']) ? (float)$loan['ownership_pct'] : 100.0;
    $actual['personal_interest_paid'] = round($interestPaid * $ownershipPct / 100, 2);
    $actual['personal_capital_paid'] = round($capitalPaid * $ownershipPct / 100, 2);
    return $actual;
}

/**
 * Riepilogo utile per la lista mutui e il dettaglio. Il residuo, la rata e il
 * tasso "attuali" derivano dalla simulazione mese per mese fino ad oggi (o
 * alla data di estinzione anticipata), quindi riflettono le variazioni di
 * tasso realmente avvenute per un mutuo variabile. total_interest proietta
 * invece l'intero piano fino a scadenza, assumendo che l'ultimo tasso noto
 * resti costante da oggi in avanti (unica ipotesi possibile per un tasso
 * futuro non ancora determinato).
 */
function loan_summary(array $loan): array
{
    $n = (int)$loan['duration_years'] * 12;
    $today = date('Y-m-d');
    $refDate = $loan['status'] === 'estinto' && $loan['early_payoff_date'] ? $loan['early_payoff_date'] : $today;

    $rateHistory = loan_rate_history_for($loan);
    $schedule = simulate_amortization((float)$loan['principal'], $rateHistory, $loan['start_date'], $n, $refDate);
    $elapsed = count($schedule);
    $last = $elapsed > 0 ? $schedule[$elapsed - 1] : null;

    $residualToday = $loan['status'] === 'estinto' ? 0.0 : ($last ? $last['balance_after'] : (float)$loan['principal']);
    $currentRate = $last ? $last['rate_used'] : (float)$loan['annual_rate'];
    $monthlyPayment = $last ? $last['payment'] : loan_monthly_payment((float)$loan['principal'], (float)$loan['annual_rate'], (float)$loan['duration_years']);

    $fullSchedule = simulate_amortization((float)$loan['principal'], $rateHistory, $loan['start_date'], $n, '9999-12-31');
    $totalInterest = array_sum(array_column($fullSchedule, 'interest_portion'));
    $endDate = (new DateTime($loan['start_date']))->modify("+{$n} months")->format('Y-m-d');
    $ownershipPct = isset($loan['ownership_pct']) ? (float)$loan['ownership_pct'] : 100.0;

    return [
        'monthly_payment' => round($monthlyPayment, 2),
        'total_payments' => $n,
        'elapsed_payments' => min($elapsed, $n),
        'end_date' => $endDate,
        'residual_today' => round($residualToday, 2),
        'total_interest' => round($totalInterest, 2),
        'interest_paid_to_date' => round(array_sum(array_column($schedule, 'interest_portion')), 2),
        'capital_paid_to_date' => round(array_sum(array_column($schedule, 'principal_portion')), 2),
        'progress_pct' => $n > 0 ? round(min($elapsed, $n) / $n * 100, 1) : 0,
        'ownership_pct' => $ownershipPct,
        'rate_type' => $loan['rate_type'] ?? 'fisso',
        'current_rate' => round($currentRate, 3),
        'personal_monthly_payment' => round($monthlyPayment * $ownershipPct / 100, 2),
        'personal_residual_today' => round($residualToday * $ownershipPct / 100, 2),
        'personal_total_interest' => round($totalInterest * $ownershipPct / 100, 2),
    ];
}
