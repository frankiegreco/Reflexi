-- Migration 065: conteggio anonimo dei download della guida PDF
-- (api/guide_pdf.php), mostrato nell'area admin. Solo un totale per giorno:
-- nessun legame con l'utente che scarica. I download degli admin non contano.
CREATE TABLE IF NOT EXISTS guide_downloads_daily (
    day DATE PRIMARY KEY,
    downloads INT UNSIGNED NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
