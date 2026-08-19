-- Migration 010: abbonamento Stripe (trial + stato abbonamento).

ALTER TABLE users ADD COLUMN subscription_status ENUM('trialing','active','past_due','canceled','none') NOT NULL DEFAULT 'none';
ALTER TABLE users ADD COLUMN trial_ends_at DATETIME DEFAULT NULL;
ALTER TABLE users ADD COLUMN stripe_customer_id VARCHAR(64) DEFAULT NULL;
ALTER TABLE users ADD COLUMN stripe_subscription_id VARCHAR(64) DEFAULT NULL;
ALTER TABLE users ADD COLUMN subscription_current_period_end DATETIME DEFAULT NULL;
