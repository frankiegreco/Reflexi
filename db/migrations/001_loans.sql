-- Migration 001: aggiunge il modulo Mutui.
-- Sicura da rieseguire (IF NOT EXISTS) e non tocca le tabelle/dati esistenti.

CREATE TABLE IF NOT EXISTS loans (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    principal DECIMAL(12,2) NOT NULL,           -- capitale erogato
    annual_rate DECIMAL(6,3) NOT NULL,           -- tasso annuo nominale, es. 3.250
    duration_years INT NOT NULL,
    start_date DATE NOT NULL,
    account_id INT DEFAULT NULL,                 -- conto da cui viene addebitata la rata
    category_id INT DEFAULT NULL,                -- categoria di spesa collegata
    recurring_id INT DEFAULT NULL,               -- rata ricorrente collegata, se creata
    status ENUM('attivo','estinto') NOT NULL DEFAULT 'attivo',
    early_payoff_date DATE DEFAULT NULL,
    early_payoff_amount DECIMAL(12,2) DEFAULT NULL,
    notes VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (account_id) REFERENCES accounts(id) ON DELETE SET NULL,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL,
    FOREIGN KEY (recurring_id) REFERENCES recurring_transactions(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Categoria dedicata per le rate di mutuo, se non esiste già
INSERT INTO categories (name, type, color, sort_order)
SELECT 'Mutuo/Prestito', 'uscita', '#c1665a', 20
WHERE NOT EXISTS (SELECT 1 FROM categories WHERE name = 'Mutuo/Prestito');
