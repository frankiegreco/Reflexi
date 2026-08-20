-- Migration 027: ridistribuisce le date di pubblicazione di tutti i 30
-- articoli News da luglio a oggi (seconda redistribuzione, dopo l'aggiunta
-- dei 6 articoli di 026_seed_news_articles_batch4.sql), sempre con
-- categorie mescolate e non raggruppate. Sostituisce integralmente la
-- distribuzione di 025_reschedule_news_published_dates.sql.

UPDATE news_articles SET published_at = '2026-07-01 09:00:00' WHERE slug = 'ammortamento-alla-francese-guida-mutuo-rata-costante';
UPDATE news_articles SET published_at = '2026-07-03 09:00:00' WHERE slug = 'cosa-e-un-etf-guida-per-principianti';
UPDATE news_articles SET published_at = '2026-07-04 09:00:00' WHERE slug = 'regola-50-30-20-vs-budget-per-categoria';
UPDATE news_articles SET published_at = '2026-07-06 09:00:00' WHERE slug = 'patrimonio-netto-cos-e-perche-seguirlo-nel-tempo';
UPDATE news_articles SET published_at = '2026-07-08 09:00:00' WHERE slug = 'mutuo-tasso-fisso-o-variabile-come-scegliere';
UPDATE news_articles SET published_at = '2026-07-10 09:00:00' WHERE slug = 'pac-piano-accumulo-etf-come-funziona';
UPDATE news_articles SET published_at = '2026-07-11 09:00:00' WHERE slug = 'obiettivi-risparmio-come-fissarli-e-raggiungerli';
UPDATE news_articles SET published_at = '2026-07-13 09:00:00' WHERE slug = 'tasso-di-risparmio-cosa-e-come-calcolarlo';
UPDATE news_articles SET published_at = '2026-07-15 09:00:00' WHERE slug = 'loan-to-value-ltv-mutuo-quanto-puoi-finanziare';
UPDATE news_articles SET published_at = '2026-07-17 09:00:00' WHERE slug = 'diversificazione-portafoglio-cosa-significa-davvero';
UPDATE news_articles SET published_at = '2026-07-18 09:00:00' WHERE slug = 'fondo-di-emergenza-quanto-tenere-da-parte';
UPDATE news_articles SET published_at = '2026-07-20 09:00:00' WHERE slug = 'inflazione-potere-acquisto-perche-il-capitale-deve-crescere';
UPDATE news_articles SET published_at = '2026-07-22 09:00:00' WHERE slug = 'spese-notarili-accessorie-mutuo-costo-reale-acquisto-casa';
UPDATE news_articles SET published_at = '2026-07-23 09:00:00' WHERE slug = 'etf-accumulazione-o-distribuzione-differenze';
UPDATE news_articles SET published_at = '2026-07-25 09:00:00' WHERE slug = 'metodo-delle-buste-budget-digitale';
UPDATE news_articles SET published_at = '2026-07-27 09:00:00' WHERE slug = 'rapporto-debito-reddito-dti-cosa-e';
UPDATE news_articles SET published_at = '2026-07-29 09:00:00' WHERE slug = 'mutuo-giovani-under-36-garanzia-prima-casa';
UPDATE news_articles SET published_at = '2026-07-30 09:00:00' WHERE slug = 'ter-costi-etf-quanto-incidono-sul-rendimento';
UPDATE news_articles SET published_at = '2026-08-01 09:00:00' WHERE slug = 'tracciare-spese-manualmente-vs-collegamento-bancario';
UPDATE news_articles SET published_at = '2026-08-03 09:00:00' WHERE slug = 'pensione-integrativa-fondo-pensione-quanto-serve';
UPDATE news_articles SET published_at = '2026-08-04 09:00:00' WHERE slug = 'estinzione-anticipata-mutuo-quando-conviene';
UPDATE news_articles SET published_at = '2026-08-06 09:00:00' WHERE slug = 'tassazione-investimenti-etf-azioni-italia';
UPDATE news_articles SET published_at = '2026-08-08 09:00:00' WHERE slug = 'abbonamenti-dimenticati-come-scovare-spese-ricorrenti';
UPDATE news_articles SET published_at = '2026-08-10 09:00:00' WHERE slug = 'indipendenza-finanziaria-fire-come-calcolare-il-numero';
UPDATE news_articles SET published_at = '2026-08-11 09:00:00' WHERE slug = 'surroga-mutuo-cosa-e-come-funziona';
UPDATE news_articles SET published_at = '2026-08-13 09:00:00' WHERE slug = 'interesse-composto-esempi-reali';
UPDATE news_articles SET published_at = '2026-08-15 09:00:00' WHERE slug = 'finanze-di-coppia-conti-separati-o-cointestati';
UPDATE news_articles SET published_at = '2026-08-17 09:00:00' WHERE slug = 'rendimento-totale-vs-cagr-come-leggere-la-performance';
UPDATE news_articles SET published_at = '2026-08-18 09:00:00' WHERE slug = 'recupero-perdite-perche-non-e-simmetrico';
UPDATE news_articles SET published_at = '2026-08-20 09:00:00' WHERE slug = 'ribilanciamento-portafoglio-cosa-e-perche';
