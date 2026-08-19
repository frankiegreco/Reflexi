<?php
declare(strict_types=1);
require __DIR__ . '/../db.php';
require __DIR__ . '/../lib_mailer.php';

if (method() !== 'POST') respond_error('Metodo non supportato', 405);

// Max 5 richieste ogni ora per IP: protegge anche la quota email del provider SMTP
// da chi tentasse di "bombardare" una casella con richieste di reset ripetute.
rate_limit_check('forgot_password', 5, 3600);
rate_limit_record('forgot_password');

$in = json_input();
$email = strtolower(trim($in['email'] ?? ''));

$stmt = db()->prepare("SELECT id, email FROM users WHERE email = :email");
$stmt->execute(['email' => $email]);
$user = $stmt->fetch();

// Risponde sempre con lo stesso messaggio, anche se l'email non esiste:
// non riveliamo quali indirizzi sono registrati (pratica di sicurezza standard).
if ($user) {
    $token = bin2hex(random_bytes(32));
    $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));
    db()->prepare("UPDATE users SET reset_token = :token, reset_token_expires = :expires WHERE id = :id")
        ->execute(['token' => $token, 'expires' => $expires, 'id' => $user['id']]);

    $resetUrl = app_base_url() . '/app/?reset_token=' . $token;
    send_email($user['email'], 'Reimposta la password — Le mie finanze', "
        <p>Hai richiesto di reimpostare la password. Il link è valido 1 ora:</p>
        <p><a href=\"{$resetUrl}\">Reimposta la password</a></p>
        <p>Se non hai richiesto tu questa operazione, ignora questa email: la tua password resterà invariata.</p>
    ");
}

respond(['ok' => true, 'message' => "Se l'indirizzo esiste, riceverai un'email con le istruzioni."]);
