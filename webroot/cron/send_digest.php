<?php
declare(strict_types=1);

// Script pensato per essere lanciato da cron via CLI (php cron/send_digest.php),
// MAI esposto come endpoint HTTP pubblico — altrimenti chiunque potrebbe
// forzare l'invio di email a raffica. Va eseguito dentro il container "web",
// dove gira comunque anche il resto del backend.
if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    exit('Questo script va eseguito solo da riga di comando (cron), non via web.');
}

require __DIR__ . '/../api/db.php';
require __DIR__ . '/../api/lib_mailer.php';
require __DIR__ . '/../api/loan_math.php';

const DIGEST_EXCLUDED_CATEGORIES = ['Investimenti (acquisto)', 'Investimenti (vendita)'];

function is_due(string $frequency, ?string $lastSentAt): bool
{
    if (!$lastSentAt) return true;
    $daysSince = (time() - strtotime($lastSentAt)) / 86400;
    if ($frequency === 'settimanale') return $daysSince >= 7;
    if ($frequency === 'mensile') return $daysSince >= 28 && date('j') <= 3; // primi giorni del mese
    return false;
}

$users = db()->query("SELECT * FROM users WHERE digest_frequency != 'nessuno' AND email_verified_at IS NOT NULL")->fetchAll();
$sentCount = 0;

foreach ($users as $user) {
    if (!is_due($user['digest_frequency'], $user['last_digest_sent_at'])) continue;

    $uid = (int)$user['id'];
    $periodDays = $user['digest_frequency'] === 'settimanale' ? 7 : 30;
    $from = date('Y-m-d', strtotime("-{$periodDays} days"));
    $to = date('Y-m-d');

    $placeholders = implode(',', array_fill(0, count(DIGEST_EXCLUDED_CATEGORIES), '?'));
    $stmt = db()->prepare("
        SELECT
            COALESCE(SUM(CASE WHEN t.type = 'entrata' THEN t.amount ELSE 0 END), 0) AS income,
            COALESCE(SUM(CASE WHEN t.type = 'uscita' THEN t.amount ELSE 0 END), 0) AS expense
        FROM transactions t JOIN categories c ON c.id = t.category_id
        WHERE t.user_id = ? AND t.date BETWEEN ? AND ? AND t.type IN ('entrata','uscita')
        AND c.name NOT IN ($placeholders)
    ");
    $stmt->execute(array_merge([$uid, $from, $to], DIGEST_EXCLUDED_CATEGORIES));
    $totals = $stmt->fetch();
    $income = (float)$totals['income'];
    $expense = (float)$totals['expense'];
    $net = $income - $expense;

    $loanStmt = db()->prepare("SELECT * FROM loans WHERE user_id = :uid AND status = 'attivo'");
    $loanStmt->execute(['uid' => $uid]);
    $loanResidual = 0.0;
    foreach ($loanStmt->fetchAll() as $loan) {
        $summary = loan_summary($loan);
        $loanResidual += $summary['personal_residual_today'];
    }

    $periodLabel = $user['digest_frequency'] === 'settimanale' ? 'questa settimana' : 'questo mese';
    $netColor = $net >= 0 ? '#10b981' : '#f43f5e';
    $netLabel = $net >= 0 ? 'risparmiati' : 'in rosso';

    $html = "
        <div style=\"font-family: Arial, sans-serif; max-width: 480px; margin: 0 auto;\">
            <h2 style=\"color: #4f46e5;\">Il tuo riepilogo Reflexi</h2>
            <p>Ciao" . ($user['name'] ? ' ' . htmlspecialchars($user['name']) : '') . ", ecco come è andata {$periodLabel}:</p>
            <table style=\"width: 100%; border-collapse: collapse; margin: 16px 0;\">
                <tr><td style=\"padding: 8px 0; color: #6b7080;\">Entrate</td><td style=\"text-align: right; font-weight: bold;\">€ " . number_format($income, 2, ',', '.') . "</td></tr>
                <tr><td style=\"padding: 8px 0; color: #6b7080;\">Uscite</td><td style=\"text-align: right; font-weight: bold;\">€ " . number_format($expense, 2, ',', '.') . "</td></tr>
                <tr><td style=\"padding: 8px 0; color: #6b7080;\">Netto {$netLabel}</td><td style=\"text-align: right; font-weight: bold; color: {$netColor};\">€ " . number_format(abs($net), 2, ',', '.') . "</td></tr>"
                . ($loanResidual > 0 ? "<tr><td style=\"padding: 8px 0; color: #6b7080;\">Debito residuo mutui (tua quota)</td><td style=\"text-align: right; font-weight: bold;\">€ " . number_format($loanResidual, 2, ',', '.') . "</td></tr>" : "") . "
            </table>
            <p><a href=\"" . app_base_url() . "/app/\" style=\"display: inline-block; background: #4f46e5; color: white; padding: 10px 20px; border-radius: 8px; text-decoration: none;\">Apri Reflexi</a></p>
            <p style=\"color: #8b95a1; font-size: 12px; margin-top: 24px;\">Puoi disattivare questi riepiloghi in qualsiasi momento dal tuo profilo su Reflexi.</p>
        </div>
    ";

    $sent = send_email($user['email'], "Il tuo riepilogo Reflexi — {$periodLabel}", $html);
    if ($sent) {
        db()->prepare("UPDATE users SET last_digest_sent_at = NOW() WHERE id = :id")->execute(['id' => $uid]);
        $sentCount++;
    }
}

echo "Digest inviati: {$sentCount}\n";
