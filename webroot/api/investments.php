<?php
declare(strict_types=1);
require __DIR__ . '/db.php';

$uid = require_auth();

// Calcola (senza salvare) quale sarebbe l'imposta su una vendita, dato costo
// medio attuale — usato dal form per "Ricalcola imposta" su operazioni gia'
// esistenti, che altrimenti non verrebbero mai ricalcolate automaticamente.
if (isset($_GET['action']) && $_GET['action'] === 'preview_tax') {
    $in = json_input();
    foreach (['instrument_id', 'quantity', 'price_per_unit'] as $f) {
        if (empty($in[$f]) && $in[$f] !== 0) respond_error("Campo obbligatorio mancante: $f");
    }
    assert_owned('instruments', $in['instrument_id'], $uid, 'strumento');

    $instrStmt = db()->prepare("SELECT tax_rate FROM instruments WHERE id = :id AND user_id = :uid");
    $instrStmt->execute(['id' => $in['instrument_id'], 'uid' => $uid]);
    $taxRate = (float)($instrStmt->fetchColumn() ?: 26);

    // Se stiamo ricalcolando una vendita gia' esistente, la escludiamo dal
    // calcolo del costo medio (altrimenti si conteggerebbe da sola).
    $excludeId = $in['exclude_id'] ?? null;
    $sql = "SELECT type, quantity, price_per_unit, fees FROM investment_transactions
        WHERE instrument_id = :id AND user_id = :uid";
    $params = ['id' => $in['instrument_id'], 'uid' => $uid];
    if ($excludeId) { $sql .= " AND id != :exclude_id"; $params['exclude_id'] = $excludeId; }
    $sql .= " ORDER BY date, id";
    $priorStmt = db()->prepare($sql);
    $priorStmt->execute($params);

    $qtyAcc = 0.0; $costAcc = 0.0;
    foreach ($priorStmt->fetchAll() as $t) {
        if ($t['type'] === 'acquisto') {
            $costAcc += (float)$t['quantity'] * (float)$t['price_per_unit'] + (float)$t['fees'];
            $qtyAcc += (float)$t['quantity'];
        } else {
            $avgCost = $qtyAcc > 0 ? $costAcc / $qtyAcc : 0;
            $costAcc -= $avgCost * (float)$t['quantity'];
            $qtyAcc -= (float)$t['quantity'];
        }
    }
    $avgCostBeforeThisSale = $qtyAcc > 0 ? $costAcc / $qtyAcc : 0;
    $fees = (float)($in['fees'] ?? 0);
    $proceeds = (float)$in['quantity'] * (float)$in['price_per_unit'] - $fees;
    $gain = $proceeds - ($avgCostBeforeThisSale * (float)$in['quantity']);
    $taxAmount = $gain > 0 ? round($gain * $taxRate / 100, 2) : 0.0;

    respond(['avg_cost' => round($avgCostBeforeThisSale, 4), 'gain' => round($gain, 2), 'tax_amount' => $taxAmount, 'tax_rate' => $taxRate]);
}

