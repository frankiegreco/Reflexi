<?php
declare(strict_types=1);
require_once __DIR__ . '/lib_forecast_model.php'; // FORECAST_ASSET_CLASSES, forecast_universe_computed()

const INVEST_ADVISOR_MIN_MONTHS = 1;
const INVEST_ADVISOR_MAX_MONTHS = 60;
const INVEST_ADVISOR_MIN_COUNT = 1;
const INVEST_ADVISOR_MAX_COUNT = 15;
const INVEST_ADVISOR_DEFAULT_COUNT = 9;
const INVEST_ADVISOR_DEFAULT_RISK_PROFILE = 'bilanciato';
const INVEST_ADVISOR_FEE_PER_TRADE = 1.0; // € fissi per acquisto, addebitati dal broker indipendentemente dall'importo

/**
 * Livello di rischio scelto dall'utente, tradotto in due leve concrete sul
 * ranking già esistente (non un parametro separato "quanto rischio vuoi",
 * che non avrebbe un effetto definito senza dire COSA cambia):
 *
 * - `volatility_exponent`: il punteggio è rendimento / volatilità^esponente.
 *   Con esponente 1 rendimento e volatilità peserebbero "allo stesso modo"
 *   — ma il modello di previsione (lib_forecast_model.php) ha un
 *   coefficiente sulla volatilità che è LUI STESSO positivo e quasi
 *   lineare (misurato: ~1.58 per la classe equity, l'unica che oggi supera
 *   il filtro di confidenza qui sotto): per uno strumento con volatilità
 *   molto alta, il rendimento previsto cresce già linearmente con la
 *   volatilità, quindi dividere per volatilità^1 lascia il punteggio quasi
 *   INVARIANTE al crescere della volatilità invece di scontarla — non un
 *   vero risk-adjustment, solo apparente (caso concreto che l'ha rivelato:
 *   INTC, volatilità 78,94%, arrivava comunque in cima alla classifica).
 *   Serve un esponente sensibilmente sopra 1 perché la divisione superi la
 *   crescita quasi lineare del numeratore e la volatilità estrema perda
 *   davvero posizioni; i tre valori sotto sono i precedenti (0.5/1.0/1.6)
 *   spostati di +1, così l'ordine relativo tra i profili — conservativo
 *   sconta la volatilità più di bilanciato, che la sconta più di aggressivo
 *   — resta lo stesso, ma ora ogni profilo applica uno sconto reale, non
 *   solo nominale.
 * - `max_weight_pct`: tetto massimo per singolo strumento. Più basso per
 *   il profilo conservativo (più spalmato, meno concentrazione su un solo
 *   strumento), più alto per l'aggressivo (permette scommesse più
 *   concentrate sui candidati con punteggio migliore).
 *
 * Nessun parametro cambia i dati sottostanti (previsioni, R², volatilità
 * storica) — solo come vengono classificati e pesati i candidati.
 */
const INVEST_ADVISOR_RISK_PROFILES = [
    'conservativo' => ['volatility_exponent' => 2.6, 'max_weight_pct' => 20.0],
    'bilanciato' => ['volatility_exponent' => 2.0, 'max_weight_pct' => 35.0],
    'aggressivo' => ['volatility_exponent' => 1.5, 'max_weight_pct' => 50.0],
];

/**
 * Alloca un importo ipotetico su un orizzonte in mesi e un numero di
 * strumenti scelti dall'utente, usando le previsioni già calcolate da
 * forecast_universe_computed(). NON è un'ottimizzazione di portafoglio
 * vera (niente Markowitz/covarianze tra strumenti) — è un ranking
 * rendimento/rischio con un tetto massimo per strumento (35%) e
 * diversificazione per asset class (i candidati per classe sono limitati
 * in proporzione al numero richiesto, vedi sotto, prima di unire e
 * pesare), pensato per essere spiegabile riga per riga, non per essere
 * matematicamente ottimale.
 *
 * Il modello di Previsioni è addestrato/validato solo su orizzonte 12
 * mesi (vedi lib_forecast_model.php): un orizzonte diverso richiede di
 * proiettare quel rendimento annuo con la formula di capitalizzazione
 * composta — corretto SE il rendimento annuo si mantenesse costante nel
 * tempo, un'assunzione reale che va dichiarata, non un dato validato per
 * quell'orizzonte specifico. Per questo il punteggio di ogni candidato
 * viene anche scalato per sqrt(R² out-of-sample) del modello della sua
 * asset class — NON l'R² in-sample: quello misura solo quanto il modello
 * si adatta ai dati che ha già visto, può essere alto anche per un modello
 * senza vera capacità predittiva (vedi lib_forecast_model.php e migrazione
 * 037). L'R² out-of-sample misurato la prima volta ha rivelato che il
 * bond, con R² in-sample 0.50 (il più alto delle tre classi), ha R²
 * out-of-sample -0.04 — nessuna vera capacità predittiva nonostante
 * l'aspetto migliore sulla carta; il crypto è arrivato a -34. Usare
 * l'in-sample per la confidenza avrebbe fatto fidare il tool proprio della
 * classe meno affidabile in pratica. R² out-of-sample negativo o assente
 * → confidenza zero per quella classe (non un valore piccolo ma diverso
 * da zero: un modello che va peggio della semplice media non merita un
 * peso positivo, per quanto piccolo).
 */
