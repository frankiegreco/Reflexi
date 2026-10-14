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
    send_password_reset_email((int)$user['id'], $user['email'], 1);
}

respond(['ok' => true, 'message' => "Se l'indirizzo esiste, riceverai un'email con le istruzioni."]);
