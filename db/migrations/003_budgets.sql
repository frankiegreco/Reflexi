-- Migration 003: aggiunge il budget mensile per categoria.
-- Sicura da rieseguire (IF NOT EXISTS) e non tocca le tabelle/dati esistenti.

CREATE TABLE IF NOT EXISTS budgets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    category_id INT NOT NULL UNIQUE,
    monthly_limit DECIMAL(10,2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
