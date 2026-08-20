<?php
declare(strict_types=1);
require __DIR__ . '/db.php';

$uid = require_auth();

switch (method()) {
    case 'GET':
        $accountId = $_GET['account_id'] ?? null;
        if (!$accountId) respond_error('ID conto mancante');
        assert_owned('accounts', $accountId, $uid, 'conto');

        $stmt = db()->prepare("SELECT id, account_id, effective_date, rate FROM account_interest_rate_history WHERE account_id = :account_id ORDER BY effective_date ASC");
        $stmt->execute(['account_id' => $accountId]);
        respond($stmt->fetchAll());
        break;

    case 'POST':
        $in = json_input();
        foreach (['account_id', 'effective_date', 'rate'] as $f) {
            if (empty($in[$f]) && $in[$f] !== 0) respond_error("Campo obbligatorio mancante: $f");
        }
        assert_owned('accounts', $in['account_id'], $uid, 'conto');

        db()->prepare("INSERT INTO account_interest_rate_history (account_id, effective_date, rate) VALUES (:account_id, :effective_date, :rate)
            ON DUPLICATE KEY UPDATE rate = VALUES(rate)")
            ->execute([
                'account_id' => $in['account_id'],
                'effective_date' => $in['effective_date'],
                'rate' => $in['rate'],
            ]);
        respond(['ok' => true], 201);
        break;

    case 'DELETE':
        $id = $_GET['id'] ?? null;
        if (!$id) respond_error('ID mancante');

        $stmt = db()->prepare("SELECT h.id, h.account_id FROM account_interest_rate_history h
            JOIN accounts a ON a.id = h.account_id WHERE h.id = :id AND a.user_id = :uid");
        $stmt->execute(['id' => $id, 'uid' => $uid]);
        $row = $stmt->fetch();
        if (!$row) respond_error('Variazione non trovata', 404);

        $countStmt = db()->prepare("SELECT COUNT(*) FROM account_interest_rate_history WHERE account_id = :account_id");
        $countStmt->execute(['account_id' => $row['account_id']]);
        if ((int)$countStmt->fetchColumn() <= 1) {
            respond_error('Non puoi eliminare l\'unica variazione di tasso rimasta per questo conto');
        }

        db()->prepare("DELETE FROM account_interest_rate_history WHERE id = :id")->execute(['id' => $id]);
        respond(['ok' => true]);
        break;

    default:
        respond_error('Metodo non supportato', 405);
}
