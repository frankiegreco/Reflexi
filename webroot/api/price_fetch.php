<?php
declare(strict_types=1);

/** Tasso di cambio verso EUR per una valuta (1.0 se già EUR), con la stessa cache usata per le quotazioni. */
function fx_rate_to_eur(string $currency): array
{
    if ($currency === 'EUR') return ['rate' => 1.0, 'stale' => false];
    $fx = fetch_live_price("{$currency}EUR=X");
    if ($fx === null) return ['rate' => null, 'stale' => true];
    return ['rate' => $fx['price'], 'stale' => $fx['stale']];
}

/**
 * Ritorna la quotazione di un ticker, usando la cache se abbastanza recente.
 * Se il fetch esterno fallisce (es. VM senza internet in quel momento) e in
 * cache c'e' un valore precedente, lo ritorna comunque marcandolo "stale".
 */
function fetch_live_price(string $ticker, int $maxAgeSeconds = 900): ?array
{
    $stmt = db()->prepare("SELECT * FROM price_cache WHERE ticker = :ticker");
    $stmt->execute(['ticker' => $ticker]);
    $cached = $stmt->fetch();

    if ($cached && $maxAgeSeconds > 0 && (time() - strtotime($cached['fetched_at'])) < $maxAgeSeconds) {
        return ['price' => (float)$cached['price'], 'currency' => $cached['currency'], 'fetched_at' => $cached['fetched_at'], 'stale' => false];
    }

    $fresh = fetch_price_from_yahoo($ticker);
    if ($fresh !== null) {
        $upsert = db()->prepare("INSERT INTO price_cache (ticker, price, currency, source, fetched_at)
            VALUES (:ticker, :price, :currency, 'yahoo', NOW())
            ON DUPLICATE KEY UPDATE price = :price2, currency = :currency2, source = 'yahoo', fetched_at = NOW()");
        $upsert->execute([
            'ticker' => $ticker,
            'price' => $fresh['price'],
            'currency' => $fresh['currency'],
            'price2' => $fresh['price'],
            'currency2' => $fresh['currency'],
        ]);

        // Un solo record per ticker+giorno: se il prezzo viene ricontrollato piu'
        // volte nello stesso giorno, aggiorna quello di oggi invece di duplicare.
        // Cosi' lo storico si costruisce da solo, senza bisogno di un cron dedicato.
        $history = db()->prepare("INSERT INTO price_history (ticker, price, currency, date)
            VALUES (:ticker, :price, :currency, CURDATE())
            ON DUPLICATE KEY UPDATE price = :price2, currency = :currency2");
        $history->execute([
            'ticker' => $ticker,
            'price' => $fresh['price'],
            'currency' => $fresh['currency'],
            'price2' => $fresh['price'],
            'currency2' => $fresh['currency'],
        ]);

        return ['price' => $fresh['price'], 'currency' => $fresh['currency'], 'fetched_at' => date('Y-m-d H:i:s'), 'stale' => false];
    }

    if ($cached) {
        return ['price' => (float)$cached['price'], 'currency' => $cached['currency'], 'fetched_at' => $cached['fetched_at'], 'stale' => true];
    }
    return null;
}

/**
 * Prezzo di chiusura reale in una data passata (non quello attuale). Usato per
 * ricostruire correttamente le rate PAC arretrate, invece di applicare il
 * prezzo di oggi a un acquisto che sarebbe dovuto avvenire mesi fa.
 * Cerca nella finestra +/- qualche giorno intorno alla data richiesta, per
 * gestire automaticamente weekend e festività in cui il mercato era chiuso.
 */
function fetch_historical_price(string $ticker, string $date): ?array
{
    $target = strtotime($date);
    $period1 = $target - 5 * 86400;
    $period2 = $target + 2 * 86400;
    $url = 'https://query1.finance.yahoo.com/v8/finance/chart/' . rawurlencode($ticker)
        . "?period1={$period1}&period2={$period2}&interval=1d";
    $userAgent = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36';

    $raw = function_exists('curl_init')
        ? fetch_via_curl($url, $userAgent)
        : fetch_via_file_get_contents($url, $userAgent);
    if (!$raw) return null;

    $data = json_decode($raw, true);
    $result = $data['chart']['result'][0] ?? null;
    if (!$result) return null;

    $timestamps = $result['timestamp'] ?? [];
    $closes = $result['indicators']['quote'][0]['close'] ?? [];
    $currency = $result['meta']['currency'] ?? null;
    if (!$timestamps || !$closes) return null;

    $bestIdx = null;
    $bestDiff = PHP_INT_MAX;
    foreach ($timestamps as $i => $ts) {
        if (!isset($closes[$i]) || $closes[$i] === null) continue;
        $diff = abs($ts - $target);
        if ($diff < $bestDiff) { $bestDiff = $diff; $bestIdx = $i; }
    }
    if ($bestIdx === null) return null;

    return [
        'price' => (float)$closes[$bestIdx],
        'currency' => $currency,
        'actual_date' => date('Y-m-d', (int)$timestamps[$bestIdx]),
    ];
}

/**
 * Scarica fino a $days di chiusure giornaliere reali in UNA sola chiamata a
 * Yahoo (range period1..period2, interval 1d) e le scrive in price_history.
 * Serve a "seminare" subito lo storico di un ticker nuovo — es. la watchlist
 * curata di Mercati, o un ticker appena cercato — invece di aspettare che si
 * accumuli un giorno alla volta con fetch_live_price(). Il prezzo di oggi non
 * viene scritto qui: resta di competenza di fetch_live_price(), che ha il
 * dato intraday più fresco (quello del grafico storico è la chiusura, spesso
 * in ritardo di qualche ora rispetto al prezzo corrente). Torna il numero di
 * giorni effettivamente scritti.
 */
function backfill_price_history(string $ticker, int $days = 90): int
{
    $period2 = time();
    $period1 = $period2 - ($days + 5) * 86400;
    $url = 'https://query1.finance.yahoo.com/v8/finance/chart/' . rawurlencode($ticker)
        . "?period1={$period1}&period2={$period2}&interval=1d";
    $userAgent = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36';

    $raw = function_exists('curl_init')
        ? fetch_via_curl($url, $userAgent)
        : fetch_via_file_get_contents($url, $userAgent);
    if (!$raw) return 0;

    $data = json_decode($raw, true);
    $result = $data['chart']['result'][0] ?? null;
    if (!$result) return 0;

    $timestamps = $result['timestamp'] ?? [];
    $closes = $result['indicators']['quote'][0]['close'] ?? [];
    $currency = $result['meta']['currency'] ?? null;
    if (!$timestamps || !$closes) return 0;

    $today = date('Y-m-d');
    $upsert = db()->prepare("INSERT INTO price_history (ticker, price, currency, date)
        VALUES (:ticker, :price, :currency, :date)
        ON DUPLICATE KEY UPDATE price = VALUES(price), currency = VALUES(currency)");

    $written = 0;
    foreach ($timestamps as $i => $ts) {
        if (!isset($closes[$i]) || $closes[$i] === null) continue;
        $date = date('Y-m-d', (int)$ts);
        if ($date === $today) continue;
        $upsert->execute(['ticker' => $ticker, 'price' => (float)$closes[$i], 'currency' => $currency, 'date' => $date]);
        $written++;
    }
    return $written;
}

/** Interroga l'endpoint pubblico (non ufficiale) di Yahoo Finance per un ticker. */
function fetch_price_from_yahoo(string $ticker): ?array
{
    $url = 'https://query1.finance.yahoo.com/v8/finance/chart/' . rawurlencode($ticker);
    $userAgent = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36';

    $raw = function_exists('curl_init')
        ? fetch_via_curl($url, $userAgent)
        : fetch_via_file_get_contents($url, $userAgent);

    if (!$raw) return null;

    $data = json_decode($raw, true);
    $result = $data['chart']['result'][0] ?? null;
    if (!$result) return null;

    $price = $result['meta']['regularMarketPrice'] ?? null;
    $currency = $result['meta']['currency'] ?? null;
    if ($price === null) return null;

    return ['price' => (float)$price, 'currency' => $currency];
}

/**
 * cURL forzato su IPv4: su Windows, in reti aziendali con DNS Active Directory
 * che restituiscono anche record IPv6, la risoluzione dual-stack di getaddrinfo
 * (usata da file_get_contents) può fallire con "no data of requested type"
 * anche quando nslookup manuale funziona. Forzare IPv4 con cURL bypassa il problema.
 */
function fetch_via_curl(string $url, string $userAgent): ?string
{
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 6,
        CURLOPT_USERAGENT => $userAgent,
        CURLOPT_IPRESOLVE => CURL_IPRESOLVE_V4,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_FOLLOWLOCATION => true,
    ]);
    $raw = curl_exec($ch);
    $ok = $raw !== false && curl_getinfo($ch, CURLINFO_HTTP_CODE) === 200;
    curl_close($ch);
    return $ok ? $raw : null;
}

function fetch_via_file_get_contents(string $url, string $userAgent): ?string
{
    $context = stream_context_create([
        'http' => [
            'method' => 'GET',
            'header' => "User-Agent: {$userAgent}\r\n",
            'timeout' => 6,
            'ignore_errors' => true,
        ],
    ]);
    $raw = @file_get_contents($url, false, $context);
    return $raw ?: null;
}
