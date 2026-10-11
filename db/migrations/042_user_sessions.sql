-- Migration 042: storico degli accessi per il dettaglio utente nell'area
-- admin. Una riga per login riuscito: inizio, ultima attività (aggiornata al
-- massimo una volta al minuto da touch_login_session() in db.php), fine al
-- logout, IP e user agent. Durata = (ended_at o last_seen_at) - started_at.
-- Le righe più vecchie di 180 giorni vengono cancellate al login successivo.

CREATE TABLE IF NOT EXISTS user_sessions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    started_at DATETIME NOT NULL,
    last_seen_at DATETIME NOT NULL,
    ended_at DATETIME DEFAULT NULL,
    ip VARCHAR(45) DEFAULT NULL,
    user_agent VARCHAR(255) DEFAULT NULL,
    INDEX idx_user_sessions_user (user_id, started_at),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
