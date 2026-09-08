<?php
declare(strict_types=1);
require __DIR__ . '/db.php';
require_once __DIR__ . '/price_fetch.php';         // fetch_live_price(), fx_rate_to_eur()
require_once __DIR__ . '/lib_market_trends.php';   // is_valid_public_ticker(), ensure_price_history()
require_once __DIR__ . '/lib_instrument_metrics.php'; // closest_row_to()

// Trading simulato: conti e transazioni completamente separati da
// accounts/instruments/investment_transactions (vedi migrazione 039) — non
// tocca mai patrimonio netto, saldi reali o portafoglio investimenti vero.
// Il prezzo di esecuzione viene SEMPRE letto qui lato server via
// fetch_live_price() (stessa cache 15 min usata in tutta l'app), mai
// accettato dal client: altrimenti un utente potrebbe "comprare" a un
// prezzo inventato, rendendo inutile la pratica.
$uid = require_auth();

// € fissi per operazione (acquisto o vendita), stessa cifra e stesso motivo
// dell'advisor investimenti (INVEST_ADVISOR_FEE_PER_TRADE): senza
// commissioni un trade piccolo o frequente non ha mai controindicazioni,
// mentre nella realtà le commissioni fisse penalizzano proprio quello —
// una lezione che vale la pena far vivere anche qui, non solo dichiararla.
// Non tocca il costo medio di carico (native currency): la commissione è
// sempre in EUR, sommarla a un accumulatore in valuta nativa senza
// conversione produrrebbe un costo medio sbagliato — resta quindi solo un
// costo di cassa (cash_impact_eur), coerente con come viene mostrata.
const PAPER_TRADING_FEE_PER_TRADE = 1.0;

/** Somma di tutte le transazioni di un conto -> saldo cassa (mai persistito, vedi migrazione 039) e posizioni aperte per ticker (costo medio ponderato, stessa logica di investments.php). */
function paper_compute_state(float $startingBalance, array $transactions): array
{
    $cashBalance = $startingBalance;
    $byTicker = [];

    foreach ($transactions as $t) {
        $cashBalance += (float)$t['cash_impact_eur'];

        $ticker = $t['ticker'];
        if (!isset($byTicker[$ticker])) {
            $byTicker[$ticker] = [
                'ticker' => $ticker, 'name' => $t['name'], 'currency' => $t['currency'],
                'quantity' => 0.0, 'cost_acc' => 0.0,
            ];
        }
        $qty = (float)$t['quantity'];
        $price = (float)$t['price_per_unit'];
        if ($t['type'] === 'acquisto') {
            $byTicker[$ticker]['cost_acc'] += $qty * $price;
            $byTicker[$ticker]['quantity'] += $qty;
        } else {
            $held = $byTicker[$ticker]['quantity'];
            $avgCost = $held > 0 ? $byTicker[$ticker]['cost_acc'] / $held : 0.0;
            $byTicker[$ticker]['cost_acc'] -= $avgCost * $qty;
            $byTicker[$ticker]['quantity'] -= $qty;
        }
    }

    // Posizioni chiuse (quantita' tornata a zero) non compaiono: una vendita
    // totale libera lo slot, non lascia una riga "0 quote" in giro.
    $positions = array_values(array_filter($byTicker, fn($p) => $p['quantity'] > 1e-9));

    return ['cash_balance' => $cashBalance, 'positions' => $positions];
}

/** Quantita' attualmente detenuta di un ticker in un conto, dalle sue transazioni gia' caricate — usata per bloccare una vendita allo scoperto. */
function paper_held_quantity(array $positions, string $ticker): float
{
    foreach ($positions as $p) {
        if ($p['ticker'] === $ticker) return $p['quantity'];
    }
    return 0.0;
}

/** Arricchisce le posizioni con prezzo live, valore di mercato in EUR e plus/minusvalenza non realizzata — un fetch per ticker, cache 15 min come ovunque in Reflexi. */
function paper_enrich_positions(array $positions): array
{
    $out = [];
    foreach ($positions as $p) {
        $live = fetch_live_price($p['ticker']);
        $avgCost = $p['cost_acc'] / $p['quantity'];
        $currentPrice = $live['price'] ?? null;
        $fx = $live !== null ? fx_rate_to_eur($live['currency']) : ['rate' => null, 'stale' => true];

        $marketValueEur = ($currentPrice !== null && $fx['rate'] !== null) ? $p['quantity'] * $currentPrice * $fx['rate'] : null;
        $costEur = null; // il costo di carico è nella valuta nativa dell'epoca dell'acquisto: non riconvertito retroattivamente, mostrato in valuta nativa

        $out[] = [
            'ticker' => $p['ticker'],
            'name' => $p['name'],
            'currency' => $p['currency'],
            'quantity' => round($p['quantity'], 6),
            'avg_cost' => round($avgCost, 4),
            'current_price' => $currentPrice,
            'price_stale' => $live['stale'] ?? true,
            'market_value_eur' => $marketValueEur !== null ? round($marketValueEur, 2) : null,
            'unrealized_gain_pct' => ($currentPrice !== null && $avgCost > 0) ? round(($currentPrice / $avgCost - 1) * 100, 2) : null,
        ];
    }
    return $out;
}

