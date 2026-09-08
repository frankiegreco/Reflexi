<?php
declare(strict_types=1);
require __DIR__ . '/db.php';
require __DIR__ . '/lib_forecast_model.php';

const FORECAST_RANKING_SIZE = 10;

$uid = require_auth();

if (method() !== 'GET') respond_error('Metodo non supportato', 405);

$stmt = db()->prepare("SELECT id, ticker, name, type, currency FROM instruments WHERE user_id = :uid ORDER BY ticker");
$stmt->execute(['uid' => $uid]);
$myInstruments = $stmt->fetchAll();

// Universo per le classifiche "migliori/peggiori": ticker distinti tracciati
// da TUTTI gli utenti (stesso approccio di market_trends.php::top_movers —
// solo ticker/nome/tipo aggregati, nessun dato che identifichi chi li
// possiede), non solo il portafoglio di chi guarda la pagina.
$globalRows = db()->query("SELECT ticker, MIN(name) AS name, MIN(type) AS type
    FROM instruments GROUP BY ticker")->fetchAll();
$globalMeta = [];
foreach ($globalRows as $r) $globalMeta[$r['ticker']] = $r;

// Caricato una volta sola per la richiesta, non per ogni ticker: è lo
// stesso modello per tutti.
$model = latest_forecast_model(db());

// Calcolo per ticker fatto una sola volta anche se un ticker compare sia nel
// portafoglio dell'utente sia nell'universo globale delle classifiche.
$computed = [];
function get_computed(string $ticker, ?array $model, array &$computed): array
{
    if (!isset($computed[$ticker])) $computed[$ticker] = compute_forecast_for_ticker(db(), $ticker, $model);
    return $computed[$ticker];
}

$portfolio = [];
foreach ($myInstruments as $inst) {
    $c = get_computed($inst['ticker'], $model, $computed);
    $portfolio[] = array_merge($c, [
        'instrument_id' => (int)$inst['id'],
        'name' => $inst['name'],
        'type' => $inst['type'],
        // La valuta scelta dall'utente per il proprio strumento prevale su
        // quella (eventualmente diversa) letta dalla cache prezzi globale.
        'currency' => $inst['currency'],
    ]);
}

foreach (array_keys($globalMeta) as $ticker) get_computed($ticker, $model, $computed);

function ranking_entry(string $ticker, array $c, array $globalMeta, float $valuePct): array
{
    $meta = $globalMeta[$ticker] ?? ['name' => $ticker, 'type' => 'altro'];
    return [
        'ticker' => $ticker,
        'name' => $meta['name'],
        'type' => $meta['type'],
        'current_price' => $c['current_price'],
        'currency' => $c['currency'],
        'value_pct' => round($valuePct, 2),
    ];
}

$currentCandidates = [];
$forecastCandidates = [];
foreach ($computed as $ticker => $c) {
    if (!isset($globalMeta[$ticker])) continue; // non tracciato da nessun instrument (es. coppie FX interne)
    if ($c['trend'] !== null) $currentCandidates[] = ranking_entry($ticker, $c, $globalMeta, $c['trend']['value_pct']);
    if ($c['forecast_available']) $forecastCandidates[] = ranking_entry($ticker, $c, $globalMeta, $c['forecast_pct']);
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
