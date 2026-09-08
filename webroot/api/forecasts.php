<?php
declare(strict_types=1);
require __DIR__ . '/db.php';
require __DIR__ . '/price_fetch.php';
require __DIR__ . '/lib_forecast_model.php';

$uid = require_auth();

if (method() !== 'GET') respond_error('Metodo non supportato', 405);

$stmt = db()->prepare("SELECT id, ticker, name, type, currency FROM instruments WHERE user_id = :uid ORDER BY ticker");
$stmt->execute(['uid' => $uid]);
$instruments = $stmt->fetchAll();

$histStmt = db()->prepare("SELECT date, price FROM price_history WHERE ticker = :ticker ORDER BY date");
$priceStmt = db()->prepare("SELECT price FROM price_cache WHERE ticker = :ticker");

// Caricato una volta sola per la richiesta, non per ogni strumento: è lo
// stesso modello per tutti gli strumenti dell'utente.
$model = latest_forecast_model(db());

$out = [];
foreach ($instruments as $inst) {
    $ticker = $inst['ticker'];

    $histStmt->execute(['ticker' => $ticker]);
    $rows = $histStmt->fetchAll();

    $priceStmt->execute(['ticker' => $ticker]);
    $cachedPrice = $priceStmt->fetchColumn();
    $currentPrice = $cachedPrice !== false ? (float)$cachedPrice : ($rows ? (float)end($rows)['price'] : 0.0);

    $analyst = fetch_analyst_data_cached($ticker);

    $forecast = compute_instrument_forecast($rows, $analyst, $currentPrice, $model);

    $out[] = array_merge([
        'instrument_id' => (int)$inst['id'],
        'ticker' => $ticker,
        'name' => $inst['name'],
        'type' => $inst['type'],
        'currency' => $inst['currency'],
        'current_price' => $currentPrice,
    ], $forecast);
}

respond($out);
