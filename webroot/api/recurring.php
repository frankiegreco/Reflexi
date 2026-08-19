<?php
declare(strict_types=1);
require __DIR__ . '/db.php';
require __DIR__ . '/lib_recurring.php';

$uid = require_auth();

// Genera le transazioni dovute e avanza la scadenza, solo per le ricorrenze
// dell'utente loggato. Richiamabile da un pulsante nell'interfaccia; il job
// cron (webroot/cron/process_recurring.php) usa la stessa logica per tutti
// gli utenti in automatico, senza passare da qui (nessuna sessione in CLI).
if (isset($_GET['action']) && $_GET['action'] === 'process') {
    respond(['created' => process_due_recurring($uid)]);
}

switch (method()) {
    case 'GET':
        $sql = "SELECT r.*, a.name AS account_name, c.name AS category_name, c.type AS category_type,
                       ta.name AS transfer_account_name
                FROM recurring_transactions r
                JOIN accounts a ON a.id = r.account_id
                JOIN categories c ON c.id = r.category_id
                LEFT JOIN accounts ta ON ta.id = r.transfer_account_id
                WHERE r.user_id = :uid
                ORDER BY r.next_run";
        $stmt = db()->prepare($sql);
        $stmt->execute(['uid' => $uid]);
        respond($stmt->fetchAll());
        break;

    case 'POST':
        $in = json_input();
        foreach (['account_id', 'category_id', 'amount', 'type', 'frequency', 'start_date'] as $f) {
            if (empty($in[$f])) respond_error("Campo obbligatorio mancante: $f");
        }
        if ($in['type'] === 'trasferimento' && empty($in['transfer_account_id'])) {
            respond_error('Un trasferimento richiede il conto di destinazione');
        }

        assert_owned('accounts', $in['account_id'], $uid, 'conto');
        assert_owned('categories', $in['category_id'], $uid, 'categoria');
        assert_owned('accounts', $in['transfer_account_id'] ?? null, $uid, 'conto destinazione');

        $stmt = db()->prepare("INSERT INTO recurring_transactions
            (user_id, account_id, category_id, transfer_account_id, amount, type, description,
             frequency, interval_count, start_date, end_date, next_run, active)
            VALUES (:uid, :account_id, :category_id, :transfer_account_id, :amount, :type, :description,
             :frequency, :interval_count, :start_date, :end_date, :next_run, :active)");
        $stmt->execute([
            'uid' => $uid,
            'account_id' => $in['account_id'],
            'category_id' => $in['category_id'],
            'transfer_account_id' => $in['transfer_account_id'] ?? null,
            'amount' => abs((float)$in['amount']),
            'type' => $in['type'],
            'description' => $in['description'] ?? null,
            'frequency' => $in['frequency'],
            'interval_count' => $in['interval_count'] ?? 1,
            'start_date' => $in['start_date'],
            'end_date' => $in['end_date'] ?? null,
            'next_run' => $in['start_date'],
            'active' => $in['active'] ?? 1,
        ]);
        respond(['id' => (int)db()->lastInsertId()], 201);
        break;

    case 'PUT':
        $in = json_input();
        if (empty($in['id'])) respond_error('ID mancante');

        $fields = [];
        $params = ['id' => $in['id'], 'uid' => $uid];
        foreach (['account_id', 'category_id', 'transfer_account_id', 'amount', 'type', 'description',
                   'frequency', 'interval_count', 'start_date', 'end_date', 'next_run', 'active'] as $f) {
            if (array_key_exists($f, $in)) {
                $fields[] = "$f = :$f";
                $params[$f] = $in[$f];
            }
        }
        if (!$fields) respond_error('Nessun campo da aggiornare');

        if (array_key_exists('account_id', $in)) assert_owned('accounts', $in['account_id'], $uid, 'conto');
        if (array_key_exists('category_id', $in)) assert_owned('categories', $in['category_id'], $uid, 'categoria');
        if (array_key_exists('transfer_account_id', $in)) assert_owned('accounts', $in['transfer_account_id'], $uid, 'conto destinazione');

        $stmt = db()->prepare("UPDATE recurring_transactions SET " . implode(', ', $fields) . " WHERE id = :id AND user_id = :uid");
        $stmt->execute($params);
        respond(['ok' => true]);
        break;

    case 'DELETE':
        if (($_GET['action'] ?? '') === 'bulk') {
            $where = ['user_id = :uid'];
            $params = ['uid' => $uid];
            if (!empty($_GET['category_id'])) {
                $where[] = 'category_id = :category_id';
                $params['category_id'] = $_GET['category_id'];
            }
            $stmt = db()->prepare("DELETE FROM recurring_transactions WHERE " . implode(' AND ', $where));
            $stmt->execute($params);
            respond(['ok' => true, 'deleted' => $stmt->rowCount()]);
            break;
        }

        $id = $_GET['id'] ?? null;
        if (!$id) respond_error('ID mancante');
        $stmt = db()->prepare("DELETE FROM recurring_transactions WHERE id = :id AND user_id = :uid");
        $stmt->execute(['id' => $id, 'uid' => $uid]);
        respond(['ok' => true]);
        break;

    default:
        respond_error('Metodo non supportato', 405);
}
