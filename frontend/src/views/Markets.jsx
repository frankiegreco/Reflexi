import { useState, useEffect } from 'react';
import { Search, TrendingUp, TrendingDown } from 'lucide-react';
import { Line } from 'react-chartjs-2';
import { apiGet } from '../api';
import { formatDate } from '../lib/format';

const TYPE_LABELS = { etf: 'ETF', azione: 'Azione', crypto: 'Crypto', fondo: 'Fondo', altro: 'Altro' };

function fmtPrice(price, currency) {
  try { return new Intl.NumberFormat('it-IT', { style: 'currency', currency: currency || 'EUR' }).format(price); }
  catch { return `${price} ${currency || ''}`; }
}

function ChangeBadge({ pct }) {
  if (pct === null || pct === undefined) return <span className="text-xs text-muted">—</span>;
  const up = pct > 0;
  const cls = pct === 0 ? 'text-muted' : up ? 'text-income' : 'text-expense';
  const Icon = up ? TrendingUp : TrendingDown;
  return (
    <span className={`inline-flex items-center gap-1 font-mono text-xs font-semibold ${cls}`}>
      {pct !== 0 && <Icon size={12} />}
      {up ? '+' : ''}{pct.toFixed(2)}%
    </span>
  );
}

export default function Markets() {
  const [watchlist, setWatchlist] = useState(null);
  const [movers, setMovers] = useState(null);
  const [query, setQuery] = useState('');
  const [searching, setSearching] = useState(false);
  const [results, setResults] = useState([]);
  const [showResults, setShowResults] = useState(false);

  const [selected, setSelected] = useState(null); // { ticker, name }
  const [quote, setQuote] = useState(null);
  const [history, setHistory] = useState([]);
  const [days, setDays] = useState(90);

  useEffect(() => {
    apiGet('market_trends.php?action=watchlist').then(setWatchlist).catch(() => setWatchlist([]));
    apiGet('market_trends.php?action=top_movers').then(setMovers).catch(() => setMovers([]));
  }, []);

  // Ricerca con debounce, stesso pattern di instrument_search.php in Investments.jsx.
  useEffect(() => {
    if (query.trim().length < 2) { setResults([]); return; }
    setSearching(true);
    const handle = setTimeout(async () => {
      try {
        const res = await apiGet(`market_trends.php?action=search&q=${encodeURIComponent(query.trim())}`);
        setResults(res);
      } catch {
        setResults([]);
      } finally {
        setSearching(false);
      }
    }, 350);
    return () => clearTimeout(handle);
  }, [query]);

  function pick(ticker, name) {
    setSelected({ ticker, name });
    setShowResults(false);
    setQuery('');
  }

  useEffect(() => {
    if (!selected) return;
    apiGet(`market_trends.php?action=quote&ticker=${encodeURIComponent(selected.ticker)}`).then(setQuote).catch(() => setQuote(null));
  }, [selected]);

  useEffect(() => {
    if (!selected) return;
    apiGet(`market_trends.php?action=history&ticker=${encodeURIComponent(selected.ticker)}&days=${days}`).then(setHistory).catch(() => setHistory([]));
  }, [selected, days]);

  return (
    <div className="space-y-6">
      <div>
        <h1 className="text-xl font-bold">Mercati</h1>
        <p className="text-sm text-muted">Andamenti di azioni, ETF e crypto — dati Yahoo Finance, non solo il tuo portafoglio.</p>
      </div>

      {/* Ricerca */}
      <div className="card relative p-4">
        <label className="field-label">Cerca un titolo</label>
        <div className="relative">
          <Search size={16} className="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-muted" />
          <input
            className="field pl-9"
            placeholder='Es. "Apple", "VWCE", "Bitcoin"'
            value={query}
            onChange={(e) => { setQuery(e.target.value); setShowResults(true); }}
            onFocus={() => setShowResults(true)}
          />
        </div>
        {showResults && query.trim().length >= 2 && (
          <div className="absolute left-4 right-4 top-full z-10 mt-1 max-h-72 overflow-y-auto rounded-xl border border-border bg-white shadow-lg">
            {searching && <div className="px-3 py-2.5 text-sm text-muted">Ricerca in corso...</div>}
            {!searching && results.length === 0 && <div className="px-3 py-2.5 text-sm text-muted">Nessun risultato.</div>}
            {!searching && results.map((r) => (
              <button
                key={r.ticker}
                type="button"
                onClick={() => pick(r.ticker, r.name)}
                className="flex w-full items-center justify-between gap-2 px-3 py-2.5 text-left text-sm hover:bg-accentSoft"
              >
                <span><span className="font-semibold">{r.name}</span><span className="ml-2 text-xs text-muted">{r.exchange}</span></span>
                <span className="flex-shrink-0 font-mono text-xs text-muted">{r.ticker}</span>
              </button>
            ))}
          </div>
        )}
      </div>

      {/* Grafico titolo selezionato */}
      {selected && (
        <div className="card p-4">
          <div className="mb-3 flex flex-wrap items-center justify-between gap-3">
            <div>
              <div className="text-base font-bold">{selected.name} <span className="text-xs font-normal text-muted">{selected.ticker}</span></div>
              {quote && (
                <div className="flex items-baseline gap-2">
                  <span className="font-mono text-lg font-semibold">{fmtPrice(quote.price, quote.currency)}</span>
                  <ChangeBadge pct={quote.change_pct} />
                </div>
              )}
            </div>
            <div className="flex gap-2">
              {[30, 90, 365].map((d) => (
                <button key={d} onClick={() => setDays(d)} className={`rounded-lg px-3 py-1 text-xs font-semibold ${days === d ? 'bg-accent text-white' : 'bg-gray-100 text-gray-600'}`}>
                  {d === 365 ? '1 anno' : `${d}gg`}
                </button>
              ))}
            </div>
          </div>
          {history.length < 2 ? (
            <p className="py-8 text-center text-sm text-muted">
              Non c'è ancora abbastanza storico per questo titolo — si costruisce da solo, torna a controllare tra qualche giorno.
            </p>
          ) : (
            <div className="h-56">
              <Line
                data={{
                  labels: history.map((p) => formatDate(p.date)),
                  datasets: [{
                    label: 'Prezzo',
                    data: history.map((p) => p.price),
                    borderColor: '#4f46e5',
                    backgroundColor: 'rgba(79,70,229,0.08)',
                    fill: true,
                    tension: 0.25,
                    pointRadius: 0,
                  }],
                }}
                options={{
                  responsive: true, maintainAspectRatio: false,
                  plugins: { legend: { display: false } },
                  scales: { y: { ticks: { callback: (v) => fmtPrice(v, history[0]?.currency) } } },
                }}
              />
            </div>
          )}
        </div>
      )}

      {/* Watchlist */}
      <div>
        <h2 className="mb-3 text-sm font-bold uppercase tracking-wide text-muted">Titoli più seguiti</h2>
        {watchlist === null ? (
          <p className="text-sm text-muted">Caricamento...</p>
        ) : watchlist.length === 0 ? (
          <p className="text-sm text-muted">Quotazioni non disponibili al momento.</p>
        ) : (
          <div className="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-4">
            {watchlist.map((i) => (
              <button key={i.ticker} onClick={() => pick(i.ticker, i.name)} className="card p-3 text-left transition hover:-translate-y-0.5 hover:border-accent">
                <div className="mb-1 text-[10px] font-semibold uppercase tracking-wide text-muted">{TYPE_LABELS[i.type] || i.type}</div>
                <div className="mb-1.5 truncate text-sm font-bold" title={i.name}>{i.name}</div>
                <div className="font-mono text-sm font-semibold">{fmtPrice(i.price, i.currency)}</div>
                <ChangeBadge pct={i.change_pct} />
              </button>
            ))}
          </div>
        )}
      </div>

      {/* Top mover */}
      <div>
        <h2 className="mb-3 text-sm font-bold uppercase tracking-wide text-muted">I movimenti più forti oggi</h2>
        {movers === null ? (
          <p className="text-sm text-muted">Caricamento...</p>
        ) : movers.length === 0 ? (
          <p className="text-sm text-muted">Non ci sono ancora abbastanza dati per calcolare i movimenti di oggi.</p>
        ) : (
          <div className="space-y-2">
            {movers.map((i) => (
              <button key={i.ticker} onClick={() => pick(i.ticker, i.name)} className="flex w-full items-center justify-between rounded-xl border border-border bg-white px-4 py-3 text-left transition hover:border-accent">
                <div>
                  <div className="text-sm font-bold">{i.name}</div>
                  <div className="text-xs text-muted">{i.ticker} · {TYPE_LABELS[i.type] || i.type}</div>
                </div>
                <div className="text-right">
                  <div className="font-mono text-sm font-semibold">{fmtPrice(i.price, i.currency)}</div>
                  <ChangeBadge pct={i.change_pct} />
                </div>
              </button>
            ))}
          </div>
        )}
      </div>
    </div>
  );
}
