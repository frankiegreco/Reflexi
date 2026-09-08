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

/**
 * Serie infragiornaliera (oggi, un punto ogni 15 minuti circa) via l'endpoint
 * chart di Yahoo con range=1d — a differenza delle chiusure in price_history
 * (granularità giornaliera) non viene mai salvata in cache: la vista "24h" ha
 * senso solo per la giornata corrente, non c'è uno storico da costruire.
 */
function fetch_intraday(string $ticker): array
{
    $url = 'https://query1.finance.yahoo.com/v8/finance/chart/' . rawurlencode($ticker)
        . '?range=1d&interval=15m';
    $userAgent = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36';
    $raw = function_exists('curl_init')
        ? fetch_via_curl($url, $userAgent)
        : fetch_via_file_get_contents($url, $userAgent);
    if (!$raw) return [];

    $data = json_decode($raw, true);
    $result = $data['chart']['result'][0] ?? null;
    if (!$result) return [];

    $timestamps = $result['timestamp'] ?? [];
    $closes = $result['indicators']['quote'][0]['close'] ?? [];
    $currency = $result['meta']['currency'] ?? null;
    if (!$timestamps || !$closes) return [];

    $tz = $result['meta']['exchangeTimezoneName'] ?? 'UTC';
    try { $zone = new DateTimeZone($tz); } catch (Exception $e) { $zone = new DateTimeZone('UTC'); }

    $out = [];
    foreach ($timestamps as $i => $ts) {
        if (!isset($closes[$i]) || $closes[$i] === null) continue;
        $dt = (new DateTime('@' . $ts))->setTimezone($zone);
        $out[] = ['date' => $dt->format('H:i'), 'price' => (float)$closes[$i], 'currency' => $currency];
    }
    return $out;
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
 * Cookie + crumb di sessione richiesti da Yahoo per l'endpoint quoteSummary
 * (a differenza dell'endpoint chart usato sopra, che non li richiede). Senza
 * crumb l'API risponde con errore "Invalid Crumb". Richiede cURL (per i
 * cookie); se non disponibile la funzione ritorna null e chi chiama
 * (fetch_analyst_data) tratta il dato come semplicemente non disponibile.
 */
function fetch_yahoo_crumb(string $userAgent): ?array
{
    if (!function_exists('curl_init')) return null;

    $cookieFile = tempnam(sys_get_temp_dir(), 'yc_');
    if ($cookieFile === false) return null;

    $ch = curl_init('https://fc.yahoo.com');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 5,
        CURLOPT_USERAGENT => $userAgent,
        CURLOPT_COOKIEJAR => $cookieFile,
        CURLOPT_IPRESOLVE => CURL_IPRESOLVE_V4,
    ]);
    curl_exec($ch);
    curl_close($ch);

    $ch = curl_init('https://query1.finance.yahoo.com/v1/test/getcrumb');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 5,
        CURLOPT_USERAGENT => $userAgent,
        CURLOPT_COOKIEFILE => $cookieFile,
        CURLOPT_IPRESOLVE => CURL_IPRESOLVE_V4,
    ]);
    $crumb = curl_exec($ch);
    curl_close($ch);

    if (!$crumb || str_contains($crumb, '<')) {
        @unlink($cookieFile);
        return null;
    }
    return ['crumb' => $crumb, 'cookie_file' => $cookieFile];
}

/**
 * Opinioni di analisti esterni (target price, raccomandazione media) dal
 * modulo `financialData` di Yahoo Finance — dati di terzi, mai un'
 * elaborazione di Reflexi (vedi spec_indicatori_mercato.md). Torna null se
 * il dato non è disponibile (tipicamente ETF/crypto, che non hanno copertura
 * di analisti) o se Yahoo non risponde: chi chiama deve semplicemente
 * nascondere la sezione, non mostrare un placeholder vuoto.
 */
