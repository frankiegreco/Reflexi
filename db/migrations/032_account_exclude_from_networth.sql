-- Migration 032: possibilita' di escludere un conto dal calcolo del
-- patrimonio netto (es. un conto che non e' davvero "tuo", o che tieni
-- traccia separatamente per altri motivi) senza doverlo archiviare — il
-- conto resta pienamente visibile e utilizzabile, solo il suo saldo non
-- entra nella somma di liquidita' usata da Riepilogo/FIRE/Report.

ALTER TABLE accounts
    ADD COLUMN exclude_from_networth TINYINT(1) NOT NULL DEFAULT 0 AFTER archived;
