-- Migration 035: modello di previsione appreso (regressione lineare OLS),
-- non piu' pesi fissi. Ogni riga e' una versione addestrata (append-only,
-- non un aggiornamento in place) cosi' resta uno storico di come il
-- modello e' cambiato nel tempo; la previsione usa sempre l'ultima riga
-- per trained_at. Popolata da webroot/cron/retrain_forecast_model.php,
-- MAI da una richiesta utente (il training scansiona tutto price_history,
-- e' un lavoro da batch).

CREATE TABLE IF NOT EXISTS forecast_model (
    id INT AUTO_INCREMENT PRIMARY KEY,
    trained_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    n_samples INT NOT NULL,
    n_tickers INT NOT NULL,
    r_squared DECIMAL(6,4) NOT NULL,
    intercept DECIMAL(14,8) NOT NULL,
    coef_trend DECIMAL(14,8) NOT NULL,
    coef_momentum DECIMAL(14,8) NOT NULL,
    coef_volatility DECIMAL(14,8) NOT NULL,
    coef_drawdown DECIMAL(14,8) NOT NULL,
    INDEX idx_trained_at (trained_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