function paper_load_transactions(int $accountId): array
{
    $stmt = db()->prepare("SELECT * FROM paper_trading_transactions WHERE account_id = :id ORDER BY executed_at, id");
    $stmt->execute(['id' => $accountId]);
    return $stmt->fetchAll();
}

/**
 * Andamento del valore totale del conto, un punto al giorno dalla creazione
 * del conto a oggi — ricostruito dai prezzi di CHIUSURA storici reali (stessa
 * tabella price_history usata da Mercati/Previsioni/Investimenti), non da
 * snapshot salvati: non serve un cron dedicato e la storia esiste già anche
 * per un conto aperto oggi ma con strumenti seguiti da tempo su Reflexi.
 * Backfilla lo storico mancante al volo (ensure_price_history, come altrove)
 * per ogni ticker E per ogni coppia valuta/EUR toccati dalle transazioni —
 * il tasso di cambio storico non è mai stato salvato a parte in Reflexi, ma
 * fetch_live_price() scrive comunque in price_history anche per i ticker
 * "USDEUR=X" ecc. (sono trattati come un ticker qualunque), quindi lo stesso
 * meccanismo copre anche il cambio storico senza una tabella dedicata.
 */
function paper_account_history(array $account, array $transactions): array
{
    $startDate = substr($account['created_at'], 0, 10);
    $today = date('Y-m-d');
    $daysSpan = max(5, (int)((strtotime($today) - strtotime($startDate)) / 86400) + 5);

    $tickers = array_unique(array_column($transactions, 'ticker'));
    $currencies = array_unique(array_filter(array_map(fn($t) => $t['currency'] !== 'EUR' ? $t['currency'] : null, $transactions)));

    $priceHistory = [];
    foreach ($tickers as $ticker) {
        ensure_price_history($ticker, $daysSpan);
        $stmt = db()->prepare("SELECT date, price FROM price_history WHERE ticker = :t ORDER BY date");
        $stmt->execute(['t' => $ticker]);
        $priceHistory[$ticker] = $stmt->fetchAll();
    }
    $fxHistory = [];
    foreach ($currencies as $ccy) {
        $fxTicker = "{$ccy}EUR=X";
        ensure_price_history($fxTicker, $daysSpan);
        $stmt = db()->prepare("SELECT date, price FROM price_history WHERE ticker = :t ORDER BY date");
        $stmt->execute(['t' => $fxTicker]);
        $fxHistory[$ccy] = $stmt->fetchAll();
    }

    $txIdx = 0;
    $n = count($transactions);
    $cash = (float)$account['starting_balance'];
    $qty = [];              // ticker -> quantità detenuta a mano a mano che si avanza nel tempo
    $currencyByTicker = [];

    $points = [];
    $cursor = $startDate;
    while ($cursor <= $today) {
        $cutoffTs = strtotime($cursor . ' 23:59:59');
        while ($txIdx < $n && strtotime($transactions[$txIdx]['executed_at']) <= $cutoffTs) {
            $t = $transactions[$txIdx];
            $cash += (float)$t['cash_impact_eur'];
            $delta = $t['type'] === 'acquisto' ? (float)$t['quantity'] : -(float)$t['quantity'];
            $qty[$t['ticker']] = ($qty[$t['ticker']] ?? 0.0) + $delta;
            $currencyByTicker[$t['ticker']] = $t['currency'];
            $txIdx++;
        }

        $positionsValue = 0.0;
        foreach ($qty as $ticker => $q) {
            if ($q <= 1e-9) continue;
            $hist = $priceHistory[$ticker] ?? [];
            if (!$hist) continue;
            $price = (float)closest_row_to($hist, $cutoffTs)['price'];
            $ccy = $currencyByTicker[$ticker];
            $fxRate = 1.0;
            if ($ccy !== 'EUR') {
                $fxHist = $fxHistory[$ccy] ?? [];
                $fxRate = $fxHist ? (float)closest_row_to($fxHist, $cutoffTs)['price'] : 1.0;
            }
            $positionsValue += $q * $price * $fxRate;
        }

        $points[] = ['date' => $cursor, 'total_value_eur' => round($cash + $positionsValue, 2)];
        $cursor = date('Y-m-d', strtotime($cursor . ' +1 day'));
    }
    return $points;
}

