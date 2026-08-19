-- Migration 004: aggiunge la quota di proprietà ai mutui, per gestire mutui
-- cointestati dove solo una percentuale compete alla propria finanza personale.
-- Non tocca i dati esistenti (default 100% per i mutui già presenti).
-- Nota: se ricevi un errore "duplicate column", significa che l'hai già applicata.

ALTER TABLE loans ADD COLUMN ownership_pct DECIMAL(5,2) NOT NULL DEFAULT 100.00 AFTER duration_years;
