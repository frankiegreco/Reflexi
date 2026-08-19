<?php
declare(strict_types=1);
require __DIR__ . '/db.php';

$uid = require_auth();

switch (method()) {
    case 'GET':
        $stmt = db()->prepare("SELECT * FROM instruments WHERE user_id = :uid ORDER BY ticker");
        $stmt->execute(['uid' => $uid]);
        respond($stmt->fetchAll());
        break;

    case 'POST':
        $in = json_input();
        if (empty($in['ticker']) || empty($in['name'])) respond_error('Ticker e nome sono obbligatori');

        $ticker = strtoupper(trim($in['ticker']));
        $exists = db()->prepare("SELECT id FROM instruments WHERE user_id = :uid AND ticker = :ticker");
        $exists->execute(['uid' => $uid, 'ticker' => $ticker]);
        if ($exists->fetch()) respond_error('Esiste già uno strumento con questo ticker');

        $stmt = db()->prepare("INSERT INTO instruments (user_id, ticker, name, type, currency, tax_rate)
            VALUES (:uid, :ticker, :name, :type, :currency, :tax_rate)");
        $stmt->execute([
            'uid' => $uid,
            'ticker' => $ticker,
            'name' => $in['name'],
            'type' => $in['type'] ?? 'etf',
            'currency' => $in['currency'] ?? 'EUR',
            'tax_rate' => $in['tax_rate'] ?? 26.00,
        ]);
        respond(['id' => (int)db()->lastInsertId()], 201);
        break;

    case 'PUT':
        $in = json_input();
        if (empty($in['id'])) respond_error('ID mancante');

        $fields = [];
        $params = ['id' => $in['id'], 'uid' => $uid];

        if (array_key_exists('ticker', $in)) {
            $newTicker = strtoupper(trim($in['ticker']));
            $exists = db()->prepare("SELECT id FROM instruments WHERE user_id = :uid AND ticker = :ticker AND id != :id");
            $exists->execute(['uid' => $uid, 'ticker' => $newTicker, 'id' => $in['id']]);
            if ($exists->fetch()) respond_error('Esiste già un altro strumento con questo ticker');
            $fields[] = "ticker = :ticker";
            $params['ticker'] = $newTicker;
        }
        foreach (['name', 'type', 'currency', 'tax_rate'] as $f) {
            if (array_key_exists($f, $in)) {
                $fields[] = "$f = :$f";
                $params[$f] = $in[$f];
            }
        }
        if (!$fields) respond_error('Nessun campo da aggiornare');

        $stmt = db()->prepare("UPDATE instruments SET " . implode(', ', $fields) . " WHERE id = :id AND user_id = :uid");
        $stmt->execute($params);
        respond(['ok' => true]);
        break;

    case 'DELETE':
        if (($_GET['action'] ?? '') === 'bulk') {
            $stmt = db()->prepare("DELETE FROM instruments WHERE user_id = :uid");
            $stmt->execute(['uid' => $uid]);
            respond(['ok' => true, 'deleted' => $stmt->rowCount()]);
            break;
        }

        $id = $_GET['id'] ?? null;
        if (!$id) respond_error('ID mancante');
        db()->prepare("DELETE FROM instruments WHERE id = :id AND user_id = :uid")->execute(['id' => $id, 'uid' => $uid]);
        respond(['ok' => true]);
        break;

    default:
        respond_error('Metodo non supportato', 405);
}
