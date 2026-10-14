<?php
declare(strict_types=1);
// Endpoint pubblico (niente sessione né auth): prezzi dei piani letti da
// Stripe tramite _pricing.php, per Paywall e Profilo dell'app React.
require __DIR__ . '/../_pricing.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: public, max-age=300');
echo json_encode(reflexi_pricing(), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
