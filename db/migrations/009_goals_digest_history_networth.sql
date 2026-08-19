-- Migration 009: storico prezzi, obiettivi di risparmio, digest email,
-- rollover budget, patrimonio immobiliare.

-- Storico prezzi (globale, come price_cache: non e' un dato per-utente,
-- e' la quotazione di mercato di quel giorno).
CREATE TABLE IF NOT EXISTS price_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ticker VARCHAR(20) NOT NULL,
    price DECIMAL(14,4) NOT NULL,
    currency VARCHAR(3) DEFAULT NULL,
    date DATE NOT NULL,
    UNIQUE KEY uniq_ticker_date (ticker, date),
    INDEX idx_ticker_date (ticker, date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Obiettivi di risparmio
CREATE TABLE IF NOT EXISTS savings_goals (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    name VARCHAR(150) NOT NULL,
    target_amount DECIMAL(12,2) NOT NULL,
    start_date DATE NOT NULL,
    target_date DATE DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Preferenza di digest email periodico
ALTER TABLE users ADD COLUMN digest_frequency ENUM('nessuno','settimanale','mensile') NOT NULL DEFAULT 'nessuno';
ALTER TABLE users ADD COLUMN last_digest_sent_at DATETIME DEFAULT NULL;

-- Rollover budget: se attivo su una categoria, l'eccedenza non spesa si
-- riporta al mese successivo invece di azzerarsi.
ALTER TABLE budgets ADD COLUMN rollover TINYINT(1) NOT NULL DEFAULT 0;

-- Patrimonio immobiliare, per il calcolo del patrimonio netto
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
