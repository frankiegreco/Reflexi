-- Migration 036: il modello di previsione era un'unica regressione allenata
-- su TUTTI i ticker insieme (azioni, ETF azionari, ETF obbligazionari,
-- crypto). Verificato dopo aver ampliato l'universo di training: un
-- modello pooled estrapola malissimo fuori dal range su cui ha imparato —
-- prevedeva -60/-74% per ETF obbligazionari a bassa volatilità/drawdown,
-- semplicemente perché il grosso dei dati di training erano azioni/crypto
-- molto più volatili. Non un bug di calcolo, un limite del modello.
--
-- Soluzione: un modello separato per asset class (equity/bond/crypto),
-- ognuno con le proprie feature/coefficienti/R^2. `asset_class` diventa
-- parte della chiave logica di lettura (vedi latest_forecast_model in
-- lib_forecast_model.php) insieme a trained_at.

ALTER TABLE forecast_model
    ADD COLUMN asset_class VARCHAR(10) NOT NULL DEFAULT 'equity' AFTER trained_at,
    ADD INDEX idx_asset_class_trained_at (asset_class, trained_at);
