-- Migration 060: annullamento programmato dell'abbonamento.
-- Data in cui l'abbonamento Stripe si chiuderà (cancel_at, o fine periodo con
-- cancel_at_period_end); NULL se non è programmato nessun annullamento.
-- La valorizza il webhook a ogni customer.subscription.updated: per chi ha già
-- annullato prima di questa migrazione arriva al prossimo evento Stripe.
ALTER TABLE users ADD COLUMN IF NOT EXISTS subscription_cancel_at DATETIME DEFAULT NULL AFTER past_due_since;
