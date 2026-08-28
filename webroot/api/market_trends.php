<?php
declare(strict_types=1);
require __DIR__ . '/db.php';
require_once __DIR__ . '/price_fetch.php';
require_once __DIR__ . '/lib_market_trends.php';

// Endpoint PUBBLICO — a differenza di ogni altro file in api/, niente
// require_auth(): mostra andamenti di mercato in generale (watchlist curata,
// top mover, storico di un ticker cercato), non il portafoglio di un utente
// specifico. Riusa la stessa logica di instrument_search.php/price_history.php
// ma senza il vincolo di ownership su un instrument_id.
if (method() !== 'GET') respond_error('Metodo non supportato', 405);

$action = $_GET['action'] ?? '';

switch ($action) {
    case 'watchlist':
        $out = [];
        foreach (MARKET_TRENDS_WATCHLIST as $item) {
            $live = fetch_live_price($item['ticker']);
            if (!$live) continue;
            ensure_price_history($item['ticker']);
            $out[] = $item + [
                'price' => $live['price'],
                'currency' => $live['currency'],
                'change_pct' => market_change_pct($item['ticker'], $live['price']),
                'sparkline' => market_sparkline($item['ticker']),
                'stale' => $live['stale'],
            ];
        }
        respond($out);
        break;

    case 'top_movers':
        // Ticker distinti tracciati da TUTTI gli utenti (solo ticker/nome/tipo,
        // nessun dato che identifichi chi li possiede), incrociati con la
        // cache prezzi già esistente — nessuna nuova fonte dati, nessuna
        // chiamata live aggiuntiva a Yahoo (per non moltiplicare le richieste
        // esterne al crescere degli utenti).
        $stmt = db()->query("SELECT i.ticker, i.name, i.type, pc.price, pc.currency
            FROM (SELECT ticker, MIN(name) AS name, MIN(type) AS type FROM instruments GROUP BY ticker) i
            JOIN price_cache pc ON pc.ticker = i.ticker");
        $rows = $stmt->fetchAll();

        $out = [];
        foreach ($rows as $row) {
            ensure_price_history($row['ticker']);
            $changePct = market_change_pct($row['ticker'], (float)$row['price']);
            if ($changePct === null) continue;
            $out[] = [
                'ticker' => $row['ticker'],
                'name' => $row['name'],
                'type' => $row['type'],
                'price' => (float)$row['price'],
                'currency' => $row['currency'],
                'change_pct' => $changePct,
                'sparkline' => market_sparkline($row['ticker']),
            ];
        }
        usort($out, fn($a, $b) => abs($b['change_pct']) <=> abs($a['change_pct']));
        respond(array_slice($out, 0, 8));
        break;

    case 'quote':
        $ticker = strtoupper(trim($_GET['ticker'] ?? ''));
        if (!is_valid_public_ticker($ticker)) respond_error('Ticker non valido');
        $live = fetch_live_price($ticker);
        if (!$live) respond_error('Quotazione non disponibile', 404);
        ensure_price_history($ticker);
        respond([
            'ticker' => $ticker,
            'price' => $live['price'],
            'currency' => $live['currency'],
            'change_pct' => market_change_pct($ticker, $live['price']),
            'stale' => $live['stale'],
        ]);
        break;

    case 'intraday':
        // Vista "24h": granularità infragiornaliera, non passa da price_history
        // (che è a chiusura giornaliera) — proxy diretto a Yahoo ogni volta,
        // per questo rate-limitato come 'search'.
        $ticker = strtoupper(trim($_GET['ticker'] ?? ''));
        if (!is_valid_public_ticker($ticker)) respond_error('Ticker non valido');
        rate_limit_check('market_intraday', 30, 300);
        rate_limit_record('market_intraday');
        respond(fetch_intraday($ticker));
        break;

    case 'history':
        $ticker = strtoupper(trim($_GET['ticker'] ?? ''));
        if (!is_valid_public_ticker($ticker)) respond_error('Ticker non valido');
        $days = min((int)($_GET['days'] ?? 90), 3650);

        // Garantisce il dato di oggi + lo storico reale già pronto al primo
        // sguardo (non solo da oggi in poi): ensure_price_history() backfilla
        // in un colpo solo fino a $days di chiusure vere se non ce ne sono
        // già a sufficienza in cache.
        fetch_live_price($ticker);
        ensure_price_history($ticker, $days);

        $stmt = db()->prepare("SELECT date, price, currency FROM price_history
            WHERE ticker = :ticker AND date >= DATE_SUB(CURDATE(), INTERVAL :days DAY)
            ORDER BY date");
        $stmt->bindValue('ticker', $ticker, PDO::PARAM_STR);
        $stmt->bindValue('days', $days, PDO::PARAM_INT);
        $stmt->execute();
        respond($stmt->fetchAll());
        break;

    case 'search':
        // L'unica azione qui che chiama Yahoo direttamente a ogni richiesta,
        // senza cache (a differenza di fetch_live_price): prima di
        // instrument_search.php era sempre dietro require_auth(), qui è
        // pubblica, quindi va protetta a parte per non diventare un proxy
        // Yahoo aperto abusabile. Le altre azioni sopra restano lette dalla
        // cache prezzi esistente e non hanno bisogno dello stesso limite.
        rate_limit_check('market_search', 30, 300);
        rate_limit_record('market_search');

        $q = trim($_GET['q'] ?? '');
        if (mb_strlen($q) < 2) respond([]);

        $url = 'https://query1.finance.yahoo.com/v1/finance/search?q=' . rawurlencode($q) . '&quotesCount=8&newsCount=0';
        $userAgent = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36';
        $raw = function_exists('curl_init')
            ? fetch_via_curl($url, $userAgent)
            : fetch_via_file_get_contents($url, $userAgent);
        if (!$raw) respond([]);

        $data = json_decode($raw, true);
        $quotes = $data['quotes'] ?? [];
        $typeMap = ['EQUITY' => 'azione', 'ETF' => 'etf', 'MUTUALFUND' => 'fondo', 'CRYPTOCURRENCY' => 'crypto'];

        $results = [];
        foreach ($quotes as $item) {
            if (empty($item['symbol'])) continue;
            $results[] = [
                'ticker' => $item['symbol'],
                'name' => $item['longname'] ?? $item['shortname'] ?? $item['symbol'],
                'exchange' => $item['exchange'] ?? '',
                'type' => $typeMap[$item['quoteType'] ?? ''] ?? 'altro',
                'currency' => $item['currency'] ?? null,
            ];
        }
        respond($results);
        break;

    default:
        respond_error('Azione non valida', 404);
}
