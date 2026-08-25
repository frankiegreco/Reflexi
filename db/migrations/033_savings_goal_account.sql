-- Migration 033: collegamento opzionale tra un obiettivo di risparmio e un
-- conto dedicato. Se l'obiettivo e' collegato a un conto, il progresso
-- diventa il saldo reale di quel conto (l'utente "accantona" i soldi
-- trasferendoli li' dentro) invece del calcolo automatico entrate-uscite
-- dalla data di inizio su tutti i conti insieme. NULL = comportamento
-- invariato per tutti gli obiettivi esistenti.

ALTER TABLE savings_goals
    ADD COLUMN account_id INT DEFAULT NULL AFTER target_amount,
    ADD FOREIGN KEY (account_id) REFERENCES accounts(id) ON DELETE SET NULL;
