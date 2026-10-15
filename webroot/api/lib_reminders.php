<?php
declare(strict_types=1);

require_once __DIR__ . '/lib_mailer.php';

// Promemoria per tornare a usare l'app (migrazione 063), inviati dall'area
// admin a un singolo utente o a tutti gli inattivi da N giorni. Solo utenti
// registrati, mai admin, mai chi li ha disattivati dal Profilo.
//
// "Inattivo" si misura sull'ultima attività in user_sessions. Lo storico parte
// dal REMINDER_HISTORY_START: chi non ha sessioni ed è iscritto da prima ha
// un'attività "sconosciuta" (potrebbe usare l'app ogni giorno con una sessione
// aperta da prima), e nell'invio di massa entra solo se l'admin lo chiede.

const REMINDER_HISTORY_START = '2026-10-11';
/** Invio di massa: salta chi ha già ricevuto un promemoria in questi giorni. */
const REMINDER_BULK_GAP_DAYS = 7;
/** Invio singolo: blocca un secondo invio allo stesso utente (doppio click). */
const REMINDER_SINGLE_GAP_MINUTES = 10;
/** Massimo di email per un invio di massa (vanno via una per una, via SMTP). */
const REMINDER_BULK_MAX = 200;
/** Validità del link di conferma email incluso per chi non l'ha confermata. */
const REMINDER_VERIFY_DAYS = 7;

/** Colonne dell'utente che servono al promemoria, con ultima attività e ultimo promemoria. */
function reminder_user_select(): string
{
    return "SELECT u.id, u.email, u.name, u.first_name, u.is_admin, u.email_verified_at, u.created_at, u.reminders_enabled,
            (SELECT MAX(s.last_seen_at) FROM user_sessions s WHERE s.user_id = u.id) AS last_seen_at,
            (SELECT MAX(r.sent_at) FROM user_reminders r WHERE r.user_id = u.id) AS last_reminder_at
        FROM users u";
}

/** Ultima attività nota: sessioni, oppure l'iscrizione se avvenuta dopo l'inizio dello storico. NULL = sconosciuta. */
function reminder_last_activity(array $u): ?string
{
    if ($u['last_seen_at']) return $u['last_seen_at'];
    return $u['created_at'] >= REMINDER_HISTORY_START ? $u['created_at'] : null;
}

/**
 * Utenti a cui va il promemoria di massa: inattivi da almeno $days giorni (e,
 * se $includeUnknown, quelli con attività sconosciuta), esclusi admin, chi li
 * ha disattivati e chi ne ha già ricevuto uno negli ultimi REMINDER_BULK_GAP_DAYS.
 */
function reminder_candidates(int $days, bool $includeUnknown): array
{
    $users = db()->query(reminder_user_select() . " WHERE u.is_admin = 0 AND u.reminders_enabled = 1 ORDER BY u.id")->fetchAll();
    $limit = time() - $days * 86400;
    $gap = time() - REMINDER_BULK_GAP_DAYS * 86400;
    return array_values(array_filter($users, function ($u) use ($limit, $gap, $includeUnknown) {
        if ($u['last_reminder_at'] && strtotime($u['last_reminder_at']) > $gap) return false;
        $activity = reminder_last_activity($u);
        return $activity === null ? $includeUnknown : strtotime($activity) < $limit;
    }));
}

/**
 * Invia il promemoria a un utente (riga di reminder_user_select()) e lo
 * registra. Ritorna false se l'email non è partita (niente registrazione).
 */
