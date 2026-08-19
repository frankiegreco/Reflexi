-- Migration 012: interessi sui conti reali (corrente, deposito, ecc.), non su
-- un'entita' separata. Quando l'interesse matura, diventa una vera transazione
-- che aumenta il saldo reale del conto — cosi' entra automaticamente nel
-- patrimonio netto, senza bisogno di gestirlo a parte.

ALTER TABLE accounts ADD COLUMN interest_enabled TINYINT(1) NOT NULL DEFAULT 0;
ALTER TABLE accounts ADD COLUMN interest_rate DECIMAL(5,3) DEFAULT NULL;
ALTER TABLE accounts ADD COLUMN interest_tax_rate DECIMAL(5,2) NOT NULL DEFAULT 26.00;
ALTER TABLE accounts ADD COLUMN interest_frequency ENUM('mensile','trimestrale','annuale') NOT NULL DEFAULT 'annuale';
ALTER TABLE accounts ADD COLUMN next_interest_date DATE DEFAULT NULL;
