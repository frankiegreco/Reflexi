-- Migration 028: allinea updated_at a published_at per tutti gli articoli
-- News. Le migration 025/027 (redistribuzione date) avevano fatto scattare
-- il trigger ON UPDATE CURRENT_TIMESTAMP su ogni riga, appiattendo il
-- lastmod della sitemap sulla data dell'operazione invece che sulla data di
-- pubblicazione di ciascun articolo. Assegnando esplicitamente updated_at
-- si aggira il trigger (comportamento standard MySQL/MariaDB: un valore
-- passato esplicitamente nello SET vince sull'ON UPDATE automatico).
UPDATE news_articles SET updated_at = published_at;
