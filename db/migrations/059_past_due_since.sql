-- Migration 059: inizio del ritardo di pagamento (stato past_due).
-- has_active_access() concede al massimo 7 giorni di accesso da past_due_since;
-- lo valorizza il webhook Stripe al passaggio in past_due e lo azzera all'uscita.
ALTER TABLE users ADD COLUMN past_due_since DATETIME DEFAULT NULL AFTER subscription_current_period_end;

-- Chi è già in past_due non ha una data d'inizio: i 7 giorni partono da ora.
UPDATE users SET past_due_since = NOW() WHERE subscription_status = 'past_due' AND past_due_since IS NULL;
