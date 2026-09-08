<?php
declare(strict_types=1);
require_once __DIR__ . '/lib_instrument_metrics.php';  // closest_row_to(), historical_volatility_pct(), historical_max_drawdown()

/**
 * Componenti "grezze" (feature) della sezione Previsioni. Il numero finale
 * mostrato all'utente viene prodotto applicando il modello appreso in
 * lib_forecast_model.php a queste feature — vedi quel file per il perché
 * (regressione addestrata su backtest storico, non pesi decisi a mano).
 * Le funzioni qui sotto restano riusate sia in inferenza (qui) sia in
 * training (lib_forecast_model.php le richiama sui prefissi storici).
 */

/**
 * Trend storico: CAGR sui migliori 3 anni disponibili (o su tutto lo
 * storico se meno di 3 anni sono disponibili, dichiarandolo in `basis`).
 * Torna null se lo storico è troppo corto per essere significativo
 * (< 30 giorni).
 */
function forecast_trend_component(array $rows): ?array
{
    $n = count($rows);
    if ($n < 2) return null;

    $first = $rows[0];
    $last = $rows[$n - 1];
    $lastDate = $last['date'];
    $lastPrice = (float)$last['price'];
    $spanDays = (int)((strtotime($lastDate) - strtotime($first['date'])) / 86400);
    if ($spanDays < 30) return null;

    $requiredDays = 3 * 365;
    if ($spanDays >= $requiredDays) {
        $startRow = closest_row_to($rows, strtotime($lastDate) - $requiredDays * 86400);
        $basis = 'CAGR storico sui 3 anni';
    } else {
        $startRow = $first;
        $basis = "CAGR storico sui {$spanDays} giorni disponibili (meno di 3 anni)";
    }

    $startPrice = (float)$startRow['price'];
    $actualDays = max(1, (int)((strtotime($lastDate) - strtotime($startRow['date'])) / 86400));

    return [
        'value_pct' => round(((($lastPrice / $startPrice) ** (365 / $actualDays)) - 1) * 100, 2),
        'basis' => $basis,
    ];
}

/**
 * Momentum: variazione di prezzo (non annualizzata) sugli ultimi N giorni.
 * Torna null se lo storico non copre nemmeno quella finestra.
 */
function forecast_momentum_component(array $rows, int $windowDays = 90): ?array
{
    $n = count($rows);
    if ($n < 2) return null;

    $last = $rows[$n - 1];
    $lastTs = strtotime($last['date']);
    $windowStartTs = $lastTs - $windowDays * 86400;
    if (strtotime($rows[0]['date']) > $windowStartTs) return null;

    $startRow = closest_row_to($rows, $windowStartTs);
    $startPrice = (float)$startRow['price'];
    if ($startPrice <= 0) return null;

    return [
        'value_pct' => round(((float)$last['price'] / $startPrice - 1) * 100, 2),
        'window_days' => $windowDays,
        'start_date' => $startRow['date'],
    ];
}

/**
 * Upside implicito dal target price medio degli analisti (dato di terzi,
 * vedi fetch_analyst_data) rispetto al prezzo corrente. NON entra nel
 * modello addestrato (nessuno storico di target price salvato per
 * allenarci sopra onestamente, vedi lib_forecast_model.php): resta un
 * riferimento informativo separato, mostrato ma non fuso nella stima.
 */
function forecast_analyst_component(?array $analyst, float $currentPrice): ?array
{
    if ($analyst === null || $analyst['target_mean_price'] === null || $currentPrice <= 0) return null;

    return [
        'value_pct' => round(($analyst['target_mean_price'] / $currentPrice - 1) * 100, 2),
        'target_mean_price' => $analyst['target_mean_price'],
        'number_of_analysts' => $analyst['number_of_analysts'],
    ];
}
