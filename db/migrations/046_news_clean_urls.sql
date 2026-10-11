-- Migration 046: link interni degli articoli News verso gli URL senza
-- estensione (/mutui invece di /mutui.php), introdotti con le regole di
-- rewrite in webroot/.htaccess. I vecchi URL continuano a funzionare con un
-- 301, ma un link diretto evita il salto. updated_at preservato: non è un
-- aggiornamento del contenuto (vedi migration 028).

UPDATE news_articles SET body = REPLACE(body, 'href="/abbonamenti.php"', 'href="/abbonamenti"'), updated_at = updated_at WHERE body LIKE '%href="/abbonamenti.php"%';
UPDATE news_articles SET body = REPLACE(body, 'href="/about.php"', 'href="/about"'), updated_at = updated_at WHERE body LIKE '%href="/about.php"%';
UPDATE news_articles SET body = REPLACE(body, 'href="/analisi.php"', 'href="/analisi"'), updated_at = updated_at WHERE body LIKE '%href="/analisi.php"%';
UPDATE news_articles SET body = REPLACE(body, 'href="/budget.php"', 'href="/budget"'), updated_at = updated_at WHERE body LIKE '%href="/budget.php"%';
UPDATE news_articles SET body = REPLACE(body, 'href="/calcolatore-budget-503020.php"', 'href="/calcolatore-budget-503020"'), updated_at = updated_at WHERE body LIKE '%href="/calcolatore-budget-503020.php"%';
UPDATE news_articles SET body = REPLACE(body, 'href="/calcolatore-inflazione.php"', 'href="/calcolatore-inflazione"'), updated_at = updated_at WHERE body LIKE '%href="/calcolatore-inflazione.php"%';
UPDATE news_articles SET body = REPLACE(body, 'href="/calcolatore-interesse-composto.php"', 'href="/calcolatore-interesse-composto"'), updated_at = updated_at WHERE body LIKE '%href="/calcolatore-interesse-composto.php"%';
UPDATE news_articles SET body = REPLACE(body, 'href="/calcolatore-obiettivo-risparmio.php"', 'href="/calcolatore-obiettivo-risparmio"'), updated_at = updated_at WHERE body LIKE '%href="/calcolatore-obiettivo-risparmio.php"%';
UPDATE news_articles SET body = REPLACE(body, 'href="/calcolatore-patrimonio-netto.php"', 'href="/calcolatore-patrimonio-netto"'), updated_at = updated_at WHERE body LIKE '%href="/calcolatore-patrimonio-netto.php"%';
UPDATE news_articles SET body = REPLACE(body, 'href="/calcolatore-pensione.php"', 'href="/calcolatore-pensione"'), updated_at = updated_at WHERE body LIKE '%href="/calcolatore-pensione.php"%';
UPDATE news_articles SET body = REPLACE(body, 'href="/calcolatore-piano-investimento.php"', 'href="/calcolatore-piano-investimento"'), updated_at = updated_at WHERE body LIKE '%href="/calcolatore-piano-investimento.php"%';
UPDATE news_articles SET body = REPLACE(body, 'href="/calcolatore-recupero-perdite.php"', 'href="/calcolatore-recupero-perdite"'), updated_at = updated_at WHERE body LIKE '%href="/calcolatore-recupero-perdite.php"%';
UPDATE news_articles SET body = REPLACE(body, 'href="/calcolatore-rendimento.php"', 'href="/calcolatore-rendimento"'), updated_at = updated_at WHERE body LIKE '%href="/calcolatore-rendimento.php"%';
UPDATE news_articles SET body = REPLACE(body, 'href="/calcolatori.php"', 'href="/calcolatori"'), updated_at = updated_at WHERE body LIKE '%href="/calcolatori.php"%';
UPDATE news_articles SET body = REPLACE(body, 'href="/cancella-account.php"', 'href="/cancella-account"'), updated_at = updated_at WHERE body LIKE '%href="/cancella-account.php"%';
UPDATE news_articles SET body = REPLACE(body, 'href="/faq.php"', 'href="/faq"'), updated_at = updated_at WHERE body LIKE '%href="/faq.php"%';
UPDATE news_articles SET body = REPLACE(body, 'href="/fire-pensione.php"', 'href="/fire-pensione"'), updated_at = updated_at WHERE body LIKE '%href="/fire-pensione.php"%';
UPDATE news_articles SET body = REPLACE(body, 'href="/investimenti.php"', 'href="/investimenti"'), updated_at = updated_at WHERE body LIKE '%href="/investimenti.php"%';
UPDATE news_articles SET body = REPLACE(body, 'href="/mercati.php"', 'href="/mercati"'), updated_at = updated_at WHERE body LIKE '%href="/mercati.php"%';
UPDATE news_articles SET body = REPLACE(body, 'href="/mutui.php"', 'href="/mutui"'), updated_at = updated_at WHERE body LIKE '%href="/mutui.php"%';
UPDATE news_articles SET body = REPLACE(body, 'href="/obiettivi.php"', 'href="/obiettivi"'), updated_at = updated_at WHERE body LIKE '%href="/obiettivi.php"%';
UPDATE news_articles SET body = REPLACE(body, 'href="/patrimonio.php"', 'href="/patrimonio"'), updated_at = updated_at WHERE body LIKE '%href="/patrimonio.php"%';
UPDATE news_articles SET body = REPLACE(body, 'href="/piani.php"', 'href="/piani"'), updated_at = updated_at WHERE body LIKE '%href="/piani.php"%';
UPDATE news_articles SET body = REPLACE(body, 'href="/previsioni.php"', 'href="/previsioni"'), updated_at = updated_at WHERE body LIKE '%href="/previsioni.php"%';
UPDATE news_articles SET body = REPLACE(body, 'href="/privacy.php"', 'href="/privacy"'), updated_at = updated_at WHERE body LIKE '%href="/privacy.php"%';
UPDATE news_articles SET body = REPLACE(body, 'href="/simulazione-trading.php"', 'href="/simulazione-trading"'), updated_at = updated_at WHERE body LIKE '%href="/simulazione-trading.php"%';
UPDATE news_articles SET body = REPLACE(body, 'href="/termini.php"', 'href="/termini"'), updated_at = updated_at WHERE body LIKE '%href="/termini.php"%';
