<?php
declare(strict_types=1);
require __DIR__ . '/db.php';
require_once __DIR__ . '/lib_forecast_model.php';

const FORECAST_RANKING_SIZE = 10;

$uid = require_auth();

if (method() !== 'GET') respond_error('Metodo non supportato', 405);

$universe = forecast_universe_computed(db(), $uid);
$globalMeta = $universe['global_meta'];
$computed = $universe['computed'];

$portfolio = [];
foreach ($universe['my_instruments'] as $inst) {
    $portfolio[] = array_merge($computed[$inst['ticker']], [
        'instrument_id' => (int)$inst['id'],
        'name' => $inst['name'],
        'type' => $inst['type'],
        // La valuta scelta dall'utente per il proprio strumento prevale su
        // quella (eventualmente diversa) letta dalla cache prezzi globale.
        'currency' => $inst['currency'],
    ]);
}

/**
 * `projected_price` va incluso SOLO per le classifiche di previsione: è il
 * prezzo attuale proiettato secondo la stima del modello, così in UI non si
 * legge "+119%" senza sapere se si riferisce al prezzo o a qualcos'altro
 * (ambiguità segnalata dall'utente). Per "andamento storico" non ha senso
 * (value_pct lì è un CAGR passato, non una proiezione) e va omesso.
 */
function ranking_entry(string $ticker, array $c, array $globalMeta, float $valuePct, bool $withProjection): array
{
    $meta = $globalMeta[$ticker] ?? ['name' => $ticker, 'type' => 'altro'];
    $entry = [
        'ticker' => $ticker,
        'name' => $meta['name'],
        'type' => $meta['type'],
        'current_price' => $c['current_price'],
        'currency' => $c['currency'],
        'value_pct' => round($valuePct, 2),
    ];
    if ($withProjection && $c['current_price'] > 0) {
        $entry['projected_price'] = round($c['current_price'] * (1 + $valuePct / 100), 4);
    }
    return $entry;
}

$currentCandidates = [];
$forecastCandidates = [];
foreach ($computed as $ticker => $c) {
    if (!isset($globalMeta[$ticker])) continue; // non tracciato da nessun instrument né dalla lista curata (es. coppie FX interne)
    if ($c['trend'] !== null) $currentCandidates[] = ranking_entry($ticker, $c, $globalMeta, $c['trend']['value_pct'], false);
    if ($c['forecast_available']) $forecastCandidates[] = ranking_entry($ticker, $c, $globalMeta, $c['forecast_pct'], true);
}

usort($currentCandidates, fn($a, $b) => $b['value_pct'] <=> $a['value_pct']);
usort($forecastCandidates, fn($a, $b) => $b['value_pct'] <=> $a['value_pct']);

respond([
    'portfolio' => $portfolio,
    'rankings' => [
        'best_current' => array_slice($currentCandidates, 0, FORECAST_RANKING_SIZE),
        'worst_current' => array_slice(array_reverse($currentCandidates), 0, FORECAST_RANKING_SIZE),
        'best_forecast' => array_slice($forecastCandidates, 0, FORECAST_RANKING_SIZE),
        'worst_forecast' => array_slice(array_reverse($forecastCandidates), 0, FORECAST_RANKING_SIZE),
    ],
]);
