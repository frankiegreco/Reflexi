import { useState, useEffect, useMemo } from 'react';
import { Search } from 'lucide-react';
import { Line } from 'react-chartjs-2';
import { apiGet } from '../api';
import { formatDate } from '../lib/format';

const TYPE_LABELS = { etf: 'ETF', azione: 'Azione', crypto: 'Crypto', fondo: 'Fondo', altro: 'Altro' };
const RANGE_DAYS = { '1m': 30, '1a': 365, all: 3650 };

function fmtPrice(price, currency) {
  try { return new Intl.NumberFormat('it-IT', { style: 'currency', currency: currency || 'EUR' }).format(price); }
  catch { return `${price} ${currency || ''}`; }
}

function changeColor(pct) {
  return pct > 0 ? '#10b981' : pct < 0 ? '#f43f5e' : '#6b7080';
}

function ChangePill({ pct }) {
  if (pct === null || pct === undefined) return <span className="text-xs text-muted">—</span>;
  const cls = pct > 0 ? 'bg-income/10 text-income' : pct < 0 ? 'bg-expense/10 text-expense' : 'bg-gray-100 text-muted';
  const arrow = pct > 0 ? '▲' : pct < 0 ? '▼' : '';
  return (
    <span className={`inline-flex items-center gap-1 rounded-md px-2 py-1 font-mono text-xs font-semibold ${cls}`}>
      {arrow} {pct > 0 ? '+' : ''}{pct.toFixed(2)}%
    </span>
  );
}

function Sparkline({ prices }) {
  if (!Array.isArray(prices) || prices.length < 2) return <span className="text-xs text-muted">—</span>;
  const W = 96, H = 30, PAD = 2;
  const min = Math.min(...prices), max = Math.max(...prices);
  const range = (max - min) || 1;
  const stepX = (W - PAD * 2) / (prices.length - 1);
  const points = prices.map((p, i) => `${(PAD + i * stepX).toFixed(1)},${(H - PAD - ((p - min) / range) * (H - PAD * 2)).toFixed(1)}`).join(' ');
  const color = changeColor(prices[prices.length - 1] - prices[0]);
  return (
    <svg width={W} height={H} viewBox={`0 0 ${W} ${H}`} preserveAspectRatio="none">
      <polyline points={points} fill="none" stroke={color} strokeWidth="1.5" />
    </svg>
  );
}