/** Riepilogo completo di un conto simulato (saldo, posizioni valorizzate, totale, rendimento) — riusato sia dalla lista sia dal dettaglio. */
function paper_account_summary(array $account): array
{
    $transactions = paper_load_transactions((int)$account['id']);
    $state = paper_compute_state((float)$account['starting_balance'], $transactions);
    $positions = paper_enrich_positions($state['positions']);

    $positionsValueEur = array_sum(array_map(fn($p) => $p['market_value_eur'] ?? 0, $positions));
    $totalValueEur = $state['cash_balance'] + $positionsValueEur;

    return [
        'id' => (int)$account['id'],
        'name' => $account['name'],
        'starting_balance' => (float)$account['starting_balance'],
        'created_at' => $account['created_at'],
        'cash_balance' => round($state['cash_balance'], 2),
        'positions' => $positions,
        'positions_value_eur' => round($positionsValueEur, 2),
        'total_value_eur' => round($totalValueEur, 2),
        'total_gain_pct' => (float)$account['starting_balance'] > 0 ? round(($totalValueEur / (float)$account['starting_balance'] - 1) * 100, 2) : 0.0,
    ];
}

$action = $_GET['action'] ?? '';

if ($action === 'trade' && method() === 'POST') {
    $in = json_input();
    assert_owned('paper_trading_accounts', $in['account_id'] ?? null, $uid, 'conto simulato');

    $ticker = strtoupper(trim($in['ticker'] ?? ''));
    $name = trim($in['name'] ?? $ticker);
    $type = $in['type'] ?? '';
    $quantity = (float)($in['quantity'] ?? 0);

    if (!is_valid_public_ticker($ticker)) respond_error('Ticker non valido');
    if (!in_array($type, ['acquisto', 'vendita'], true)) respond_error('Tipo operazione non valido');
    if ($quantity <= 0) respond_error('Quantità non valida');

    $live = fetch_live_price($ticker);
    if ($live === null) respond_error('Quotazione non disponibile per questo ticker al momento', 404);
    $fx = fx_rate_to_eur($live['currency']);
    if ($fx['rate'] === null) respond_error('Tasso di cambio non disponibile al momento, riprova tra poco', 404);

    $accountStmt = db()->prepare("SELECT * FROM paper_trading_accounts WHERE id = :id AND user_id = :uid");
    $accountStmt->execute(['id' => $in['account_id'], 'uid' => $uid]);
    $account = $accountStmt->fetch();

    $transactions = paper_load_transactions((int)$account['id']);
    $state = paper_compute_state((float)$account['starting_balance'], $transactions);

    $amountEur = $quantity * $live['price'] * $fx['rate'];
    $feeEur = PAPER_TRADING_FEE_PER_TRADE;

    if ($type === 'acquisto') {
        $totalCost = $amountEur + $feeEur;
        if ($totalCost > $state['cash_balance'] + 0.01) {
            respond_error(sprintf('Fondi virtuali insufficienti: servirebbero %.2f€ (incluso %.2f€ di commissione), ne hai %.2f€ disponibili in questo conto', $totalCost, $feeEur, $state['cash_balance']));
        }
        $cashImpact = -$totalCost;
    } else {
        $held = paper_held_quantity($state['positions'], $ticker);
        if ($quantity > $held + 1e-9) {
            respond_error(sprintf('Non possiedi abbastanza quote di %s in questo conto simulato (detenute: %s, richieste: %s)', $ticker, rtrim(rtrim(number_format($held, 6, '.', ''), '0'), '.'), rtrim(rtrim(number_format($quantity, 6, '.', ''), '0'), '.')));
        }
        $cashImpact = $amountEur - $feeEur;
    }

    $stmt = db()->prepare("INSERT INTO paper_trading_transactions
        (account_id, ticker, name, type, quantity, price_per_unit, currency, fx_rate_to_eur, cash_impact_eur, fees_eur)
        VALUES (:account_id, :ticker, :name, :type, :quantity, :price_per_unit, :currency, :fx_rate, :cash_impact, :fees)");
    $stmt->execute([
        'account_id' => $account['id'],
        'ticker' => $ticker,
        'name' => $name,
        'type' => $type,
        'quantity' => $quantity,
        'price_per_unit' => $live['price'],
        'currency' => $live['currency'],
        'fx_rate' => $fx['rate'],
        'cash_impact' => round($cashImpact, 2),
        'fees' => $feeEur,
    ]);

    respond(paper_account_summary($account), 201);
}

if ($action === 'reset' && method() === 'POST') {
    $in = json_input();
    assert_owned('paper_trading_accounts', $in['account_id'] ?? null, $uid, 'conto simulato');

    db()->prepare("DELETE FROM paper_trading_transactions WHERE account_id = :id")->execute(['id' => $in['account_id']]);

    $accountStmt = db()->prepare("SELECT * FROM paper_trading_accounts WHERE id = :id AND user_id = :uid");
    $accountStmt->execute(['id' => $in['account_id'], 'uid' => $uid]);
    respond(paper_account_summary($accountStmt->fetch()));
}

switch (method()) {
    case 'GET':
        if ($action === 'detail') {
            $id = $_GET['id'] ?? null;
            assert_owned('paper_trading_accounts', $id, $uid, 'conto simulato');
            $accountStmt = db()->prepare("SELECT * FROM paper_trading_accounts WHERE id = :id AND user_id = :uid");
            $accountStmt->execute(['id' => $id, 'uid' => $uid]);
            $account = $accountStmt->fetch();

            $summary = paper_account_summary($account);
            $txStmt = db()->prepare("SELECT * FROM paper_trading_transactions WHERE account_id = :id ORDER BY executed_at DESC, id DESC");
            $txStmt->execute(['id' => $id]);
            $summary['transactions'] = $txStmt->fetchAll();
            respond($summary);
        }

        if ($action === 'history') {
            $id = $_GET['id'] ?? null;
            assert_owned('paper_trading_accounts', $id, $uid, 'conto simulato');
            $accountStmt = db()->prepare("SELECT * FROM paper_trading_accounts WHERE id = :id AND user_id = :uid");
            $accountStmt->execute(['id' => $id, 'uid' => $uid]);
            $account = $accountStmt->fetch();
            respond(paper_account_history($account, paper_load_transactions((int)$id)));
        }

        $stmt = db()->prepare("SELECT * FROM paper_trading_accounts WHERE user_id = :uid ORDER BY created_at");
        $stmt->execute(['uid' => $uid]);
        respond(array_map('paper_account_summary', $stmt->fetchAll()));
        break;

    case 'POST':
        $in = json_input();
        if (empty($in['name'])) respond_error('Il nome del conto simulato è obbligatorio');
        $startingBalance = (float)($in['starting_balance'] ?? 0);
        if ($startingBalance <= 0) respond_error('Il capitale virtuale di partenza deve essere maggiore di zero');

        $stmt = db()->prepare("INSERT INTO paper_trading_accounts (user_id, name, starting_balance) VALUES (:uid, :name, :starting_balance)");
        $stmt->execute(['uid' => $uid, 'name' => $in['name'], 'starting_balance' => $startingBalance]);
        respond(['id' => (int)db()->lastInsertId()], 201);
        break;

    case 'DELETE':
        if ($action === 'delete_trade') {
            $id = $_GET['id'] ?? null;
            if (!$id) respond_error('ID mancante');
            // paper_trading_transactions non ha user_id diretto: la proprietà
            // passa dal conto simulato collegato, verificata col JOIN qui sotto
            // invece di assert_owned() (pensata per tabelle con user_id diretto).
            $stmt = db()->prepare("DELETE t FROM paper_trading_transactions t
                JOIN paper_trading_accounts a ON a.id = t.account_id
                WHERE t.id = :id AND a.user_id = :uid");
            $stmt->execute(['id' => $id, 'uid' => $uid]);
            if ($stmt->rowCount() === 0) respond_error('Operazione non valida o non appartenente al tuo account', 403);
            respond(['ok' => true]);
        }

        $id = $_GET['id'] ?? null;
        if (!$id) respond_error('ID mancante');
        assert_owned('paper_trading_accounts', $id, $uid, 'conto simulato');
        db()->prepare("DELETE FROM paper_trading_accounts WHERE id = :id AND user_id = :uid")->execute(['id' => $id, 'uid' => $uid]);
        respond(['ok' => true]);
        break;

    default:
        respond_error('Metodo non supportato', 405);
}
