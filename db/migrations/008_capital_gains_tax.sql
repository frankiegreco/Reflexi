-- Migration 008: tassazione delle plusvalenze sulle vendite di investimenti.
-- Aliquota configurabile per strumento (26% di default, es. titoli di stato al 12.5%),
-- imposta calcolata e trattenuta ad ogni vendita cosi' il movimento di cassa
-- collegato riflette il netto realmente accreditato, non il lordo.

ALTER TABLE instruments ADD COLUMN tax_rate DECIMAL(5,2) NOT NULL DEFAULT 26.00;
ALTER TABLE investment_transactions ADD COLUMN tax_amount DECIMAL(10,2) NOT NULL DEFAULT 0;
