<?php
declare(strict_types=1);
require __DIR__ . '/db.php';

// Tabella di riferimento CONDIVISA (non per singolo utente) — dati di mercato
// popolati da ricerca web e aggiornabili manualmente nel tempo, sullo stesso
// principio "a tabella" dei portali di comparazione mutui. Nota: al momento
// qualsiasi utente autenticato può modificarla (non esiste un ruolo admin
// distinto) — accettabile in questa fase con pochi utenti, da rivedere se
// la base utenti crescesse.
$uid = require_auth();

switch (method()) {
    case 'GET':
        $stmt = db()->query("SELECT * FROM reference_mortgage_rates ORDER BY observed_date DESC, rate_type, bank_name");
        $rows = $stmt->fetchAll();
        foreach ($rows as &$r) {
            $r['loan_amount'] = $r['loan_amount'] !== null ? (float)$r['loan_amount'] : null;
            $r['duration_years'] = $r['duration_years'] !== null ? (int)$r['duration_years'] : null;
            $r['tan'] = $r['tan'] !== null ? (float)$r['tan'] : null;
            $r['taeg'] = $r['taeg'] !== null ? (float)$r['taeg'] : null;
            $r['monthly_payment'] = $r['monthly_payment'] !== null ? (float)$r['monthly_payment'] : null;
        }
        respond($rows);
        break;

    case 'POST':
        $in = json_input();
        foreach (['bank_name', 'source_name', 'source_url', 'observed_date'] as $f) {
            if (empty($in[$f])) respond_error("Campo obbligatorio mancante: $f");
        }
        $stmt = db()->prepare("INSERT INTO reference_mortgage_rates
            (bank_name, rate_type, loan_amount, duration_years, tan, taeg, monthly_payment, profile_description, source_name, source_url, observed_date)
            VALUES (:bank_name, :rate_type, :loan_amount, :duration_years, :tan, :taeg, :monthly_payment, :profile_description, :source_name, :source_url, :observed_date)");
        $stmt->execute([
            'bank_name' => $in['bank_name'],
            'rate_type' => $in['rate_type'] ?? 'fisso',
            'loan_amount' => $in['loan_amount'] ?? null,
            'duration_years' => $in['duration_years'] ?? null,
            'tan' => $in['tan'] ?? null,
            'taeg' => $in['taeg'] ?? null,
            'monthly_payment' => $in['monthly_payment'] ?? null,
            'profile_description' => $in['profile_description'] ?? null,
            'source_name' => $in['source_name'],
            'source_url' => $in['source_url'],
            'observed_date' => $in['observed_date'],
        ]);
        respond(['id' => (int)db()->lastInsertId()], 201);
        break;

    case 'PUT':
        $in = json_input();
        if (empty($in['id'])) respond_error('ID mancante');

        $fields = [];
        $params = ['id' => $in['id']];
        foreach (['bank_name', 'rate_type', 'loan_amount', 'duration_years', 'tan', 'taeg', 'monthly_payment', 'profile_description', 'source_name', 'source_url', 'observed_date'] as $f) {
            if (array_key_exists($f, $in)) {
                $fields[] = "$f = :$f";
                $params[$f] = $in[$f];
            }
        }
        if (!$fields) respond_error('Nessun campo da aggiornare');

        $stmt = db()->prepare("UPDATE reference_mortgage_rates SET " . implode(', ', $fields) . " WHERE id = :id");
        $stmt->execute($params);
        respond(['ok' => true]);
        break;

    case 'DELETE':
        if (($_GET['action'] ?? '') === 'bulk') {
            $stmt = db()->query("DELETE FROM reference_mortgage_rates");
            respond(['ok' => true, 'deleted' => $stmt->rowCount()]);
            break;
        }

        $id = $_GET['id'] ?? null;
        if (!$id) respond_error('ID mancante');
        db()->prepare("DELETE FROM reference_mortgage_rates WHERE id = :id")->execute(['id' => $id]);
        respond(['ok' => true]);
        break;

    default:
        respond_error('Metodo non supportato', 405);
}
