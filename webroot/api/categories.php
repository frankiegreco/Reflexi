<?php
declare(strict_types=1);
require __DIR__ . '/db.php';

$uid = require_auth();

switch (method()) {
    case 'GET':
        $type = $_GET['type'] ?? null;
        if ($type) {
            $stmt = db()->prepare("SELECT * FROM categories WHERE user_id = :uid AND type = :type ORDER BY sort_order, name");
            $stmt->execute(['uid' => $uid, 'type' => $type]);
        } else {
            $stmt = db()->prepare("SELECT * FROM categories WHERE user_id = :uid ORDER BY type, sort_order, name");
            $stmt->execute(['uid' => $uid]);
        }
        respond($stmt->fetchAll());
        break;

    case 'POST':
        $in = json_input();
        if (empty($in['name']) || empty($in['type'])) respond_error('Nome e tipo sono obbligatori');

        $stmt = db()->prepare("INSERT INTO categories (user_id, name, type, color, icon, classification, sort_order)
            VALUES (:uid, :name, :type, :color, :icon, :classification, :sort_order)");
        $stmt->execute([
            'uid' => $uid,
            'name' => $in['name'],
            'type' => $in['type'],
            'color' => $in['color'] ?? '#8b95a1',
            'icon' => $in['icon'] ?? null,
            'classification' => $in['classification'] ?? null,
            'sort_order' => $in['sort_order'] ?? 0,
        ]);
        respond(['id' => (int)db()->lastInsertId()], 201);
        break;

    case 'PUT':
        $in = json_input();
        if (empty($in['id'])) respond_error('ID mancante');

        $fields = [];
        $params = ['id' => $in['id'], 'uid' => $uid];
        foreach (['name', 'type', 'color', 'icon', 'classification', 'sort_order'] as $f) {
            if (array_key_exists($f, $in)) {
                $fields[] = "$f = :$f";
                $params[$f] = $in[$f];
            }
        }
        if (!$fields) respond_error('Nessun campo da aggiornare');

        $stmt = db()->prepare("UPDATE categories SET " . implode(', ', $fields) . " WHERE id = :id AND user_id = :uid");
        $stmt->execute($params);
        respond(['ok' => true]);
        break;

    case 'DELETE':
        if (($_GET['action'] ?? '') === 'bulk') {
            $where = ['user_id = :uid'];
            $params = ['uid' => $uid];
            if (!empty($_GET['type'])) {
                $where[] = 'type = :type';
                $params['type'] = $_GET['type'];
            }
            $idStmt = db()->prepare("SELECT id FROM categories WHERE " . implode(' AND ', $where));
            $idStmt->execute($params);
            $ids = $idStmt->fetchAll(PDO::FETCH_COLUMN);

            // Le categorie usate da transazioni esistenti sono protette da FK
            // (RESTRICT): proviamo riga per riga cosi' le altre si eliminano
            // comunque, invece di far fallire tutta l'operazione in blocco.
            $del = db()->prepare("DELETE FROM categories WHERE id = :id AND user_id = :uid");
            $deleted = 0;
            $skipped = 0;
            foreach ($ids as $id) {
                try {
                    $del->execute(['id' => $id, 'uid' => $uid]);
                    $deleted++;
                } catch (PDOException $e) {
                    $skipped++;
                }
            }
            respond(['ok' => true, 'deleted' => $deleted, 'skipped' => $skipped]);
            break;
        }

        $id = $_GET['id'] ?? null;
        if (!$id) respond_error('ID mancante');
        $stmt = db()->prepare("DELETE FROM categories WHERE id = :id AND user_id = :uid");
        $stmt->execute(['id' => $id, 'uid' => $uid]);
        respond(['ok' => true]);
        break;

    default:
        respond_error('Metodo non supportato', 405);
}
