<?php
declare(strict_types=1);
require __DIR__ . '/db.php';

$uid = require_auth();

switch (method()) {
    case 'GET':
        $loanId = $_GET['loan_id'] ?? null;
        if (!$loanId) respond_error('ID mutuo mancante');
        assert_owned('loans', $loanId, $uid, 'mutuo');

        $stmt = db()->prepare("SELECT id, loan_id, effective_date, rate FROM loan_rate_history WHERE loan_id = :loan_id ORDER BY effective_date ASC");
        $stmt->execute(['loan_id' => $loanId]);
        respond($stmt->fetchAll());
        break;

    case 'POST':
        $in = json_input();
        foreach (['loan_id', 'effective_date', 'rate'] as $f) {
            if (empty($in[$f]) && $in[$f] !== 0) respond_error("Campo obbligatorio mancante: $f");
        }
        assert_owned('loans', $in['loan_id'], $uid, 'mutuo');

        $stmt = db()->prepare("INSERT INTO loan_rate_history (loan_id, effective_date, rate) VALUES (:loan_id, :effective_date, :rate)");
        $stmt->execute([
            'loan_id' => $in['loan_id'],
            'effective_date' => $in['effective_date'],
            'rate' => $in['rate'],
        ]);
        respond(['id' => (int)db()->lastInsertId()], 201);
        break;

    case 'DELETE':
        $id = $_GET['id'] ?? null;
        if (!$id) respond_error('ID mancante');

        $stmt = db()->prepare("SELECT h.id, h.loan_id FROM loan_rate_history h
            JOIN loans l ON l.id = h.loan_id WHERE h.id = :id AND l.user_id = :uid");
        $stmt->execute(['id' => $id, 'uid' => $uid]);
        $row = $stmt->fetch();
        if (!$row) respond_error('Variazione non trovata', 404);

        $countStmt = db()->prepare("SELECT COUNT(*) FROM loan_rate_history WHERE loan_id = :loan_id");
        $countStmt->execute(['loan_id' => $row['loan_id']]);
        if ((int)$countStmt->fetchColumn() <= 1) {
            respond_error('Non puoi eliminare l\'unica variazione di tasso rimasta per questo mutuo');
        }

        db()->prepare("DELETE FROM loan_rate_history WHERE id = :id")->execute(['id' => $id]);
        respond(['ok' => true]);
        break;

    default:
        respond_error('Metodo non supportato', 405);
}
