import { useState, useEffect } from 'react';
import { AlertTriangle, Info } from 'lucide-react';
import { apiGet } from '../api';

const TYPE_LABELS = { etf: 'ETF', azione: 'Azione', obbligazione: 'Obbligazione', fondo: 'Fondo', crypto: 'Crypto', altro: 'Altro' };

function fmtPrice(price, currency) {
  try { return new Intl.NumberFormat('it-IT', { style: 'currency', currency: currency || 'EUR' }).format(price); }
  catch { return `${price} ${currency || ''}`; }
}

function pct(n) {
  if (n === null || n === undefined) return '—';
  return `${n > 0 ? '+' : ''}${n.toFixed(2)}%`;
}

function ComponentRow({ label, comp, render }) {
  if (!comp?.available) {
    return (
      <div className="flex items-center justify-between py-1.5 text-xs text-muted">
        <span>{label}</span>
        <span>non disponibile</span>
      </div>
    );
  }
  return (
    <div className="flex items-center justify-between py-1.5 text-xs">
      <span className="text-muted">{label} <span className="text-muted/70">({comp.weight_pct}%)</span></span>
      <div className="text-right">
        <span className={`font-mono font-semibold ${comp.value_pct >= 0 ? 'text-income' : 'text-expense'}`}>{pct(comp.value_pct)}</span>
        {render && <div className="text-[10px] text-muted">{render(comp)}</div>}
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
          <div className="mt-2 divide-y divide-border border-t border-border pt-1">
            <ComponentRow label="Trend storico" comp={item.components.trend} render={(c) => c.basis} />
            <ComponentRow label="Upside target analisti" comp={item.components.analyst_upside}
              render={(c) => `${fmtPrice(c.target_mean_price, item.currency)} · ${c.number_of_analysts} analisti`} />
            <ComponentRow label="Momentum" comp={item.components.momentum} render={(c) => `ultimi ${c.window_days} giorni`} />
          </div>
        </>
      )}
    </div>
  );
}

export default function Forecasts() {
  const [forecasts, setForecasts] = useState(null);

  useEffect(() => {
    apiGet('forecasts.php').then(setForecasts).catch(() => setForecasts([]));
  }, []);

  return (
    <div>
      <div className="mb-6">
        <h1 className="text-xl font-bold">Previsioni</h1>
        <p className="text-sm text-muted">Stima di rendimento a 12 mesi per gli strumenti nel tuo portafoglio, calcolata da un modello di Reflexi.</p>
      </div>

      <div className="mb-6 flex items-start gap-3 rounded-xl border border-amber-200 bg-amber-50 p-4 text-xs text-amber-900">
        <AlertTriangle size={16} className="mt-0.5 flex-shrink-0 text-amber-600" />
        <p>
          <strong>Non è consulenza finanziaria.</strong> La percentuale mostrata è una stima generata da un modello
          Reflexi che combina tendenza storica, opinioni di analisti esterni e momentum recente — non è una garanzia,
          non riflette tutti i fattori che muovono un prezzo, e può sbagliarsi anche di molto. Non è pensata come
          indicazione di acquisto o vendita: usala solo come uno dei tanti elementi di valutazione, mai come unico
          riferimento per una decisione di investimento.
        </p>
      </div>

      {forecasts === null && <p className="py-10 text-center text-sm text-muted">Calcolo previsioni...</p>}
      {forecasts !== null && forecasts.length === 0 && (
        <p className="py-10 text-center text-sm text-muted">Aggiungi uno strumento in Investimenti per vedere qui la sua previsione.</p>
      )}

      {forecasts && forecasts.length > 0 && (
        <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
          {forecasts.map((f) => <ForecastCard key={f.instrument_id} item={f} />)}
        </div>
      )}

      <div className="mt-8 flex items-start gap-2 rounded-xl border border-border bg-white p-4 text-xs text-muted">
        <Info size={14} className="mt-0.5 flex-shrink-0 text-accent" />
        <p>
          Composizione del modello: 40% tendenza storica (rendimento annualizzato sui migliori 3 anni disponibili),
          40% differenza tra il target price medio degli analisti (dato Yahoo Finance, quando disponibile) e il
          prezzo attuale, 20% variazione di prezzo negli ultimi 3 mesi. Se un ingrediente non è disponibile per uno
          strumento (tipicamente il target price per ETF e crypto, che non hanno copertura di analisti), il suo peso
          viene ridistribuito sugli altri invece di essere trattato come zero.
        </p>
      </div>
    </div>
  );
}
