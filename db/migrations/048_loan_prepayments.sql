-- Migration 048: versamenti volontari in conto capitale (estinzioni parziali)
-- di un mutuo. Entrano nel piano di ammortamento (simulate_amortization in
-- loan_math.php) riducendo la durata o la rata secondo `mode`.
-- transaction_id: l'uscita registrata sul conto per quel versamento, se creata.

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
