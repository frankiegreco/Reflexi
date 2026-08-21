-- Migration 031: le operazioni di acquisto/vendita titoli generate
-- dall'import CSV (righe Buy/Sell/PAC di un broker) vanno tracciate sullo
-- stesso import_batches delle transazioni di cassa collegate, cosi'
-- annullare un import rimuove anche gli acquisti/vendite che ha creato,
-- non solo il movimento di cassa collegato.

ALTER TABLE investment_transactions
    ADD COLUMN import_batch_id INT DEFAULT NULL AFTER linked_transaction_id,
    ADD FOREIGN KEY (import_batch_id) REFERENCES import_batches(id) ON DELETE CASCADE,
    ADD INDEX idx_investment_import_batch (import_batch_id);