function compute_investment_allocation(array $computed, array $globalMeta, float $amount, int $months, int $count = INVEST_ADVISOR_DEFAULT_COUNT, string $riskProfile = INVEST_ADVISOR_DEFAULT_RISK_PROFILE): array
{
    $months = max(INVEST_ADVISOR_MIN_MONTHS, min(INVEST_ADVISOR_MAX_MONTHS, $months));
    $count = max(INVEST_ADVISOR_MIN_COUNT, min(INVEST_ADVISOR_MAX_COUNT, $count));
    if (!isset(INVEST_ADVISOR_RISK_PROFILES[$riskProfile])) $riskProfile = INVEST_ADVISOR_DEFAULT_RISK_PROFILE;
    $profile = INVEST_ADVISOR_RISK_PROFILES[$riskProfile];

    $byClass = array_fill_keys(FORECAST_ASSET_CLASSES, []);
    foreach ($computed as $ticker => $c) {
        if (!isset($globalMeta[$ticker])) continue;
        if (!$c['forecast_available']) continue;

        $horizonReturnPct = ((1 + $c['forecast_pct'] / 100) ** ($months / 12) - 1) * 100;
        if ($horizonReturnPct <= 0) continue; // non ha senso "raccomandare" un calo previsto

        $volatilityPct = $c['features']['volatility']['annualized_pct'];
        if ($volatilityPct <= 0) continue;

        $rSquaredOos = $c['model']['r_squared_oos']; // può essere null (test insufficiente) o negativo
        $confidence = $rSquaredOos !== null ? sqrt(max(0.0, $rSquaredOos)) : 0.0;
        // Confidenza zero = il modello di questa asset class non ha
        // dimostrato nessuna vera capacità predittiva out-of-sample:
        // escluso del tutto, non incluso con un peso che finirebbe a 0
        // comunque (mostrare "consigliato: 0€" sarebbe solo confuso).
        if ($confidence <= 0) continue;

        $rawScore = $horizonReturnPct / ($volatilityPct ** $profile['volatility_exponent']);

        $meta = $globalMeta[$ticker];
        $byClass[$c['asset_class']][] = [
            'ticker' => $ticker,
            'name' => $meta['name'],
            'type' => $meta['type'],
            'asset_class' => $c['asset_class'],
            'current_price' => $c['current_price'],
            'currency' => $c['currency'],
            'horizon_return_pct' => round($horizonReturnPct, 2),
            'volatility_pct' => $volatilityPct,
            'r_squared' => $c['model']['r_squared'],
            'r_squared_oos' => $rSquaredOos,
            'score' => $rawScore * $confidence,
        ];
    }

    // Candidati per classe limitati in proporzione al numero totale
    // richiesto (non un tetto fisso di 3): con 3 classi disponibili e
    // count=9 si comporta come prima (3 per classe); con count=1 pesca 1
    // per classe poi il taglio finale sotto sceglie il migliore assoluto
    // invece di forzare la diversificazione quando l'utente ne vuole solo uno.
    $classesWithCandidates = count(array_filter($byClass, fn($list) => count($list) > 0));
    $maxPerClass = $classesWithCandidates > 0 ? max(1, (int)ceil($count / $classesWithCandidates)) : 1;

    $candidates = [];
    foreach ($byClass as $list) {
        usort($list, fn($a, $b) => $b['score'] <=> $a['score']);
        foreach (array_slice($list, 0, $maxPerClass) as $c) $candidates[] = $c;
    }

    if (!$candidates) {
        return [
            'allocation_available' => false,
            'message' => 'Nessuno strumento tracciato ha sia una previsione di rendimento positiva per questo orizzonte sia un modello con capacità predittiva out-of-sample dimostrata al momento.',
            'months' => $months,
            'risk_profile' => $riskProfile,
        ];
    }

    usort($candidates, fn($a, $b) => $b['score'] <=> $a['score']);
    $candidates = array_slice($candidates, 0, $count); // rispetta esattamente il numero richiesto (o meno, se non ce ne sono abbastanza)

    // Ogni acquisto è una transazione con commissione fissa (uguale per
    // qualunque strumento): non cambia MAI la classifica di chi è "migliore"
    // (la commissione non favorisce un titolo sull'altro), ma riduce il
    // capitale che va davvero investito quando lo si spalma su più
    // strumenti — con un importo piccolo e tanti strumenti richiesti può
    // arrivare a mangiarsi una fetta rilevante, o perfino superare
    // l'importo stesso. Sottratta una sola volta dal capitale prima di
    // pesare (non per singolo strumento dentro al ciclo), e il guadagno %
    // finale resta calcolato sull'importo lordo originale così l'effetto
    // si vede nel risultato invece di sparire silenziosamente.
    $totalFees = count($candidates) * INVEST_ADVISOR_FEE_PER_TRADE;
    $netAmount = $amount - $totalFees;
    if ($netAmount <= 0) {
        return [
            'allocation_available' => false,
            'message' => sprintf(
                'Con %d strumenti le commissioni di acquisto (%s€, %s€ ciascuna) superano o esauriscono l\'importo disponibile. Riduci il numero di strumenti o aumenta l\'importo.',
                count($candidates), number_format($totalFees, 2, ',', '.'), number_format(INVEST_ADVISOR_FEE_PER_TRADE, 2, ',', '.')
            ),
            'months' => $months,
            'risk_profile' => $riskProfile,
        ];
    }

    $weights = cap_and_redistribute_weights(array_column($candidates, 'score'), $profile['max_weight_pct']);

    $rows = [];
    $totalProjectedValue = 0.0;
    foreach ($candidates as $i => $c) {
        $allocatedAmount = $netAmount * $weights[$i];
        $projectedValue = $allocatedAmount * (1 + $c['horizon_return_pct'] / 100);
        $totalProjectedValue += $projectedValue;

        $rows[] = array_merge($c, [
            'weight_pct' => round($weights[$i] * 100, 2),
            'allocated_amount' => round($allocatedAmount, 2),
            'projected_value' => round($projectedValue, 2),
        ]);
    }

    return [
        'allocation_available' => true,
        'months' => $months,
        'amount' => $amount,
        'fee_per_trade' => INVEST_ADVISOR_FEE_PER_TRADE,
        'total_fees' => round($totalFees, 2),
        'net_amount' => round($netAmount, 2),
        'requested_count' => $count,
        'risk_profile' => $riskProfile,
        'max_weight_pct' => $profile['max_weight_pct'],
        'allocations' => $rows,
        'total_projected_value' => round($totalProjectedValue, 2),
        'total_projected_gain_pct' => round(($totalProjectedValue / $amount - 1) * 100, 2),
    ];
}

