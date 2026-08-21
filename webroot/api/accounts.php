<?php
declare(strict_types=1);
require __DIR__ . '/db.php';
require_once __DIR__ . '/price_fetch.php';
require_once __DIR__ . '/account_interest_math.php';

$uid = require_auth();

const BALANCE_SQL = "
    a.initial_balance
    + COALESCE((SELECT SUM(amount) FROM transactions WHERE account_id = a.id AND type = 'entrata'), 0)
    - COALESCE((SELECT SUM(amount) FROM transactions WHERE account_id = a.id AND type = 'uscita'), 0)
    - COALESCE((SELECT SUM(amount) FROM transactions WHERE account_id = a.id AND type = 'trasferimento'), 0)
    + COALESCE((SELECT SUM(amount) FROM transactions WHERE transfer_account_id = a.id AND type = 'trasferimento'), 0)
    AS balance
";

function interest_frequency_fraction(string $frequency): float
{
    return match ($frequency) {
        'mensile' => 1 / 12,
        'trimestrale' => 1 / 4,
        default => 1.0, // annuale
    };
}

function advance_interest_date(string $date, string $frequency): string
{
    $d = new DateTime($date);
    $map = ['mensile' => 'P1M', 'trimestrale' => 'P3M', 'annuale' => 'P1Y'];
    $d->add(new DateInterval($map[$frequency] ?? 'P1Y'));
    return $d->format('Y-m-d');
}

