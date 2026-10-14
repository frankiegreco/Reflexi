-- go_live_reset.sql — reset dei dati Stripe di TEST al passaggio a Stripe LIVE.
--
-- DA ESEGUIRE UNA SOLA VOLTA:
--   - DOPO il backup del database (mariadb-dump, vedi GO_LIVE_STRIPE.md);
--   - DOPO aver messo le chiavi live nel .env e riavviato il container web;
--   - PRIMA del primo checkout live.
-- NON è una migrazione (per questo non sta in db/migrations/): una nuova
-- installazione non deve mai eseguirlo.
--
-- Perché serve: gli ID cus_... / sub_... creati in modalità test non esistono
-- in live e il checkout live fallirebbe su quei customer. Gli ID di test e di
-- live hanno lo STESSO prefisso, quindi lo script NON può distinguerli: azzera
-- TUTTI gli utenti con stripe_customer_id valorizzato. Se qualcuno avesse già
-- un abbonamento live, verrebbe azzerato anche lui: eseguirlo solo prima del
-- primo checkout live.
--
-- Gli utenti azzerati tornano in prova: trial_ends_at = iscrizione + 180 giorni.
-- Chi si è iscritto più di 180 giorni fa ha quindi la prova GIÀ SCADUTA e vedrà
-- il Paywall al primo accesso (controllare con la SELECT qui sotto prima).
--
-- Uso:
--   docker exec -i finanze_db mariadb -u root -p"$DB_ROOT_PASSWORD" finanze < db/scripts/go_live_reset.sql

-- Anteprima: chi verrà toccato e con quale nuova scadenza della prova.
SELECT id, email, subscription_status, created_at,
       DATE_ADD(created_at, INTERVAL 180 DAY) AS nuovo_trial_ends_at,
       DATE_ADD(created_at, INTERVAL 180 DAY) < NOW() AS prova_gia_scaduta
FROM users
WHERE stripe_customer_id IS NOT NULL;

START TRANSACTION;

-- past_due_since esiste solo dalla migrazione 059: azzerato solo se c'è.
-- Va fatto prima dell'UPDATE principale, che svuota stripe_customer_id.
SET @has_past_due_since := (
    SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = 'past_due_since'
);
SET @sql := IF(@has_past_due_since > 0,
    'UPDATE users SET past_due_since = NULL WHERE stripe_customer_id IS NOT NULL',
    'DO 0');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

UPDATE users
SET stripe_customer_id = NULL,
    stripe_subscription_id = NULL,
    subscription_current_period_end = NULL,
    subscription_status = 'trialing',
    trial_ends_at = DATE_ADD(created_at, INTERVAL 180 DAY)
WHERE stripe_customer_id IS NOT NULL;

SELECT ROW_COUNT() AS utenti_azzerati;

COMMIT;
