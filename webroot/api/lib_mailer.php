<?php
declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception as PHPMailerException;

/**
 * Invia un'email via SMTP. Configurazione da variabili d'ambiente:
 * SMTP_HOST, SMTP_PORT, SMTP_USER, SMTP_PASS, SMTP_SECURE (tls/ssl), SMTP_FROM, SMTP_FROM_NAME.
 * Ritorna true se inviata, false se fallita (senza interrompere la richiesta:
 * un'email non inviata non deve rompere registrazione/reset).
 */
function send_email(string $to, string $subject, string $htmlBody): bool
{
    $host = getenv('SMTP_HOST');
    if (!$host) {
        error_log("SMTP non configurato: email a {$to} con oggetto '{$subject}' non inviata.");
        return false;
    }

    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host = $host;
        $mail->Port = (int)(getenv('SMTP_PORT') ?: 587);
        $mail->SMTPAuth = true;
        $mail->Username = getenv('SMTP_USER') ?: '';
        $mail->Password = getenv('SMTP_PASS') ?: '';
        $mail->SMTPSecure = getenv('SMTP_SECURE') ?: PHPMailer::ENCRYPTION_STARTTLS;
        $mail->CharSet = 'UTF-8';

        $mail->setFrom(getenv('SMTP_FROM') ?: 'no-reply@localhost', getenv('SMTP_FROM_NAME') ?: 'Le mie finanze');
        $mail->addAddress($to);
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body = $htmlBody;

        $mail->send();
        return true;
    } catch (PHPMailerException $e) {
        error_log('Invio email fallito: ' . $mail->ErrorInfo);
        return false;
    }
}

/** URL di base dell'app, per costruire i link nelle email (verifica, reset). */
function app_base_url(): string
{
    $configured = getenv('APP_URL');
    if ($configured) return rtrim($configured, '/');

    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    return "{$scheme}://{$host}";
}

/**
 * Genera un nuovo link di reimpostazione password per l'utente e lo invia per
 * email. Usato da "password dimenticata" (valido 1 ora) e dall'area admin
 * (valido più a lungo: l'utente può leggere l'email ore dopo). Ritorna l'esito
 * dell'invio.
 */
function send_password_reset_email(int $userId, string $email, int $validHours = 1): bool
{
    $token = bin2hex(random_bytes(32));
    $expires = date('Y-m-d H:i:s', strtotime("+{$validHours} hours"));
    db()->prepare("UPDATE users SET reset_token = :token, reset_token_expires = :expires WHERE id = :id")
        ->execute(['token' => $token, 'expires' => $expires, 'id' => $userId]);

    $resetUrl = app_base_url() . '/app/?reset_token=' . $token;
    $validity = $validHours === 1 ? '1 ora' : "{$validHours} ore";
    return send_email($email, 'Reimposta la password — Le mie finanze', "
        <p>Hai richiesto di reimpostare la password. Il link è valido {$validity}:</p>
        <p><a href=\"{$resetUrl}\">Reimposta la password</a></p>
        <p>Se non hai richiesto tu questa operazione, ignora questa email: la tua password resterà invariata.</p>
    ");
}
