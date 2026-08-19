-- Migration 013: FIRE, fondi pensione, storico patrimonio netto.

ALTER TABLE users ADD COLUMN fire_withdrawal_rate DECIMAL(4,2) NOT NULL DEFAULT 4.00;
ALTER TABLE users ADD COLUMN fire_expected_return DECIMAL(4,2) NOT NULL DEFAULT 6.00;

CREATE TABLE IF NOT EXISTS pension_funds (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    name VARCHAR(150) NOT NULL,
    fund_type ENUM('negoziale','aperto','pip','tfr_azienda') NOT NULL DEFAULT 'negoziale',
    current_balance DECIMAL(12,2) NOT NULL DEFAULT 0,
    monthly_contribution DECIMAL(10,2) NOT NULL DEFAULT 0,
    employer_contribution DECIMAL(10,2) NOT NULL DEFAULT 0,
    expected_annual_return DECIMAL(5,2) NOT NULL DEFAULT 3.00,
    notes VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Uno snapshot al giorno del patrimonio netto, per poter mostrare un vero
-- andamento nel tempo nei report (altrimenti avremmo solo il valore di oggi).
CREATE TABLE IF NOT EXISTS net_worth_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    date DATE NOT NULL,
    net_worth DECIMAL(14,2) NOT NULL,
    liquid_balance DECIMAL(14,2) NOT NULL,
    investments_value DECIMAL(14,2) NOT NULL,
    real_estate_value DECIMAL(14,2) NOT NULL,
    debt_total DECIMAL(14,2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_user_date (user_id, date),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
