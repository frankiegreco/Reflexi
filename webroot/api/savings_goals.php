<?php
declare(strict_types=1);
require __DIR__ . '/db.php';
require_once __DIR__ . '/lib_goals.php';

$uid = require_auth();

switch (method()) {
    case 'GET':
        $stmt = db()->prepare("SELECT * FROM savings_goals WHERE user_id = :uid ORDER BY target_date IS NULL, target_date, created_at DESC");
        $stmt->execute(['uid' => $uid]);
        $goals = $stmt->fetchAll();
        foreach ($goals as &$g) {
            $g['current_amount'] = round(goal_current_amount($uid, $g), 2);
            $g['pct'] = $g['target_amount'] > 0 ? round(min(100, max(0, $g['current_amount'] / $g['target_amount'] * 100)), 1) : 0;
        }
        respond($goals);
        break;

    case 'POST':
        $in = json_input();
        foreach (['name', 'target_amount'] as $f) {
            if (empty($in[$f])) respond_error("Campo obbligatorio mancante: $f");
        }
        if (($in['account_id'] ?? null) === '') $in['account_id'] = null;
        assert_owned('accounts', $in['account_id'] ?? null, $uid, 'conto');
        $stmt = db()->prepare("INSERT INTO savings_goals (user_id, name, target_amount, account_id, start_date, target_date)
            VALUES (:uid, :name, :target_amount, :account_id, :start_date, :target_date)");
        $stmt->execute([
            'uid' => $uid,
            'name' => $in['name'],
            'target_amount' => $in['target_amount'],
            'account_id' => $in['account_id'] ?? null,
            'start_date' => $in['start_date'] ?? date('Y-m-d'),
            'target_date' => $in['target_date'] ?? null,
        ]);
        respond(['id' => (int)db()->lastInsertId()], 201);
        break;

    case 'PUT':
        $in = json_input();
        if (empty($in['id'])) respond_error('ID mancante');
        if (($in['account_id'] ?? null) === '') $in['account_id'] = null;
        if (array_key_exists('account_id', $in)) assert_owned('accounts', $in['account_id'], $uid, 'conto');

        $fields = [];
        $params = ['id' => $in['id'], 'uid' => $uid];
        foreach (['name', 'target_amount', 'account_id', 'start_date', 'target_date'] as $f) {
            if (array_key_exists($f, $in)) {
                $fields[] = "$f = :$f";
                $params[$f] = $in[$f];
            }
        }
        if (!$fields) respond_error('Nessun campo da aggiornare');

        $stmt = db()->prepare("UPDATE savings_goals SET " . implode(', ', $fields) . " WHERE id = :id AND user_id = :uid");
        $stmt->execute($params);
        respond(['ok' => true]);
        break;

    case 'DELETE':
        if (($_GET['action'] ?? '') === 'bulk') {
            $stmt = db()->prepare("DELETE FROM savings_goals WHERE user_id = :uid");
            $stmt->execute(['uid' => $uid]);
            respond(['ok' => true, 'deleted' => $stmt->rowCount()]);
            break;
        }

        $id = $_GET['id'] ?? null;
        if (!$id) respond_error('ID mancante');
        db()->prepare("DELETE FROM savings_goals WHERE id = :id AND user_id = :uid")->execute(['id' => $id, 'uid' => $uid]);
        respond(['ok' => true]);
        break;

    default:
        respond_error('Metodo non supportato', 405);
}
