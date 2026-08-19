<?php
declare(strict_types=1);

function advance_date(string $date, string $frequency, int $interval): string
{
    $d = new DateTime($date);
    $map = [
        'giornaliera' => 'P' . $interval . 'D',
        'settimanale' => 'P' . ($interval * 7) . 'D',
        'mensile' => 'P' . $interval . 'M',
        'annuale' => 'P' . $interval . 'Y',
    ];
    $d->add(new DateInterval($map[$frequency] ?? 'P1M'));
    return $d->format('Y-m-d');
}

/**
 * Genera le transazioni dovute (next_run <= oggi) per le ricorrenze attive
 * dell'utente indicato e avanza la loro scadenza. Genera tutte le occorrenze
 * arretrate fino ad oggi, non solo l'ultima. Ritorna il numero di transazioni create.
 */
function process_due_recurring(int $uid): int
{
    $today = (new DateTime())->format('Y-m-d');
    $stmt = db()->prepare("SELECT * FROM recurring_transactions
        WHERE user_id = :uid AND active = 1 AND next_run <= :today
        AND (end_date IS NULL OR next_run <= end_date)");
    $stmt->execute(['uid' => $uid, 'today' => $today]);
    $due = $stmt->fetchAll();

    $created = 0;
    $insert = db()->prepare("INSERT INTO transactions
        (user_id, account_id, category_id, transfer_account_id, amount, type, description, date, recurring_id)
        VALUES (:uid, :account_id, :category_id, :transfer_account_id, :amount, :type, :description, :date, :recurring_id)");
    $update = db()->prepare("UPDATE recurring_transactions SET next_run = :next_run WHERE id = :id AND user_id = :uid");

    foreach ($due as $r) {
        $nextRun = $r['next_run'];
        while ($nextRun <= $today && (!$r['end_date'] || $nextRun <= $r['end_date'])) {
            $insert->execute([
                'uid' => $uid,
                'account_id' => $r['account_id'],
                'category_id' => $r['category_id'],
                'transfer_account_id' => $r['transfer_account_id'],
                'amount' => $r['amount'],
                'type' => $r['type'],
                'description' => $r['description'],
                'date' => $nextRun,
                'recurring_id' => $r['id'],
            ]);
            $created++;
            $nextRun = advance_date($nextRun, $r['frequency'], (int)$r['interval_count']);
        }
        $update->execute(['next_run' => $nextRun, 'id' => $r['id'], 'uid' => $uid]);
    }

    return $created;
}
