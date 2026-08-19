<?php
declare(strict_types=1);
require __DIR__ . '/../db.php';
require __DIR__ . '/../lib_mailer.php';
require __DIR__ . '/../lib_seed.php';

if (method() !== 'POST') respond_error('Metodo non supportato', 405);

// Max 5 registrazioni ogni ora per IP: sufficiente per un uso normale
// (te stesso + qualche amico dallo stesso indirizzo), blocca la creazione massiva.
rate_limit_check('register', 5, 3600);
rate_limit_record('register');

$in = json_input();
$email = strtolower(trim($in['email'] ?? ''));
$password = $in['password'] ?? '';
$name = trim($in['name'] ?? '');

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) respond_error('Email non valida');
if (strlen($password) < 8) respond_error('La password deve avere almeno 8 caratteri');

$exists = db()->prepare("SELECT id FROM users WHERE email = :email");
$exists->execute(['email' => $email]);
if ($exists->fetch()) respond_error('Esiste già un account con questa email');

$hash = password_hash($password, PASSWORD_DEFAULT);
$token = bin2hex(random_bytes(32));
$expires = date('Y-m-d H:i:s', strtotime('+24 hours'));

$trialEnds = date('Y-m-d H:i:s', strtotime('+180 days'));
$stmt = db()->prepare("INSERT INTO users (email, password_hash, name, verification_token, verification_token_expires, subscription_status, trial_ends_at)
    VALUES (:email, :hash, :name, :token, :expires, 'trialing', :trial_ends)");
$stmt->execute(['email' => $email, 'hash' => $hash, 'name' => $name ?: null, 'token' => $token, 'expires' => $expires, 'trial_ends' => $trialEnds]);
$userId = (int)db()->lastInsertId();

seed_default_data_for_user($userId);

$verifyUrl = app_base_url() . '/api/auth/verify_email.php?token=' . $token;
$greeting = $name ? htmlspecialchars($name) : '';
send_email($email, 'Conferma la tua email — Le mie finanze', "
    <p>Ciao {$greeting},</p>
    <p>Grazie per esserti registrato. Conferma il tuo indirizzo email cliccando il link qui sotto (valido 24 ore):</p>
    <p><a href=\"{$verifyUrl}\">Conferma la tua email</a></p>
    <p>Se non hai richiesto tu questa registrazione, ignora questa email.</p>
");

respond(['ok' => true, 'message' => 'Registrazione avvenuta. Controlla la tua email per confermare l\'account prima di accedere.'], 201);
