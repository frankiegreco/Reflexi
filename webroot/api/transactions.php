<?php
declare(strict_types=1);
require __DIR__ . '/db.php';

$uid = require_auth();

switch (method()) {
    case 'GET':
        $where = ['t.user_id = :uid'];
        $params = ['uid' => $uid];

        if (!empty($_GET['account_id'])) {
            $where[] = '(t.account_id = :account_id OR t.transfer_account_id = :account_id2)';
            $params['account_id'] = $_GET['account_id'];
            $params['account_id2'] = $_GET['account_id'];
        }
        if (!empty($_GET['category_id'])) {
            $where[] = 't.category_id = :category_id';
            $params['category_id'] = $_GET['category_id'];
        }
        if (!empty($_GET['type'])) {
            $where[] = 't.type = :type';
            $params['type'] = $_GET['type'];
        }
        if (!empty($_GET['date_from'])) {
            $where[] = 't.date >= :date_from';
            $params['date_from'] = $_GET['date_from'];
        }
        if (!empty($_GET['date_to'])) {
            $where[] = 't.date <= :date_to';
            $params['date_to'] = $_GET['date_to'];
        }
        if (!empty($_GET['search'])) {
            $where[] = 't.description LIKE :search';
            $params['search'] = '%' . $_GET['search'] . '%';
        }

        $limit = min((int)($_GET['limit'] ?? 100), 1000);
        $offset = max((int)($_GET['offset'] ?? 0), 0);

        $sql = "SELECT t.*, a.name AS account_name, a.color AS account_color,
                       c.name AS category_name, c.color AS category_color,
                       ta.name AS transfer_account_name
                FROM transactions t
                JOIN accounts a ON a.id = t.account_id
                JOIN categories c ON c.id = t.category_id
                LEFT JOIN accounts ta ON ta.id = t.transfer_account_id
                WHERE " . implode(' AND ', $where) . "
                ORDER BY t.date DESC, t.id DESC
                LIMIT $limit OFFSET $offset";

        $stmt = db()->prepare($sql);
        $stmt->execute($params);
        respond($stmt->fetchAll());
        break;

    case 'POST':
        $in = json_input();
        foreach (['account_id', 'category_id', 'amount', 'type', 'date'] as $f) {
            if (empty($in[$f]) && $in[$f] !== 0) respond_error("Campo obbligatorio mancante: $f");
        }
        if ($in['type'] === 'trasferimento' && empty($in['transfer_account_id'])) {
            respond_error('Un trasferimento richiede il conto di destinazione');
        }

        assert_owned('accounts', $in['account_id'], $uid, 'conto');
        assert_owned('categories', $in['category_id'], $uid, 'categoria');
        assert_owned('accounts', $in['transfer_account_id'] ?? null, $uid, 'conto destinazione');

        $stmt = db()->prepare("INSERT INTO transactions
            (user_id, account_id, category_id, transfer_account_id, amount, type, description, date, recurring_id)
            VALUES (:uid, :account_id, :category_id, :transfer_account_id, :amount, :type, :description, :date, :recurring_id)");
        $stmt->execute([
            'uid' => $uid,
            'account_id' => $in['account_id'],
            'category_id' => $in['category_id'],
            'transfer_account_id' => $in['transfer_account_id'] ?? null,
            'amount' => abs((float)$in['amount']),
            'type' => $in['type'],
            'description' => $in['description'] ?? null,
            'date' => $in['date'],
            'recurring_id' => $in['recurring_id'] ?? null,
        ]);
        respond(['id' => (int)db()->lastInsertId()], 201);
        break;

    case 'PUT':
        $in = json_input();
        if (empty($in['id'])) respond_error('ID mancante');

        $fields = [];
        $params = ['id' => $in['id'], 'uid' => $uid];
        foreach (['account_id', 'category_id', 'transfer_account_id', 'amount', 'type', 'description', 'date'] as $f) {
            if (array_key_exists($f, $in)) {
                $fields[] = "$f = :$f";
                $params[$f] = $f === 'amount' ? abs((float)$in[$f]) : $in[$f];
            }
        }
        if (!$fields) respond_error('Nessun campo da aggiornare');

        if (array_key_exists('account_id', $in)) assert_owned('accounts', $in['account_id'], $uid, 'conto');
        if (array_key_exists('category_id', $in)) assert_owned('categories', $in['category_id'], $uid, 'categoria');
        if (array_key_exists('transfer_account_id', $in)) assert_owned('accounts', $in['transfer_account_id'], $uid, 'conto destinazione');

        $stmt = db()->prepare("UPDATE transactions SET " . implode(', ', $fields) . " WHERE id = :id AND user_id = :uid");
        $stmt->execute($params);
        respond(['ok' => true]);
        break;

    case 'DELETE':
        if (($_GET['action'] ?? '') === 'bulk') {
            $where = ['user_id = :uid'];
            $params = ['uid' => $uid];

            if (!empty($_GET['account_id'])) {
                $where[] = '(account_id = :account_id OR transfer_account_id = :account_id2)';
                $params['account_id'] = $_GET['account_id'];
                $params['account_id2'] = $_GET['account_id'];
            }
            if (!empty($_GET['category_id'])) {
                $where[] = 'category_id = :category_id';
                $params['category_id'] = $_GET['category_id'];
            }
            if (!empty($_GET['type'])) {
                $where[] = 'type = :type';
                $params['type'] = $_GET['type'];
            }
            if (!empty($_GET['date_from'])) {
                $where[] = 'date >= :date_from';
                $params['date_from'] = $_GET['date_from'];
            }
            if (!empty($_GET['date_to'])) {
                $where[] = 'date <= :date_to';
                $params['date_to'] = $_GET['date_to'];
            }
            if (!empty($_GET['search'])) {
                $where[] = 'description LIKE :search';
                $params['search'] = '%' . $_GET['search'] . '%';
            }

            $stmt = db()->prepare("DELETE FROM transactions WHERE " . implode(' AND ', $where));
            $stmt->execute($params);
            respond(['ok' => true, 'deleted' => $stmt->rowCount()]);
            break;
        }

        $id = $_GET['id'] ?? null;
        if (!$id) respond_error('ID mancante');
        $stmt = db()->prepare("DELETE FROM transactions WHERE id = :id AND user_id = :uid");
        $stmt->execute(['id' => $id, 'uid' => $uid]);
        respond(['ok' => true]);
        break;

    default:
        respond_error('Metodo non supportato', 405);
}
