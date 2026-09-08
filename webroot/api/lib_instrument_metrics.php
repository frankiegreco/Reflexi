<?php
declare(strict_types=1);

/**
 * Calcola le metriche storiche della sezione "Indicatori" di uno strumento,
 * su una serie di prezzi giornalieri (array di ['date' => 'Y-m-d', 'price' => float],
 * ordinata per data crescente). Sono tutti calcoli standard di finanza
 * quantitativa applicati al passato — nessuno predice il futuro, vedi
 * spec_indicatori_mercato.md ("principio non negoziabile").
 */
function compute_instrument_metrics(array $rows, float $riskFreeRatePct = 2.0): array
{
    $n = count($rows);
    if ($n < 2) {
        return [
            'insufficient_history' => true,
            'data_points' => $n,
            'message' => 'Storico ancora insufficiente per calcolare questi indicatori.',
        ];
    }

    $first = $rows[0];
    $last = $rows[$n - 1];
    $lastDate = $last['date'];
    $lastPrice = (float)$last['price'];
    $spanDays = (int)((strtotime($lastDate) - strtotime($first['date'])) / 86400);

    if ($spanDays < 30) {
        return [
            'insufficient_history' => true,
            'data_points' => $n,
            'span_days' => $spanDays,
            'message' => "Storico ancora insufficiente per calcolare questi indicatori (solo {$spanDays} giorni disponibili, ne servono almeno 30).",
        ];
    }

    // 1. Rendimento totale e annualizzato su 1/3/5 anni, solo se c'è
    // davvero abbastanza storico per ciascun periodo (mai calcolare su un
    // periodo diverso da quello richiesto senza dirlo).
    $returns = [];
    foreach (['1y' => 1, '3y' => 3, '5y' => 5] as $key => $years) {
        $requiredDays = $years * 365;
        if ($spanDays < $requiredDays) {
            $returns[$key] = [
                'available' => false,
                'required_days' => $requiredDays,
                'available_days' => $spanDays,
                'message' => "Servono almeno {$years} " . ($years === 1 ? 'anno' : 'anni')
                    . " di storico, disponibili solo {$spanDays} giorni.",
            ];
            continue;
        }

        $targetTs = strtotime($lastDate) - $requiredDays * 86400;
        $startRow = closest_row_to($rows, $targetTs);
        $startPrice = (float)$startRow['price'];
        $actualDays = max(1, (int)((strtotime($lastDate) - strtotime($startRow['date'])) / 86400));

        $returns[$key] = [
            'available' => true,
            'total_return_pct' => round(($lastPrice / $startPrice - 1) * 100, 2),
            'annualized_return_pct' => round(((($lastPrice / $startPrice) ** (365 / $actualDays)) - 1) * 100, 2),
            'start_date' => $startRow['date'],
            'end_date' => $lastDate,
        ];
    }

    // 2. Volatilità annualizzata (vedi historical_volatility_pct) sulla
    // finestra più recente disponibile, fino a 1 anno.
    $vol = historical_volatility_pct($rows, 365);
    $volatilityPct = $vol['annualized_pct'] ?? null;
    $windowDays = $vol['window_days'] ?? min($spanDays, 365);
    $windowRows = $vol['window_rows'];

    // Rendimento annualizzato sulla stessa finestra della volatilità, per
    // avere due grandezze comparabili nel calcolo dello Sharpe più sotto.
    $windowAnnualizedReturnPct = null;
    if (count($windowRows) >= 2) {
        $windowFirstPrice = (float)$windowRows[0]['price'];
        $windowActualDays = max(1, (int)((strtotime($lastDate) - strtotime($windowRows[0]['date'])) / 86400));
        $windowAnnualizedReturnPct = ((($lastPrice / $windowFirstPrice) ** (365 / $windowActualDays)) - 1) * 100;
    }

    // 3. Max drawdown (vedi historical_max_drawdown): il calo più grande dal
    // punto più alto raggiunto al punto più basso successivo, su tutto lo
    // storico disponibile.
    $dd = historical_max_drawdown($rows);

    // 4. Sharpe ratio approssimato: (rendimento annualizzato - tasso privo
    // di rischio ipotizzato) / volatilità annualizzata, sulla stessa finestra.
    $sharpe = null;
    if ($volatilityPct !== null && $volatilityPct > 0 && $windowAnnualizedReturnPct !== null) {
        $sharpe = round(($windowAnnualizedReturnPct - $riskFreeRatePct) / $volatilityPct, 2);
    }

    return [
        'insufficient_history' => false,
        'data_points' => $n,
        'span_days' => $spanDays,
        'first_date' => $first['date'],
        'last_date' => $lastDate,
        'returns' => $returns,
        'volatility' => $volatilityPct === null ? null : [
            'annualized_pct' => $volatilityPct,
            'window_days' => $windowDays,
            'data_points' => count($windowRows),
        ],
        'max_drawdown' => [
            'pct' => $dd['pct'],
            'peak_date' => $dd['peak_date'],
            'trough_date' => $dd['trough_date'],
        ],
        'sharpe_ratio' => $sharpe === null ? null : [
            'value' => $sharpe,
            'risk_free_rate_pct' => $riskFreeRatePct,
            'annualized_return_pct_used' => round($windowAnnualizedReturnPct, 2),
            'window_days' => $windowDays,
        ],
    ];
}

