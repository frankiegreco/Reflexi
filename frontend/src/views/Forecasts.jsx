import { useState, useEffect } from 'react';
import { AlertTriangle, Info } from 'lucide-react';
import { apiGet } from '../api';
import { formatDate } from '../lib/format';

const TYPE_LABELS = { etf: 'ETF', azione: 'Azione', obbligazione: 'Obbligazione', fondo: 'Fondo', crypto: 'Crypto', altro: 'Altro' };

function fmtPrice(price, currency) {
  try { return new Intl.NumberFormat('it-IT', { style: 'currency', currency: currency || 'EUR' }).format(price); }
  catch { return `${price} ${currency || ''}`; }
}

function pct(n) {
  if (n === null || n === undefined) return '—';
  return `${n > 0 ? '+' : ''}${n.toFixed(2)}%`;
}

function PctPill({ value }) {
  const cls = value > 0 ? 'bg-income/10 text-income' : value < 0 ? 'bg-expense/10 text-expense' : 'bg-gray-100 text-muted';
  return <span className={`inline-flex items-center rounded-md px-2 py-1 font-mono text-xs font-semibold ${cls}`}>{pct(value)}</span>;
}

function FeatureRow({ label, value, hint }) {
  return (
    <div className="flex items-center justify-between py-1 text-xs">
      <span className="text-muted">{label}</span>
      <div className="text-right">
        <span className={`font-mono font-semibold ${value >= 0 ? 'text-income' : 'text-expense'}`}>{pct(value)}</span>
        {hint && <div className="text-[10px] text-muted">{hint}</div>}
      </div>
    </div>
  );
}

function ForecastCard({ item }) {
  const cls = !item.forecast_available ? 'text-muted' : item.forecast_pct >= 0 ? 'text-income' : 'text-expense';

  return (
    <div className="card min-w-0 p-4">
      <div className="mb-2 flex items-start justify-between gap-2">
        <div className="min-w-0">
          <div className="truncate font-semibold text-gray-900">{item.name}</div>
          <div className="text-xs text-muted">{item.ticker} · {TYPE_LABELS[item.type] || item.type}</div>
        </div>
        {item.current_price > 0 && (
          <div className="flex-shrink-0 font-mono text-xs text-muted">{fmtPrice(item.current_price, item.currency)}</div>
        )}
      </div>

      {!item.forecast_available ? (
        <p className="text-xs text-muted">{item.message}</p>
      ) : (
        <>
          <div className="mb-1 flex items-baseline gap-2">
            <span className={`font-mono text-2xl font-bold ${cls}`}>{pct(item.forecast_pct)}</span>
            <span className="text-xs text-muted">stima {item.horizon}</span>
          </div>
          <div className="mb-2 text-[10px] text-muted">
            Modello addestrato il {formatDate(item.model.trained_at.slice(0, 10))} su {item.model.n_samples} campioni storici
            ({item.model.n_tickers} strumenti) · R² {(item.model.r_squared * 100).toFixed(1)}%
          </div>

          <div className="divide-y divide-border border-t border-border pt-1">
            <FeatureRow label="Trend storico" value={item.features.trend.value_pct} />
            <FeatureRow label="Momentum" value={item.features.momentum.value_pct} hint={`ultimi ${item.features.momentum.window_days} giorni`} />
            <FeatureRow label="Volatilità annualizzata" value={item.features.volatility.annualized_pct} />
            <FeatureRow label="Max drawdown" value={item.features.drawdown.pct} />
          </div>

          {item.analyst_reference && (
            <div className="mt-2 rounded-lg bg-gray-50 p-2 text-[11px]">
              <div className="flex items-center justify-between">
                <span className="text-muted">Target price analisti <span className="text-muted/70">(non incluso nella stima)</span></span>
                <span className={`font-mono font-semibold ${item.analyst_reference.value_pct >= 0 ? 'text-income' : 'text-expense'}`}>
                  {pct(item.analyst_reference.value_pct)}
                </span>
              </div>
              <div className="text-[10px] text-muted">
                {fmtPrice(item.analyst_reference.target_mean_price, item.currency)} medio · {item.analyst_reference.number_of_analysts} analisti (Yahoo Finance)
              </div>
            </div>
          )}
        </>
      )}
    </div>
  );
}

