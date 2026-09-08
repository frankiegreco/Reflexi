-- Migration 038: range di rendimenti realmente osservati nel training del
-- modello di previsione. La regressione OLS non ha nessun limite naturale
-- sull'output: per feature estreme mai viste (es. volatilita' annualizzata
-- altissima su un titolo con oscillazioni fuori norma) puo' estrapolare
-- ben oltre qualunque rendimento a 12 mesi mai realmente accaduto nello
-- storico usato per allenarla (caso concreto: INTC con volatilita' 78.94%
-- ha prodotto una previsione di +116% a 12 mesi). Salva il min/max delle
-- label (rendimenti a 365gg REALMENTE realizzati nel training) per asset
-- class, cosi' l'inferenza puo' tagliare la previsione a quell'intervallo
-- invece di promettere un rendimento piu' estremo di uno mai osservato
-- davvero.

ALTER TABLE forecast_model
    ADD COLUMN label_min_pct DECIMAL(10,2) NULL AFTER coef_drawdown,
    ADD COLUMN label_max_pct DECIMAL(10,2) NULL AFTER label_min_pct;