/**
 * Volatilità annualizzata: deviazione standard dei rendimenti giornalieri
 * sulla finestra più recente disponibile (fino a $maxWindowDays giorni),
 * annualizzata con la formula standard (radice di 252 giorni di borsa).
 * Estratta da compute_instrument_metrics() perché riusata anche dal
 * training del modello di previsione (lib_forecast_model.php), che ne ha
 * bisogno come feature calcolata "a una data storica" per ogni campione.
 */
function historical_volatility_pct(array $rows, int $maxWindowDays = 365): array
{
    $n = count($rows);
    if ($n < 2) return ['annualized_pct' => null, 'window_days' => 0, 'window_rows' => $rows];

    $lastDate = $rows[$n - 1]['date'];
    $spanDays = (int)((strtotime($lastDate) - strtotime($rows[0]['date'])) / 86400);
    $windowDays = min($spanDays, $maxWindowDays);
    $windowStartTs = strtotime($lastDate) - $windowDays * 86400;
    $windowRows = array_values(array_filter($rows, fn($r) => strtotime($r['date']) >= $windowStartTs));

    $dailyReturns = [];
    for ($i = 1; $i < count($windowRows); $i++) {
        $p0 = (float)$windowRows[$i - 1]['price'];
        $p1 = (float)$windowRows[$i]['price'];
        if ($p0 > 0) $dailyReturns[] = ($p1 / $p0) - 1;
    }

    if (count($dailyReturns) < 2) return ['annualized_pct' => null, 'window_days' => $windowDays, 'window_rows' => $windowRows];

    $mean = array_sum($dailyReturns) / count($dailyReturns);
    $variance = array_sum(array_map(fn($r) => ($r - $mean) ** 2, $dailyReturns)) / (count($dailyReturns) - 1);

    return [
        'annualized_pct' => round(sqrt($variance) * sqrt(252) * 100, 2),
        'window_days' => $windowDays,
        'window_rows' => $windowRows,
    ];
}

/**
 * Max drawdown: il calo percentuale più grande dal punto più alto
 * raggiunto al punto più basso successivo, su tutto lo storico passato.
 * Estratta per lo stesso motivo di historical_volatility_pct() sopra.
 */
function historical_max_drawdown(array $rows): array
{
    $peak = (float)$rows[0]['price'];
    $peakDate = $rows[0]['date'];
    $maxDrawdownPct = 0.0;
    $ddPeakDate = $peakDate;
    $ddTroughDate = $peakDate;
    foreach ($rows as $r) {
        $p = (float)$r['price'];
        if ($p > $peak) { $peak = $p; $peakDate = $r['date']; }
        $drawdown = ($p / $peak - 1) * 100;
        if ($drawdown < $maxDrawdownPct) {
            $maxDrawdownPct = $drawdown;
            $ddPeakDate = $peakDate;
            $ddTroughDate = $r['date'];
        }
    }
    return ['pct' => round($maxDrawdownPct, 2), 'peak_date' => $ddPeakDate, 'trough_date' => $ddTroughDate];
}

/** Riga con la data più vicina a un timestamp target, in un array ordinato per data crescente. */
function closest_row_to(array $rows, int $targetTs): array
{
    $best = $rows[0];
    $bestDiff = PHP_INT_MAX;
    foreach ($rows as $r) {
        $diff = abs(strtotime($r['date']) - $targetTs);
        if ($diff < $bestDiff) { $bestDiff = $diff; $best = $r; }
    }
    return $best;
}
