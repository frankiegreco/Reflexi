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
          {item.current_price > 0 && (
            <div className="mb-2 font-mono text-xs text-gray-700">
              {fmtPrice(item.current_price, item.currency)} <span className="text-muted">oggi</span>
              {' → '}
              {fmtPrice(item.current_price * (1 + item.forecast_pct / 100), item.currency)} <span className="text-muted">stimato</span>
            </div>
          )}
          <div className="mb-2 text-[10px] text-muted">
            Modello addestrato il {formatDate(item.model.trained_at.slice(0, 10))} su {item.model.n_samples} campioni storici
            ({item.model.n_tickers} strumenti) · R² in-sample {(item.model.r_squared * 100).toFixed(1)}%
            {' · '}R² out-of-sample{' '}
            <span className={item.model.r_squared_oos === null ? '' : item.model.r_squared_oos > 0 ? 'text-income' : 'text-expense'}>
              {item.model.r_squared_oos === null ? 'n/d' : `${(item.model.r_squared_oos * 100).toFixed(1)}%`}
            </span>
            {item.model.r_squared_oos !== null && item.model.r_squared_oos <= 0 && ' (nessuna vera capacità predittiva dimostrata)'}
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

const RISK_PROFILES = [
  { key: 'conservativo', label: 'Conservativo', hint: 'penalizza di più la volatilità, max 20% per strumento' },
  { key: 'bilanciato', label: 'Bilanciato', hint: 'rendimento e volatilità pesano uguale, max 35% per strumento' },
  { key: 'aggressivo', label: 'Aggressivo', hint: 'penalizza di meno la volatilità, max 50% per strumento' },
];

