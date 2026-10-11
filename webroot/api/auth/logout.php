<?php
declare(strict_types=1);
require __DIR__ . '/../db.php';

if (!empty($_SESSION['login_session_id']) && !empty($_SESSION['user_id'])) {
    db()->prepare("UPDATE user_sessions SET ended_at = NOW(), last_seen_at = NOW()
        WHERE id = :id AND user_id = :uid AND ended_at IS NULL")
        ->execute(['id' => $_SESSION['login_session_id'], 'uid' => $_SESSION['user_id']]);
}

session_reopen();
$_SESSION = [];
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
}
session_destroy();

respond(['ok' => true]);
