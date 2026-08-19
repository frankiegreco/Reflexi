-- Migration 006: rate limiting su login/registrazione/reset password.
-- Traccia i tentativi per IP, cosi' si puo' bloccare temporaneamente chi ne fa troppi
-- in poco tempo (protezione base da brute force e abuso della registrazione/email).

CREATE TABLE IF NOT EXISTS rate_limit_attempts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    action VARCHAR(50) NOT NULL,
    identifier VARCHAR(190) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_action_identifier_time (action, identifier, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
