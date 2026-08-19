<?php
declare(strict_types=1);
require __DIR__ . '/db.php';
require_once __DIR__ . '/lib_networth.php';

$uid = require_auth();
if (method() !== 'GET') respond_error('Metodo non supportato', 405);

$type = $_GET['type'] ?? '';
$months = max(1, min(36, (int)($_GET['months'] ?? 12)));

switch ($type) {

    case 'net_worth_trend':
        // Ogni visita a questo report aggiorna anche lo snapshot di oggi,
        // cosi' lo storico si costruisce da solo con l'uso normale dell'app.
        snapshot_net_worth($uid);

        $stmt = db()->prepare("SELECT date, net_worth, liquid_balance, investments_value, real_estate_value, debt_total
            FROM net_worth_history WHERE user_id = :uid AND date >= DATE_SUB(CURDATE(), INTERVAL :months MONTH)
            ORDER BY date");
        $stmt->bindValue('uid', $uid, PDO::PARAM_INT);
        $stmt->bindValue('months', $months, PDO::PARAM_INT);
        $stmt->execute();
        $rows = $stmt->fetchAll();
        foreach ($rows as &$r) {
            foreach (['net_worth', 'liquid_balance', 'investments_value', 'real_estate_value', 'debt_total'] as $f) {
                $r[$f] = (float)$r[$f];
            }
        }
        respond($rows);
        break;

    case 'income_expense':
        $stmt = db()->prepare("
            SELECT DATE_FORMAT(date, '%Y-%m') AS ym,
                COALESCE(SUM(CASE WHEN type = 'entrata' THEN amount ELSE 0 END), 0) AS income,
                COALESCE(SUM(CASE WHEN type = 'uscita' THEN amount ELSE 0 END), 0) AS expense
            FROM transactions
            WHERE user_id = :uid AND date >= DATE_SUB(CURDATE(), INTERVAL :months MONTH) AND type IN ('entrata','uscita')
            GROUP BY ym ORDER BY ym
        ");
        $stmt->bindValue('uid', $uid, PDO::PARAM_INT);
        $stmt->bindValue('months', $months, PDO::PARAM_INT);
        $stmt->execute();
        $rows = $stmt->fetchAll();
        foreach ($rows as &$r) { $r['income'] = (float)$r['income']; $r['expense'] = (float)$r['expense']; }
        respond($rows);
        break;

    case 'spending_by_category':
        $stmt = db()->prepare("
            SELECT c.name, c.color, COALESCE(SUM(t.amount), 0) AS total
            FROM transactions t JOIN categories c ON c.id = t.category_id
            WHERE t.user_id = :uid AND t.type = 'uscita' AND t.date >= DATE_SUB(CURDATE(), INTERVAL :months MONTH)
            GROUP BY c.id ORDER BY total DESC
        ");
        $stmt->bindValue('uid', $uid, PDO::PARAM_INT);
        $stmt->bindValue('months', $months, PDO::PARAM_INT);
        $stmt->execute();
        $rows = $stmt->fetchAll();
        foreach ($rows as &$r) { $r['total'] = (float)$r['total']; }
        respond($rows);
        break;

    case 'spending_by_payee':
        // Raggruppa per descrizione esatta (case-insensitive) — funziona bene
        // per chi usa la ricerca esercenti o scrive sempre lo stesso nome.
        $stmt = db()->prepare("
            SELECT COALESCE(NULLIF(TRIM(description), ''), '(senza descrizione)') AS payee,
                COUNT(*) AS occurrences, COALESCE(SUM(amount), 0) AS total
            FROM transactions
            WHERE user_id = :uid AND type = 'uscita' AND date >= DATE_SUB(CURDATE(), INTERVAL :months MONTH)
            GROUP BY LOWER(TRIM(description)) ORDER BY total DESC LIMIT 15
        ");
        $stmt->bindValue('uid', $uid, PDO::PARAM_INT);
        $stmt->bindValue('months', $months, PDO::PARAM_INT);
        $stmt->execute();
        $rows = $stmt->fetchAll();
        foreach ($rows as &$r) { $r['total'] = (float)$r['total']; $r['occurrences'] = (int)$r['occurrences']; }
        respond($rows);
        break;

    default:
        respond_error('Tipo di report non valido');
}
