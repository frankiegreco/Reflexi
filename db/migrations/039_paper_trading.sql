-- Migration 039: trading simulato (paper trading), zero impatto sulle
-- finanze reali. Tabelle completamente separate da accounts/instruments/
-- investment_transactions apposta: nessuna FK verso quelle tabelle, nessuna
-- possibilita' che un conto/strumento simulato finisca mai nei calcoli di
-- patrimonio netto, saldi reali o portafoglio investimenti vero.
--
-- Il saldo di cassa NON e' un campo persistito (stessa convenzione dei
-- saldi conto reali, vedi CLAUDE.md): si calcola sempre come
-- starting_balance + SUM(cash_impact_eur) delle transazioni. cash_impact_eur
-- e' gia' convertito in EUR al momento dell'esecuzione (fx_rate_to_eur, la
-- stessa funzione usata per i conti reali in valuta estera) cosi' un conto
-- simulato puo' restare in un'unica valuta di cassa (EUR) anche comprando
-- strumenti quotati in USD/altre valute, senza conversioni implicite
-- silenziose: prezzo nativo, valuta nativa e tasso di cambio usato restano
-- tutti salvati sulla riga per trasparenza.

CREATE TABLE IF NOT EXISTS paper_trading_accounts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    name VARCHAR(100) NOT NULL,
    starting_balance DECIMAL(14,2) NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS paper_trading_transactions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    account_id INT NOT NULL,
    ticker VARCHAR(20) NOT NULL,
    name VARCHAR(150) NOT NULL,
    type ENUM('acquisto','vendita') NOT NULL,
    quantity DECIMAL(18,6) NOT NULL,
    price_per_unit DECIMAL(14,4) NOT NULL,
    currency VARCHAR(3) NOT NULL DEFAULT 'EUR',
    fx_rate_to_eur DECIMAL(14,6) NOT NULL DEFAULT 1,
    cash_impact_eur DECIMAL(14,2) NOT NULL,
    executed_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (account_id) REFERENCES paper_trading_accounts(id) ON DELETE CASCADE,
    INDEX idx_account_executed (account_id, executed_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
