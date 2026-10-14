-- Migration 062: dati raccolti dal modulo di registrazione.
-- Nome e cognome obbligatori (users.name resta valorizzato con "Nome Cognome",
-- è quello che leggono email, Profilo e area admin); telefono e livello di
-- esperienza in finanza facoltativi. Gli account esistenti restano a NULL.
ALTER TABLE users
    ADD COLUMN IF NOT EXISTS first_name VARCHAR(75) DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS last_name VARCHAR(75) DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS phone VARCHAR(30) DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS finance_experience ENUM('principiante','intermedio','avanzato') DEFAULT NULL;