function MarketTable({ title, hint, items, onSelect, emptyLabel }) {
  return (
    <div className="mb-8">
      <div className="mb-2 flex items-center justify-between">
        <h2 className="text-xs font-bold uppercase tracking-wider text-muted">{title}</h2>
        {hint && <span className="text-xs text-muted">{hint}</span>}
      </div>
      <div className="overflow-x-auto rounded-2xl border border-border">
        <table className="w-full min-w-[520px] border-collapse text-sm">
          <thead>
            <tr className="border-b border-border text-left text-[11px] uppercase tracking-wider text-muted">
              <th className="px-4 py-3 font-semibold">#</th>
              <th className="px-4 py-3 font-semibold">Nome</th>
              <th className="px-4 py-3 text-right font-semibold">Prezzo</th>
              <th className="px-4 py-3 text-right font-semibold">24h</th>
              <th className="hidden px-4 py-3 text-right font-semibold sm:table-cell">Ultimi 14gg</th>
            </tr>
          </thead>
          <tbody>
            {items === null && (
              <tr><td colSpan={5} className="px-4 py-8 text-center text-sm text-muted">Caricamento...</td></tr>
            )}
            {items !== null && items.length === 0 && (
              <tr><td colSpan={5} className="px-4 py-8 text-center text-sm text-muted">{emptyLabel}</td></tr>
            )}
            {items?.map((i, idx) => (
              <tr
                key={i.ticker}
                onClick={() => onSelect(i.ticker, i.name)}
                className="cursor-pointer border-b border-border last:border-0 transition hover:bg-gray-50"
              >
                <td className="px-4 py-3 font-mono text-xs text-muted">{idx + 1}</td>
                <td className="px-4 py-3">
                  <div className="font-semibold text-gray-900">{i.name}</div>
                  <div className="text-xs text-muted">{i.ticker} · {TYPE_LABELS[i.type] || i.type}</div>
                </td>
                <td className="px-4 py-3 text-right font-mono text-gray-900">{fmtPrice(i.price, i.currency)}</td>
                <td className="px-4 py-3 text-right"><ChangePill pct={i.change_pct} /></td>
                <td className="hidden px-4 py-3 text-right sm:table-cell"><div className="flex justify-end"><Sparkline prices={i.sparkline} /></div></td>
              </tr>
            ))}
          </tbody>
        </table>
      </div>
    </div>
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
  const [range, setRange] = useState('1m');

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
    const path = range === '24h'
      ? `market_trends.php?action=intraday&ticker=${encodeURIComponent(selected.ticker)}`
      : `market_trends.php?action=history&ticker=${encodeURIComponent(selected.ticker)}&days=${RANGE_DAYS[range]}`;
    apiGet(path).then(setHistory).catch(() => setHistory([]));
  }, [selected, range]);

  const trendColor = useMemo(() => {
    if (history.length < 2) return '#4f46e5';
    return changeColor(history[history.length - 1].price - history[0].price);
  }, [history]);

  return (
    <div>
      <div className="mb-6">
        <h1 className="text-xl font-bold">Mercati</h1>
        <p className="text-sm text-muted">Andamenti di azioni, ETF e crypto — dati Yahoo Finance, non solo il tuo portafoglio.</p>
      </div>

      {/* Ricerca */}
      <div className="relative mb-8">
        <Search size={16} className="pointer-events-none absolute left-4 top-1/2 -translate-y-1/2 text-muted" />
        <input
          className="w-full rounded-xl border border-border bg-white py-3 pl-11 pr-4 text-sm text-gray-900 placeholder:text-muted outline-none transition focus:border-accent"
          placeholder='Cerca un ticker o un nome — es. Apple, VWCE, Bitcoin'
          value={query}
          onChange={(e) => { setQuery(e.target.value); setShowResults(true); }}
          onFocus={() => setShowResults(true)}
        />
        {showResults && query.trim().length >= 2 && (
          <div className="absolute left-0 right-0 top-full z-10 mt-2 max-h-72 overflow-y-auto rounded-xl border border-border bg-white shadow-2xl">
            {searching && <div className="px-4 py-3 text-sm text-muted">Ricerca in corso...</div>}
            {!searching && results.length === 0 && <div className="px-4 py-3 text-sm text-muted">Nessun risultato.</div>}
            {!searching && results.map((r) => (
              <button
                key={r.ticker}
                type="button"
                onClick={() => pick(r.ticker, r.name)}
                className="flex w-full items-center justify-between gap-2 border-b border-border px-4 py-2.5 text-left text-sm last:border-0 hover:bg-accentSoft"
              >
                <span className="text-gray-900">{r.name} <span className="text-xs text-muted">{r.exchange}</span></span>
                <span className="flex-shrink-0 font-mono text-xs text-muted">{r.ticker}</span>
              </button>
            ))}
          </div>
        )}
      </div>

      {/* Grafico titolo selezionato */}
      {selected && (
        <div className="mb-10 rounded-2xl border border-border bg-white p-5 sm:p-6">
          <div className="mb-4 flex flex-wrap items-start justify-between gap-4">
            <div>
              <div className="text-sm font-semibold text-muted">{selected.name} · {selected.ticker}</div>
              {quote && (
                <div className="mt-1 flex items-baseline gap-2.5">
                  <span className="font-mono text-2xl font-bold text-gray-900">{fmtPrice(quote.price, quote.currency)}</span>
                  <ChangePill pct={quote.change_pct} />
                </div>
              )}
            </div>
            <div className="flex gap-1 rounded-lg bg-gray-100 p-1">
              {['24h', '1m', '1a', 'all'].map((r) => (
                <button
                  key={r}
                  onClick={() => setRange(r)}
                  className={`rounded-md px-3 py-1.5 text-xs font-semibold transition ${range === r ? 'bg-accent text-white' : 'text-gray-600 hover:text-gray-900'}`}
                >
                  {r === 'all' ? 'All' : r}
                </button>
              ))}
            </div>
          </div>
          {history.length < 2 ? (
            <p className="py-10 text-center text-sm text-muted">
              Non c'è ancora abbastanza storico per questo titolo — si costruisce da solo, torna a controllare tra qualche giorno.
            </p>
          ) : (
            <div className="h-64 sm:h-72">
              <Line
                data={{
                  labels: history.map((p) => formatDate(p.date)),
                  datasets: [{
                    label: 'Prezzo',
                    data: history.map((p) => p.price),
                    borderColor: trendColor,
                    backgroundColor: (ctx) => {
                      const { chartArea, ctx: c } = ctx.chart;
                      if (!chartArea) return `${trendColor}22`;
                      const g = c.createLinearGradient(0, chartArea.top, 0, chartArea.bottom);
                      g.addColorStop(0, `${trendColor}40`);
                      g.addColorStop(1, `${trendColor}00`);
                      return g;
                    },
                    fill: true,
                    tension: 0.25,
                    pointRadius: 0,
                    pointHoverRadius: 4,
                    pointHoverBackgroundColor: trendColor,
                    pointHoverBorderColor: '#ffffff',
                  }],
                }}
                options={{
                  responsive: true, maintainAspectRatio: false,
                  interaction: { mode: 'index', intersect: false },
                  plugins: {
                    legend: { display: false },
                    tooltip: {
                      backgroundColor: '#ffffff',
                      borderColor: '#e7e9f0',
                      borderWidth: 1,
                      titleColor: '#6b7080',
                      bodyColor: '#111827',
                      bodyFont: { family: '"IBM Plex Mono", monospace', weight: '600' },
                      padding: 10,
                      displayColors: false,
                      callbacks: { label: (ctx) => fmtPrice(ctx.parsed.y, history[0]?.currency) },
                    },
                  },
                  scales: {
                    x: { grid: { display: false }, ticks: { color: '#6b7080', maxTicksLimit: 6, font: { size: 10 } } },
                    y: {
                      grid: { color: '#e7e9f0' },
                      ticks: { color: '#6b7080', font: { size: 10, family: '"IBM Plex Mono", monospace' }, callback: (v) => fmtPrice(v, history[0]?.currency) },
                    },
                  },
                }}
              />
            </div>
          )}
        </div>
      )}

      <MarketTable
        title="Titoli più seguiti"
        hint="Aggiornato ogni ~15 min"
        items={watchlist}
        onSelect={pick}
        emptyLabel="Quotazioni non disponibili al momento."
      />

      <MarketTable
        title="I movimenti più forti oggi"
        hint="Tra gli strumenti tracciati dagli utenti Reflexi"
        items={movers}
        onSelect={pick}
        emptyLabel="Non ci sono ancora abbastanza dati per calcolare i movimenti di oggi."
      />

      <p className="text-center text-xs text-muted">Dati da Yahoo Finance. A scopo informativo, non è una raccomandazione di investimento.</p>
    </div>
  );
}