function fetch_analyst_data(string $ticker): ?array
{
    $userAgent = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36';
    $auth = fetch_yahoo_crumb($userAgent);
    if ($auth === null) return null;

    $url = 'https://query1.finance.yahoo.com/v10/finance/quoteSummary/' . rawurlencode($ticker)
        . '?modules=financialData&crumb=' . rawurlencode($auth['crumb']);

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 5,
        CURLOPT_USERAGENT => $userAgent,
        CURLOPT_COOKIEFILE => $auth['cookie_file'],
        CURLOPT_IPRESOLVE => CURL_IPRESOLVE_V4,
        CURLOPT_SSL_VERIFYPEER => true,
    ]);
    $raw = curl_exec($ch);
    curl_close($ch);
    @unlink($auth['cookie_file']);

    if (!$raw) return null;

    $data = json_decode($raw, true);
    $fd = $data['quoteSummary']['result'][0]['financialData'] ?? null;
    $analystCount = $fd['numberOfAnalystOpinions']['raw'] ?? null;
    if (!$fd || !$analystCount) return null;

    return [
        'target_mean_price' => $fd['targetMeanPrice']['raw'] ?? null,
        'target_high_price' => $fd['targetHighPrice']['raw'] ?? null,
        'target_low_price' => $fd['targetLowPrice']['raw'] ?? null,
        'recommendation_mean' => $fd['recommendationMean']['raw'] ?? null,
        'number_of_analysts' => (int)$analystCount,
        'currency' => $fd['financialCurrency'] ?? null,
    ];
}

/**
 * Come fetch_analyst_data(), ma con cache persistente in `analyst_cache`
 * (globale per ticker, ttl 12h di default): i target price degli analisti
 * non cambiano intraday, e a differenza della quotazione semplice la fetch
 * richiede un handshake cookie+crumb in più (vedi fetch_yahoo_crumb) — vale
 * la pena risparmiarla, specialmente per la sezione Previsioni che la
 * interroga per ogni strumento in portafoglio a ogni apertura pagina.
 * Cachea anche l'esito negativo (`available = 0`, tipico per ETF/crypto),
 * altrimenti ogni apertura pagina ritenterebbe Yahoo per gli stessi
 * strumenti senza copertura analisti.
 */
function fetch_analyst_data_cached(string $ticker, int $maxAgeSeconds = 43200): ?array
{
    $stmt = db()->prepare("SELECT * FROM analyst_cache WHERE ticker = :ticker");
    $stmt->execute(['ticker' => $ticker]);
    $cached = $stmt->fetch();

    if ($cached && (time() - strtotime($cached['fetched_at'])) < $maxAgeSeconds) {
        return analyst_cache_row_to_array($cached);
    }

    $fresh = fetch_analyst_data($ticker);

    $upsert = db()->prepare("INSERT INTO analyst_cache
            (ticker, target_mean_price, target_high_price, target_low_price, recommendation_mean, number_of_analysts, currency, available, fetched_at)
        VALUES (:ticker, :tmp, :thp, :tlp, :rm, :na, :cur, :avail, NOW())
        ON DUPLICATE KEY UPDATE
            target_mean_price = :tmp2, target_high_price = :thp2, target_low_price = :tlp2,
            recommendation_mean = :rm2, number_of_analysts = :na2, currency = :cur2,
            available = :avail2, fetched_at = NOW()");
    $upsert->execute([
        'ticker' => $ticker,
        'tmp' => $fresh['target_mean_price'] ?? null, 'tmp2' => $fresh['target_mean_price'] ?? null,
        'thp' => $fresh['target_high_price'] ?? null, 'thp2' => $fresh['target_high_price'] ?? null,
        'tlp' => $fresh['target_low_price'] ?? null, 'tlp2' => $fresh['target_low_price'] ?? null,
        'rm' => $fresh['recommendation_mean'] ?? null, 'rm2' => $fresh['recommendation_mean'] ?? null,
        'na' => $fresh['number_of_analysts'] ?? null, 'na2' => $fresh['number_of_analysts'] ?? null,
        'cur' => $fresh['currency'] ?? null, 'cur2' => $fresh['currency'] ?? null,
        'avail' => $fresh !== null ? 1 : 0, 'avail2' => $fresh !== null ? 1 : 0,
    ]);

    return $fresh;
}

function analyst_cache_row_to_array(array $row): ?array
{
    if (!$row['available']) return null;
    return [
        'target_mean_price' => $row['target_mean_price'] !== null ? (float)$row['target_mean_price'] : null,
        'target_high_price' => $row['target_high_price'] !== null ? (float)$row['target_high_price'] : null,
        'target_low_price' => $row['target_low_price'] !== null ? (float)$row['target_low_price'] : null,
        'recommendation_mean' => $row['recommendation_mean'] !== null ? (float)$row['recommendation_mean'] : null,
        'number_of_analysts' => $row['number_of_analysts'] !== null ? (int)$row['number_of_analysts'] : null,
        'currency' => $row['currency'],
    ];
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
