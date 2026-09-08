<?php
declare(strict_types=1);
require_once __DIR__ . '/lib_instrument_metrics.php';   // historical_volatility_pct(), historical_max_drawdown(), closest_row_to()
require_once __DIR__ . '/lib_instrument_forecast.php';  // forecast_trend_component(), forecast_momentum_component()
require_once __DIR__ . '/price_fetch.php';              // fetch_analyst_data_cached(), usata da compute_forecast_for_ticker()
require_once __DIR__ . '/lib_forecast_universe.php';    // FORECAST_BOND_TICKERS, FORECAST_UNIVERSE
require_once __DIR__ . '/lib_market_trends.php';        // ensure_price_history()

const FORECAST_ASSET_CLASSES = ['equity', 'bond', 'crypto'];
// Storico voluto per l'universo curato: abbastanza per il trend a 3 anni
// usato sia da Indicatori sia dalle feature del modello, con un margine.
const FORECAST_UNIVERSE_HISTORY_DAYS = 1500;

/**
 * Modello di previsione vero e proprio: una regressione lineare (OLS) i cui
 * coefficienti sono APPRESI da un backtest sui dati storici reali presenti
 * in `price_history` (globale, tutti i ticker mai tracciati da Reflexi, non
 * solo quelli dell'utente), non decisi a mano. Sostituisce la versione
 * precedente di questa sezione, che usava pesi fissi (40/40/20) — quella
 * non era davvero "intelligenza artificiale", solo una media pesata; questa
 * lo è nel senso tecnico minimo del termine: un modello statistico
 * addestrato su dati, non impostato da chi scrive il codice.
 *
 * Feature scelte (tutte calcolabili "a una data storica" solo dai prezzi,
 * quindi ricostruibili per ogni punto passato — necessario per generare
 * esempi di training): trend storico (CAGR), momentum 90gg, volatilità
 * annualizzata, max drawdown. L'upside da target price analisti NON è una
 * feature del modello: Reflexi non ha mai salvato uno storico delle
 * opinioni di analisti nel tempo (solo l'ultimo valore in analyst_cache),
 * quindi non esiste un dato storico per addestrarci sopra onestamente —
 * resta visibile in Previsioni come dato di terzo separato, non fuso nel
 * numero prodotto dal modello.
 *
 * Label del training: rendimento reale effettivamente realizzato nei 365
 * giorni successivi alla data del campione — un fatto storico verificabile,
 * non un'opinione. Il modello imparato viene poi applicato ai dati PIU'
 * RECENTI di ogni strumento per produrre la stima futura: questa è la sola
 * parte genuinamente predittiva (proietta pesi appresi dal passato su dati
 * che non hanno ancora un esito conosciuto).
 */
const FORECAST_MODEL_FEATURES = ['trend', 'momentum', 'volatility', 'drawdown'];
const FORECAST_MODEL_MIN_TICKER_SPAN_DAYS = 395; // 30gg di storico minimo + 365gg per l'etichetta forward
const FORECAST_MODEL_SAMPLE_STEP_DAYS = 21;
const FORECAST_MODEL_LABEL_HORIZON_DAYS = 365;
const FORECAST_MODEL_LABEL_TOLERANCE_DAYS = 20;
// Validazione out-of-sample (vedi migrazione 037): ultimo 20% cronologico
// dei campioni di OGNI ticker va in test, mai usato per stimare i
// coefficienti valutati. Split per ticker, non per data globale: i ticker
// hanno storico di lunghezza molto diversa (da poche settimane a 8+ anni),
// un unico taglio a calendario escluderebbe del tutto i ticker più corti
// da uno dei due insiemi.
const FORECAST_MODEL_TEST_FRACTION = 0.2;
const FORECAST_MODEL_MIN_OOS_TEST_SAMPLES = 5;

/**
 * Estrae le 4 feature calcolabili da un prefisso di storico (come se "oggi"
 * fosse l'ultima riga di $prefixRows) — usata sia in training (su prefissi
 * storici) sia in inferenza (sull'intero storico disponibile oggi).
 * Torna null se anche una sola feature non è calcolabile.
 */
