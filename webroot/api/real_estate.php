<?php
declare(strict_types=1);
require __DIR__ . '/db.php';

$uid = require_auth();

switch (method()) {
    case 'GET':
        $stmt = db()->prepare("SELECT r.*, l.name AS loan_name FROM real_estate_assets r
            LEFT JOIN loans l ON l.id = r.linked_loan_id
            WHERE r.user_id = :uid ORDER BY r.created_at");
        $stmt->execute(['uid' => $uid]);
        respond($stmt->fetchAll());
        break;

    case 'POST':
        $in = json_input();
        if (empty($in['name']) || !isset($in['estimated_value'])) {
            respond_error('Nome e valore stimato sono obbligatori');
        }
        if (!empty($in['linked_loan_id'])) assert_owned('loans', $in['linked_loan_id'], $uid, 'mutuo');

        $stmt = db()->prepare("INSERT INTO real_estate_assets (user_id, name, estimated_value, linked_loan_id, notes)
            VALUES (:uid, :name, :estimated_value, :linked_loan_id, :notes)");
        $stmt->execute([
            'uid' => $uid,
            'name' => $in['name'],
            'estimated_value' => $in['estimated_value'],
            'linked_loan_id' => $in['linked_loan_id'] ?? null,
            'notes' => $in['notes'] ?? null,
        ]);
        respond(['id' => (int)db()->lastInsertId()], 201);
        break;

    case 'PUT':
        $in = json_input();
        if (empty($in['id'])) respond_error('ID mancante');
        if (array_key_exists('linked_loan_id', $in) && $in['linked_loan_id']) {
            assert_owned('loans', $in['linked_loan_id'], $uid, 'mutuo');
        }

        $fields = [];
        $params = ['id' => $in['id'], 'uid' => $uid];
        foreach (['name', 'estimated_value', 'linked_loan_id', 'notes'] as $f) {
            if (array_key_exists($f, $in)) {
                $fields[] = "$f = :$f";
                $params[$f] = $in[$f];
            }
        }
        if (!$fields) respond_error('Nessun campo da aggiornare');

        $stmt = db()->prepare("UPDATE real_estate_assets SET " . implode(', ', $fields) . " WHERE id = :id AND user_id = :uid");
        $stmt->execute($params);
        respond(['ok' => true]);
        break;

    case 'DELETE':
        if (($_GET['action'] ?? '') === 'bulk') {
            $stmt = db()->prepare("DELETE FROM real_estate_assets WHERE user_id = :uid");
            $stmt->execute(['uid' => $uid]);
            respond(['ok' => true, 'deleted' => $stmt->rowCount()]);
            break;
        }

        $id = $_GET['id'] ?? null;
        if (!$id) respond_error('ID mancante');
        db()->prepare("DELETE FROM real_estate_assets WHERE id = :id AND user_id = :uid")->execute(['id' => $id, 'uid' => $uid]);
        respond(['ok' => true]);
        break;

    default:
        respond_error('Metodo non supportato', 405);
}
