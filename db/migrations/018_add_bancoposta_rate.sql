-- Migration 018: aggiunge un dato reale da fonte diretta (sito ufficiale
-- BancoPosta/Intesa Sanpaolo, non un aggregatore).

INSERT INTO reference_mortgage_rates (bank_name, rate_type, loan_amount, duration_years, tan, taeg, monthly_payment, profile_description, source_name, source_url, observed_date) VALUES
('Intesa Sanpaolo (BancoPosta)', 'fisso', 100000, 20, 3.96, 4.21, NULL, 'Prima/seconda casa, fino all''80% del valore immobile, con polizza incendio inclusa', 'Poste Italiane (BancoPosta)', 'https://www.poste.it/mutui-bancoposta/mutuo-acquisto-casa-bancoposta', CURDATE());
