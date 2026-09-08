-- Migration 037: validazione out-of-sample per il modello di previsione.
-- Finora l'unico R^2 salvato era misurato sullo stesso set di dati usato
-- per addestrare il modello (in-sample) — non dice nulla sulla vera
-- capacita' predittiva, solo su quanto il modello si adatta al passato che
-- gia' conosce. Aggiunge un secondo R^2 misurato su un holdout temporale
-- (l'ultimo 20% cronologico dei campioni di ogni ticker, non usato per
-- stimare i coefficienti) — puo' essere negativo, a differenza dell'R^2
-- in-sample che con l'intercetta e' sempre >= 0 sul proprio training set.

ALTER TABLE forecast_model
    ADD COLUMN n_samples_train INT NULL AFTER n_samples,
    ADD COLUMN n_samples_test INT NULL AFTER n_samples_train,
    ADD COLUMN r_squared_oos DECIMAL(7,4) NULL AFTER r_squared;