function forecast_model_features(array $prefixRows): ?array
{
    $trend = forecast_trend_component($prefixRows);
    $momentum = forecast_momentum_component($prefixRows, 90);
    $vol = historical_volatility_pct($prefixRows, 365)['annualized_pct'] ?? null;
    $dd = historical_max_drawdown($prefixRows)['pct'] ?? null;

    if ($trend === null || $momentum === null || $vol === null || $dd === null) return null;

    return [
        'trend' => $trend['value_pct'],
        'momentum' => $momentum['value_pct'],
        'volatility' => $vol,
        'drawdown' => $dd,
    ];
}

/**
 * Genera esempi di training per UN ticker, campionando ogni
 * FORECAST_MODEL_SAMPLE_STEP_DAYS giorni lungo tutto lo storico disponibile.
 * Ogni esempio è (feature calcolate a quella data, rendimento realmente
 * realizzato nei 365 giorni successivi). Torna [] se lo storico del ticker
 * è troppo corto per generare anche un solo esempio completo.
 *
 * Nota onestà: i campioni di uno stesso ticker si sovrappongono molto (le
 * finestre trailing di due date a 21 giorni di distanza condividono quasi
 * tutto lo storico) — non sono indipendenti in senso statistico stretto.
 * È un limite noto e accettato di questo tipo di backtest con storico
 * limitato, non un bug: va riportato nella scheda del modello, non nascosto.
 */
function forecast_model_samples_for_ticker(array $fullRows): array
{
    $n = count($fullRows);
    if ($n < 2) return [];

    $firstTs = strtotime($fullRows[0]['date']);
    $lastTs = strtotime($fullRows[$n - 1]['date']);
    $spanDays = (int)(($lastTs - $firstTs) / 86400);
    if ($spanDays < FORECAST_MODEL_MIN_TICKER_SPAN_DAYS) return [];

    $samples = [];
    $cutoffTs = $firstTs + 30 * 86400;
    $lastCutoffTs = $lastTs - FORECAST_MODEL_LABEL_HORIZON_DAYS * 86400;

    // $asOfIndex avanza in modo monotono con $cutoffTs (entrambi crescenti):
    // resta sempre l'ultima riga con data <= cutoff, senza dover
    // riscandire l'array intero a ogni passo di campionamento.
    $asOfIndex = 0;
    while ($cutoffTs <= $lastCutoffTs) {
        while ($asOfIndex + 1 < $n && strtotime($fullRows[$asOfIndex + 1]['date']) <= $cutoffTs) $asOfIndex++;

        if (strtotime($fullRows[$asOfIndex]['date']) <= $cutoffTs && $asOfIndex >= 1) {
            $prefixRows = array_slice($fullRows, 0, $asOfIndex + 1);
            $features = forecast_model_features($prefixRows);

            if ($features !== null) {
                $asOfTs = strtotime($fullRows[$asOfIndex]['date']);
                $targetTs = $asOfTs + FORECAST_MODEL_LABEL_HORIZON_DAYS * 86400;
                $forwardRow = closest_row_to($fullRows, $targetTs);
                $forwardDiffDays = abs((strtotime($forwardRow['date']) - $targetTs) / 86400);

                if ($forwardDiffDays <= FORECAST_MODEL_LABEL_TOLERANCE_DAYS && strtotime($forwardRow['date']) > $asOfTs) {
                    $asOfPrice = (float)$fullRows[$asOfIndex]['price'];
                    $forwardPrice = (float)$forwardRow['price'];
                    if ($asOfPrice > 0) {
                        $samples[] = [
                            'features' => $features,
                            'label_pct' => ($forwardPrice / $asOfPrice - 1) * 100,
                        ];
                    }
                }
            }
        }

        $cutoffTs += FORECAST_MODEL_SAMPLE_STEP_DAYS * 86400;
    }

    return $samples;
}

