<?php
declare(strict_types=1);
require __DIR__ . '/../db.php';
require __DIR__ . '/../lib_mailer.php';

if (method() !== 'POST') respond_error('Metodo non supportato', 405);

rate_limit_check('resend_verification', 3, 3600);
rate_limit_record('resend_verification');

$in = json_input();
$email = strtolower(trim($in['email'] ?? ''));

$stmt = db()->prepare("SELECT id, name, email_verified_at FROM users WHERE email = :email");
$stmt->execute(['email' => $email]);
$user = $stmt->fetch();

// Risposta uniforme anche se l'account non esiste o è già verificato: non
// riveliamo quali email sono registrate (stessa pratica usata per il reset password).
if ($user && !$user['email_verified_at']) {
    $token = bin2hex(random_bytes(32));
    $expires = date('Y-m-d H:i:s', strtotime('+24 hours'));
    db()->prepare("UPDATE users SET verification_token = :token, verification_token_expires = :expires WHERE id = :id")
        ->execute(['token' => $token, 'expires' => $expires, 'id' => $user['id']]);

    $verifyUrl = app_base_url() . '/api/auth/verify_email.php?token=' . $token;
    $greeting = $user['name'] ? htmlspecialchars($user['name']) : '';
    send_email($email, 'Conferma la tua email — Reflexi', "
        <p>Ciao {$greeting},</p>
        <p>Ecco un nuovo link per confermare il tuo indirizzo email (valido 24 ore):</p>
        <p><a href=\"{$verifyUrl}\">Conferma la tua email</a></p>
    ");
}

respond(['ok' => true, 'message' => "Se l'account esiste e non è ancora verificato, ti abbiamo inviato una nuova email di conferma."]);
