-- Migration 007: piani di accumulo capitale (PAC).
-- Un PAC investe un importo fisso a intervalli regolari in uno strumento,
-- calcolando da solo le quote acquistate in base al prezzo del giorno.

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
