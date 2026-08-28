<?php
declare(strict_types=1);

/**
 * Ticker "vetrina" per la sezione Mercati (sito pubblico + app), non legati
 * a nessun utente — a differenza di `instruments`, che è sempre per-utente.
 * Mix di ETF/azioni/crypto scelto per essere riconoscibile a colpo d'occhio;
 * non è una raccomandazione di investimento.
 */
const MARKET_TRENDS_WATCHLIST = [
    ['ticker' => 'VWCE.MI', 'name' => 'Vanguard FTSE All-World', 'type' => 'etf'],
    ['ticker' => 'SWDA.MI', 'name' => 'iShares Core MSCI World', 'type' => 'etf'],
    ['ticker' => 'SXR8.DE', 'name' => 'iShares Core S&P 500', 'type' => 'etf'],
    ['ticker' => 'EIMI.MI', 'name' => 'iShares Core MSCI EM IMI', 'type' => 'etf'],
    ['ticker' => 'AAPL', 'name' => 'Apple', 'type' => 'azione'],
    ['ticker' => 'MSFT', 'name' => 'Microsoft', 'type' => 'azione'],
    ['ticker' => 'NVDA', 'name' => 'Nvidia', 'type' => 'azione'],
    ['ticker' => 'AMZN', 'name' => 'Amazon', 'type' => 'azione'],
    ['ticker' => 'BTC-USD', 'name' => 'Bitcoin', 'type' => 'crypto'],
    ['ticker' => 'ETH-USD', 'name' => 'Ethereum', 'type' => 'crypto'],
    ['ticker' => 'SOL-USD', 'name' => 'Solana', 'type' => 'crypto'],
    ['ticker' => 'XRP-USD', 'name' => 'XRP', 'type' => 'crypto'],
];

/**
 * Allowlist per i ticker passati dall'utente sugli endpoint pubblici di
 * market_trends.php: qui non c'è `assert_owned()` a fare da guardia come per
 * gli strumenti di un utente loggato, quindi il ticker va validato a mano
 * prima di infilarlo in una query o in una URL verso Yahoo Finance. Niente
 * di più permissivo di quanto Yahoo stesso accetta come simbolo (lettere,
 * cifre, punto, trattino, `=` e `^` per FX/indici).
 */
function is_valid_public_ticker(string $ticker): bool
{
    return (bool)preg_match('/^[A-Z0-9.\-=^]{1,15}$/', $ticker);
}

/**
 * Variazione % rispetto all'ultima chiusura nota precedente a oggi in
 * `price_history` (che si popola da sola a ogni fetch live, vedi
 * price_fetch.php::fetch_live_price). Torna null se non c'è ancora storico
 * pregresso (ticker mai interrogato prima di oggi).
 */
function market_change_pct(string $ticker, float $currentPrice): ?float
{
    $stmt = db()->prepare("SELECT price FROM price_history
        WHERE ticker = :ticker AND date < CURDATE()
        ORDER BY date DESC LIMIT 1");
    $stmt->execute(['ticker' => $ticker]);
    $prev = $stmt->fetchColumn();
    if ($prev === false || (float)$prev == 0.0) return null;
    return round((($currentPrice - (float)$prev) / (float)$prev) * 100, 2);
}
