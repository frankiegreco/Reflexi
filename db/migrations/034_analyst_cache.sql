-- Migration 034: cache delle opinioni di analisti esterni (Yahoo Finance),
-- globale per ticker (come price_cache), non per utente. Ttl piu' lungo di
-- price_cache: i target price degli analisti non cambiano intraday, e la
-- fetch richiede un handshake cookie+crumb in piu' rispetto alla quotazione
-- semplice (vedi price_fetch.php::fetch_yahoo_crumb) quindi vale la pena
-- risparmiarla. `available = 0` cachea anche l'esito negativo (es. ETF senza
-- copertura analisti), per non ritentare Yahoo ad ogni apertura della
-- sezione Previsioni.

CREATE TABLE IF NOT EXISTS analyst_cache (
    ticker VARCHAR(20) NOT NULL PRIMARY KEY,
    target_mean_price DECIMAL(14,4) DEFAULT NULL,
    target_high_price DECIMAL(14,4) DEFAULT NULL,
    target_low_price DECIMAL(14,4) DEFAULT NULL,
    recommendation_mean DECIMAL(4,2) DEFAULT NULL,
    number_of_analysts INT DEFAULT NULL,
    currency VARCHAR(3) DEFAULT NULL,
    available TINYINT(1) NOT NULL DEFAULT 0,
    fetched_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
