<?php
declare(strict_types=1);
// Guida PDF "Gestione della finanza personale in 7 passi" (build/guida/generate.py),
// gratuita per chi ha un account: basta la sessione, non serve un abbonamento
// attivo (resta scaricabile anche a trial scaduto). È un link aperto dal
// browser, non una chiamata dell'app: senza sessione rimanda alla
// registrazione invece di rispondere 401 JSON.
require __DIR__ . '/db.php';

$file = __DIR__ . '/../private/guida-finanza-personale-7-passi.pdf';
$uid = current_user_id();
$exists = false;
if ($uid) {
    $stmt = db()->prepare("SELECT 1 FROM users WHERE id = :id");
    $stmt->execute(['id' => $uid]);
    $exists = (bool)$stmt->fetchColumn();
}
if (!$exists) {
    header('Location: /app/?mode=register&guida=1');
    exit;
}
if (!is_file($file)) respond_error('Guida non disponibile', 404);

header('Content-Type: application/pdf');
header('Content-Disposition: attachment; filename="Reflexi - Guida alla finanza personale in 7 passi.pdf"');
header('Content-Length: ' . filesize($file));
header('Cache-Control: private, no-store');
readfile($file);