function RankingList({ title, hint, items }) {
  return (
    <div>
      <div className="mb-2">
        <h3 className="text-xs font-bold uppercase tracking-wider text-muted">{title}</h3>
        {hint && <p className="text-[11px] text-muted">{hint}</p>}
      </div>
      <div className="overflow-hidden rounded-2xl border border-border">
        {items === null && <div className="px-4 py-6 text-center text-sm text-muted">Calcolo...</div>}
        {items !== null && items.length === 0 && (
          <div className="px-4 py-6 text-center text-sm text-muted">Non ci sono ancora abbastanza strumenti tracciati per questa classifica.</div>
        )}
        {items?.map((i, idx) => (
          <div key={i.ticker} className="flex items-center justify-between gap-3 border-b border-border px-4 py-2.5 last:border-0">
            <div className="flex min-w-0 items-center gap-3">
              <span className="w-4 flex-shrink-0 font-mono text-xs text-muted">{idx + 1}</span>
              <div className="min-w-0">
                <div className="truncate text-sm font-semibold text-gray-900">{i.name}</div>
                <div className="text-xs text-muted">{i.ticker} · {TYPE_LABELS[i.type] || i.type}</div>
              </div>
            </div>
            <div className="flex-shrink-0 text-right">
              <PctPill value={i.value_pct} />
              <div className="mt-0.5 font-mono text-[10px] text-muted">{fmtPrice(i.current_price, i.currency)}</div>
            </div>
          </div>
        ))}
      </div>
    </div>
  );
}

export default function Forecasts() {
  const [data, setData] = useState(null);

  useEffect(() => {
    apiGet('forecasts.php').then(setData).catch(() => setData({ portfolio: [], rankings: null }));
  }, []);

  const portfolio = data?.portfolio ?? null;
  const rankings = data?.rankings ?? null;

  return (
    <div>
      <div className="mb-6">
        <h1 className="text-xl font-bold">Previsioni</h1>
        <p className="text-sm text-muted">Stima di rendimento a 12 mesi, prodotta da un modello statistico addestrato sullo storico prezzi.</p>
      </div>

      <div className="mb-6 flex items-start gap-3 rounded-xl border border-amber-200 bg-amber-50 p-4 text-xs text-amber-900">
        <AlertTriangle size={16} className="mt-0.5 flex-shrink-0 text-amber-600" />
        <p>
          <strong>Non è consulenza finanziaria.</strong> La percentuale mostrata è la previsione di un modello di
          regressione addestrato su dati storici — non una garanzia, non un segnale di acquisto o vendita, e con una
          capacità predittiva reale molto bassa (vedi l'R² mostrato su ogni card: più è basso, meno il modello spiega
          i rendimenti passati). Nessun modello — incluso questo — può prevedere in modo affidabile i mercati
          finanziari. Usala solo come uno dei tanti elementi di valutazione, mai come unico riferimento per una
          decisione di investimento.
        </p>
      </div>

      <h2 className="mb-3 text-sm font-bold text-gray-800">I tuoi strumenti</h2>
      {portfolio === null && <p className="py-10 text-center text-sm text-muted">Calcolo previsioni...</p>}
      {portfolio !== null && portfolio.length === 0 && (
        <p className="py-6 text-center text-sm text-muted">Aggiungi uno strumento in Investimenti per vedere qui la sua previsione.</p>
      )}
      {portfolio && portfolio.length > 0 && (
        <div className="mb-10 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
          {portfolio.map((f) => <ForecastCard key={f.instrument_id} item={f} />)}
        </div>
      )}

      <h2 className="mb-1 text-sm font-bold text-gray-800">Classifiche</h2>
      <p className="mb-4 text-xs text-muted">
        Tra tutti gli strumenti tracciati dagli utenti Reflexi (non solo il tuo portafoglio) — "andamento storico" è
        il trend reale già registrato (CAGR sui migliori 3 anni disponibili), "previsione" è la stima a 12 mesi dello
        stesso modello di cui sopra.
      </p>
      <div className="grid grid-cols-1 gap-6 lg:grid-cols-2">
        <RankingList title="Migliori — andamento storico" items={rankings?.best_current ?? null} />
        <RankingList title="Migliori — previsione 12 mesi" items={rankings?.best_forecast ?? null} />
        <RankingList title="Peggiori — andamento storico" items={rankings?.worst_current ?? null} />
        <RankingList title="Peggiori — previsione 12 mesi" items={rankings?.worst_forecast ?? null} />
      </div>

      <div className="mt-8 flex items-start gap-2 rounded-xl border border-border bg-white p-4 text-xs text-muted">
        <Info size={14} className="mt-0.5 flex-shrink-0 text-accent" />
        <p>
          Come funziona: una regressione lineare i cui coefficienti sono appresi da un backtest su tutto lo storico
          prezzi tracciato da Reflexi (non solo i tuoi strumenti) — per ogni punto storico passato calcola trend,
          momentum, volatilità e max drawdown noti fino a quel momento, e li confronta con il rendimento
          effettivamente realizzato nei 12 mesi successivi. Il target price medio degli analisti (quando disponibile)
          è mostrato come riferimento separato: non esiste uno storico delle opinioni degli analisti su cui allenare
          il modello, quindi non entra nella stima.
        </p>
      </div>
    </div>
  );
}
