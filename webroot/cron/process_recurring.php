<?php
declare(strict_types=1);

// Script pensato per essere lanciato da cron via CLI (php cron/process_recurring.php),
// MAI esposto come endpoint HTTP pubblico — altrimenti chiunque potrebbe forzare
// la generazione di transazioni per tutti gli utenti. Va eseguito dentro il
// container "web", dove gira comunque anche il resto del backend.
if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    exit('Questo script va eseguito solo da riga di comando (cron), non via web.');
}

require __DIR__ . '/../api/db.php';
require __DIR__ . '/../api/lib_recurring.php';

$users = db()->query("SELECT id FROM users")->fetchAll();
$totalCreated = 0;

foreach ($users as $user) {
    $totalCreated += process_due_recurring((int)$user['id']);
}

echo "Transazioni generate da ricorrenze: {$totalCreated}\n";
