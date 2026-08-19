<?php
declare(strict_types=1);
require __DIR__ . '/db.php';

$uid = require_auth();

if (method() !== 'GET') respond_error('Metodo non supportato', 405);

$instrumentId = $_GET['instrument_id'] ?? null;
if (!$instrumentId) respond_error('instrument_id mancante');
assert_owned('instruments', $instrumentId, $uid, 'strumento');

$days = min((int)($_GET['days'] ?? 90), 730);

$stmt = db()->prepare("SELECT ticker FROM instruments WHERE id = :id AND user_id = :uid");
$stmt->execute(['id' => $instrumentId, 'uid' => $uid]);
$ticker = $stmt->fetchColumn();
if (!$ticker) respond_error('Strumento non trovato', 404);

$histStmt = db()->prepare("SELECT date, price, currency FROM price_history
    WHERE ticker = :ticker AND date >= DATE_SUB(CURDATE(), INTERVAL :days DAY)
    ORDER BY date");
$histStmt->bindValue('ticker', $ticker, PDO::PARAM_STR);
$histStmt->bindValue('days', $days, PDO::PARAM_INT);
$histStmt->execute();

respond($histStmt->fetchAll());
