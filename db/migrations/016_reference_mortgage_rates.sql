-- Migration 016: tabella di riferimento tassi mutuo, popolata inizialmente da
-- una ricerca web datata e aggiornabile manualmente in futuro (stesso
-- principio dei portali di comparazione: dati periodicamente aggiornati "a
-- tabella", non un'interrogazione live alle banche).

CREATE TABLE IF NOT EXISTS reference_mortgage_rates (
    id INT AUTO_INCREMENT PRIMARY KEY,
    bank_name VARCHAR(150) NOT NULL,
    rate_type ENUM('fisso','variabile') NOT NULL DEFAULT 'fisso',
    tan DECIMAL(5,2) DEFAULT NULL,
    taeg DECIMAL(5,2) DEFAULT NULL,
    monthly_payment DECIMAL(10,2) DEFAULT NULL,
    profile_description VARCHAR(255) NOT NULL,
    source_name VARCHAR(150) NOT NULL,
    source_url VARCHAR(500) NOT NULL,
    observed_date DATE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO reference_mortgage_rates (bank_name, rate_type, tan, taeg, monthly_payment, profile_description, source_name, source_url, observed_date) VALUES
('Credem', 'fisso', 2.91, 3.29, 416.77, 'Prima casa, Milano, mutuo 100.000€ su immobile 200.000€, 30 anni, reddito 2.600€/mese', 'MutuiOnline.it', 'https://www.mutuionline.it/news/le-migliori-offerte-di-mutuo-prima-casa-a-luglio-2026-00047191.asp', '2026-07-03'),
('Credem', 'fisso', NULL, 3.11, 824.14, 'Prima casa, mutuo 198.000€ su immobile 250.000€, 30 anni, senza spese di istruttoria', 'OfferteShopping.it', 'https://www.offerteshopping.it/confronta-i-mutui-a-tasso-fisso-di-luglio-2026-scopri-le-proposte-piu-vantaggiose/', '2026-07-01'),
('Banco BPM', 'fisso', NULL, 3.11, 824.14, 'Immobile ad alta efficienza energetica, stesso profilo del confronto sopra', 'OfferteShopping.it', 'https://www.offerteshopping.it/confronta-i-mutui-a-tasso-fisso-di-luglio-2026-scopri-le-proposte-piu-vantaggiose/', '2026-07-01'),
('Media di mercato', 'fisso', 2.53, NULL, NULL, 'Media indicativa 2,40%-2,65%, LTV 80%, durata 20-25 anni', 'BusinessOnline.it', 'https://www.businessonline.it/news/mutui-a-tasso-variabile-o-fisso-a-luglio-2026-quanto-si-paga-offerte-migliori-e-consigli-analisti_n85593.html', '2026-07-01'),
('Media di mercato (immobili green)', 'fisso', 2.85, 3.00, NULL, 'Offerte riservate a immobili ad alta efficienza energetica (classe A/B)', 'MutuiSupermarket.it', 'https://www.mutuisupermarket.it/news-mutui/migliori-mutui-green-tasso-fisso-luglio-2026/', '2026-07-01');
