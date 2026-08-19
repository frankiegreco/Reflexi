-- Migration 019: estende il trial a 180 giorni (6 mesi) per chi e' attualmente
-- in trial, calcolato dalla loro data di iscrizione originale (non da oggi).

UPDATE users
SET trial_ends_at = DATE_ADD(created_at, INTERVAL 180 DAY)
WHERE subscription_status = 'trialing';
