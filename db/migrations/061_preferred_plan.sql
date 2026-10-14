-- Migration 061: piano scelto sulla pagina Piani e prezzi prima di registrarsi
-- (/app/?mode=register&plan=...). Solo una preferenza: durante la prova e nel
-- paywall l'app mette in evidenza quel piano, non attiva nulla da sola.
ALTER TABLE users ADD COLUMN IF NOT EXISTS preferred_plan ENUM('mensile','annuale') DEFAULT NULL;
