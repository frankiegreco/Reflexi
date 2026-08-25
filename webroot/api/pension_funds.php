<?php
declare(strict_types=1);
require __DIR__ . '/db.php';
require_once __DIR__ . '/lib_projection.php';

$uid = require_auth();

/** Proiezione del capitale a una data età di pensionamento, con versamenti mensili costanti. */
function project_pension(float $balance, float $monthlyContribution, float $employerContribution, float $annualReturn, int $months): float
{
    return compound_growth_project($balance, $monthlyContribution + $employerContribution, $annualReturn, $months);
}

switch (method()) {
    case 'GET':
        $retirementAge = (int)($_GET['retirement_age'] ?? 67);
        $currentAge = (int)($_GET['current_age'] ?? 0);
        $monthsToRetirement = $currentAge > 0 ? max(0, ($retirementAge - $currentAge) * 12) : 0;

        $stmt = db()->prepare("SELECT * FROM pension_funds WHERE user_id = :uid ORDER BY created_at");
        $stmt->execute(['uid' => $uid]);
        $funds = $stmt->fetchAll();

        foreach ($funds as &$f) {
            $f['current_balance'] = (float)$f['current_balance'];
            $f['monthly_contribution'] = (float)$f['monthly_contribution'];
            $f['employer_contribution'] = (float)$f['employer_contribution'];
            $f['expected_annual_return'] = (float)$f['expected_annual_return'];
            $f['projected_at_retirement'] = $monthsToRetirement > 0
                ? round(project_pension($f['current_balance'], $f['monthly_contribution'], $f['employer_contribution'], $f['expected_annual_return'], $monthsToRetirement), 2)
                : $f['current_balance'];
        }
        respond($funds);
        break;

    case 'POST':
        $in = json_input();
        if (empty($in['name'])) respond_error('Il nome del fondo è obbligatorio');

        $stmt = db()->prepare("INSERT INTO pension_funds (user_id, name, fund_type, current_balance, monthly_contribution, employer_contribution, expected_annual_return, notes)
            VALUES (:uid, :name, :type, :balance, :monthly, :employer, :return, :notes)");
        $stmt->execute([
            'uid' => $uid,
            'name' => $in['name'],
            'type' => $in['fund_type'] ?? 'negoziale',
            'balance' => $in['current_balance'] ?? 0,
            'monthly' => $in['monthly_contribution'] ?? 0,
            'employer' => $in['employer_contribution'] ?? 0,
            'return' => $in['expected_annual_return'] ?? 3.00,
            'notes' => $in['notes'] ?? null,
        ]);
        respond(['id' => (int)db()->lastInsertId()], 201);
        break;

    case 'PUT':
        $in = json_input();
        if (empty($in['id'])) respond_error('ID mancante');

        $fields = [];
        $params = ['id' => $in['id'], 'uid' => $uid];
        foreach (['name', 'fund_type', 'current_balance', 'monthly_contribution', 'employer_contribution', 'expected_annual_return', 'notes'] as $f) {
            if (array_key_exists($f, $in)) {
                $fields[] = "$f = :$f";
                $params[$f] = $in[$f];
            }
        }
        if (!$fields) respond_error('Nessun campo da aggiornare');

        $stmt = db()->prepare("UPDATE pension_funds SET " . implode(', ', $fields) . " WHERE id = :id AND user_id = :uid");
        $stmt->execute($params);
        respond(['ok' => true]);
        break;

    case 'DELETE':
        if (($_GET['action'] ?? '') === 'bulk') {
            $stmt = db()->prepare("DELETE FROM pension_funds WHERE user_id = :uid");
            $stmt->execute(['uid' => $uid]);
            respond(['ok' => true, 'deleted' => $stmt->rowCount()]);
            break;
        }

        $id = $_GET['id'] ?? null;
        if (!$id) respond_error('ID mancante');
        db()->prepare("DELETE FROM pension_funds WHERE id = :id AND user_id = :uid")->execute(['id' => $id, 'uid' => $uid]);
        respond(['ok' => true]);
        break;

    default:
        respond_error('Metodo non supportato', 405);
}
