<?php
declare(strict_types=1);
require __DIR__ . '/db.php';

$uid = require_auth();
if (method() !== 'GET') respond_error('Metodo non supportato', 405);

// Solo strumenti in EUR: come nella vista Investimenti, i totali restano
// separati per valuta di proposito, nessuna conversione automatica qui.
$stmt = db()->prepare("SELECT id, ticker FROM instruments WHERE user_id = :uid AND currency = 'EUR'");
$stmt->execute(['uid' => $uid]);
$instruments = $stmt->fetchAll();

if (!$instruments) respond([]);

$allDates = [];        // insieme di tutte le date con almeno una quotazione nota
$priceByTickerDate = []; // [ticker][date] => prezzo
$qtyEvents = [];        // [instrument_id] => [[date, delta_quantity], ...] ordinati

foreach ($instruments as $instr) {
    $priceStmt = db()->prepare("SELECT date, price FROM price_history WHERE ticker = :ticker ORDER BY date");
    $priceStmt->execute(['ticker' => $instr['ticker']]);
    foreach ($priceStmt->fetchAll() as $row) {
        $priceByTickerDate[$instr['ticker']][$row['date']] = (float)$row['price'];
        $allDates[$row['date']] = true;
    }

    $txStmt = db()->prepare("SELECT date, type, quantity FROM investment_transactions WHERE instrument_id = :id AND user_id = :uid ORDER BY date");
    $txStmt->execute(['id' => $instr['id'], 'uid' => $uid]);
    $events = [];
    foreach ($txStmt->fetchAll() as $t) {
        $delta = $t['type'] === 'acquisto' ? (float)$t['quantity'] : -(float)$t['quantity'];
        $events[] = [$t['date'], $delta];
    }
    $qtyEvents[$instr['id']] = $events;
}

if (!$allDates) respond([]);

ksort($allDates);
$dates = array_keys($allDates);

$result = [];
foreach ($dates as $date) {
    $total = 0.0;
    foreach ($instruments as $instr) {
        // Quantita' posseduta a quella data: somma degli eventi fino a quella data inclusa
        $qty = 0.0;
        foreach ($qtyEvents[$instr['id']] as [$evDate, $delta]) {
            if ($evDate <= $date) $qty += $delta;
        }
        if ($qty <= 0) continue;

        // Prezzo a quella data, o l'ultimo prezzo noto prima di quella data
        $priceMap = $priceByTickerDate[$instr['ticker']] ?? [];
        if (isset($priceMap[$date])) {
            $price = $priceMap[$date];
        } else {
            $price = null;
            foreach ($priceMap as $d => $p) {
                if ($d <= $date) $price = $p; else break;
            }
        }
        if ($price !== null) $total += $qty * $price;
    }
    $result[] = ['date' => $date, 'value' => round($total, 2)];
}

respond($result);
