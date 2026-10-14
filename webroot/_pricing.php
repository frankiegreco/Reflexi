<?php
declare(strict_types=1);

// Prezzi dei piani letti da Stripe (STRIPE_PRICE_MONTHLY / STRIPE_PRICE_ANNUAL):
// unica fonte, niente cifre scritte a mano nelle pagine. Usato dalle pagine
// pubbliche (piani.php, _seo.php) e da api/pricing.php per l'app React.
//
// Cache su file per un'ora. Se Stripe non risponde si usa l'ultima cache, anche
// scaduta; se non c'è nemmeno quella i prezzi sono null e le pagine mostrano
// "prezzo visibile al checkout" invece di inventare cifre. Un prezzo che Stripe
// restituisce ma non è adatto al piano (archiviato, non ricorrente, intervallo
// sbagliato) finisce nel log e il piano risulta non disponibile (available).

require_once __DIR__ . '/vendor/autoload.php';

const PRICING_CACHE_TTL = 3600;

function pricing_cache_file(): string
{
    return sys_get_temp_dir() . '/reflexi_pricing.json';
}

const PRICING_PLAN_INTERVALS = ['monthly' => 'month', 'annual' => 'year'];

/**
 * available: per piano, false se il prezzo configurato è sicuramente
 * inutilizzabile (archiviato, non ricorrente, intervallo sbagliato, ID
 * inesistente): il frontend nasconde quel piano invece di far partire un
 * checkout destinato a fallire. true quando il piano è valido o quando non lo
 * sappiamo (Stripe non raggiungibile): lì resta "prezzo visibile al checkout".
 */
function pricing_empty(bool $available = true): array
{
    return [
        'monthly' => null,
        'annual' => null,
        'currency' => null,
        'tax_behavior' => null,
        'annual_per_month' => null,
        'months_saved' => null,
        'available' => ['monthly' => $available, 'annual' => $available],
    ];
}

/**
 * Motivi per cui un prezzo non va bene per il piano con l'intervallo atteso
 * ('month'|'year'); lista vuota se è utilizzabile. Condivisa con
 * cli/check_stripe_env.php. Il prodotto va espanso ('expand' => ['product']).
 */
function pricing_price_problems(\Stripe\Price $price, string $interval): array
{
    $problems = [];
    if (!$price->active) $problems[] = 'prezzo archiviato';
    if ($price->product instanceof \Stripe\Product && !$price->product->active) $problems[] = 'prodotto archiviato';
    if ($price->type !== 'recurring' || !$price->recurring) {
        $problems[] = 'non ricorrente';
    } elseif ($price->recurring->interval !== $interval || $price->recurring->interval_count !== 1) {
        $problems[] = "intervallo {$price->recurring->interval_count} {$price->recurring->interval}, atteso 1 $interval";
    }
    if ($price->unit_amount === null) $problems[] = 'importo non fisso';
    return $problems;
}

/** Un prezzo Stripe valido -> importo in unità (es. 7.99) e metadati. */
function pricing_from_stripe(\Stripe\Price $price): array
{
    return [
        'amount' => $price->unit_amount / 100,
        'unit_amount' => $price->unit_amount,
        'currency' => $price->currency,
        'interval' => $price->recurring->interval,
        'tax_behavior' => $price->tax_behavior,
    ];
}

/**
 * Legge e valida un prezzo; null (con anomalia nel log) se non utilizzabile.
 * Un ID inesistente o di un'altra modalità (InvalidRequest) è un errore di
 * configurazione, non un guasto temporaneo: piano non disponibile. Gli errori
 * di rete invece risalgono, così reflexi_pricing() ripiega sulla cache.
 */
function pricing_fetch_plan(string $var, string $id, string $interval, string $key): ?array
{
    try {
        $price = \Stripe\Price::retrieve(['id' => $id, 'expand' => ['product']], ['api_key' => $key]);
    } catch (\Stripe\Exception\InvalidRequestException $e) {
        error_log("reflexi_pricing: $var = $id non utilizzabile: " . $e->getMessage());
        return null;
    }
    $problems = pricing_price_problems($price, $interval);
    if ($problems) {
        error_log("reflexi_pricing: $var = $id non utilizzabile, piano nascosto: " . implode(', ', $problems));
        return null;
    }
    return pricing_from_stripe($price);
}