/** Ordinary least squares via equazioni normali: beta = (XtX)^-1 Xt y. */
function ols_fit(array $X, array $y): ?array
{
    $n = count($X);
    if ($n === 0) return null;
    $p = count($X[0]);

    // Xt*X (p x p) e Xt*y (p)
    $XtX = array_fill(0, $p, array_fill(0, $p, 0.0));
    $Xty = array_fill(0, $p, 0.0);
    for ($i = 0; $i < $n; $i++) {
        for ($a = 0; $a < $p; $a++) {
            $Xty[$a] += $X[$i][$a] * $y[$i];
            for ($b = 0; $b < $p; $b++) {
                $XtX[$a][$b] += $X[$i][$a] * $X[$i][$b];
            }
        }
    }

    $inv = matrix_inverse($XtX);
    if ($inv === null) return null;

    $beta = array_fill(0, $p, 0.0);
    for ($a = 0; $a < $p; $a++) {
        for ($b = 0; $b < $p; $b++) {
            $beta[$a] += $inv[$a][$b] * $Xty[$b];
        }
    }

    // R^2 sullo stesso set di training (non è una validazione out-of-sample:
    // vedi disclaimer nella scheda del modello).
    $yMean = array_sum($y) / $n;
    $ssTot = 0.0;
    $ssRes = 0.0;
    for ($i = 0; $i < $n; $i++) {
        $pred = 0.0;
        for ($a = 0; $a < $p; $a++) $pred += $beta[$a] * $X[$i][$a];
        $ssRes += ($y[$i] - $pred) ** 2;
        $ssTot += ($y[$i] - $yMean) ** 2;
    }
    $rSquared = $ssTot > 0 ? 1 - ($ssRes / $ssTot) : 0.0;

    return ['beta' => $beta, 'r_squared' => $rSquared];
}

/** R^2 di coefficienti già stimati su un set (X, y) diverso da quello di fit — usata per la valutazione out-of-sample. */
function evaluate_r_squared(array $beta, array $X, array $y): float
{
    $n = count($y);
    if ($n === 0) return 0.0;
    $p = count($beta);

    $yMean = array_sum($y) / $n;
    $ssTot = 0.0;
    $ssRes = 0.0;
    for ($i = 0; $i < $n; $i++) {
        $pred = 0.0;
        for ($a = 0; $a < $p; $a++) $pred += $beta[$a] * $X[$i][$a];
        $ssRes += ($y[$i] - $pred) ** 2;
        $ssTot += ($y[$i] - $yMean) ** 2;
    }
    return $ssTot > 0 ? 1 - ($ssRes / $ssTot) : 0.0;
}

/** Inversione di una matrice quadrata via Gauss-Jordan con pivot parziale. Torna null se singolare. */
function matrix_inverse(array $m): ?array
{
    $n = count($m);
    $aug = [];
    for ($i = 0; $i < $n; $i++) {
        $aug[$i] = array_merge($m[$i], array_fill(0, $n, 0.0));
        $aug[$i][$n + $i] = 1.0;
    }

    for ($col = 0; $col < $n; $col++) {
        $pivotRow = $col;
        $maxVal = abs($aug[$col][$col]);
        for ($r = $col + 1; $r < $n; $r++) {
            if (abs($aug[$r][$col]) > $maxVal) { $maxVal = abs($aug[$r][$col]); $pivotRow = $r; }
        }
        if ($maxVal < 1e-10) return null; // singolare o quasi

        if ($pivotRow !== $col) { $tmp = $aug[$col]; $aug[$col] = $aug[$pivotRow]; $aug[$pivotRow] = $tmp; }

        $pivot = $aug[$col][$col];
        for ($k = 0; $k < 2 * $n; $k++) $aug[$col][$k] /= $pivot;

        for ($r = 0; $r < $n; $r++) {
            if ($r === $col) continue;
            $factor = $aug[$r][$col];
            if ($factor === 0.0) continue;
            for ($k = 0; $k < 2 * $n; $k++) $aug[$r][$k] -= $factor * $aug[$col][$k];
        }
    }

    $inv = [];
    for ($i = 0; $i < $n; $i++) $inv[$i] = array_slice($aug[$i], $n, $n);
    return $inv;
}

