-- Migration 017: aggiunge capitale e durata alla tabella tassi mutuo, cosi'
-- la rata si calcola con l'ammortamento francese invece di essere copiata a
-- mano dall'articolo di riferimento.

ALTER TABLE reference_mortgage_rates ADD COLUMN loan_amount DECIMAL(12,2) DEFAULT NULL AFTER rate_type;
ALTER TABLE reference_mortgage_rates ADD COLUMN duration_years INT DEFAULT NULL AFTER loan_amount;
ALTER TABLE reference_mortgage_rates MODIFY profile_description VARCHAR(255) DEFAULT NULL;

UPDATE reference_mortgage_rates SET loan_amount = 100000, duration_years = 30 WHERE bank_name = 'Credem' AND observed_date = '2026-07-03';
UPDATE reference_mortgage_rates SET loan_amount = 198000, duration_years = 30 WHERE bank_name IN ('Credem', 'Banco BPM') AND observed_date = '2026-07-01';
