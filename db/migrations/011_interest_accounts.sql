-- Migration 011: conti deposito/interesse, tracciati in Investimenti.
-- Concettualmente distinti dai conti transazionali (accounts): qui si traccia
-- un capitale che matura interesse ad un tasso noto, vincolato o meno.

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
