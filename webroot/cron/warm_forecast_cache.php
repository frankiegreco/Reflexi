<?php
declare(strict_types=1);

// Tiene calde le cache Yahoo usate dalla sezione "Previsioni" (prezzi e
// storico dell'universo curato, dati analisti di tutti i ticker in
// classifica), così la richiesta dell'utente non deve mai scaricarle in serie:
// a cache fredda forecasts.php impiegava ~40 secondi. Schedulato via crontab
// sulla VM ogni 30 minuti (vedi `crontab -l`). Le soglie qui sono più basse di
// quelle lato richiesta (FORECAST_UNIVERSE_PRICE_MAX_AGE = 1h, analisti 12h),
// in modo che il cron rinfreschi i dati prima che l'utente li trovi scaduti.
if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    exit('Questo script va eseguito solo da riga di comando (cron), non via web.');
}

require __DIR__ . '/../api/db.php';
require __DIR__ . '/../api/lib_forecast_model.php';

$start = microtime(true);
$tickers = [];
foreach (FORECAST_UNIVERSE as $item) {
    fetch_live_price($item['ticker'], 1500);
    ensure_price_history($item['ticker'], FORECAST_UNIVERSE_HISTORY_DAYS);
    $tickers[$item['ticker']] = true;
}
foreach (db()->query("SELECT DISTINCT ticker FROM instruments") as $r) {
    $tickers[$r['ticker']] = true;
}
foreach (array_keys($tickers) as $ticker) {
    fetch_analyst_data_cached($ticker, 6 * 3600);
}

printf("[%s] cache previsioni aggiornata: %d ticker in %.1fs\n", date('Y-m-d H:i:s'), count($tickers), microtime(true) - $start);
