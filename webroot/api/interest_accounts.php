<?php
declare(strict_types=1);
require __DIR__ . '/db.php';

$uid = require_auth();

function with_projection(array $row): array
{
    $balance = (float)$row['balance'];
    $rate = (float)$row['interest_rate'];
    $taxRate = (float)$row['interest_tax_rate'];
    $grossAnnual = $balance * $rate / 100;
    $netAnnual = $grossAnnual * (1 - $taxRate / 100);
    $row['balance'] = $balance;
    $row['interest_rate'] = $rate;
    $row['interest_tax_rate'] = $taxRate;
    $row['gross_annual_interest'] = round($grossAnnual, 2);
    $row['net_annual_interest'] = round($netAnnual, 2);
    $row['is_vincolato'] = !empty($row['vincolo_end_date']) && strtotime($row['vincolo_end_date']) > time();
    return $row;
}

switch (method()) {
    case 'GET':
        $stmt = db()->prepare("SELECT * FROM interest_accounts WHERE user_id = :uid ORDER BY created_at");
        $stmt->execute(['uid' => $uid]);
        respond(array_map('with_projection', $stmt->fetchAll()));
        break;

    case 'POST':
        $in = json_input();
        foreach (['name', 'balance', 'interest_rate'] as $f) {
            if (empty($in[$f]) && $in[$f] !== 0) respond_error("Campo obbligatorio mancante: $f");
        }
        $stmt = db()->prepare("INSERT INTO interest_accounts (user_id, name, bank_name, balance, interest_rate, interest_tax_rate, vincolo_end_date, notes)
            VALUES (:uid, :name, :bank_name, :balance, :rate, :tax_rate, :vincolo, :notes)");
        $stmt->execute([
            'uid' => $uid,
            'name' => $in['name'],
            'bank_name' => $in['bank_name'] ?? null,
            'balance' => $in['balance'],
            'rate' => $in['interest_rate'],
            'tax_rate' => $in['interest_tax_rate'] ?? 26.00,
            'vincolo' => $in['vincolo_end_date'] ?? null,
            'notes' => $in['notes'] ?? null,
        ]);
        respond(['id' => (int)db()->lastInsertId()], 201);
        break;

    case 'PUT':
        $in = json_input();
        if (empty($in['id'])) respond_error('ID mancante');

        $fields = [];
        $params = ['id' => $in['id'], 'uid' => $uid];
        foreach (['name', 'bank_name', 'balance', 'interest_rate', 'interest_tax_rate', 'vincolo_end_date', 'notes'] as $f) {
            if (array_key_exists($f, $in)) {
                $fields[] = "$f = :$f";
                $params[$f] = $in[$f];
            }
        }
        if (!$fields) respond_error('Nessun campo da aggiornare');

        $stmt = db()->prepare("UPDATE interest_accounts SET " . implode(', ', $fields) . " WHERE id = :id AND user_id = :uid");
        $stmt->execute($params);
        respond(['ok' => true]);
        break;

    case 'DELETE':
        $id = $_GET['id'] ?? null;
        if (!$id) respond_error('ID mancante');
        db()->prepare("DELETE FROM interest_accounts WHERE id = :id AND user_id = :uid")->execute(['id' => $id, 'uid' => $uid]);
        respond(['ok' => true]);
        break;

    default:
        respond_error('Metodo non supportato', 405);
}
