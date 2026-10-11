-- Migration 043: log di sicurezza ed errori per la sezione "Admin · Log".
-- Una riga per evento (login fallito, rate limit, accesso admin negato,
-- errore server, ...), scritta da log_event() in db.php. Mai password o
-- token nel log. Righe oltre 90 giorni cancellate da log_event() stessa.

CREATE TABLE IF NOT EXISTS security_events (
    id INT AUTO_INCREMENT PRIMARY KEY,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    level ENUM('info','warning','error') NOT NULL,
    type VARCHAR(40) NOT NULL,
    user_id INT DEFAULT NULL,
    email VARCHAR(190) DEFAULT NULL,
    ip VARCHAR(45) DEFAULT NULL,
    user_agent VARCHAR(255) DEFAULT NULL,
    endpoint VARCHAR(255) DEFAULT NULL,
    message TEXT,
    INDEX idx_security_events_created (created_at),
    INDEX idx_security_events_type (type, created_at),
    INDEX idx_security_events_ip (ip, created_at),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
