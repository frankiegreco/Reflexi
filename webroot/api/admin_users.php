<?php
declare(strict_types=1);
require __DIR__ . '/db.php';

// Area admin: elenco utenti registrati con i soli dati anagrafici e di
// account. Di proposito NON legge nessuna tabella dati (conti, transazioni,
// investimenti, ...): i dati finanziari degli utenti restano privati anche
// per l'admin. Niente token, hash password o ID Stripe in uscita.

require_admin();
if (method() !== 'GET') respond_error('Metodo non supportato', 405);

$rows = db()->query("
    SELECT id, email, name, is_admin, email_verified_at, created_at,
           subscription_status, trial_ends_at, subscription_current_period_end,
           digest_frequency
    FROM users
    ORDER BY created_at DESC, id DESC
")->fetchAll();

$users = array_map(fn($u) => [
    'id' => (int)$u['id'],
    'email' => $u['email'],
    'name' => $u['name'],
    'is_admin' => (int)$u['is_admin'] === 1,
    'email_verified' => $u['email_verified_at'] !== null,
    'created_at' => $u['created_at'],
    'subscription_status' => $u['subscription_status'],
    'trial_ends_at' => $u['trial_ends_at'],
    'subscription_current_period_end' => $u['subscription_current_period_end'],
    'digest_frequency' => $u['digest_frequency'],
], $rows);

$since30 = strtotime('-30 days');
respond([
    'users' => $users,
    'summary' => [
        'total' => count($users),
        'verified' => count(array_filter($users, fn($u) => $u['email_verified'])),
        'active' => count(array_filter($users, fn($u) => $u['subscription_status'] === 'active')),
        'trialing' => count(array_filter($users, fn($u) => $u['subscription_status'] === 'trialing')),
        'new_30d' => count(array_filter($users, fn($u) => strtotime($u['created_at']) >= $since30)),
    ],
]);
