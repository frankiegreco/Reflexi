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

        $mail->setFrom(getenv('SMTP_FROM') ?: 'no-reply@localhost', getenv('SMTP_FROM_NAME') ?: 'Reflexi');
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
    return send_email($email, 'Reimposta la password — Reflexi', "
        <p>Hai richiesto di reimpostare la password. Il link è valido {$validity}:</p>
        <p><a href=\"{$resetUrl}\">Reimposta la password</a></p>
        <p>Se non hai richiesto tu questa operazione, ignora questa email: la tua password resterà invariata.</p>
    ");
}

/**
 * Cornice HTML delle email "curate" (oggi i promemoria): tabelle e stili in
 * linea, perché Gmail, Outlook e le app di posta ignorano CSS esterni e
 * layout moderni. $preheader è l'anteprima mostrata accanto all'oggetto nella
 * casella di posta; $content il corpo, già HTML; $footer la nota in fondo.
 */
function email_layout(string $preheader, string $content, string $footer): string
{
    $preheader = htmlspecialchars($preheader);
    $site = htmlspecialchars(app_base_url());
    return <<<HTML
<!DOCTYPE html>
<html lang="it">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>Reflexi</title></head>
<body style="margin:0;padding:0;background:#f3f4f8;">
<div style="display:none;max-height:0;overflow:hidden;opacity:0;color:transparent;">{$preheader}</div>
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background:#f3f4f8;">
  <tr><td align="center" style="padding:32px 16px;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="max-width:560px;">
      <tr><td style="padding:0 4px 20px;font-family:Arial,Helvetica,sans-serif;font-size:22px;font-weight:700;color:#14162b;letter-spacing:-0.3px;">
        <span style="color:#4f46e5;">&#9679;</span> Reflexi
      </td></tr>
      <tr><td style="background:#ffffff;border-radius:16px;padding:36px 32px;font-family:Arial,Helvetica,sans-serif;font-size:16px;line-height:1.6;color:#1f2937;">
        {$content}
      </td></tr>
      <tr><td style="padding:20px 8px 0;font-family:Arial,Helvetica,sans-serif;font-size:12px;line-height:1.6;color:#9ca3af;text-align:center;">
        {$footer}<br><a href="{$site}" style="color:#9ca3af;">reflexi.it</a> &middot; Il riflesso vero delle tue finanze
      </td></tr>
    </table>
  </td></tr>
</table>
</body>
</html>
HTML;
}

/** Pulsante centrato per email_layout(), cliccabile in tutto il riquadro anche in Outlook. */
function email_button(string $url, string $label): string
{
    $url = htmlspecialchars($url);
    return <<<HTML
<table role="presentation" cellpadding="0" cellspacing="0" border="0" align="center" style="margin:28px auto;">
  <tr><td align="center" bgcolor="#4f46e5" style="border-radius:12px;">
    <a href="{$url}" style="display:inline-block;padding:14px 32px;font-family:Arial,Helvetica,sans-serif;font-size:16px;font-weight:700;color:#ffffff;text-decoration:none;border-radius:12px;">{$label}</a>
  </td></tr>
</table>
HTML;
}

