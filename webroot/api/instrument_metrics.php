<?php
declare(strict_types=1);
require __DIR__ . '/db.php';
require __DIR__ . '/lib_instrument_metrics.php';

$uid = require_auth();

if (method() !== 'GET') respond_error('Metodo non supportato', 405);

$instrumentId = $_GET['instrument_id'] ?? null;
if (!$instrumentId) respond_error('instrument_id mancante');
assert_owned('instruments', $instrumentId, $uid, 'strumento');

$stmt = db()->prepare("SELECT ticker, currency FROM instruments WHERE id = :id AND user_id = :uid");
$stmt->execute(['id' => $instrumentId, 'uid' => $uid]);
$instrument = $stmt->fetch();
if (!$instrument) respond_error('Strumento non trovato', 404);

$histStmt = db()->prepare("SELECT date, price FROM price_history WHERE ticker = :ticker ORDER BY date");
$histStmt->execute(['ticker' => $instrument['ticker']]);
$rows = $histStmt->fetchAll();

$riskFreeRatePct = isset($_GET['risk_free_rate']) ? (float)$_GET['risk_free_rate'] : 2.0;

$metrics = compute_instrument_metrics($rows, $riskFreeRatePct);
$metrics['ticker'] = $instrument['ticker'];
$metrics['currency'] = $instrument['currency'];

respond($metrics);