function pricing_fetch_from_stripe(string $monthlyId, string $annualId): array
{
    $key = getenv('STRIPE_SECRET_KEY') ?: '';
    if ($key === '') throw new RuntimeException('STRIPE_SECRET_KEY mancante');

    // Timeout brevi: la funzione gira durante il rendering di pagine pubbliche,
    // meglio la cache vecchia che una pagina appesa per 30 secondi.
    $http = new \Stripe\HttpClient\CurlClient();
    $http->setConnectTimeout(3);
    $http->setTimeout(5);
    \Stripe\ApiRequestor::setHttpClient($http);

    $monthly = pricing_fetch_plan('STRIPE_PRICE_MONTHLY', $monthlyId, PRICING_PLAN_INTERVALS['monthly'], $key);
    $annual = pricing_fetch_plan('STRIPE_PRICE_ANNUAL', $annualId, PRICING_PLAN_INTERVALS['annual'], $key);

    $data = pricing_empty();
    $data['monthly'] = $monthly;
    $data['annual'] = $annual;
    $data['available'] = ['monthly' => $monthly !== null, 'annual' => $annual !== null];
    $ref = $monthly ?? $annual;
    if ($ref) {
        $data['currency'] = $ref['currency'];
        $data['tax_behavior'] = $ref['tax_behavior'];
    }
    if ($annual) {
        $data['annual_per_month'] = round($annual['unit_amount'] / 12 / 100, 2);
    }
    if ($monthly && $annual && $monthly['unit_amount'] > 0) {
        // 12 - (annuale / mensile), per difetto: mesi "regalati" dal piano annuale.
        $data['months_saved'] = (int)floor(12 - $annual['unit_amount'] / $monthly['unit_amount']);
    }
    return $data;
}

/**
 * Prezzi correnti: cache fresca, altrimenti Stripe, altrimenti ultima cache,
 * altrimenti tutto null. La cache vale solo per gli stessi price ID (un cambio
 * di ID nel .env, es. passaggio test -> live, la invalida).
 */
function reflexi_pricing(): array
{
    $monthlyId = getenv('STRIPE_PRICE_MONTHLY') ?: '';
    $annualId = getenv('STRIPE_PRICE_ANNUAL') ?: '';
    if ($monthlyId === '' || $annualId === '') {
        // Senza ID il checkout di quel piano fallisce comunque: non disponibile.
        $data = pricing_empty();
        $data['available'] = ['monthly' => $monthlyId !== '', 'annual' => $annualId !== ''];
        return $data;
    }

    $idsKey = $monthlyId . '|' . $annualId;
    $cached = null;
    $raw = @file_get_contents(pricing_cache_file());
    if ($raw !== false) {
        $decoded = json_decode($raw, true);
        if (is_array($decoded) && ($decoded['ids'] ?? null) === $idsKey) $cached = $decoded;
    }
    if ($cached && time() - (int)$cached['fetched_at'] < PRICING_CACHE_TTL) return $cached['data'];

    try {
        $data = pricing_fetch_from_stripe($monthlyId, $annualId);
        @file_put_contents(pricing_cache_file(), json_encode(['ids' => $idsKey, 'fetched_at' => time(), 'data' => $data]), LOCK_EX);
        return $data;
    } catch (\Throwable $e) {
        error_log('reflexi_pricing: ' . $e->getMessage());
        return $cached ? $cached['data'] : pricing_empty();
    }
}

/** Importo formattato all'italiana: 7.99 -> "7,99€", 79 -> "79€", "+ IVA" se il prezzo è al netto. */
function pricing_format(?float $amount, ?string $currency, ?string $taxBehavior = null): string
{
    if ($amount === null) return '';
    $decimals = fmod($amount, 1.0) == 0.0 ? 0 : 2;
    $num = number_format($amount, $decimals, ',', '.');
    $symbol = strtolower((string)$currency) === 'eur' ? '€' : ' ' . strtoupper((string)$currency);
    return $num . $symbol . ($taxBehavior === 'exclusive' ? ' + IVA' : '');
}
