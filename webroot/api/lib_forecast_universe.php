<?php
declare(strict_types=1);

/**
 * Universo aggiuntivo per le classifiche "migliori/peggiori" di Previsioni,
 * oltre ai ticker che gli utenti hanno effettivamente aggiunto ai propri
 * strumenti. "Tutto il mercato" non è raggiungibile con l'API non ufficiale
 * di Yahoo usata da questo progetto (nessun elenco completo di titoli
 * esistenti, serve un feed dati a pagamento per quello) — questa è una
 * lista curata, diversificata sulle 4 asset class richieste (azioni, ETF
 * azionari, ETF obbligazionari, crypto), verificata live contro Yahoo
 * prima di essere aggiunta qui. Va estesa a mano se si vuole più
 * copertura, non generata automaticamente.
 *
 * Tenuta separata da MARKET_TRENDS_WATCHLIST (lib_market_trends.php): quella
 * lista alimenta la vetrina della sezione Mercati (con sparkline, prezzo
 * live), questa serve solo a dare più materia prima alle classifiche di
 * Previsioni — le due liste si sovrappongono in parte, ma servono scopi
 * diversi e possono evolvere in modo indipendente.
 */
/**
 * Ticker classificati come "bond" per l'addestramento del modello di
 * previsione (lib_forecast_model.php), anche se il loro `type` in UI resta
 * 'etf' (sono strutturati come ETF, è corretto mostrarli così). Volatilità
 * e drawdown di un ETF obbligazionario sono di un ordine di grandezza
 * diversi da quelli di un ETF azionario — allenare un solo modello su
 * entrambi senza distinguerli produce previsioni assurde per l'uno o
 * l'altro gruppo (verificato: un modello pooled prevedeva -60/-74% per ETF
 * obbligazionari a bassa volatilità, un artefatto di estrapolazione fuori
 * dal range su cui il modello ha imparato, non un errore di calcolo).
 */
const FORECAST_BOND_TICKERS = ['AGG', 'BND', 'TLT', 'IEF', 'LQD', 'HYG'];

const FORECAST_UNIVERSE = [
    // Azioni USA
    ['ticker' => 'AAPL', 'name' => 'Apple', 'type' => 'azione'],
    ['ticker' => 'MSFT', 'name' => 'Microsoft', 'type' => 'azione'],
    ['ticker' => 'NVDA', 'name' => 'Nvidia', 'type' => 'azione'],
    ['ticker' => 'AMZN', 'name' => 'Amazon', 'type' => 'azione'],
    ['ticker' => 'GOOGL', 'name' => 'Alphabet', 'type' => 'azione'],
    ['ticker' => 'META', 'name' => 'Meta Platforms', 'type' => 'azione'],
    ['ticker' => 'TSLA', 'name' => 'Tesla', 'type' => 'azione'],
    ['ticker' => 'BRK-B', 'name' => 'Berkshire Hathaway', 'type' => 'azione'],
    ['ticker' => 'JPM', 'name' => 'JPMorgan Chase', 'type' => 'azione'],
    ['ticker' => 'JNJ', 'name' => 'Johnson & Johnson', 'type' => 'azione'],
    ['ticker' => 'V', 'name' => 'Visa', 'type' => 'azione'],
    ['ticker' => 'PG', 'name' => 'Procter & Gamble', 'type' => 'azione'],
    ['ticker' => 'UNH', 'name' => 'UnitedHealth', 'type' => 'azione'],
    ['ticker' => 'HD', 'name' => 'Home Depot', 'type' => 'azione'],
    ['ticker' => 'XOM', 'name' => 'ExxonMobil', 'type' => 'azione'],
    ['ticker' => 'KO', 'name' => 'Coca-Cola', 'type' => 'azione'],
    ['ticker' => 'PEP', 'name' => 'PepsiCo', 'type' => 'azione'],
    ['ticker' => 'DIS', 'name' => 'Disney', 'type' => 'azione'],
    ['ticker' => 'INTC', 'name' => 'Intel', 'type' => 'azione'],
    ['ticker' => 'AMD', 'name' => 'AMD', 'type' => 'azione'],

    // Azioni Europa
    ['ticker' => 'ASML.AS', 'name' => 'ASML', 'type' => 'azione'],
    ['ticker' => 'SAP.DE', 'name' => 'SAP', 'type' => 'azione'],
    ['ticker' => 'NESN.SW', 'name' => 'Nestlé', 'type' => 'azione'],
    ['ticker' => 'MC.PA', 'name' => 'LVMH', 'type' => 'azione'],
    ['ticker' => 'ENI.MI', 'name' => 'Eni', 'type' => 'azione'],
    ['ticker' => 'ISP.MI', 'name' => 'Intesa Sanpaolo', 'type' => 'azione'],
    ['ticker' => 'ENEL.MI', 'name' => 'Enel', 'type' => 'azione'],

    // ETF azionari
    ['ticker' => 'VWCE.MI', 'name' => 'Vanguard FTSE All-World', 'type' => 'etf'],
    ['ticker' => 'SWDA.MI', 'name' => 'iShares Core MSCI World', 'type' => 'etf'],
    ['ticker' => 'SXR8.DE', 'name' => 'iShares Core S&P 500', 'type' => 'etf'],
    ['ticker' => 'EIMI.MI', 'name' => 'iShares Core MSCI EM IMI', 'type' => 'etf'],
    ['ticker' => 'CSSPX.MI', 'name' => 'iShares Core S&P 500 (Acc)', 'type' => 'etf'],

    // ETF obbligazionari
    ['ticker' => 'AGG', 'name' => 'iShares Core US Aggregate Bond', 'type' => 'etf'],
    ['ticker' => 'BND', 'name' => 'Vanguard Total Bond Market', 'type' => 'etf'],
    ['ticker' => 'TLT', 'name' => 'iShares 20+ Year Treasury Bond', 'type' => 'etf'],
    ['ticker' => 'IEF', 'name' => 'iShares 7-10 Year Treasury Bond', 'type' => 'etf'],
    ['ticker' => 'LQD', 'name' => 'iShares iBoxx Investment Grade Corporate Bond', 'type' => 'etf'],
    ['ticker' => 'HYG', 'name' => 'iShares iBoxx High Yield Corporate Bond', 'type' => 'etf'],

    // Crypto
    ['ticker' => 'BTC-USD', 'name' => 'Bitcoin', 'type' => 'crypto'],
    ['ticker' => 'ETH-USD', 'name' => 'Ethereum', 'type' => 'crypto'],
    ['ticker' => 'SOL-USD', 'name' => 'Solana', 'type' => 'crypto'],
    ['ticker' => 'XRP-USD', 'name' => 'XRP', 'type' => 'crypto'],
    ['ticker' => 'ADA-USD', 'name' => 'Cardano', 'type' => 'crypto'],
    ['ticker' => 'BNB-USD', 'name' => 'BNB', 'type' => 'crypto'],
    ['ticker' => 'DOGE-USD', 'name' => 'Dogecoin', 'type' => 'crypto'],
    ['ticker' => 'LINK-USD', 'name' => 'Chainlink', 'type' => 'crypto'],
];