function InvestAdvisor() {
  const [amount, setAmount] = useState('10000');
  const [months, setMonths] = useState('4');
  const [count, setCount] = useState('9');
  const [risk, setRisk] = useState('bilanciato');
  const [result, setResult] = useState(null);
  const [loading, setLoading] = useState(false);

  async function calculate() {
    const amt = parseFloat(amount);
    const mo = parseInt(months, 10);
    const cnt = parseInt(count, 10);
    if (!amt || amt <= 0 || !mo || mo <= 0 || !cnt || cnt <= 0) return;
    setLoading(true);
    try {
      const r = await apiGet(`invest_advisor.php?amount=${amt}&months=${mo}&count=${cnt}&risk=${risk}`);
      setResult(r);
    } catch {
      setResult({ allocation_available: false, message: 'Errore nel calcolo.' });
    } finally {
      setLoading(false);
    }
  }

  return (
    <div className="mb-10 card p-5">
      <h2 className="mb-1 text-sm font-bold text-gray-800">Voglio investire</h2>
      <p className="mb-4 text-xs text-muted">
        Inserisci un importo ipotetico in euro, un orizzonte, su quanti strumenti diversificare e un profilo di
        rischio: il modello alloca in base a rendimento stimato, rischio storico e affidabilità del modello per
        quella categoria (azioni/obbligazioni/crypto).
      </p>

      <div className="mb-3">
        <label className="field-label">Profilo di rischio</label>
        <div className="flex flex-wrap gap-1 rounded-lg bg-gray-100 p-1" style={{ width: 'fit-content' }}>
          {RISK_PROFILES.map((r) => (
            <button
              key={r.key}
              type="button"
              title={r.hint}
              onClick={() => setRisk(r.key)}
              className={`rounded-md px-3 py-1.5 text-xs font-semibold transition ${risk === r.key ? 'bg-accent text-white' : 'text-gray-600 hover:text-gray-900'}`}
            >
              {r.label}
            </button>
          ))}
        </div>
      </div>

      <div className="mb-4 flex flex-wrap items-end gap-3">
        <div>
          <label className="field-label">Importo (€)</label>
          <input className="field w-36" type="number" min="1" value={amount} onChange={(e) => setAmount(e.target.value)} />
        </div>
        <div>
          <label className="field-label">Orizzonte (mesi)</label>
          <input className="field w-28" type="number" min="1" max="60" value={months} onChange={(e) => setMonths(e.target.value)} />
        </div>
        <div>
          <label className="field-label">N. strumenti</label>
          <input className="field w-28" type="number" min="1" max="15" value={count} onChange={(e) => setCount(e.target.value)} />
        </div>
        <button className="btn-accent" onClick={calculate} disabled={loading}>{loading ? 'Calcolo...' : 'Calcola allocazione'}</button>
      </div>

      {result?.allocation_available && result.allocations.length < result.requested_count && (
        <p className="mb-3 text-xs text-muted">
          Richiesti {result.requested_count} strumenti, disponibili solo {result.allocations.length} con previsione positiva su questo orizzonte.
        </p>
      )}

      {result && !result.allocation_available && (
        <p className="text-sm text-muted">{result.message}</p>
      )}

      {result?.allocation_available && (
        <>
          <div className="mb-1 flex items-baseline gap-2">
            <span className={`font-mono text-2xl font-bold ${result.total_projected_gain_pct >= 0 ? 'text-income' : 'text-expense'}`}>
              {fmtPrice(result.total_projected_value, 'EUR')}
            </span>
            <span className="text-xs text-muted">
              stimato dopo {result.months} {result.months === 1 ? 'mese' : 'mesi'} ({pct(result.total_projected_gain_pct)})
            </span>
          </div>
          <p className="mb-3 text-xs text-muted">
            Commissioni stimate {fmtPrice(result.total_fees, 'EUR')} ({result.allocations.length} {result.allocations.length === 1 ? 'acquisto' : 'acquisti'} × {fmtPrice(result.fee_per_trade, 'EUR')}) —
            importo netto investito {fmtPrice(result.net_amount, 'EUR')}
          </p>

          <div className="overflow-x-auto rounded-2xl border border-border">
            <table className="w-full min-w-[560px] border-collapse text-sm">
              <thead>
                <tr className="border-b border-border text-left text-[11px] uppercase tracking-wider text-muted">
                  <th className="px-3 py-2 font-semibold">Strumento</th>
                  <th className="px-3 py-2 text-right font-semibold">Quota</th>
                  <th className="px-3 py-2 text-right font-semibold">Importo</th>
                  <th className="px-3 py-2 text-right font-semibold">Rendimento stimato</th>
                  <th className="px-3 py-2 text-right font-semibold">Valore stimato</th>
                </tr>
              </thead>
              <tbody>
                {result.allocations.map((a) => (
                  <tr key={a.ticker} className="border-b border-border last:border-0">
                    <td className="px-3 py-2">
                      <div className="font-semibold text-gray-900">{a.name}</div>
                      <div className="text-xs text-muted">{a.ticker} · {TYPE_LABELS[a.type] || a.type} · R² out-of-sample {(a.r_squared_oos * 100).toFixed(0)}%</div>
                      <div className="text-[10px] text-muted">
                        Modello 12m {pct(a.model_forecast_pct)} · Storico 3y {pct(a.historical_trend_pct)} · pesati {(a.confidence * 100).toFixed(0)}/{(100 - a.confidence * 100).toFixed(0)}
                      </div>
                    </td>
                    <td className="px-3 py-2 text-right font-mono">{a.weight_pct}%</td>
                    <td className="px-3 py-2 text-right font-mono">{fmtPrice(a.allocated_amount, 'EUR')}</td>
                    <td className="px-3 py-2 text-right"><PctPill value={a.horizon_return_pct} /></td>
                    <td className="px-3 py-2 text-right font-mono">{fmtPrice(a.projected_value, 'EUR')}</td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>

          <div className="mt-3 flex items-start gap-2 rounded-xl border border-amber-200 bg-amber-50 p-3 text-[11px] text-amber-900">
            <AlertTriangle size={13} className="mt-0.5 flex-shrink-0 text-amber-600" />
            <p>
              <strong>Non è consulenza finanziaria.</strong> Il rendimento usato per scegliere e proiettare non è la
              previsione grezza del modello, ma un mix pesato tra quella e il rendimento storico realmente realizzato
              dal titolo (CAGR sui migliori 3 anni disponibili) — il peso è la confidenza out-of-sample del modello
              (vedi "Modello 12m · Storico 3y · pesati X/Y" sotto ogni riga): più il modello è incerto, più il numero
              si avvicina alla media storica reale invece che alla previsione. Orizzonte di {result.months} mesi
              ottenuto proiettando matematicamente questo rendimento (capitalizzazione composta) — il modello NON è
              validato su questo orizzonte specifico, solo su 12 mesi. Profilo <strong>{RISK_PROFILES.find((r) => r.key === result.risk_profile)?.label}</strong>:
              max {result.max_weight_pct}% per strumento. Considera solo categorie (azioni/obbligazioni/crypto) il cui
              modello ha una capacità predittiva out-of-sample dimostrata (R² out-of-sample &gt; 0, misurato su dati
              mai usati per stimare i coefficienti) — le altre sono escluse del tutto, non incluse con un peso
              piccolo: un modello che va peggio della semplice media storica non merita un peso positivo, per quanto
              piccolo. Rendimenti calcolati sulla valuta nativa di ogni strumento e applicati all'importo in euro
              senza conversione valutaria (stessa scelta delle altre schermate di Reflexi — ignora il rischio di
              cambio). È un calcolo automatico che può sbagliarsi anche di molto, specialmente su orizzonti brevi:
              non seguirlo come unico riferimento per una decisione reale.
            </p>
          </div>
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
              <div className="mt-0.5 font-mono text-[10px] text-muted">
                {i.projected_price !== undefined
                  ? <>{fmtPrice(i.current_price, i.currency)} → {fmtPrice(i.projected_price, i.currency)}</>
                  : fmtPrice(i.current_price, i.currency)}
              </div>
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

      <InvestAdvisor />

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
        Tra un ampio paniere di azioni, ETF (azionari e obbligazionari) e crypto (non solo il tuo portafoglio) — "andamento
        storico" è il trend reale già registrato (CAGR sui migliori 3 anni disponibili, non una previsione), "previsione"
        è la stima a 12 mesi dello stesso modello di cui sopra, con il prezzo attuale e quello stimato mostrati entrambi
        per evitare ambiguità sul dato.
      </p>
      <div className="grid grid-cols-1 gap-6 lg:grid-cols-2">
        <RankingList title="Migliori — andamento storico" hint="% = rendimento già realizzato, non una previsione" items={rankings?.best_current ?? null} />
        <RankingList title="Migliori — previsione 12 mesi" hint="% e prezzo stimato a 12 mesi dal modello" items={rankings?.best_forecast ?? null} />
        <RankingList title="Peggiori — andamento storico" hint="% = rendimento già realizzato, non una previsione" items={rankings?.worst_current ?? null} />
        <RankingList title="Peggiori — previsione 12 mesi" hint="% e prezzo stimato a 12 mesi dal modello" items={rankings?.worst_forecast ?? null} />
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
