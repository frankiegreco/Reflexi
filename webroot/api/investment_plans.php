<?php
declare(strict_types=1);
require __DIR__ . '/db.php';
require_once __DIR__ . '/price_fetch.php';

$uid = require_auth();

function advance_plan_date(string $date, string $frequency, int $interval): string
{
    $d = new DateTime($date);
    $map = [
        'settimanale' => 'P' . ($interval * 7) . 'D',
        'mensile' => 'P' . $interval . 'M',
        'annuale' => 'P' . $interval . 'Y',
    ];
    $d->add(new DateInterval($map[$frequency] ?? 'P1M'));
    return $d->format('Y-m-d');
}

// Genera gli acquisti dovuti (next_run <= oggi): calcola le quote in base al
// prezzo del giorno, crea la transazione di investimento + il movimento di
// cassa collegato, avanza la scadenza del piano.
if (isset($_GET['action']) && $_GET['action'] === 'process') {
    $today = (new DateTime())->format('Y-m-d');
    $stmt = db()->prepare("SELECT p.*, i.ticker, i.name AS instrument_name
        FROM investment_plans p JOIN instruments i ON i.id = p.instrument_id
        WHERE p.user_id = :uid AND p.active = 1 AND p.next_run <= :today
        AND (p.end_date IS NULL OR p.next_run <= p.end_date)");
    $stmt->execute(['uid' => $uid, 'today' => $today]);
    $due = $stmt->fetchAll();

    $catStmt = db()->prepare("SELECT id FROM categories WHERE user_id = :uid AND name = 'Investimenti (acquisto)'");
    $catStmt->execute(['uid' => $uid]);
    $categoryId = $catStmt->fetchColumn();

    $created = 0;
    $skipped = [];
    $capped = []; // piani con troppe rate arretrate per un solo giro (limite di sicurezza)
    $MAX_PERIODS_PER_RUN = 24; // evita richieste troppo lunghe verso Yahoo in un colpo solo
    $updateNextRun = db()->prepare("UPDATE investment_plans SET next_run = :next_run WHERE id = :id AND user_id = :uid");

    foreach ($due as $plan) {
        // Costruisce l'elenco di tutte le rate mancate, dalla piu' vecchia ad oggi.
        $installmentDates = [];
        $cursor = $plan['next_run'];
        while ($cursor <= $today && count($installmentDates) < $MAX_PERIODS_PER_RUN) {
            $installmentDates[] = $cursor;
            $cursor = advance_plan_date($cursor, $plan['frequency'], (int)$plan['interval_count']);
        }
        $hitCap = $cursor <= $today; // ce n'erano ancora altre oltre al limite

        foreach ($installmentDates as $installmentDate) {
            // Per la rata di oggi usiamo il prezzo live (piu' aggiornato, anche
            // infragiornaliero); per le rate passate il prezzo di chiusura reale
            // di quella data specifica, non quello di oggi.
            $priceData = ($installmentDate === $today)
                ? fetch_live_price($plan['ticker'], 3600)
                : fetch_historical_price($plan['ticker'], $installmentDate);

            if (!$priceData) {
                // Ci fermiamo qui per questo piano: la prossima scadenza resta
                // su questa data cosi' si riprova al prossimo giro, senza
                // saltare la rata ne' proseguire con dati mancanti.
                $skipped[] = $plan['instrument_name'] . " ({$installmentDate})";
                $cursor = $installmentDate;
                $hitCap = false; // non e' un problema di limite, e' un problema di prezzo
                break;
            }

            $netAmount = max(0, (float)$plan['amount'] - (float)$plan['fees']);
            $quantity = $priceData['price'] > 0 ? $netAmount / $priceData['price'] : 0;
            // La data effettiva usata e' quella del giorno di borsa piu' vicino
            // (gestisce weekend/festivi in cui il mercato era chiuso).
            $actualDate = $priceData['actual_date'] ?? $installmentDate;

            $txStmt = db()->prepare("INSERT INTO transactions (user_id, account_id, category_id, amount, type, description, date)
                VALUES (:uid, :account_id, :category_id, :amount, 'uscita', :description, :date)");
            $txStmt->execute([
                'uid' => $uid,
                'account_id' => $plan['account_id'],
                'category_id' => $categoryId,
                'amount' => $plan['amount'],
                'description' => 'PAC ' . $plan['instrument_name'],
                'date' => $actualDate,
            ]);
            $linkedTxId = (int)db()->lastInsertId();

            db()->prepare("INSERT INTO investment_transactions
                (user_id, instrument_id, account_id, linked_transaction_id, type, quantity, price_per_unit, fees, date, notes)
                VALUES (:uid, :instrument_id, :account_id, :linked_id, 'acquisto', :quantity, :price, :fees, :date, 'Generato da PAC')")
                ->execute([
                    'uid' => $uid,
                    'instrument_id' => $plan['instrument_id'],
                    'account_id' => $plan['account_id'],
                    'linked_id' => $linkedTxId,
                    'quantity' => $quantity,
                    'price' => $priceData['price'],
                    'fees' => $plan['fees'],
                    'date' => $actualDate,
                ]);
            $created++;
        }

        $updateNextRun->execute(['next_run' => $cursor, 'id' => $plan['id'], 'uid' => $uid]);
        if ($hitCap) {
            $capped[] = $plan['instrument_name'] . " (recuperate {$MAX_PERIODS_PER_RUN} rate, altre ancora arretrate — premi di nuovo il pulsante per continuare)";
        }
    }

    respond(['created' => $created, 'skipped' => $skipped, 'capped' => $capped]);
}

switch (method()) {
    case 'GET':
        $stmt = db()->prepare("SELECT p.*, i.ticker, i.name AS instrument_name, a.name AS account_name
            FROM investment_plans p
            JOIN instruments i ON i.id = p.instrument_id
            JOIN accounts a ON a.id = p.account_id
            WHERE p.user_id = :uid
            ORDER BY p.active DESC, p.next_run");
        $stmt->execute(['uid' => $uid]);
        respond($stmt->fetchAll());
        break;

    case 'POST':
        $in = json_input();
        foreach (['instrument_id', 'account_id', 'amount', 'frequency', 'start_date'] as $f) {
            if (empty($in[$f])) respond_error("Campo obbligatorio mancante: $f");
        }
        assert_owned('instruments', $in['instrument_id'], $uid, 'strumento');
        assert_owned('accounts', $in['account_id'], $uid, 'conto');

        $stmt = db()->prepare("INSERT INTO investment_plans
            (user_id, instrument_id, account_id, amount, fees, frequency, interval_count, start_date, end_date, next_run, notes)
            VALUES (:uid, :instrument_id, :account_id, :amount, :fees, :frequency, :interval_count, :start_date, :end_date, :next_run, :notes)");
        $stmt->execute([
            'uid' => $uid,
            'instrument_id' => $in['instrument_id'],
            'account_id' => $in['account_id'],
            'amount' => $in['amount'],
            'fees' => $in['fees'] ?? 0,
            'frequency' => $in['frequency'],
            'interval_count' => $in['interval_count'] ?? 1,
            'start_date' => $in['start_date'],
            'end_date' => $in['end_date'] ?? null,
            'next_run' => $in['start_date'],
            'notes' => $in['notes'] ?? null,
        ]);
        respond(['id' => (int)db()->lastInsertId()], 201);
        break;

    case 'PUT':
        $in = json_input();
        if (empty($in['id'])) respond_error('ID mancante');

        if (array_key_exists('instrument_id', $in)) assert_owned('instruments', $in['instrument_id'], $uid, 'strumento');
        if (array_key_exists('account_id', $in)) assert_owned('accounts', $in['account_id'], $uid, 'conto');

        $fields = [];
        $params = ['id' => $in['id'], 'uid' => $uid];
        foreach (['instrument_id', 'account_id', 'amount', 'fees', 'frequency', 'interval_count', 'start_date', 'end_date', 'active', 'notes'] as $f) {
            if (array_key_exists($f, $in)) {
                $fields[] = "$f = :$f";
                $params[$f] = $in[$f];
            }
        }
        if (!$fields) respond_error('Nessun campo da aggiornare');

        $stmt = db()->prepare("UPDATE investment_plans SET " . implode(', ', $fields) . " WHERE id = :id AND user_id = :uid");
        $stmt->execute($params);
        respond(['ok' => true]);
        break;

    case 'DELETE':
        if (($_GET['action'] ?? '') === 'bulk') {
            $stmt = db()->prepare("DELETE FROM investment_plans WHERE user_id = :uid");
            $stmt->execute(['uid' => $uid]);
            respond(['ok' => true, 'deleted' => $stmt->rowCount()]);
            break;
        }

        $id = $_GET['id'] ?? null;
        if (!$id) respond_error('ID mancante');
        db()->prepare("DELETE FROM investment_plans WHERE id = :id AND user_id = :uid")->execute(['id' => $id, 'uid' => $uid]);
        respond(['ok' => true]);
        break;

    default:
        respond_error('Metodo non supportato', 405);
}
