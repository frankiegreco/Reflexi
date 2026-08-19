<?php
declare(strict_types=1);

/** Categorie e conto di partenza per un utente appena registrato. */
function seed_default_data_for_user(int $userId): void
{
    $categories = [
        ['Stipendio', 'entrata', '#7fa876', 1],
        ['Freelance', 'entrata', '#7fa876', 2],
        ['Regalo', 'entrata', '#7fa876', 3],
        ['Rimborso', 'entrata', '#7fa876', 4],
        ['Investimenti (vendita)', 'entrata', '#4f46e5', 6],
        ['Altre entrate', 'entrata', '#7fa876', 5],
        ['Spesa', 'uscita', '#c1665a', 10],
        ['Ristoranti', 'uscita', '#c1665a', 11],
        ['Trasporti', 'uscita', '#c1665a', 12],
        ['Casa e bollette', 'uscita', '#c1665a', 13],
        ['Salute', 'uscita', '#c1665a', 14],
        ['Svago', 'uscita', '#c1665a', 15],
        ['Abbonamenti', 'uscita', '#c1665a', 16],
        ['Shopping', 'uscita', '#c1665a', 17],
        ['Sport', 'uscita', '#c1665a', 18],
        ['Altre uscite', 'uscita', '#c1665a', 19],
        ['Mutuo/Prestito', 'uscita', '#c1665a', 20],
        ['Investimenti (acquisto)', 'uscita', '#4f46e5', 21],
    ];
    $stmt = db()->prepare("INSERT INTO categories (user_id, name, type, color, sort_order) VALUES (:uid, :name, :type, :color, :sort)");
    foreach ($categories as [$name, $type, $color, $sort]) {
        $stmt->execute(['uid' => $userId, 'name' => $name, 'type' => $type, 'color' => $color, 'sort' => $sort]);
    }

    db()->prepare("INSERT INTO accounts (user_id, name, type, initial_balance, color, sort_order)
        VALUES (:uid, 'Conto principale', 'corrente', 0, '#4f46e5', 1)")
        ->execute(['uid' => $userId]);
}
