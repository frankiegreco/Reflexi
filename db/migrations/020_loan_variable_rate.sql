-- Migration 020: supporto mutui a tasso variabile.
-- Tipo di tasso sul mutuo (fisso = comportamento identico a oggi).
ALTER TABLE loans ADD COLUMN rate_type ENUM('fisso','variabile') NOT NULL DEFAULT 'fisso';

-- Storico delle variazioni di tasso. Per un mutuo a tasso fisso c'e' sempre e
-- solo UNA riga (il tasso di partenza). Per uno variabile, una riga per ogni
-- cambio, con la data da cui il nuovo tasso e' in vigore.
CREATE TABLE IF NOT EXISTS loan_rate_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    loan_id INT NOT NULL,
    effective_date DATE NOT NULL,
    rate DECIMAL(5,3) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (loan_id) REFERENCES loans(id) ON DELETE CASCADE,
    UNIQUE KEY uniq_loan_date (loan_id, effective_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Migrazione dei mutui esistenti: crea una riga di storico con il tasso e la
-- data di inizio attuali di ogni mutuo gia' presente, cosi' tutto continua a
-- funzionare esattamente come prima senza bisogno di altro intervento manuale.
INSERT INTO loan_rate_history (loan_id, effective_date, rate)
SELECT id, start_date, annual_rate FROM loans;
