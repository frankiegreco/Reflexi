-- Migration 002: aggiunge il modulo Investimenti (ETF, azioni, ecc.)
-- Sicura da rieseguire (IF NOT EXISTS) e non tocca le tabelle/dati esistenti.

CREATE TABLE IF NOT EXISTS instruments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ticker VARCHAR(20) NOT NULL UNIQUE,      -- es. VWCE.MI, AAPL, SWDA.MI
    name VARCHAR(150) NOT NULL,
    type ENUM('etf','azione','obbligazione','fondo','crypto','altro') NOT NULL DEFAULT 'etf',
    currency VARCHAR(3) NOT NULL DEFAULT 'EUR',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS investment_transactions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    instrument_id INT NOT NULL,
    account_id INT DEFAULT NULL,
    linked_transaction_id INT DEFAULT NULL,   -- movimento di cassa collegato, se creato
    type ENUM('acquisto','vendita') NOT NULL,
    quantity DECIMAL(18,6) NOT NULL,
    price_per_unit DECIMAL(14,4) NOT NULL,
    fees DECIMAL(10,2) NOT NULL DEFAULT 0,
    date DATE NOT NULL,
    notes VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (instrument_id) REFERENCES instruments(id) ON DELETE CASCADE,
    FOREIGN KEY (account_id) REFERENCES accounts(id) ON DELETE SET NULL,
    INDEX idx_instrument (instrument_id),
    INDEX idx_date (date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Cache delle quotazioni per non interrogare l'API esterna ad ogni caricamento
CREATE TABLE IF NOT EXISTS price_cache (
    ticker VARCHAR(20) PRIMARY KEY,
    price DECIMAL(14,4) NOT NULL,
    currency VARCHAR(3) DEFAULT NULL,
    fetched_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    source VARCHAR(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Categorie dedicate, cosi' se scegli di registrare anche il movimento di cassa
-- di un acquisto/vendita, compare distinto nel resto dell'app
INSERT INTO categories (name, type, color, sort_order)
SELECT 'Investimenti (acquisto)', 'uscita', '#4f46e5', 21
WHERE NOT EXISTS (SELECT 1 FROM categories WHERE name = 'Investimenti (acquisto)');

INSERT INTO categories (name, type, color, sort_order)
SELECT 'Investimenti (vendita)', 'entrata', '#4f46e5', 6
WHERE NOT EXISTS (SELECT 1 FROM categories WHERE name = 'Investimenti (vendita)');
