<?php
declare(strict_types=1);
require_once __DIR__ . '/lib_instrument_metrics.php'; // per closest_row_to()

/**
 * Motore della sezione "Previsioni" — a differenza di instrument_metrics.php
 * (sezione "Indicatori" di Investimenti), qui il numero prodotto È
 * deliberatamente predittivo: combina tre segnali in un'unica percentuale
 * di rendimento stimato a 12 mesi. Scelta consapevole del titolare del
 * progetto nonostante il principio "nessun punteggio combinato" scritto per
 * la sezione Indicatori — quel principio resta valido lì, non qui: le due
 * sezioni hanno uno scopo dichiarato opposto (fatti verificabili vs stima).
 * Va sempre presentato con disclaimer "non è consulenza finanziaria" e
 * scomposto nei tre ingredienti, mai come solo un numero secco.
 *
 * Pesi: trend storico 40%, upside da target price analisti 40%, momentum
 * (ultimi 3 mesi) 20%. Se un ingrediente non è disponibile per uno
 * strumento (es. ETF senza copertura analisti), il suo peso viene
 * ridistribuito proporzionalmente sugli ingredienti rimasti, invece di
 * trattarlo come zero (zero implicherebbe "nessun rendimento", non "dato
 * assente" — sono cose diverse).
 */
const FORECAST_WEIGHTS = ['trend' => 0.40, 'analyst_upside' => 0.40, 'momentum' => 0.20];

/**
 * Trend storico: CAGR sui migliori 3 anni disponibili (o su tutto lo
 * storico se meno di 3 anni sono disponibili — qui, a differenza di
 * Indicatori, è accettabile usare un periodo più corto come proxy, basta
 * dirlo in `basis`). Torna null se lo storico è troppo corto per essere
 * significativo (< 30 giorni).
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
 * Torna null se lo storico non copre nemmeno quella finestra — un momentum
 * su una finestra più corta di quella dichiarata sarebbe silenziosamente
 * un altro indicatore, non "gli ultimi 3 mesi".
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
 * vedi fetch_analyst_data) rispetto al prezzo corrente. Il target price di
 * Yahoo è convenzionalmente a 12 mesi, quindi è già sullo stesso orizzonte
 * del resto della previsione, senza bisogno di annualizzarlo.
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

/**
 * Combina i tre ingredienti in un'unica percentuale di rendimento stimato
 * a 12 mesi, ridistribuendo il peso di eventuali ingredienti assenti su
 * quelli disponibili. Torna `forecast_available = false` se nessun
 * ingrediente è calcolabile (storico troppo corto e nessuna copertura
 * analisti).
 */
function compute_instrument_forecast(array $rows, ?array $analyst, float $currentPrice): array
{
    $trend = forecast_trend_component($rows);
    $momentum = forecast_momentum_component($rows);
    $analystComp = forecast_analyst_component($analyst, $currentPrice);

    $available = [];
    if ($trend !== null) $available['trend'] = $trend['value_pct'];
    if ($analystComp !== null) $available['analyst_upside'] = $analystComp['value_pct'];
    if ($momentum !== null) $available['momentum'] = $momentum['value_pct'];

    if (!$available) {
        return [
            'forecast_available' => false,
            'message' => 'Dati insufficienti per generare una previsione per questo strumento (storico troppo corto e nessuna copertura analisti).',
        ];
    }

    $weightSum = 0.0;
    foreach (array_keys($available) as $key) $weightSum += FORECAST_WEIGHTS[$key];

    $forecastPct = 0.0;
    foreach ($available as $key => $valuePct) {
        $forecastPct += $valuePct * (FORECAST_WEIGHTS[$key] / $weightSum);
    }

    return [
        'forecast_available' => true,
        'forecast_pct' => round($forecastPct, 2),
        'horizon' => '12 mesi',
        'components' => [
            'trend' => $trend === null ? ['available' => false] : [
                'available' => true,
                'value_pct' => $trend['value_pct'],
                'weight_pct' => round((FORECAST_WEIGHTS['trend'] / $weightSum) * 100, 1),
                'basis' => $trend['basis'],
            ],
            'analyst_upside' => $analystComp === null ? ['available' => false] : [
                'available' => true,
                'value_pct' => $analystComp['value_pct'],
                'weight_pct' => round((FORECAST_WEIGHTS['analyst_upside'] / $weightSum) * 100, 1),
                'target_mean_price' => $analystComp['target_mean_price'],
                'number_of_analysts' => $analystComp['number_of_analysts'],
            ],
            'momentum' => $momentum === null ? ['available' => false] : [
                'available' => true,
                'value_pct' => $momentum['value_pct'],
                'weight_pct' => round((FORECAST_WEIGHTS['momentum'] / $weightSum) * 100, 1),
                'window_days' => $momentum['window_days'],
            ],
        ],
    ];
}
