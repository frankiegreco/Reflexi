<?php
declare(strict_types=1);
require __DIR__ . '/db.php';

if (method() !== 'GET') respond_error('Metodo non supportato', 405);

$uid = require_auth();
$where = ['t.user_id = :uid'];
$params = ['uid' => $uid];

if (!empty($_GET['account_id'])) {
    $where[] = '(t.account_id = :account_id OR t.transfer_account_id = :account_id)';
    $params['account_id'] = $_GET['account_id'];
}
if (!empty($_GET['category_id'])) {
    $where[] = 't.category_id = :category_id';
    $params['category_id'] = $_GET['category_id'];
}
if (!empty($_GET['date_from'])) {
    $where[] = 't.date >= :date_from';
    $params['date_from'] = $_GET['date_from'];
}
if (!empty($_GET['date_to'])) {
    $where[] = 't.date <= :date_to';
    $params['date_to'] = $_GET['date_to'];
}

$sql = "SELECT t.date, a.name AS conto, c.name AS categoria, t.type AS tipo,
               t.amount AS importo, t.description AS descrizione, ta.name AS conto_destinazione
        FROM transactions t
        JOIN accounts a ON a.id = t.account_id
        JOIN categories c ON c.id = t.category_id
        LEFT JOIN accounts ta ON ta.id = t.transfer_account_id
        WHERE " . implode(' AND ', $where) . "
        ORDER BY t.date DESC, t.id DESC";

$stmt = db()->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll();

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="transazioni_' . date('Y-m-d') . '.csv"');

$out = fopen('php://output', 'w');
fputs($out, "\xEF\xBB\xBF"); // BOM per Excel
fputcsv($out, ['Data', 'Conto', 'Categoria', 'Tipo', 'Importo', 'Descrizione', 'Conto destinazione'], ';');
foreach ($rows as $r) {
    fputcsv($out, [
        $r['date'], $r['conto'], $r['categoria'], $r['tipo'],
        number_format((float)$r['importo'], 2, ',', ''), $r['descrizione'], $r['conto_destinazione'],
    ], ';');
}
fclose($out);
exit;
