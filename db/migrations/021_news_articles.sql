-- Migration 021: sezione News/blog pubblica del sito.
-- Contenuto gestito come dato (via Adminer), non come file di codice: ogni
-- nuovo articolo entra automaticamente in sitemap, feed e "articoli
-- correlati" senza bisogno di deploy. `body` è HTML scritto a mano (stesso
-- approccio del resto del sito pubblico, nessun parser Markdown), inserito
-- solo da chi amministra il sito: nessun rischio XSS da input esterno.
CREATE TABLE IF NOT EXISTS news_articles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    slug VARCHAR(160) NOT NULL UNIQUE,
    title VARCHAR(200) NOT NULL,
    excerpt VARCHAR(300) NOT NULL,
    meta_description VARCHAR(160) NOT NULL,
    category VARCHAR(40) NOT NULL,
    cover_emoji VARCHAR(8) NOT NULL DEFAULT '📰',
    body MEDIUMTEXT NOT NULL,
    status ENUM('bozza','pubblicato') NOT NULL DEFAULT 'bozza',
    published_at DATETIME NULL,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_status_published (status, published_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
