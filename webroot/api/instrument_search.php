<?php
declare(strict_types=1);
require __DIR__ . '/db.php';
require_once __DIR__ . '/price_fetch.php';

$uid = require_auth();

if (method() !== 'GET') respond_error('Metodo non supportato', 405);

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

$typeMap = [
    'EQUITY' => 'azione',
    'ETF' => 'etf',
    'MUTUALFUND' => 'fondo',
    'CRYPTOCURRENCY' => 'crypto',
];

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