/**
 * Pesi proporzionali ai punteggi, con un tetto massimo per singolo
 * elemento (in %), l'eccesso ridistribuito sugli altri in proporzione.
 * Se il tetto è matematicamente irraggiungibile con così pochi candidati
 * (es. 2 candidati e tetto 35%: non possono stare entrambi sotto il 35%
 * e sommare a 100%), lo ignora invece di produrre pesi che non sommano a 1.
 */
function cap_and_redistribute_weights(array $scores, float $capPct): array
{
    $n = count($scores);
    $sum = array_sum($scores);
    $weights = array_map(fn($s) => $s / $sum, $scores);

    $cap = $capPct / 100;
    if ($cap * $n < 1.0) return $weights;

    $capped = [];
    for ($iter = 0; $iter < 20; $iter++) {
        $freeKeys = array_filter(array_keys($weights), fn($k) => !isset($capped[$k]));
        if (!$freeKeys) break;

        $freeSum = 0.0;
        foreach ($freeKeys as $k) $freeSum += $weights[$k];
        $cappedBudget = 0.0;
        foreach (array_keys($capped) as $k) $cappedBudget += $weights[$k];
        $remainingBudget = 1.0 - $cappedBudget;

        $anyNewlyCapped = false;
        foreach ($freeKeys as $k) {
            $candidateWeight = $weights[$k] / $freeSum * $remainingBudget;
            if ($candidateWeight > $cap + 1e-9) {
                $weights[$k] = $cap;
                $capped[$k] = true;
                $anyNewlyCapped = true;
            }
        }

        if (!$anyNewlyCapped) {
            foreach ($freeKeys as $k) $weights[$k] = $weights[$k] / $freeSum * $remainingBudget;
            break;
        }
    }

    return $weights;
}
