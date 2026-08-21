-- Migration 030: importazione massiva transazioni da CSV (es. export di
-- Trade Republic o di un altro conto/broker). Ogni import viene tracciato in
-- import_batches cosi' l'utente puo' vedere lo storico e, se ha sbagliato
-- file/mapping, annullare l'intero batch con un click: cancellare la riga
-- del batch elimina a cascata solo le transazioni create da quell'import
-- (import_batch_id), senza toccare quelle inserite manualmente o da altri
-- import. import_mappings salva la configurazione di mapping colonne->campi
-- con un nome scelto dall'utente (es. "Trade Republic"), per non dover
-- rifare tutta la mappatura ad ogni export mensile dello stesso servizio.

CREATE TABLE IF NOT EXISTS import_batches (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    account_id INT NOT NULL,
    source VARCHAR(100) NOT NULL DEFAULT 'CSV',
    filename VARCHAR(255) DEFAULT NULL,
    row_count INT NOT NULL DEFAULT 0,
    imported_count INT NOT NULL DEFAULT 0,
    duplicate_count INT NOT NULL DEFAULT 0,
    error_count INT NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (account_id) REFERENCES accounts(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

ALTER TABLE transactions
    ADD COLUMN import_batch_id INT DEFAULT NULL AFTER recurring_id,
    ADD FOREIGN KEY (import_batch_id) REFERENCES import_batches(id) ON DELETE CASCADE,
    ADD INDEX idx_import_batch (import_batch_id);

CREATE TABLE IF NOT EXISTS import_mappings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    name VARCHAR(100) NOT NULL,
    config JSON NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY uniq_user_name (user_id, name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
