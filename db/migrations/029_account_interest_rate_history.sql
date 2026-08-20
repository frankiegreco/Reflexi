-- Migration 029: storico dei tassi di interesse sui conti, sul modello di
-- loan_rate_history per i mutui a tasso variabile. Il tasso di un conto
-- deposito/remunerato puo' cambiare nel tempo (promozioni a scadenza,
-- rinegoziazioni, tassi di mercato), quindi va tracciato uno storico invece
-- di un unico valore corrente: cosi' il calcolo dell'interesse arretrato usa
-- il tasso realmente in vigore in ciascun periodo, non quello attuale
-- applicato retroattivamente a tutti i periodi passati.

CREATE TABLE IF NOT EXISTS account_interest_rate_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    account_id INT NOT NULL,
    effective_date DATE NOT NULL,
    rate DECIMAL(5,3) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (account_id) REFERENCES accounts(id) ON DELETE CASCADE,
    UNIQUE KEY uniq_account_date (account_id, effective_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Migrazione dei conti con interesse gia' attivo: crea una riga di storico
-- con il tasso attuale, valida dalla creazione del conto, cosi' tutto
-- continua a funzionare esattamente come prima senza intervento manuale.
INSERT INTO account_interest_rate_history (account_id, effective_date, rate)
SELECT id, DATE(created_at), interest_rate FROM accounts
WHERE interest_enabled = 1 AND interest_rate IS NOT NULL;
