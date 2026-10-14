-- Schema per l'app di gestione finanze personali (multi-utente)
SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(190) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    is_admin TINYINT(1) NOT NULL DEFAULT 0,
    name VARCHAR(150) DEFAULT NULL,
    first_name VARCHAR(75) DEFAULT NULL,
    last_name VARCHAR(75) DEFAULT NULL,
    phone VARCHAR(30) DEFAULT NULL,
    finance_experience ENUM('principiante','intermedio','avanzato') DEFAULT NULL,
    email_verified_at TIMESTAMP NULL DEFAULT NULL,
    verification_token VARCHAR(64) DEFAULT NULL,
    verification_token_expires DATETIME DEFAULT NULL,
    reset_token VARCHAR(64) DEFAULT NULL,
    reset_token_expires DATETIME DEFAULT NULL,
    digest_frequency ENUM('nessuno','settimanale','mensile') NOT NULL DEFAULT 'nessuno',
    last_digest_sent_at DATETIME DEFAULT NULL,
    subscription_status ENUM('trialing','active','past_due','canceled','none') NOT NULL DEFAULT 'none',
    trial_ends_at DATETIME DEFAULT NULL,
    stripe_customer_id VARCHAR(64) DEFAULT NULL,
    stripe_subscription_id VARCHAR(64) DEFAULT NULL,
    subscription_current_period_end DATETIME DEFAULT NULL,
    past_due_since DATETIME DEFAULT NULL,
    subscription_cancel_at DATETIME DEFAULT NULL,
    preferred_plan ENUM('mensile','annuale') DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS accounts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    name VARCHAR(100) NOT NULL,
    type ENUM('corrente','risparmio','contanti','carta_credito','investimento','altro') NOT NULL DEFAULT 'corrente',
    initial_balance DECIMAL(12,2) NOT NULL DEFAULT 0,
    color VARCHAR(7) NOT NULL DEFAULT '#4a90a4',
    archived TINYINT(1) NOT NULL DEFAULT 0,
    sort_order INT NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    name VARCHAR(100) NOT NULL,
    type ENUM('entrata','uscita') NOT NULL,
    color VARCHAR(7) NOT NULL DEFAULT '#8b95a1',
    icon VARCHAR(50) DEFAULT NULL,
    sort_order INT NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS recurring_transactions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    account_id INT NOT NULL,
    category_id INT NOT NULL,
    transfer_account_id INT DEFAULT NULL,
    amount DECIMAL(12,2) NOT NULL,
    type ENUM('entrata','uscita','trasferimento') NOT NULL,
    description VARCHAR(255) DEFAULT NULL,
    frequency ENUM('giornaliera','settimanale','mensile','annuale') NOT NULL DEFAULT 'mensile',
    interval_count INT NOT NULL DEFAULT 1,
    start_date DATE NOT NULL,
    end_date DATE DEFAULT NULL,
    next_run DATE NOT NULL,
    active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (account_id) REFERENCES accounts(id) ON DELETE CASCADE,
    FOREIGN KEY (category_id) REFERENCES categories(id),
    FOREIGN KEY (transfer_account_id) REFERENCES accounts(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS transactions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    account_id INT NOT NULL,
    category_id INT NOT NULL,
    transfer_account_id INT DEFAULT NULL,
    amount DECIMAL(12,2) NOT NULL,
    type ENUM('entrata','uscita','trasferimento') NOT NULL,
    description VARCHAR(255) DEFAULT NULL,
    date DATE NOT NULL,
    recurring_id INT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (account_id) REFERENCES accounts(id) ON DELETE CASCADE,
    FOREIGN KEY (category_id) REFERENCES categories(id),
    FOREIGN KEY (transfer_account_id) REFERENCES accounts(id),
    FOREIGN KEY (recurring_id) REFERENCES recurring_transactions(id) ON DELETE SET NULL,
    INDEX idx_date (date),
    INDEX idx_account (account_id),
    INDEX idx_category (category_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS loans (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    name VARCHAR(150) NOT NULL,
    principal DECIMAL(12,2) NOT NULL,
    annual_rate DECIMAL(6,3) NOT NULL,
    duration_years INT NOT NULL,
    ownership_pct DECIMAL(5,2) NOT NULL DEFAULT 100.00,
    start_date DATE NOT NULL,
    account_id INT DEFAULT NULL,
    category_id INT DEFAULT NULL,
    recurring_id INT DEFAULT NULL,
    status ENUM('attivo','estinto') NOT NULL DEFAULT 'attivo',
    early_payoff_date DATE DEFAULT NULL,
    early_payoff_amount DECIMAL(12,2) DEFAULT NULL,
    notes VARCHAR(255) DEFAULT NULL,
    rate_type ENUM('fisso','variabile') NOT NULL DEFAULT 'fisso',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (account_id) REFERENCES accounts(id) ON DELETE SET NULL,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL,
    FOREIGN KEY (recurring_id) REFERENCES recurring_transactions(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Storico delle variazioni di tasso di un mutuo. Per un mutuo a tasso fisso
-- c'e' sempre e solo una riga (il tasso di partenza); per uno variabile, una
-- riga per ogni cambio, con la data da cui il nuovo tasso e' in vigore.
CREATE TABLE IF NOT EXISTS loan_rate_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    loan_id INT NOT NULL,
    effective_date DATE NOT NULL,
    rate DECIMAL(5,3) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (loan_id) REFERENCES loans(id) ON DELETE CASCADE,
    UNIQUE KEY uniq_loan_date (loan_id, effective_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Versamenti volontari in conto capitale di un mutuo (estinzioni parziali):
-- riducono durata o rata nel piano di ammortamento (vedi migrazione 048).
CREATE TABLE IF NOT EXISTS loan_prepayments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    loan_id INT NOT NULL,
    payment_date DATE NOT NULL,
    amount DECIMAL(12,2) NOT NULL,
    mode ENUM('durata','rata') NOT NULL DEFAULT 'durata',
    transaction_id INT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (loan_id) REFERENCES loans(id) ON DELETE CASCADE,
    FOREIGN KEY (transaction_id) REFERENCES transactions(id) ON DELETE SET NULL,
    INDEX idx_loan_date (loan_id, payment_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Modulo Investimenti (ETF, azioni, ecc.)
CREATE TABLE IF NOT EXISTS instruments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    ticker VARCHAR(20) NOT NULL,
    name VARCHAR(150) NOT NULL,
    type ENUM('etf','azione','obbligazione','fondo','crypto','altro') NOT NULL DEFAULT 'etf',
    currency VARCHAR(3) NOT NULL DEFAULT 'EUR',
    tax_rate DECIMAL(5,2) NOT NULL DEFAULT 26.00,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY uniq_user_ticker (user_id, ticker)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS investment_transactions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    instrument_id INT NOT NULL,
    account_id INT DEFAULT NULL,
    linked_transaction_id INT DEFAULT NULL,
    type ENUM('acquisto','vendita') NOT NULL,
    quantity DECIMAL(18,6) NOT NULL,
    price_per_unit DECIMAL(14,4) NOT NULL,
    fees DECIMAL(10,2) NOT NULL DEFAULT 0,
    tax_amount DECIMAL(10,2) NOT NULL DEFAULT 0,
    date DATE NOT NULL,
    notes VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (instrument_id) REFERENCES instruments(id) ON DELETE CASCADE,
    FOREIGN KEY (account_id) REFERENCES accounts(id) ON DELETE SET NULL,
    INDEX idx_instrument (instrument_id),
    INDEX idx_date (date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Cache prezzi GLOBALE (condivisa tra tutti gli utenti, per ticker: meno chiamate esterne)
CREATE TABLE IF NOT EXISTS price_cache (
    ticker VARCHAR(20) PRIMARY KEY,
    price DECIMAL(14,4) NOT NULL,
    currency VARCHAR(3) DEFAULT NULL,
    fetched_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    source VARCHAR(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Cache delle opinioni di analisti esterni (Yahoo Finance), globale per
-- ticker come price_cache ma con ttl piu' lungo (vedi migrazione 034).
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

-- Modello di previsione appreso (regressione OLS), append-only: ogni riga
-- e' una versione addestrata di UN modello per asset class (equity/bond/
-- crypto, vedi migrazione 036 sul perche' di questa separazione), si usa
-- sempre l'ultima per quella asset class (vedi migrazione 035).
-- r_squared = fit in-sample sull'intera serie di ogni asset class (usato
-- per i coefficienti applicati in produzione); r_squared_oos = fit
-- misurato su un holdout temporale (ultimo 20% cronologico di ogni
-- ticker, mai usato per stimare i coefficienti) — puo' essere negativo,
-- a differenza di r_squared. Vedi migrazione 037 per il perche'.
-- label_min_pct/label_max_pct = range dei rendimenti a 365gg REALMENTE
-- realizzati nei campioni di training di quella asset class: la previsione
-- in inferenza viene tagliata a questo intervallo, perche' la regressione
-- OLS non ha nessun limite naturale sull'output e su feature estreme (es.
-- volatilita' fuori norma) puo' estrapolare ben oltre qualunque rendimento
-- mai realmente accaduto nello storico usato per allenarla. Vedi
-- migrazione 038 per il caso concreto che l'ha fatto emergere.
CREATE TABLE IF NOT EXISTS forecast_model (
    id INT AUTO_INCREMENT PRIMARY KEY,
    trained_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    asset_class VARCHAR(10) NOT NULL DEFAULT 'equity',
    n_samples INT NOT NULL,
    n_samples_train INT NULL,
    n_samples_test INT NULL,
    n_tickers INT NOT NULL,
    r_squared DECIMAL(6,4) NOT NULL,
    r_squared_oos DECIMAL(7,4) NULL,
    intercept DECIMAL(14,8) NOT NULL,
    coef_trend DECIMAL(14,8) NOT NULL,
    coef_momentum DECIMAL(14,8) NOT NULL,
    coef_volatility DECIMAL(14,8) NOT NULL,
    coef_drawdown DECIMAL(14,8) NOT NULL,
    label_min_pct DECIMAL(10,2) NULL,
    label_max_pct DECIMAL(10,2) NULL,
    INDEX idx_asset_class_trained_at (asset_class, trained_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Trading simulato (paper trading), zero impatto sulle finanze reali —
-- tabelle separate da accounts/instruments/investment_transactions apposta
-- (vedi migrazione 039). Il saldo di cassa non e' persistito, si calcola
-- sempre come starting_balance + SUM(cash_impact_eur) delle transazioni
-- (stessa convenzione dei saldi conto reali).
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
    fees_eur DECIMAL(10,2) NOT NULL DEFAULT 0,
    executed_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (account_id) REFERENCES paper_trading_accounts(id) ON DELETE CASCADE,
    INDEX idx_account_executed (account_id, executed_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS budgets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    category_id INT NOT NULL,
    monthly_limit DECIMAL(10,2) NOT NULL,
    rollover TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE,
    UNIQUE KEY uniq_user_category (user_id, category_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS investment_plans (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    instrument_id INT NOT NULL,
    account_id INT NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    fees DECIMAL(10,2) NOT NULL DEFAULT 0,
    frequency ENUM('settimanale','mensile','annuale') NOT NULL DEFAULT 'mensile',
    interval_count INT NOT NULL DEFAULT 1,
    start_date DATE NOT NULL,
    end_date DATE DEFAULT NULL,
    next_run DATE NOT NULL,
    active TINYINT(1) NOT NULL DEFAULT 1,
    notes VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (instrument_id) REFERENCES instruments(id) ON DELETE CASCADE,
    FOREIGN KEY (account_id) REFERENCES accounts(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS rate_limit_attempts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    action VARCHAR(50) NOT NULL,
    identifier VARCHAR(190) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_action_identifier_time (action, identifier, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS price_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ticker VARCHAR(20) NOT NULL,
    price DECIMAL(14,4) NOT NULL,
    currency VARCHAR(3) DEFAULT NULL,
    date DATE NOT NULL,
    UNIQUE KEY uniq_ticker_date (ticker, date),
    INDEX idx_ticker_date (ticker, date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS savings_goals (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    name VARCHAR(150) NOT NULL,
    target_amount DECIMAL(12,2) NOT NULL,
    account_id INT DEFAULT NULL,
    start_date DATE NOT NULL,
    target_date DATE DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (account_id) REFERENCES accounts(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS real_estate_assets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    name VARCHAR(150) NOT NULL,
    estimated_value DECIMAL(12,2) NOT NULL,
    linked_loan_id INT DEFAULT NULL,
    notes VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (linked_loan_id) REFERENCES loans(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS interest_accounts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    name VARCHAR(150) NOT NULL,
    bank_name VARCHAR(150) DEFAULT NULL,
    balance DECIMAL(12,2) NOT NULL,
    interest_rate DECIMAL(5,3) NOT NULL,
    interest_tax_rate DECIMAL(5,2) NOT NULL DEFAULT 26.00,
    vincolo_end_date DATE DEFAULT NULL,
    notes VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS user_sessions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    started_at DATETIME NOT NULL,
    last_seen_at DATETIME NOT NULL,
    ended_at DATETIME DEFAULT NULL,
    ip VARCHAR(45) DEFAULT NULL,
    user_agent VARCHAR(255) DEFAULT NULL,
    INDEX idx_user_sessions_user (user_id, started_at),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS security_events (
    id INT AUTO_INCREMENT PRIMARY KEY,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    level ENUM('info','warning','error') NOT NULL,
    type VARCHAR(40) NOT NULL,
    user_id INT DEFAULT NULL,
    email VARCHAR(190) DEFAULT NULL,
    ip VARCHAR(45) DEFAULT NULL,
    user_agent VARCHAR(255) DEFAULT NULL,
    endpoint VARCHAR(255) DEFAULT NULL,
    message TEXT,
    INDEX idx_security_events_created (created_at),
    INDEX idx_security_events_type (type, created_at),
    INDEX idx_security_events_ip (ip, created_at),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
