-- Migration 045: title SEO separato per gli articoli News. Il titolo
-- completo (`title`) resta l'H1 della pagina e l'headline dei dati
-- strutturati; `seo_title` (max 60 caratteri, parola chiave in testa) va in
-- <title>, og:title e twitter:title, dove Google e i social troncano oltre
-- ~60 caratteri (prima 37 articoli su 42 venivano tagliati). Se NULL,
-- news-articolo.php usa `title`. updated_at viene preservato esplicitamente
-- (vedi migration 028): cambiare il title non è un aggiornamento del testo.

ALTER TABLE news_articles ADD COLUMN seo_title VARCHAR(70) DEFAULT NULL AFTER title;

UPDATE news_articles SET seo_title = 'Ammortamento alla francese: come si calcola la rata', updated_at = updated_at WHERE slug = 'ammortamento-alla-francese-guida-mutuo-rata-costante';
UPDATE news_articles SET seo_title = 'Cos''è un ETF: guida per chi inizia da zero', updated_at = updated_at WHERE slug = 'cosa-e-un-etf-guida-per-principianti';
UPDATE news_articles SET seo_title = 'Regola 50/30/20 o budget per categoria?', updated_at = updated_at WHERE slug = 'regola-50-30-20-vs-budget-per-categoria';
UPDATE news_articles SET seo_title = 'Patrimonio netto: cos''è e perché seguirlo', updated_at = updated_at WHERE slug = 'patrimonio-netto-cos-e-perche-seguirlo-nel-tempo';
UPDATE news_articles SET seo_title = 'Mutuo a tasso fisso o variabile: come scegliere', updated_at = updated_at WHERE slug = 'mutuo-tasso-fisso-o-variabile-come-scegliere';
UPDATE news_articles SET seo_title = 'PAC in ETF: cos''è e come funziona', updated_at = updated_at WHERE slug = 'pac-piano-accumulo-etf-come-funziona';
UPDATE news_articles SET seo_title = 'Obiettivi di risparmio: come fissarli e raggiungerli', updated_at = updated_at WHERE slug = 'obiettivi-risparmio-come-fissarli-e-raggiungerli';
UPDATE news_articles SET seo_title = 'Tasso di risparmio: cos''è e come calcolarlo', updated_at = updated_at WHERE slug = 'tasso-di-risparmio-cosa-e-come-calcolarlo';
UPDATE news_articles SET seo_title = 'LTV del mutuo: quanto puoi farti finanziare', updated_at = updated_at WHERE slug = 'loan-to-value-ltv-mutuo-quanto-puoi-finanziare';
UPDATE news_articles SET seo_title = 'Diversificazione del portafoglio: cosa significa', updated_at = updated_at WHERE slug = 'diversificazione-portafoglio-cosa-significa-davvero';
UPDATE news_articles SET seo_title = 'Fondo di emergenza: quanto tenere da parte', updated_at = updated_at WHERE slug = 'fondo-di-emergenza-quanto-tenere-da-parte';
UPDATE news_articles SET seo_title = 'Inflazione e potere d''acquisto: il costo nascosto', updated_at = updated_at WHERE slug = 'inflazione-potere-acquisto-perche-il-capitale-deve-crescere';
UPDATE news_articles SET seo_title = 'Spese accessorie del mutuo: quanto costa comprare casa', updated_at = updated_at WHERE slug = 'spese-notarili-accessorie-mutuo-costo-reale-acquisto-casa';
UPDATE news_articles SET seo_title = 'ETF ad accumulazione o a distribuzione: quale scegliere', updated_at = updated_at WHERE slug = 'etf-accumulazione-o-distribuzione-differenze';
UPDATE news_articles SET seo_title = 'Metodo delle buste: come applicarlo al budget', updated_at = updated_at WHERE slug = 'metodo-delle-buste-budget-digitale';
UPDATE news_articles SET seo_title = 'Rapporto debito/reddito (DTI): cos''è e perché conta', updated_at = updated_at WHERE slug = 'rapporto-debito-reddito-dti-cosa-e';
UPDATE news_articles SET seo_title = 'Mutuo under 36: la garanzia sulla prima casa', updated_at = updated_at WHERE slug = 'mutuo-giovani-under-36-garanzia-prima-casa';
UPDATE news_articles SET seo_title = 'TER degli ETF: quanto incidono i costi sul rendimento', updated_at = updated_at WHERE slug = 'ter-costi-etf-quanto-incidono-sul-rendimento';
UPDATE news_articles SET seo_title = 'Tracciare le spese a mano o collegare la banca?', updated_at = updated_at WHERE slug = 'tracciare-spese-manualmente-vs-collegamento-bancario';
UPDATE news_articles SET seo_title = 'Pensione integrativa: quanto serve e quando iniziare', updated_at = updated_at WHERE slug = 'pensione-integrativa-fondo-pensione-quanto-serve';
UPDATE news_articles SET seo_title = 'Estinzione anticipata del mutuo: quando conviene', updated_at = updated_at WHERE slug = 'estinzione-anticipata-mutuo-quando-conviene';
UPDATE news_articles SET seo_title = 'Tassazione di ETF, azioni e conto corrente in Italia', updated_at = updated_at WHERE slug = 'tassazione-investimenti-etf-azioni-italia';
UPDATE news_articles SET seo_title = 'Abbonamenti dimenticati: come scovare le spese ricorrenti', updated_at = updated_at WHERE slug = 'abbonamenti-dimenticati-come-scovare-spese-ricorrenti';
UPDATE news_articles SET seo_title = 'FIRE: cos''è e come calcolare il tuo numero', updated_at = updated_at WHERE slug = 'indipendenza-finanziaria-fire-come-calcolare-il-numero';
UPDATE news_articles SET seo_title = 'Surroga del mutuo: cos''è e come funziona', updated_at = updated_at WHERE slug = 'surroga-mutuo-cosa-e-come-funziona';
UPDATE news_articles SET seo_title = 'Interesse composto spiegato con esempi reali', updated_at = updated_at WHERE slug = 'interesse-composto-esempi-reali';
UPDATE news_articles SET seo_title = 'Finanze di coppia: conti separati o cointestati?', updated_at = updated_at WHERE slug = 'finanze-di-coppia-conti-separati-o-cointestati';
UPDATE news_articles SET seo_title = 'Rendimento totale o CAGR: come leggere la performance', updated_at = updated_at WHERE slug = 'rendimento-totale-vs-cagr-come-leggere-la-performance';
UPDATE news_articles SET seo_title = 'Recupero perdite: perché −20% richiede più di +20%', updated_at = updated_at WHERE slug = 'recupero-perdite-perche-non-e-simmetrico';
UPDATE news_articles SET seo_title = 'Ribilanciamento del portafoglio: cos''è e perché farlo', updated_at = updated_at WHERE slug = 'ribilanciamento-portafoglio-cosa-e-perche';
UPDATE news_articles SET seo_title = 'TAN e TAEG del mutuo: la differenza spiegata', updated_at = updated_at WHERE slug = 'tan-e-taeg-mutuo-differenza';
UPDATE news_articles SET seo_title = 'BTP e titoli di Stato: come funzionano e quanto rendono', updated_at = updated_at WHERE slug = 'btp-titoli-di-stato-come-funzionano';
UPDATE news_articles SET seo_title = 'Budget familiare mensile: come farlo in 5 passi', updated_at = updated_at WHERE slug = 'budget-familiare-mensile-come-farlo';
UPDATE news_articles SET seo_title = 'TFR in azienda o nel fondo pensione: come scegliere', updated_at = updated_at WHERE slug = 'tfr-in-azienda-o-fondo-pensione';
UPDATE news_articles SET seo_title = 'Euribor e IRS: cosa sono e come pesano sul mutuo', updated_at = updated_at WHERE slug = 'euribor-irs-mutuo-cosa-sono';
UPDATE news_articles SET seo_title = 'ETF monetari o conto deposito: cosa scegliere', updated_at = updated_at WHERE slug = 'etf-monetari-o-conto-deposito';
UPDATE news_articles SET seo_title = 'Spese fisse e variabili: differenze ed esempi', updated_at = updated_at WHERE slug = 'spese-fisse-e-variabili-differenza';
UPDATE news_articles SET seo_title = 'Come gestire i soldi a 20, 30, 40 e 50 anni', updated_at = updated_at WHERE slug = 'gestire-i-soldi-a-20-30-40-50-anni';
UPDATE news_articles SET seo_title = 'Detrazione interessi mutuo prima casa: come funziona', updated_at = updated_at WHERE slug = 'detrazione-interessi-mutuo-prima-casa';
UPDATE news_articles SET seo_title = 'PAC o PIC: investire tutto subito o a rate?', updated_at = updated_at WHERE slug = 'pac-o-pic-investire-tutto-subito-o-a-rate';
UPDATE news_articles SET seo_title = 'Acquisti d''impulso: 7 strategie per evitarli', updated_at = updated_at WHERE slug = 'acquisti-impulsivi-come-evitarli';
UPDATE news_articles SET seo_title = 'Arriva un figlio: come pianificare le spese', updated_at = updated_at WHERE slug = 'arrivo-di-un-figlio-pianificare-le-spese';
