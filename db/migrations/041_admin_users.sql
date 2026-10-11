-- Migration 041: flag admin per l'area di amministrazione (elenco utenti
-- registrati). Il flag si assegna solo via SQL, mai da API: nessun endpoint
-- permette di diventare admin.

ALTER TABLE users
    ADD COLUMN is_admin TINYINT(1) NOT NULL DEFAULT 0 AFTER password_hash;

-- Il primo admin si assegna a mano, ad esempio:
-- UPDATE users SET is_admin = 1 WHERE email = 'admin@example.com';