/**
 * Classe di asset usata dal modello di previsione — NON lo stesso campo di
 * `instruments.type` mostrato in UI (un ETF obbligazionario resta 'etf' in
 * UI, è corretto). Serve solo ad allenare/applicare modelli separati:
 * volatilità e drawdown di un bond ETF sono di un ordine di grandezza
 * diverso da quelli di un ETF azionario, un solo modello pooled su
 * entrambi estrapola malissimo per l'uno o l'altro gruppo (vedi migrazione
 * 036 per i numeri concreti che l'hanno dimostrato).
 */
function forecast_asset_class(string $ticker, string $displayType): string
{
    if ($displayType === 'crypto') return 'crypto';
    if (in_array($ticker, FORECAST_BOND_TICKERS, true)) return 'bond';
    return 'equity';
}

/**
 * Mappa ticker -> tipo mostrato in UI, per classificare i campioni di
 * training: aggrega `instruments` (tutti gli utenti, come altrove) e la
 * sovrappone con FORECAST_UNIVERSE (nomi/tipi curati, prevalgono).
 */
function forecast_ticker_type_map(PDO $db): array
{
    $map = [];
    foreach ($db->query("SELECT ticker, MIN(type) AS type FROM instruments GROUP BY ticker") as $r) {
        $map[$r['ticker']] = $r['type'];
    }
    foreach (FORECAST_UNIVERSE as $item) $map[$item['ticker']] = $item['type'];
    return $map;
}

/** Converte un array di campioni {features, label_pct} nelle matrici (X con colonna intercetta, y) attese da ols_fit()/evaluate_r_squared(). */
function forecast_samples_to_xy(array $samples): array
{
    $X = [];
    $y = [];
    foreach ($samples as $s) {
        $X[] = [1.0, $s['features']['trend'], $s['features']['momentum'], $s['features']['volatility'], $s['features']['drawdown']];
        $y[] = $s['label_pct'];
    }
    return [$X, $y];
}

/**
 * Addestra UN modello per ciascuna asset class (equity/bond/crypto) su
 * TUTTO price_history (ogni ticker mai tracciato, non solo quelli
 * dell'utente che lancia il training) e salva i coefficienti in
 * `forecast_model`. Pensato per essere lanciato da
 * cron/retrain_forecast_model.php, non ad ogni richiesta HTTP: il backtest
 * su tutto lo storico è un lavoro da batch, non da request.
 *
 * Per ogni ticker, i campioni (già in ordine cronologico, vedi
 * forecast_model_samples_for_ticker) si dividono in train/test: l'ultimo
 * FORECAST_MODEL_TEST_FRACTION per data non entra mai nella stima dei
 * coefficienti valutati (`$fitTrain`), solo nella loro verifica
 * (`r_squared_oos`). Split per ticker e non per una singola data globale:
 * vedi la costante per il perché. I coefficienti SALVATI e usati per le
 * previsioni reali sono invece rifittati su train+test insieme
 * (`$fitFinal`): lo split serve a misurare la capacità predittiva, non a
 * scartare per sempre il 20% di dati più recenti dal modello in
 * produzione — usare tutti i dati disponibili per la stima finale è la
 * pratica standard una volta che la valutazione è fatta.
 */
