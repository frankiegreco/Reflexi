-- Migration 015: valuta del conto (conti in valuta estera, es. CHF, USD).
-- Il saldo resta nella valuta nativa del conto; la conversione in EUR per
-- patrimonio netto e riepiloghi avviene al volo tramite il cambio Yahoo
-- Finance (stesso meccanismo gia' usato per le quotazioni degli investimenti).

ALTER TABLE accounts ADD COLUMN currency VARCHAR(3) NOT NULL DEFAULT 'EUR';
