<?php
declare(strict_types=1);

// Dati anagrafici dell'utente (migrazione 062): validazione condivisa tra
// registrazione (auth/register.php) e modifica dal Profilo (auth/me.php PUT).
// Le funzioni rispondono con 400 e interrompono la richiesta se un dato non va.

const FINANCE_EXPERIENCE_LEVELS = ['principiante', 'intermedio', 'avanzato'];

/** Nome e cognome obbligatori; ritorna [nome, cognome] ripuliti dagli spazi. */
function profile_names(mixed $first, mixed $last): array
{
    $first = trim((string)($first ?? ''));
    $last = trim((string)($last ?? ''));
    if ($first === '' || $last === '') respond_error('Inserisci nome e cognome');
    if (mb_strlen($first) > 75 || mb_strlen($last) > 75) respond_error('Nome e cognome possono avere al massimo 75 caratteri');
    return [$first, $last];
}

/** Telefono facoltativo: cifre con eventuale + iniziale e separatori comuni, 6-15 cifre. NULL se vuoto. */
function profile_phone(mixed $phone): ?string
{
    $phone = trim((string)($phone ?? ''));
    if ($phone === '') return null;
    $digits = strlen(preg_replace('/\D/', '', $phone));
    if (!preg_match('/^\+?[0-9 .\-\/()]{6,25}$/', $phone) || $digits < 6 || $digits > 15) {
        respond_error('Numero di telefono non valido');
    }
    return $phone;
}

/** Livello di esperienza facoltativo: un valore fuori elenco vale "non indicato". */
function profile_experience(mixed $value): ?string
{
    return in_array($value, FINANCE_EXPERIENCE_LEVELS, true) ? $value : null;
}