function train_forecast_model(PDO $db): array
{
    $typeMap = forecast_ticker_type_map($db);
    $tickers = $db->query("SELECT DISTINCT ticker FROM price_history")->fetchAll(PDO::FETCH_COLUMN);

    $trainByClass = array_fill_keys(FORECAST_ASSET_CLASSES, []);
    $testByClass = array_fill_keys(FORECAST_ASSET_CLASSES, []);
    $tickersByClass = array_fill_keys(FORECAST_ASSET_CLASSES, []);

    foreach ($tickers as $ticker) {
        // Un ticker senza un `type` noto (né in instruments né nella lista
        // curata) non ha un'asset class affidabile: escluso dal training
        // invece di indovinare 'equity' di default.
        if (!isset($typeMap[$ticker])) continue;
        $assetClass = forecast_asset_class($ticker, $typeMap[$ticker]);

        $stmt = $db->prepare("SELECT date, price FROM price_history WHERE ticker = :t ORDER BY date");
        $stmt->execute(['t' => $ticker]);
        $rows = $stmt->fetchAll();

        $samples = forecast_model_samples_for_ticker($rows);
        if (!$samples) continue;

        $tickersByClass[$assetClass][] = $ticker;

        $n = count($samples);
        $nTest = (int)round($n * FORECAST_MODEL_TEST_FRACTION);
        if ($nTest > 0 && ($n - $nTest) > 0) {
            $trainPart = array_slice($samples, 0, $n - $nTest);
            $testPart = array_slice($samples, $n - $nTest);
        } else {
            // Troppo pochi campioni per questo ticker per splittare in modo
            // sensato: vanno tutti in train, nessun contributo al test.
            $trainPart = $samples;
            $testPart = [];
        }

        foreach ($trainPart as $s) $trainByClass[$assetClass][] = $s;
        foreach ($testPart as $s) $testByClass[$assetClass][] = $s;
    }

    $results = [];
    foreach (FORECAST_ASSET_CLASSES as $assetClass) {
        $trainSamples = $trainByClass[$assetClass];
        $testSamples = $testByClass[$assetClass];
        $tickersUsed = $tickersByClass[$assetClass];

        if (count($trainSamples) < 20) {
            $results[$assetClass] = ['ok' => false, 'message' => "Troppi pochi campioni di training per '{$assetClass}' (min. 20, disponibili " . count($trainSamples) . ") — servono più ticker di questa classe con più storico."];
            continue;
        }

        [$XTrain, $yTrain] = forecast_samples_to_xy($trainSamples);
        $fitTrain = ols_fit($XTrain, $yTrain);
        if ($fitTrain === null) {
            $results[$assetClass] = ['ok' => false, 'message' => "Fit fallito per '{$assetClass}' (matrice singolare)."];
            continue;
        }

        $rSquaredOos = null;
        if (count($testSamples) >= FORECAST_MODEL_MIN_OOS_TEST_SAMPLES) {
            [$XTest, $yTest] = forecast_samples_to_xy($testSamples);
            $rSquaredOos = evaluate_r_squared($fitTrain['beta'], $XTest, $yTest);
        }

        $allSamples = array_merge($trainSamples, $testSamples);
        [$XAll, $yAll] = forecast_samples_to_xy($allSamples);
        $fitFinal = ols_fit($XAll, $yAll);
        if ($fitFinal === null) {
            $results[$assetClass] = ['ok' => false, 'message' => "Fit finale fallito per '{$assetClass}' (matrice singolare)."];
            continue;
        }

        $stmt = $db->prepare("INSERT INTO forecast_model
            (asset_class, n_samples, n_samples_train, n_samples_test, n_tickers, r_squared, r_squared_oos, intercept, coef_trend, coef_momentum, coef_volatility, coef_drawdown)
            VALUES (:asset_class, :n_samples, :n_samples_train, :n_samples_test, :n_tickers, :r_squared, :r_squared_oos, :intercept, :coef_trend, :coef_momentum, :coef_volatility, :coef_drawdown)");
        $stmt->execute([
            'asset_class' => $assetClass,
            'n_samples' => count($allSamples),
            'n_samples_train' => count($trainSamples),
            'n_samples_test' => count($testSamples),
            'n_tickers' => count($tickersUsed),
            'r_squared' => $fitFinal['r_squared'],
            'r_squared_oos' => $rSquaredOos,
            'intercept' => $fitFinal['beta'][0],
            'coef_trend' => $fitFinal['beta'][1],
            'coef_momentum' => $fitFinal['beta'][2],
            'coef_volatility' => $fitFinal['beta'][3],
            'coef_drawdown' => $fitFinal['beta'][4],
        ]);

        $results[$assetClass] = [
            'ok' => true,
            'n_samples' => count($allSamples),
            'n_samples_train' => count($trainSamples),
            'n_samples_test' => count($testSamples),
            'n_tickers' => count($tickersUsed),
            'tickers_used' => $tickersUsed,
            'r_squared' => round($fitFinal['r_squared'], 4),
            'r_squared_oos' => $rSquaredOos !== null ? round($rSquaredOos, 4) : null,
            'coefficients' => [
                'intercept' => $fitFinal['beta'][0],
                'trend' => $fitFinal['beta'][1],
                'momentum' => $fitFinal['beta'][2],
                'volatility' => $fitFinal['beta'][3],
                'drawdown' => $fitFinal['beta'][4],
            ],
        ];
    }

    return $results;
}

/** Ultimo modello addestrato per un'asset class, o null se non è mai stato lanciato un training per quella classe. */
function latest_forecast_model(PDO $db, string $assetClass): ?array
{
    $stmt = $db->prepare("SELECT * FROM forecast_model WHERE asset_class = :c ORDER BY trained_at DESC LIMIT 1");
    $stmt->execute(['c' => $assetClass]);
    $row = $stmt->fetch();
    return $row ?: null;
}

/** Tutti i modelli più recenti, uno per asset class — comodo per non ripetere la query per ogni ticker. */
function latest_forecast_models(PDO $db): array
{
    $out = [];
    foreach (FORECAST_ASSET_CLASSES as $assetClass) $out[$assetClass] = latest_forecast_model($db, $assetClass);
    return $out;
}

/** Applica i coefficienti di un modello addestrato alle feature attuali di uno strumento. */
function apply_forecast_model(array $model, array $features): float
{
    return (float)$model['intercept']
        + (float)$model['coef_trend'] * $features['trend']
        + (float)$model['coef_momentum'] * $features['momentum']
        + (float)$model['coef_volatility'] * $features['volatility']
        + (float)$model['coef_drawdown'] * $features['drawdown'];
}

/**
 * Calcola tutto quello che serve per UN ticker (storico, prezzo attuale,
 * dato analisti, previsione) — usata sia per il portafoglio dell'utente sia
 * per le classifiche globali "migliori/peggiori" in forecasts.php, così il
 * calcolo per ticker non viene duplicato tra le due liste. `trend` è
 * esposto anche fuori da `forecast_available`: serve solo 30 giorni di
 * storico (a differenza della previsione completa, che ne serve 90 per il
 * momentum), quindi un ticker può avere un "andamento attuale" anche senza
 * avere ancora una previsione completa.
 */
function compute_forecast_for_ticker(PDO $db, string $ticker, string $displayType, array $modelsByClass): array
{
    $histStmt = $db->prepare("SELECT date, price FROM price_history WHERE ticker = :ticker ORDER BY date");
    $histStmt->execute(['ticker' => $ticker]);
    $rows = $histStmt->fetchAll();

    $priceStmt = $db->prepare("SELECT price, currency FROM price_cache WHERE ticker = :ticker");
    $priceStmt->execute(['ticker' => $ticker]);
    $cached = $priceStmt->fetch();

    $currentPrice = $cached ? (float)$cached['price'] : ($rows ? (float)end($rows)['price'] : 0.0);
    $currency = $cached['currency'] ?? null;

    $assetClass = forecast_asset_class($ticker, $displayType);
    $model = $modelsByClass[$assetClass] ?? null;

    $analyst = fetch_analyst_data_cached($ticker);
    $forecast = compute_instrument_forecast($rows, $analyst, $currentPrice, $model);

    return array_merge([
        'ticker' => $ticker,
        'currency' => $currency,
        'current_price' => $currentPrice,
        'trend' => $rows ? forecast_trend_component($rows) : null,
        'asset_class' => $assetClass,
    ], $forecast);
}

/**
 * Orchestratore usato da forecasts.php: applica il modello addestrato alle
 * feature attuali di uno strumento per produrre la stima a 12 mesi. Il
 * target price analisti (forecast_analyst_component, in
 * lib_instrument_forecast.php) viene comunque calcolato e restituito, ma
 * SEPARATO dalla stima — è un riferimento di terzi, non un ingrediente
 * del modello (vedi il perché nel docblock in cima a questo file).
 */
function compute_instrument_forecast(array $rows, ?array $analyst, float $currentPrice, ?array $model): array
{
    $analystComp = forecast_analyst_component($analyst, $currentPrice);
    $analystReference = $analystComp === null ? null : [
        'value_pct' => $analystComp['value_pct'],
        'target_mean_price' => $analystComp['target_mean_price'],
        'number_of_analysts' => $analystComp['number_of_analysts'],
    ];

    if ($model === null) {
        return [
            'forecast_available' => false,
            'message' => 'Il modello di previsione non è ancora stato addestrato su questa installazione (serve lanciare cron/retrain_forecast_model.php almeno una volta).',
            'analyst_reference' => $analystReference,
        ];
    }

    $features = forecast_model_features($rows);
    if ($features === null) {
        return [
            'forecast_available' => false,
            'message' => 'Storico prezzi troppo corto per calcolare le feature del modello (serve almeno 90 giorni continui).',
            'analyst_reference' => $analystReference,
        ];
    }

    return [
        'forecast_available' => true,
        'forecast_pct' => round(apply_forecast_model($model, $features), 2),
        'horizon' => '12 mesi',
        'model' => [
            'trained_at' => $model['trained_at'],
            'n_samples' => (int)$model['n_samples'],
            'n_tickers' => (int)$model['n_tickers'],
            'r_squared' => (float)$model['r_squared'],
            'r_squared_oos' => $model['r_squared_oos'] !== null ? (float)$model['r_squared_oos'] : null,
        ],
        'features' => [
            'trend' => ['value_pct' => $features['trend']],
            'momentum' => ['value_pct' => $features['momentum'], 'window_days' => 90],
            'volatility' => ['annualized_pct' => $features['volatility']],
            'drawdown' => ['pct' => $features['drawdown']],
        ],
        'analyst_reference' => $analystReference,
    ];
}

/**
 * Costruisce l'universo (strumenti dell'utente + lista curata multi-asset,
 * vedi FORECAST_UNIVERSE) e calcola la previsione per ognuno UNA sola
 * volta — condiviso da forecasts.php (classifiche) e invest_advisor.php
 * (allocazione ipotetica), così le due pagine leggono esattamente gli
 * stessi numeri invece di ricalcolarli in modo scollegato.
 */
function forecast_universe_computed(PDO $db, int $uid): array
{
    $stmt = $db->prepare("SELECT id, ticker, name, type, currency FROM instruments WHERE user_id = :uid ORDER BY ticker");
    $stmt->execute(['uid' => $uid]);
    $myInstruments = $stmt->fetchAll();

    // Ticker distinti tracciati da TUTTI gli utenti (stesso approccio di
    // market_trends.php::top_movers), PIÙ la lista curata multi-asset —
    // "tutto il mercato" non è raggiungibile con l'API non ufficiale di
    // Yahoo usata da questo progetto (vedi lib_forecast_universe.php).
    $globalMeta = [];
    foreach ($db->query("SELECT ticker, MIN(name) AS name, MIN(type) AS type FROM instruments GROUP BY ticker") as $r) {
        $globalMeta[$r['ticker']] = $r;
    }
    foreach (FORECAST_UNIVERSE as $item) {
        // La lista curata prevale (nomi puliti), ma non cancella un ticker
        // già presente per un altro motivo.
        $globalMeta[$item['ticker']] = $item;
        fetch_live_price($item['ticker']); // popola price_cache col prezzo di oggi
        ensure_price_history($item['ticker'], FORECAST_UNIVERSE_HISTORY_DAYS);
    }

    $modelsByClass = latest_forecast_models($db);

    $computed = [];
    foreach ($myInstruments as $inst) {
        if (!isset($computed[$inst['ticker']])) {
            $computed[$inst['ticker']] = compute_forecast_for_ticker($db, $inst['ticker'], $inst['type'], $modelsByClass);
        }
    }
    foreach ($globalMeta as $ticker => $meta) {
        if (!isset($computed[$ticker])) {
            $computed[$ticker] = compute_forecast_for_ticker($db, $ticker, $meta['type'], $modelsByClass);
        }
    }

    return [
        'my_instruments' => $myInstruments,
        'global_meta' => $globalMeta,
        'computed' => $computed,
        'models_by_class' => $modelsByClass,
    ];
}
