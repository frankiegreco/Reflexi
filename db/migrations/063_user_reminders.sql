-- Migration 063: promemoria per tornare a usare l'app, inviati dall'area admin
-- agli utenti registrati (singolo utente o tutti gli inattivi da N giorni).
-- Una riga per email inviata; reminders_enabled = 0 se l'utente li ha
-- disattivati dal Profilo (i promemoria non gli vengono più mandati).
ALTER TABLE users ADD COLUMN IF NOT EXISTS reminders_enabled TINYINT(1) NOT NULL DEFAULT 1;

CREATE TABLE IF NOT EXISTS user_reminders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    sent_by INT DEFAULT NULL,
    sent_at DATETIME NOT NULL,
    INDEX idx_user_reminders_user (user_id, sent_at),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (sent_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
