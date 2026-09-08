<?php
declare(strict_types=1);
require __DIR__ . '/db.php';
require_once __DIR__ . '/lib_forecast_model.php';
require __DIR__ . '/lib_market_trends.php';   // ensure_price_history()
require_once __DIR__ . '/lib_forecast_universe.php';

const FORECAST_RANKING_SIZE = 10;
// Storico voluto per l'universo curato: abbastanza per il trend a 3 anni
// usato sia da Indicatori sia dalle feature del modello, con un margine.
const FORECAST_UNIVERSE_HISTORY_DAYS = 1500;

$uid = require_auth();

if (method() !== 'GET') respond_error('Metodo non supportato', 405);

$stmt = db()->prepare("SELECT id, ticker, name, type, currency FROM instruments WHERE user_id = :uid ORDER BY ticker");
$stmt->execute(['uid' => $uid]);
$myInstruments = $stmt->fetchAll();

// Universo per le classifiche "migliori/peggiori": ticker distinti tracciati
// da TUTTI gli utenti (stesso approccio di market_trends.php::top_movers),
// PIÙ una lista curata multi-asset (FORECAST_UNIVERSE) — "tutto il mercato"
// non è raggiungibile con l'API non ufficiale di Yahoo usata da questo
// progetto (vedi il commento in lib_forecast_universe.php), quindi
// allarghiamo la base con una selezione diversificata invece di limitarci
// a ciò che qualche utente ha aggiunto per conto proprio.
$globalRows = db()->query("SELECT ticker, MIN(name) AS name, MIN(type) AS type
    FROM instruments GROUP BY ticker")->fetchAll();
$globalMeta = [];
foreach ($globalRows as $r) $globalMeta[$r['ticker']] = $r;
foreach (FORECAST_UNIVERSE as $item) {
    // La lista curata prevale (nomi puliti), ma non cancella un ticker già
    // presente per un altro motivo.
    $globalMeta[$item['ticker']] = $item;
    fetch_live_price($item['ticker']); // popola price_cache col prezzo di oggi (stesso pattern di market_trends.php::watchlist)
    ensure_price_history($item['ticker'], FORECAST_UNIVERSE_HISTORY_DAYS);
}

// Caricati una volta sola per la richiesta, non per ogni ticker: sono gli
// stessi 3 modelli (uno per asset class) per tutti.
$modelsByClass = latest_forecast_models(db());

// Calcolo per ticker fatto una sola volta anche se un ticker compare sia nel
// portafoglio dell'utente sia nell'universo globale delle classifiche.
$computed = [];
function get_computed(string $ticker, string $displayType, array $modelsByClass, array &$computed): array
{
    if (!isset($computed[$ticker])) $computed[$ticker] = compute_forecast_for_ticker(db(), $ticker, $displayType, $modelsByClass);
    return $computed[$ticker];
}

$portfolio = [];
foreach ($myInstruments as $inst) {
    $c = get_computed($inst['ticker'], $inst['type'], $modelsByClass, $computed);
    $portfolio[] = array_merge($c, [
        'instrument_id' => (int)$inst['id'],
        'name' => $inst['name'],
        'type' => $inst['type'],
        // La valuta scelta dall'utente per il proprio strumento prevale su
        // quella (eventualmente diversa) letta dalla cache prezzi globale.
        'currency' => $inst['currency'],
    ]);
}

foreach ($globalMeta as $ticker => $meta) get_computed($ticker, $meta['type'], $modelsByClass, $computed);

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