function send_reminder(int $adminId, array $u, string $note = ''): bool
{
    $base = app_base_url();
    $name = $u['first_name'] ?: $u['name'];
    $hello = $name ? 'Ciao ' . htmlspecialchars($name) . ',' : 'Ciao,';
    $p = 'margin:0 0 16px;';

    $items = '';
    foreach (['Le spese del mese rispetto al budget', 'I tuoi obiettivi di risparmio', 'Le rate del mutuo e il debito residuo', 'L\'andamento dei tuoi investimenti'] as $item) {
        $items .= '<tr><td width="28" valign="top" style="padding:4px 0;color:#4f46e5;font-weight:700;">&#10003;</td>'
            . '<td style="padding:4px 0;color:#374151;">' . $item . '</td></tr>';
    }

    $content = "<h1 style=\"margin:0 0 20px;font-size:24px;line-height:1.3;color:#14162b;\">{$hello}<br>ti aspettiamo su Reflexi</h1>"
        . "<p style=\"{$p}\">Bastano pochi minuti per aggiornare i movimenti e vedere a che punto sei:</p>"
        . "<table role=\"presentation\" cellpadding=\"0\" cellspacing=\"0\" border=\"0\" style=\"margin:0 0 8px;\">{$items}</table>";

    $note = trim($note);
    if ($note !== '') {
        $content .= '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin:20px 0 0;"><tr>'
            . '<td style="background:#eef0fd;border-left:4px solid #4f46e5;border-radius:8px;padding:14px 16px;color:#374151;font-size:15px;">'
            . nl2br(htmlspecialchars($note)) . '</td></tr></table>';
    }

    if ($u['email_verified_at'] === null) {
        // Senza email confermata il login è bloccato: nuovo link di conferma.
        $token = bin2hex(random_bytes(32));
        db()->prepare("UPDATE users SET verification_token = :t, verification_token_expires = :e WHERE id = :id")
            ->execute(['t' => $token, 'e' => date('Y-m-d H:i:s', strtotime('+' . REMINDER_VERIFY_DAYS . ' days')), 'id' => $u['id']]);
        $content .= email_button("{$base}/api/auth/verify_email.php?token={$token}", 'Conferma la tua email')
            . '<p style="margin:0;font-size:14px;color:#6b7280;text-align:center;">Prima di accedere conferma il tuo indirizzo email: il link vale ' . REMINDER_VERIFY_DAYS . ' giorni.</p>';
    } else {
        $content .= email_button("{$base}/app/", 'Torna su Reflexi')
            . '<p style="margin:0;font-size:14px;color:#6b7280;text-align:center;">Non ricordi la password? Dalla pagina di accesso scegli "Password dimenticata".</p>';
    }

    $html = email_layout(
        'Bastano pochi minuti per aggiornare i tuoi conti.',
        $content,
        'Ricevi questo promemoria perché hai un account Reflexi.<br>Se non vuoi riceverne altri, disattivali dal tuo Profilo, nella sezione "Riepilogo periodico via email".'
    );

    $subject = $name ? "{$name}, i tuoi conti ti aspettano su Reflexi" : 'I tuoi conti ti aspettano su Reflexi';
    if (!send_email($u['email'], $subject, $html)) return false;

    db()->prepare("INSERT INTO user_reminders (user_id, sent_by, sent_at) VALUES (:uid, :admin, NOW())")
        ->execute(['uid' => $u['id'], 'admin' => $adminId]);
    log_event('info', 'admin_reminder_sent', "Admin #{$adminId} ha inviato un promemoria", (int)$u['id'], $u['email']);
    return true;
}

/** Invio singolo dal dettaglio utente: controlli + invio, risponde con errore se non si può. */
function send_single_reminder(int $adminId, int $userId, string $note): string
{
    if (mb_strlen(trim($note)) > 1000) respond_error('Il messaggio può avere al massimo 1000 caratteri');
    $stmt = db()->prepare(reminder_user_select() . " WHERE u.id = :id");
    $stmt->execute(['id' => $userId]);
    $u = $stmt->fetch();
    if (!$u) respond_error('Utente non trovato', 404);
    if ((int)$u['reminders_enabled'] !== 1) respond_error('L\'utente ha disattivato i promemoria dal suo Profilo.');
    if ($u['last_reminder_at'] && strtotime($u['last_reminder_at']) > time() - REMINDER_SINGLE_GAP_MINUTES * 60) {
        respond_error('Promemoria già inviato a questo utente pochi minuti fa.', 429);
    }
    if (!send_reminder($adminId, $u, $note)) respond_error('Promemoria non inviato: controlla la configurazione SMTP del server.', 502);
    return "Promemoria inviato a {$u['email']}" . ($u['email_verified_at'] === null ? ', con il link per confermare l\'email.' : '.');
}
