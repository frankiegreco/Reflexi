<?php
declare(strict_types=1);
require __DIR__ . '/db.php';
require __DIR__ . '/price_fetch.php';

$uid = require_auth();

if (method() !== 'GET') respond_error('Metodo non supportato', 405);

$instrumentId = $_GET['instrument_id'] ?? null;
if (!$instrumentId) respond_error('instrument_id mancante');
assert_owned('instruments', $instrumentId, $uid, 'strumento');

$stmt = db()->prepare("SELECT ticker FROM instruments WHERE id = :id AND user_id = :uid");
$stmt->execute(['id' => $instrumentId, 'uid' => $uid]);
$ticker = $stmt->fetchColumn();
if (!$ticker) respond_error('Strumento non trovato', 404);

$analyst = fetch_analyst_data($ticker);

respond(['available' => $analyst !== null, 'data' => $analyst]);
