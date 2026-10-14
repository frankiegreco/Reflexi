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
$firstName = trim((string)($in['first_name'] ?? ''));
$lastName = trim((string)($in['last_name'] ?? ''));
$phone = trim((string)($in['phone'] ?? ''));
$experience = in_array($in['finance_experience'] ?? null, ['principiante', 'intermedio', 'avanzato'], true) ? $in['finance_experience'] : null;
// Piano scelto su /piani prima di registrarsi: solo una preferenza, vedi migrazione 061.
$plan = in_array($in['plan'] ?? null, ['mensile', 'annuale'], true) ? $in['plan'] : null;

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) respond_error('Email non valida');
if (strlen($password) < 8) respond_error('La password deve avere almeno 8 caratteri');
if ($firstName === '' || $lastName === '') respond_error('Inserisci nome e cognome');
if (mb_strlen($firstName) > 75 || mb_strlen($lastName) > 75) respond_error('Nome e cognome possono avere al massimo 75 caratteri');
// Telefono facoltativo: cifre con eventuale + iniziale e separatori comuni, 6-15 cifre.
if ($phone !== '') {
    if (!preg_match('/^\+?[0-9 .\-\/()]{6,25}$/', $phone)) respond_error('Numero di telefono non valido');
    $digits = strlen(preg_replace('/\D/', '', $phone));
    if ($digits < 6 || $digits > 15) respond_error('Numero di telefono non valido');
}
$name = $firstName . ' ' . $lastName;

$exists = db()->prepare("SELECT id FROM users WHERE email = :email");
$exists->execute(['email' => $email]);
if ($exists->fetch()) respond_error('Esiste già un account con questa email');

$hash = password_hash($password, PASSWORD_DEFAULT);
$token = bin2hex(random_bytes(32));
$expires = date('Y-m-d H:i:s', strtotime('+24 hours'));

$trialEnds = date('Y-m-d H:i:s', strtotime('+180 days'));
$stmt = db()->prepare("INSERT INTO users (email, password_hash, name, verification_token, verification_token_expires, subscription_status, trial_ends_at)
    VALUES (:email, :hash, :name, :token, :expires, 'trialing', :trial_ends)");
$stmt->execute(['email' => $email, 'hash' => $hash, 'name' => $name, 'token' => $token, 'expires' => $expires, 'trial_ends' => $trialEnds]);
$userId = (int)db()->lastInsertId();

// Colonne delle migrazioni 061 e 062: scritte solo se presenti, così la
// registrazione funziona anche su un'installazione non ancora aggiornata.
$extra = [];
if ($plan && db_has_column('users', 'preferred_plan')) $extra['preferred_plan'] = $plan;
if (db_has_column('users', 'first_name')) {
    $extra += ['first_name' => $firstName, 'last_name' => $lastName, 'phone' => $phone ?: null, 'finance_experience' => $experience];
}
if ($extra) {
    $set = implode(', ', array_map(fn($c) => "{$c} = :{$c}", array_keys($extra)));
    db()->prepare("UPDATE users SET {$set} WHERE id = :id")->execute($extra + ['id' => $userId]);
}

seed_default_data_for_user($userId);

$verifyUrl = app_base_url() . '/api/auth/verify_email.php?token=' . $token;
$greeting = htmlspecialchars($firstName);
send_email($email, 'Conferma la tua email — Le mie finanze', "
    <p>Ciao {$greeting},</p>
    <p>Grazie per esserti registrato. Conferma il tuo indirizzo email cliccando il link qui sotto (valido 24 ore):</p>
    <p><a href=\"{$verifyUrl}\">Conferma la tua email</a></p>
    <p>Se non hai richiesto tu questa registrazione, ignora questa email.</p>
");

respond(['ok' => true, 'message' => 'Registrazione avvenuta. Controlla la tua email per confermare l\'account prima di accedere.'], 201);
