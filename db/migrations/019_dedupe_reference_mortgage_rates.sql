-- Migration 019: rimuove i duplicati esatti creati dal doppio seed
-- (init.sql + migration 016 avevano inserito le stesse 5 righe due volte)
-- e aggiunge un vincolo di unicita' per impedire che si ripeta in futuro.

-- 1) Rimuove i duplicati: per ogni gruppo di righe identiche su
--    (bank_name, tan, taeg, source_name, observed_date) tiene solo quella
--    con id piu' basso (la prima inserita) e cancella le altre.
DELETE t1 FROM reference_mortgage_rates t1
JOIN reference_mortgage_rates t2
  ON t1.id > t2.id
  AND t1.bank_name = t2.bank_name
  AND t1.tan <=> t2.tan
  AND t1.taeg <=> t2.taeg
  AND t1.source_name = t2.source_name
  AND t1.observed_date = t2.observed_date;

-- 2) Vincolo di unicita': stessa banca + stesso tasso + stessa fonte +
--    stessa data di rilevazione non puo' comparire due volte.
--    Se in futuro serve reinserire un aggiornamento della stessa offerta,
--    basta cambiare observed_date (nuova rilevazione) oppure usare
--    INSERT ... ON DUPLICATE KEY UPDATE per aggiornare la riga esistente.
ALTER TABLE reference_mortgage_rates
ADD UNIQUE KEY uniq_offer (bank_name, tan, taeg, source_name, observed_date);
