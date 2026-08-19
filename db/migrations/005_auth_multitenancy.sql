-- Migration 005: aggiunge utenti + multi-tenancy.
-- Ogni tabella di dati utente riceve una colonna user_id; i dati gia' presenti
-- (installazione mono-utente) vengono assegnati a un "utente legacy" creato qui,
-- cosi' non si perde nulla. price_cache resta GLOBALE (condivisa tra utenti,
-- riduce le chiamate a Yahoo Finance).

CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(190) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    name VARCHAR(150) DEFAULT NULL,
    email_verified_at TIMESTAMP NULL DEFAULT NULL,
    verification_token VARCHAR(64) DEFAULT NULL,
    verification_token_expires DATETIME DEFAULT NULL,
    reset_token VARCHAR(64) DEFAULT NULL,
    reset_token_expires DATETIME DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Utente "legacy" per i dati gia' esistenti in un'installazione mono-utente.
-- password_hash e' un valore inutilizzabile di proposito: dopo la migration
-- dovrai impostare la tua email reale su questa riga e usare "password
-- dimenticata" per scegliere la tua password (vedi istruzioni nel README).
INSERT INTO users (email, password_hash, name, email_verified_at)
SELECT 'da-cambiare@localhost', '$2y$10$invalidinvalidinvalidinvalidinvalidinvalidinvalidinva', 'Utente esistente', NOW()
WHERE NOT EXISTS (SELECT 1 FROM users);

-- accounts
ALTER TABLE accounts ADD COLUMN user_id INT NULL AFTER id;
UPDATE accounts SET user_id = (SELECT id FROM users ORDER BY id LIMIT 1) WHERE user_id IS NULL;
ALTER TABLE accounts MODIFY COLUMN user_id INT NOT NULL;
ALTER TABLE accounts ADD CONSTRAINT fk_accounts_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE;

-- categories
ALTER TABLE categories ADD COLUMN user_id INT NULL AFTER id;
UPDATE categories SET user_id = (SELECT id FROM users ORDER BY id LIMIT 1) WHERE user_id IS NULL;
ALTER TABLE categories MODIFY COLUMN user_id INT NOT NULL;
ALTER TABLE categories ADD CONSTRAINT fk_categories_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE;

-- transactions
ALTER TABLE transactions ADD COLUMN user_id INT NULL AFTER id;
UPDATE transactions SET user_id = (SELECT id FROM users ORDER BY id LIMIT 1) WHERE user_id IS NULL;
ALTER TABLE transactions MODIFY COLUMN user_id INT NOT NULL;
ALTER TABLE transactions ADD CONSTRAINT fk_transactions_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE;

-- recurring_transactions
ALTER TABLE recurring_transactions ADD COLUMN user_id INT NULL AFTER id;
UPDATE recurring_transactions SET user_id = (SELECT id FROM users ORDER BY id LIMIT 1) WHERE user_id IS NULL;
ALTER TABLE recurring_transactions MODIFY COLUMN user_id INT NOT NULL;
ALTER TABLE recurring_transactions ADD CONSTRAINT fk_recurring_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE;

-- loans
ALTER TABLE loans ADD COLUMN user_id INT NULL AFTER id;
UPDATE loans SET user_id = (SELECT id FROM users ORDER BY id LIMIT 1) WHERE user_id IS NULL;
ALTER TABLE loans MODIFY COLUMN user_id INT NOT NULL;
ALTER TABLE loans ADD CONSTRAINT fk_loans_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE;

-- instruments (il ticker deve restare univoco per utente, non globale)
ALTER TABLE instruments ADD COLUMN user_id INT NULL AFTER id;
UPDATE instruments SET user_id = (SELECT id FROM users ORDER BY id LIMIT 1) WHERE user_id IS NULL;
ALTER TABLE instruments MODIFY COLUMN user_id INT NOT NULL;
ALTER TABLE instruments ADD CONSTRAINT fk_instruments_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE;
ALTER TABLE instruments DROP INDEX ticker;
ALTER TABLE instruments ADD CONSTRAINT uniq_user_ticker UNIQUE (user_id, ticker);
-- Se questa riga da' errore "key non esiste", esegui prima "SHOW INDEX FROM instruments;"
-- e sostituisci "ticker" con il nome esatto dell'indice mostrato.

-- investment_transactions
ALTER TABLE investment_transactions ADD COLUMN user_id INT NULL AFTER id;
UPDATE investment_transactions SET user_id = (SELECT id FROM users ORDER BY id LIMIT 1) WHERE user_id IS NULL;
ALTER TABLE investment_transactions MODIFY COLUMN user_id INT NOT NULL;
ALTER TABLE investment_transactions ADD CONSTRAINT fk_invtx_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE;

-- budgets
ALTER TABLE budgets ADD COLUMN user_id INT NULL AFTER id;
UPDATE budgets SET user_id = (SELECT id FROM users ORDER BY id LIMIT 1) WHERE user_id IS NULL;
ALTER TABLE budgets MODIFY COLUMN user_id INT NOT NULL;
ALTER TABLE budgets ADD CONSTRAINT fk_budgets_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE;
-- Il budget era unico per categoria; ora deve essere unico per utente+categoria
ALTER TABLE budgets DROP INDEX category_id;
ALTER TABLE budgets ADD CONSTRAINT uniq_user_category UNIQUE (user_id, category_id);