switch (method()) {
    case 'GET':
        $where = 'it.user_id = :uid';
        $params = ['uid' => $uid];
        if (!empty($_GET['instrument_id'])) {
            $where .= ' AND it.instrument_id = :instrument_id';
            $params['instrument_id'] = $_GET['instrument_id'];
        }
        $stmt = db()->prepare("SELECT it.*, i.ticker, i.name AS instrument_name, a.name AS account_name
            FROM investment_transactions it
            JOIN instruments i ON i.id = it.instrument_id
            LEFT JOIN accounts a ON a.id = it.account_id
            WHERE $where
            ORDER BY it.date DESC, it.id DESC");
        $stmt->execute($params);
        respond($stmt->fetchAll());
        break;

    case 'POST':
        $in = json_input();
        foreach (['instrument_id', 'type', 'quantity', 'price_per_unit', 'date'] as $f) {
            if (empty($in[$f]) && $in[$f] !== 0) respond_error("Campo obbligatorio mancante: $f");
        }
        assert_owned('instruments', $in['instrument_id'], $uid, 'strumento');

        $instrStmt = db()->prepare("SELECT name, tax_rate FROM instruments WHERE id = :id AND user_id = :uid");
        $instrStmt->execute(['id' => $in['instrument_id'], 'uid' => $uid]);
        $instrument = $instrStmt->fetch();
        $instrName = $instrument['name'] ?? 'strumento';

        $isBuy = $in['type'] === 'acquisto';
        $quantity = (float)$in['quantity'];
        $pricePerUnit = (float)$in['price_per_unit'];
        $fees = (float)($in['fees'] ?? 0);
        $taxAmount = 0.0;

        // Su una vendita, calcola la plusvalenza usando il costo medio di carico
        // (stesso metodo usato in portfolio.php) e trattiene l'imposta sostitutiva
        // SOLO se c'e' un guadagno — su una minusvalenza non si paga nulla.
        // Semplificazione dichiarata: non tiene conto della compensazione di
        // minusvalenze pregresse con plusvalenze future (regime dichiarativo reale
        // in Italia lo permetterebbe entro 4 anni) — qui ogni vendita e' calcolata
        // in isolamento, come farebbe un intermediario in regime amministrato.
        if (!$isBuy) {
            $priorStmt = db()->prepare("SELECT type, quantity, price_per_unit, fees FROM investment_transactions
                WHERE instrument_id = :id AND user_id = :uid ORDER BY date, id");
            $priorStmt->execute(['id' => $in['instrument_id'], 'uid' => $uid]);
            $qtyAcc = 0.0; $costAcc = 0.0;
            foreach ($priorStmt->fetchAll() as $t) {
                if ($t['type'] === 'acquisto') {
                    $costAcc += (float)$t['quantity'] * (float)$t['price_per_unit'] + (float)$t['fees'];
                    $qtyAcc += (float)$t['quantity'];
                } else {
                    $avgCost = $qtyAcc > 0 ? $costAcc / $qtyAcc : 0;
                    $costAcc -= $avgCost * (float)$t['quantity'];
                    $qtyAcc -= (float)$t['quantity'];
                }
            }
            $avgCostBeforeThisSale = $qtyAcc > 0 ? $costAcc / $qtyAcc : 0;
            $proceeds = $quantity * $pricePerUnit - $fees;
            $gain = $proceeds - ($avgCostBeforeThisSale * $quantity);
            $taxRate = (float)($instrument['tax_rate'] ?? 26);
            $taxAmount = $gain > 0 ? round($gain * $taxRate / 100, 2) : 0.0;
        }

        $linkedId = null;
        if (!empty($in['record_cash_movement'])) {
            if (empty($in['account_id'])) respond_error('Per registrare il movimento di cassa serve un conto');
            assert_owned('accounts', $in['account_id'], $uid, 'conto');

            // Sull'acquisto il netto e' prezzo+commissioni; sulla vendita e'
            // prezzo-commissioni-imposta: e' quello che arriva davvero sul conto.
            $amount = $isBuy
                ? ($quantity * $pricePerUnit + $fees)
                : ($quantity * $pricePerUnit - $fees - $taxAmount);
            $catName = $isBuy ? 'Investimenti (acquisto)' : 'Investimenti (vendita)';
            $catStmt = db()->prepare("SELECT id FROM categories WHERE user_id = :uid AND name = :name");
            $catStmt->execute(['uid' => $uid, 'name' => $catName]);
            $categoryId = $catStmt->fetchColumn();

            if ($categoryId) {
                $description = ($isBuy ? 'Acquisto ' : 'Vendita ') . $instrName;
                if (!$isBuy && $taxAmount > 0) {
                    $description .= " (netto imposte: -" . number_format($taxAmount, 2, ',', '.') . "€)";
                }
                $txStmt = db()->prepare("INSERT INTO transactions (user_id, account_id, category_id, amount, type, description, date)
                    VALUES (:uid, :account_id, :category_id, :amount, :type, :description, :date)");
                $txStmt->execute([
                    'uid' => $uid,
                    'account_id' => $in['account_id'],
                    'category_id' => $categoryId,
                    'amount' => abs($amount),
                    'type' => $isBuy ? 'uscita' : 'entrata',
                    'description' => $description,
                    'date' => $in['date'],
                ]);
                $linkedId = (int)db()->lastInsertId();
            }
        }

        $stmt = db()->prepare("INSERT INTO investment_transactions
            (user_id, instrument_id, account_id, linked_transaction_id, type, quantity, price_per_unit, fees, tax_amount, date, notes)
            VALUES (:uid, :instrument_id, :account_id, :linked_transaction_id, :type, :quantity, :price_per_unit, :fees, :tax_amount, :date, :notes)");
        $stmt->execute([
            'uid' => $uid,
            'instrument_id' => $in['instrument_id'],
            'account_id' => $in['account_id'] ?? null,
            'linked_transaction_id' => $linkedId,
            'type' => $in['type'],
            'quantity' => $quantity,
            'price_per_unit' => $pricePerUnit,
            'fees' => $fees,
            'tax_amount' => $taxAmount,
            'date' => $in['date'],
            'notes' => $in['notes'] ?? null,
        ]);
        respond(['id' => (int)db()->lastInsertId(), 'linked_transaction_id' => $linkedId, 'tax_amount' => $taxAmount], 201);
        break;

    case 'PUT':
        $in = json_input();
        if (empty($in['id'])) respond_error('ID mancante');

        $fields = [];
        $params = ['id' => $in['id'], 'uid' => $uid];
        foreach (['instrument_id', 'account_id', 'type', 'quantity', 'price_per_unit', 'fees', 'tax_amount', 'date', 'notes'] as $f) {
            if (array_key_exists($f, $in)) {
                $fields[] = "$f = :$f";
                $params[$f] = $in[$f];
            }
        }
        if (!$fields) respond_error('Nessun campo da aggiornare');

        if (array_key_exists('instrument_id', $in)) assert_owned('instruments', $in['instrument_id'], $uid, 'strumento');
        if (array_key_exists('account_id', $in)) assert_owned('accounts', $in['account_id'], $uid, 'conto');

        $stmt = db()->prepare("UPDATE investment_transactions SET " . implode(', ', $fields) . " WHERE id = :id AND user_id = :uid");
        $stmt->execute($params);

        // Se questa operazione ha un movimento di cassa collegato, lo riallinea
        // al nuovo importo netto corretto — altrimenti dopo una modifica (es.
        // ricalcolo dell'imposta) il saldo del conto resterebbe quello vecchio,
        // disallineato da quanto risulta ora nella scheda dell'investimento.
        $rowStmt = db()->prepare("SELECT * FROM investment_transactions WHERE id = :id AND user_id = :uid");
        $rowStmt->execute(['id' => $in['id'], 'uid' => $uid]);
        $row = $rowStmt->fetch();

        $linkedAdjusted = false;
        if ($row && $row['linked_transaction_id']) {
            $isBuy = $row['type'] === 'acquisto';
            $correctAmount = $isBuy
                ? ((float)$row['quantity'] * (float)$row['price_per_unit'] + (float)$row['fees'])
                : ((float)$row['quantity'] * (float)$row['price_per_unit'] - (float)$row['fees'] - (float)$row['tax_amount']);
            $linkStmt = db()->prepare("UPDATE transactions SET amount = :amount WHERE id = :id AND user_id = :uid");
            $linkStmt->execute(['amount' => abs($correctAmount), 'id' => $row['linked_transaction_id'], 'uid' => $uid]);
            $linkedAdjusted = true;
        }

        respond(['ok' => true, 'linked_transaction_adjusted' => $linkedAdjusted]);
        break;

    case 'DELETE':
        if (($_GET['action'] ?? '') === 'bulk') {
            $deleteLinked = ($_GET['delete_linked'] ?? '0') === '1';
            if ($deleteLinked) {
                db()->prepare("DELETE t FROM transactions t
                    JOIN investment_transactions it ON it.linked_transaction_id = t.id
                    WHERE it.user_id = :uid AND t.user_id = :uid2")
                    ->execute(['uid' => $uid, 'uid2' => $uid]);
            }
            $stmt = db()->prepare("DELETE FROM investment_transactions WHERE user_id = :uid");
            $stmt->execute(['uid' => $uid]);
            respond(['ok' => true, 'deleted' => $stmt->rowCount()]);
            break;
        }

        $id = $_GET['id'] ?? null;
        if (!$id) respond_error('ID mancante');
        $deleteLinked = ($_GET['delete_linked'] ?? '0') === '1';

        if ($deleteLinked) {
            $stmt = db()->prepare("SELECT linked_transaction_id FROM investment_transactions WHERE id = :id AND user_id = :uid");
            $stmt->execute(['id' => $id, 'uid' => $uid]);
            $linkedId = $stmt->fetchColumn();
            if ($linkedId) {
                db()->prepare("DELETE FROM transactions WHERE id = :id AND user_id = :uid")->execute(['id' => $linkedId, 'uid' => $uid]);
            }
        }
        db()->prepare("DELETE FROM investment_transactions WHERE id = :id AND user_id = :uid")->execute(['id' => $id, 'uid' => $uid]);
        respond(['ok' => true]);
        break;

    default:
        respond_error('Metodo non supportato', 405);
}