// Genera l'interesse maturato per i conti che lo hanno attivo e sono scaduti:
// crea una VERA transazione di entrata (non un dato separato), cosi' il saldo
// reale del conto aumenta e il patrimonio netto lo riflette automaticamente.
// Riepilogo: quanto e' stato maturato realmente finora (dalle transazioni gia'
// generate) e quanto ci si aspetta nei prossimi 12 mesi ai tassi attuali.
if (isset($_GET['action']) && $_GET['action'] === 'interest_summary') {
    $catStmt = db()->prepare("SELECT id FROM categories WHERE user_id = :uid AND name = 'Interessi attivi'");
    $catStmt->execute(['uid' => $uid]);
    $categoryId = $catStmt->fetchColumn();

    $totalEarned = 0.0;
    if ($categoryId) {
        $sumStmt = db()->prepare("SELECT COALESCE(SUM(amount), 0) FROM transactions WHERE user_id = :uid AND category_id = :cat AND type = 'entrata'");
        $sumStmt->execute(['uid' => $uid, 'cat' => $categoryId]);
        $totalEarned = (float)$sumStmt->fetchColumn();
    }

    $stmt = db()->prepare("SELECT a.id, " . BALANCE_SQL . ", interest_rate, interest_tax_rate FROM accounts a
        WHERE a.user_id = :uid AND a.interest_enabled = 1 AND a.archived = 0");
    $stmt->execute(['uid' => $uid]);
    $totalProjected = 0.0;
    foreach ($stmt->fetchAll() as $a) {
        $gross = (float)$a['balance'] * account_current_rate($a) / 100;
        $totalProjected += $gross * (1 - (float)$a['interest_tax_rate'] / 100);
    }

    respond(['total_earned_to_date' => round($totalEarned, 2), 'total_projected_annual' => round($totalProjected, 2)]);
}

if (isset($_GET['action']) && $_GET['action'] === 'process_interest') {
    $today = date('Y-m-d');
    $MAX_PERIODS = 36; // limite di sicurezza per singolo conto in un click
    $stmt = db()->prepare("SELECT a.*, " . BALANCE_SQL . " FROM accounts a
        WHERE a.user_id = :uid AND a.interest_enabled = 1 AND a.archived = 0
        AND a.next_interest_date IS NOT NULL AND a.next_interest_date <= :today");
    $stmt->execute(['uid' => $uid, 'today' => $today]);
    $due = $stmt->fetchAll();

    $catStmt = db()->prepare("SELECT id FROM categories WHERE user_id = :uid AND name = 'Interessi attivi'");
    $catStmt->execute(['uid' => $uid]);
    $categoryId = $catStmt->fetchColumn();
    if (!$categoryId) {
        db()->prepare("INSERT INTO categories (user_id, name, type, color) VALUES (:uid, 'Interessi attivi', 'entrata', '#10b981')")
            ->execute(['uid' => $uid]);
        $categoryId = (int)db()->lastInsertId();
    }

    $created = 0;
    $capped = [];
    $updateNext = db()->prepare("UPDATE accounts SET next_interest_date = :next WHERE id = :id AND user_id = :uid");

    foreach ($due as $account) {
        // Costruisce tutte le date di accredito arretrate, dalla piu' vecchia ad oggi.
        $accrualDates = [];
        $cursor = $account['next_interest_date'];
        while ($cursor <= $today && count($accrualDates) < $MAX_PERIODS) {
            $accrualDates[] = $cursor;
            $cursor = advance_interest_date($cursor, $account['interest_frequency']);
        }
        $hitCap = $cursor <= $today;

        // Sia il TASSO che il SALDO usati per ogni periodo arretrato sono ora
        // quelli realmente in vigore in quella data specifica (non quelli di
        // oggi applicati retroattivamente a tutti i periodi): il tasso viene
        // dallo storico esplicito, il saldo si ricalcola a fine di ciascuna
        // data dalle transazioni realmente registrate fino a quel momento —
        // comprese le eventuali rate di interesse già generate in questo
        // stesso ciclo per i periodi precedenti, cosi' l'interesse composto
        // si riflette correttamente sui periodi successivi.
        $fraction = interest_frequency_fraction($account['interest_frequency']);
        $rateHistory = account_load_rate_history((int)$account['id']);
        if (!$rateHistory) {
            $rateHistory = [['effective_date' => $account['next_interest_date'], 'rate' => $account['interest_rate']]];
        }

        foreach ($accrualDates as $accrualDate) {
            $balance = account_balance_at((int)$account['id'], $accrualDate);
            if ($balance > 0) {
                $rate = account_rate_at($rateHistory, $accrualDate);
                $gross = $balance * $rate / 100 * $fraction;
                $net = $gross * (1 - (float)$account['interest_tax_rate'] / 100);

                db()->prepare("INSERT INTO transactions (user_id, account_id, category_id, amount, type, description, date)
                    VALUES (:uid, :account_id, :category_id, :amount, 'entrata', :description, :date)")
                    ->execute([
                        'uid' => $uid,
                        'account_id' => $account['id'],
                        'category_id' => $categoryId,
                        'amount' => round($net, 2),
                        'description' => sprintf('Interessi maturati — %s (saldo %.2f€, tasso %s%%)', $account['name'], $balance, rtrim(rtrim(number_format($rate, 3, '.', ''), '0'), '.')),
                        'date' => $accrualDate,
                    ]);
                $created++;
            }
        }

        $updateNext->execute(['next' => $cursor, 'id' => $account['id'], 'uid' => $uid]);
        if ($hitCap) {
            $capped[] = $account['name'] . " (recuperati {$MAX_PERIODS} periodi, altri ancora arretrati — premi di nuovo per continuare)";
        }
    }
    respond(['created' => $created, 'capped' => $capped]);
}

switch (method()) {
    case 'GET':
        $includeArchived = isset($_GET['include_archived']) && $_GET['include_archived'] === '1';
        $where = $includeArchived ? 'WHERE a.user_id = :uid' : 'WHERE a.user_id = :uid AND a.archived = 0';
        $stmt = db()->prepare("SELECT a.*, " . BALANCE_SQL . " FROM accounts a {$where} ORDER BY a.sort_order, a.id");
        $stmt->execute(['uid' => $uid]);
        $rows = $stmt->fetchAll();
        foreach ($rows as &$r) {
            if ($r['interest_enabled']) {
                // Il tasso "vivo" secondo lo storico, non la colonna interest_rate da
                // sola: puo' essersi disallineata se un periodo e' stato aggiunto/tolto
                // dalla sezione "Storico tasso" invece che dal form principale, o se un
                // periodo futuro nel frattempo e' entrato in vigore.
                $r['interest_rate'] = account_current_rate($r);
                $gross = (float)$r['balance'] * $r['interest_rate'] / 100;
                $net = $gross * (1 - (float)$r['interest_tax_rate'] / 100);
                $r['projected_gross_annual_interest'] = round($gross, 2);
                $r['projected_net_annual_interest'] = round($net, 2);
            }
            if ($r['currency'] !== 'EUR') {
                $fx = fx_rate_to_eur($r['currency']);
                $r['fx_rate'] = $fx['rate'];
                $r['fx_stale'] = $fx['stale'];
                $r['balance_eur'] = $fx['rate'] !== null ? round((float)$r['balance'] * $fx['rate'], 2) : null;
            } else {
                $r['fx_rate'] = 1.0;
                $r['fx_stale'] = false;
                $r['balance_eur'] = (float)$r['balance'];
            }
        }
        respond($rows);
        break;

    case 'POST':
        $in = json_input();
        if (empty($in['name'])) respond_error('Il nome del conto è obbligatorio');

        $interestEnabled = !empty($in['interest_enabled']);
        if ($interestEnabled && empty($in['interest_rate']) && $in['interest_rate'] !== 0) {
            respond_error('Se attivi gli interessi, indica un tasso');
        }

        $stmt = db()->prepare("INSERT INTO accounts
            (user_id, name, type, initial_balance, color, sort_order, interest_enabled, interest_rate, interest_tax_rate, interest_frequency, next_interest_date, currency, exclude_from_networth)
            VALUES (:uid, :name, :type, :initial_balance, :color, :sort_order, :interest_enabled, :interest_rate, :interest_tax_rate, :interest_frequency, :next_interest_date, :currency, :exclude_from_networth)");
        $stmt->execute([
            'uid' => $uid,
            'name' => $in['name'],
            'type' => $in['type'] ?? 'corrente',
            'initial_balance' => $in['initial_balance'] ?? 0,
            'color' => $in['color'] ?? '#4a90a4',
            'sort_order' => $in['sort_order'] ?? 0,
            'interest_enabled' => $interestEnabled ? 1 : 0,
            'interest_rate' => $interestEnabled ? $in['interest_rate'] : null,
            'interest_tax_rate' => $in['interest_tax_rate'] ?? 26.00,
            'interest_frequency' => $in['interest_frequency'] ?? 'annuale',
            'next_interest_date' => $interestEnabled ? ($in['next_interest_date'] ?? date('Y-m-d')) : null,
            'currency' => strtoupper($in['currency'] ?? 'EUR'),
            'exclude_from_networth' => !empty($in['exclude_from_networth']) ? 1 : 0,
        ]);
        $newAccountId = (int)db()->lastInsertId();

        // Prima riga dello storico tasso: valida dalla data del primo
        // accredito previsto (puo' essere passata, per conti gia' aperti
        // altrove prima di iniziare a tracciarli qui). Ulteriori variazioni
        // si aggiungono dalla sezione "Storico tasso".
        if ($interestEnabled) {
            db()->prepare("INSERT INTO account_interest_rate_history (account_id, effective_date, rate) VALUES (:account_id, :effective_date, :rate)")
                ->execute([
                    'account_id' => $newAccountId,
                    'effective_date' => $in['next_interest_date'] ?? date('Y-m-d'),
                    'rate' => $in['interest_rate'],
                ]);
        }
        respond(['id' => $newAccountId], 201);
        break;

    case 'PUT':
        $in = json_input();
        if (empty($in['id'])) respond_error('ID mancante');

        $fields = [];
        $params = ['id' => $in['id'], 'uid' => $uid];
        foreach (['name', 'type', 'initial_balance', 'color', 'sort_order', 'archived',
                   'interest_enabled', 'interest_rate', 'interest_tax_rate', 'interest_frequency', 'next_interest_date', 'currency', 'exclude_from_networth'] as $f) {
            if (array_key_exists($f, $in)) {
                $fields[] = "$f = :$f";
                $value = $in[$f];
                if (in_array($f, ['archived', 'interest_enabled', 'exclude_from_networth'], true)) {
                    $value = $value ? 1 : 0;
                }
                if ($f === 'currency') {
                    $value = strtoupper($value);
                }
                $params[$f] = $value;
            }
        }
        if (!$fields) respond_error('Nessun campo da aggiornare');

        $stmt = db()->prepare("UPDATE accounts SET " . implode(', ', $fields) . " WHERE id = :id AND user_id = :uid");
        $stmt->execute($params);

        // Il campo "tasso" del form principale rappresenta il tasso corrente:
        // se il conto ha gia' uno storico, questa modifica vale da oggi (una
        // vera variazione va inserita dalla sezione "Storico tasso" con la
        // sua data effettiva); se e' la prima volta che si attivano gli
        // interessi, seeda lo storico come alla creazione del conto.
        if (array_key_exists('interest_rate', $in) && $in['interest_rate'] !== null) {
            $checkStmt = db()->prepare("SELECT interest_enabled, next_interest_date FROM accounts WHERE id = :id AND user_id = :uid");
            $checkStmt->execute(['id' => $in['id'], 'uid' => $uid]);
            $updatedAccount = $checkStmt->fetch();
            if ($updatedAccount && $updatedAccount['interest_enabled']) {
                $countStmt = db()->prepare("SELECT COUNT(*) FROM account_interest_rate_history WHERE account_id = :id");
                $countStmt->execute(['id' => $in['id']]);
                $hasHistory = (int)$countStmt->fetchColumn() > 0;
                $effectiveDate = $hasHistory ? date('Y-m-d') : ($updatedAccount['next_interest_date'] ?? date('Y-m-d'));

                db()->prepare("INSERT INTO account_interest_rate_history (account_id, effective_date, rate) VALUES (:account_id, :effective_date, :rate)
                    ON DUPLICATE KEY UPDATE rate = VALUES(rate)")
                    ->execute(['account_id' => $in['id'], 'effective_date' => $effectiveDate, 'rate' => $in['interest_rate']]);
            }
        }
        respond(['ok' => true]);
        break;

    case 'DELETE':
        if (($_GET['action'] ?? '') === 'bulk') {
            $stmt = db()->prepare("DELETE FROM accounts WHERE user_id = :uid");
            $stmt->execute(['uid' => $uid]);
            respond(['ok' => true, 'deleted' => $stmt->rowCount()]);
            break;
        }

        $id = $_GET['id'] ?? null;
        if (!$id) respond_error('ID mancante');
        $stmt = db()->prepare("DELETE FROM accounts WHERE id = :id AND user_id = :uid");
        $stmt->execute(['id' => $id, 'uid' => $uid]);
        respond(['ok' => true]);
        break;

    default:
        respond_error('Metodo non supportato', 405);
}
