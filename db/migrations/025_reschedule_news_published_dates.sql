-- Migration 025: ridistribuisce le date di pubblicazione di tutti i 24
-- articoli News da luglio a oggi, con categorie mescolate invece che
-- raggruppate, cosi' la timeline sembra un piano editoriale continuo
-- invece di due infornate distinte (022/023 il 5-19/8, 024 tutta il 20/8).

UPDATE news_articles SET published_at = '2026-07-01 09:00:00' WHERE slug = 'ammortamento-alla-francese-guida-mutuo-rata-costante';
UPDATE news_articles SET published_at = '2026-07-03 09:00:00' WHERE slug = 'pac-piano-accumulo-etf-come-funziona';
UPDATE news_articles SET published_at = '2026-07-05 09:00:00' WHERE slug = 'regola-50-30-20-vs-budget-per-categoria';
UPDATE news_articles SET published_at = '2026-07-08 09:00:00' WHERE slug = 'indipendenza-finanziaria-fire-come-calcolare-il-numero';
UPDATE news_articles SET published_at = '2026-07-10 09:00:00' WHERE slug = 'estinzione-anticipata-mutuo-quando-conviene';
UPDATE news_articles SET published_at = '2026-07-12 09:00:00' WHERE slug = 'interesse-composto-esempi-reali';
UPDATE news_articles SET published_at = '2026-07-14 09:00:00' WHERE slug = 'fondo-di-emergenza-quanto-tenere-da-parte';
UPDATE news_articles SET published_at = '2026-07-16 09:00:00' WHERE slug = 'inflazione-potere-acquisto-perche-il-capitale-deve-crescere';
UPDATE news_articles SET published_at = '2026-07-18 09:00:00' WHERE slug = 'mutuo-tasso-fisso-o-variabile-come-scegliere';
UPDATE news_articles SET published_at = '2026-07-21 09:00:00' WHERE slug = 'rendimento-totale-vs-cagr-come-leggere-la-performance';
UPDATE news_articles SET published_at = '2026-07-23 09:00:00' WHERE slug = 'abbonamenti-dimenticati-come-scovare-spese-ricorrenti';
UPDATE news_articles SET published_at = '2026-07-25 09:00:00' WHERE slug = 'patrimonio-netto-cos-e-perche-seguirlo-nel-tempo';
UPDATE news_articles SET published_at = '2026-07-27 09:00:00' WHERE slug = 'mutuo-giovani-under-36-garanzia-prima-casa';
UPDATE news_articles SET published_at = '2026-07-29 09:00:00' WHERE slug = 'recupero-perdite-perche-non-e-simmetrico';
UPDATE news_articles SET published_at = '2026-07-31 09:00:00' WHERE slug = 'tracciare-spese-manualmente-vs-collegamento-bancario';
UPDATE news_articles SET published_at = '2026-08-03 09:00:00' WHERE slug = 'pensione-integrativa-fondo-pensione-quanto-serve';
UPDATE news_articles SET published_at = '2026-08-05 09:00:00' WHERE slug = 'surroga-mutuo-cosa-e-come-funziona';
UPDATE news_articles SET published_at = '2026-08-07 09:00:00' WHERE slug = 'diversificazione-portafoglio-cosa-significa-davvero';
UPDATE news_articles SET published_at = '2026-08-09 09:00:00' WHERE slug = 'metodo-delle-buste-budget-digitale';
UPDATE news_articles SET published_at = '2026-08-11 09:00:00' WHERE slug = 'rapporto-debito-reddito-dti-cosa-e';
UPDATE news_articles SET published_at = '2026-08-13 09:00:00' WHERE slug = 'etf-accumulazione-o-distribuzione-differenze';
UPDATE news_articles SET published_at = '2026-08-16 09:00:00' WHERE slug = 'finanze-di-coppia-conti-separati-o-cointestati';
UPDATE news_articles SET published_at = '2026-08-18 09:00:00' WHERE slug = 'tassazione-investimenti-etf-azioni-italia';
UPDATE news_articles SET published_at = '2026-08-20 09:00:00' WHERE slug = 'ribilanciamento-portafoglio-cosa-e-perche';
