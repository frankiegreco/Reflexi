<?php
declare(strict_types=1);

// Ri-addestra il modello di previsione della sezione "Previsioni" su tutto
// lo storico prezzi disponibile (globale, non solo per un utente). Va
// lanciato da riga di comando (php cron/retrain_forecast_model.php dentro
// il container "web"), MAI come endpoint HTTP: e' un lavoro da batch che
// scansiona tutto price_history, non qualcosa da far scattare a ogni
// richiesta di un utente. Non e' collegato a un cron automatico per ora:
// va rilanciato manualmente quando si accumula storico prezzi nuovo
// (ogni pochi mesi ha senso, non ogni giorno — i coefficienti non
// cambiano velocemente con pochi giorni di dati in piu').
if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    exit('Questo script va eseguito solo da riga di comando (cron), non via web.');
}

require __DIR__ . '/../api/db.php';
require __DIR__ . '/../api/lib_forecast_model.php';

$result = train_forecast_model(db());

if (!$result['ok']) {
    fwrite(STDERR, "Training fallito: {$result['message']}\n");
    exit(1);
}

echo "Modello addestrato:\n";
echo "  Campioni:   {$result['n_samples']} (da {$result['n_tickers']} ticker: " . implode(', ', $result['tickers_used']) . ")\n";
echo "  R^2 (training set, non out-of-sample): {$result['r_squared']}\n";
echo "  Coefficienti:\n";
foreach ($result['coefficients'] as $name => $value) {
    echo "    {$name}: " . round($value, 4) . "\n";
}
