-- Migration 050: correzioni all'articolo estinzione-anticipata-mutuo-quando-conviene.
-- 1) Il link "calcolatore di rendimento" puntava a /calcolatore-recupero-perdite
--    invece che a /calcolatore-rendimento.
-- 2) Collegamento al nuovo articolo sul rientro anticipato (migration 049).
-- Correzione del contenuto: updated_at si aggiorna di proposito.

UPDATE news_articles SET body = REPLACE(body,
    '<a href="/calcolatore-recupero-perdite" class="text-accent hover:underline font-medium">calcolatore di rendimento</a>',
    '<a href="/calcolatore-rendimento" class="text-accent hover:underline font-medium">calcolatore di rendimento</a>'),
    updated_at = NOW()
WHERE slug = 'estinzione-anticipata-mutuo-quando-conviene'
  AND body LIKE '%href="/calcolatore-recupero-perdite" class="text-accent hover:underline font-medium">calcolatore di rendimento</a>%';

UPDATE news_articles SET body = REPLACE(body,
    'dipende da un confronto preciso che spesso non viene fatto.</p>',
    'dipende da un confronto preciso che spesso non viene fatto. E se pensi a un versamento parziale, c''è anche da scegliere se usarlo per accorciare la durata o abbassare la rata: lo vediamo con i numeri nella guida al <a href="/news/rientro-anticipato-mutuo-ridurre-durata-o-rata" class="text-accent hover:underline font-medium">rientro anticipato del mutuo</a>.</p>'),
    updated_at = NOW()
WHERE slug = 'estinzione-anticipata-mutuo-quando-conviene'
  AND body NOT LIKE '%/news/rientro-anticipato-mutuo-ridurre-durata-o-rata%';
