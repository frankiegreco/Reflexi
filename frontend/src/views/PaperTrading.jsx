import { useState, useEffect, useCallback } from 'react';
import { Search, Plus, RotateCcw, Trash2, FlaskConical, ArrowUpCircle, ArrowDownCircle } from 'lucide-react';
import { Line } from 'react-chartjs-2';
import { apiGet, apiSend, apiDelete } from '../api';
import { euro } from '../lib/format';
import Modal from '../components/Modal';
import StatCard from '../components/StatCard';
import Tabs from '../components/Tabs';

function fmtQty(n) {
  return String(parseFloat(Number(n).toFixed(6)));
}

function fmtDateTime(s) {
  if (!s) return '';
  return new Date(s.replace(' ', 'T')).toLocaleString('it-IT', { day: '2-digit', month: '2-digit', year: '2-digit', hour: '2-digit', minute: '2-digit' });
}

function GainPill({ pct }) {
  if (pct === null || pct === undefined) return <span className="text-xs text-muted">—</span>;
  const cls = pct > 0 ? 'bg-income/10 text-income' : pct < 0 ? 'bg-expense/10 text-expense' : 'bg-gray-100 text-muted';
  return <span className={`inline-flex items-center rounded-md px-2 py-1 font-mono text-xs font-semibold ${cls}`}>{pct > 0 ? '+' : ''}{pct.toFixed(2)}%</span>;
}

export default function PaperTrading() {
  const [accounts, setAccounts] = useState(null);
  const [activeId, setActiveId] = useState(null);
  const [detail, setDetail] = useState(null);
  const [history, setHistory] = useState(null);

  const [createOpen, setCreateOpen] = useState(false);
  const [createForm, setCreateForm] = useState({ name: '', starting_balance: '100000' });
  const [createError, setCreateError] = useState('');

  const [query, setQuery] = useState('');
  const [searching, setSearching] = useState(false);
  const [results, setResults] = useState([]);
  const [showResults, setShowResults] = useState(false);

  const [tradeTarget, setTradeTarget] = useState(null); // { ticker, name, type }
  const [tradeQuote, setTradeQuote] = useState(null);
  const [tradeIntraday, setTradeIntraday] = useState([]);
  const [tradeQuantity, setTradeQuantity] = useState('');
  const [tradeError, setTradeError] = useState('');
  const [tradeLoading, setTradeLoading] = useState(false);

  const loadAccounts = useCallback(() => {
    apiGet('paper_trading.php').then((rows) => {
      setAccounts(rows);
      if (rows.length > 0 && !rows.find((r) => r.id === activeId)) setActiveId(rows[0].id);
      if (rows.length === 0) setActiveId(null);
    }).catch(() => setAccounts([]));
  }, [activeId]);

  useEffect(() => { loadAccounts(); }, []); // eslint-disable-line react-hooks/exhaustive-deps

  const loadDetail = useCallback(() => {
    if (!activeId) { setDetail(null); return; }
    apiGet(`paper_trading.php?action=detail&id=${activeId}`).then(setDetail).catch(() => setDetail(null));
  }, [activeId]);

  useEffect(() => { loadDetail(); }, [loadDetail]);

  const loadHistory = useCallback(() => {
    if (!activeId) { setHistory(null); return; }
    setHistory(null);
    apiGet(`paper_trading.php?action=history&id=${activeId}`).then(setHistory).catch(() => setHistory([]));
  }, [activeId]);

  useEffect(() => { loadHistory(); }, [loadHistory]);

  // Ricerca ticker con debounce, stesso pattern di Markets.jsx.
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

  function openTrade(ticker, name, type) {
    setTradeTarget({ ticker, name, type });
    setTradeQuantity('');
    setTradeError('');
    setShowResults(false);
    setQuery('');
    setTradeQuote(null);
    setTradeIntraday([]);
    apiGet(`market_trends.php?action=quote&ticker=${encodeURIComponent(ticker)}`).then(setTradeQuote).catch(() => setTradeQuote(null));
    apiGet(`market_trends.php?action=intraday&ticker=${encodeURIComponent(ticker)}`).then(setTradeIntraday).catch(() => setTradeIntraday([]));
  }

  async function submitCreate(e) {
    e.preventDefault();
    setCreateError('');
    const startingBalance = parseFloat(createForm.starting_balance);
    if (!createForm.name.trim()) { setCreateError('Il nome è obbligatorio'); return; }
    if (!startingBalance || startingBalance <= 0) { setCreateError('Il capitale iniziale deve essere maggiore di zero'); return; }
    try {
      const res = await apiSend('paper_trading.php', 'POST', { name: createForm.name.trim(), starting_balance: startingBalance });
      setCreateOpen(false);
      setCreateForm({ name: '', starting_balance: '100000' });
      setActiveId(res.id);
      loadAccounts();
    } catch (err) {
      setCreateError(err.message);
    }
  }

  async function submitTrade(e) {
    e.preventDefault();
    setTradeError('');
    const quantity = parseFloat(tradeQuantity);
    if (!quantity || quantity <= 0) { setTradeError('Quantità non valida'); return; }
    setTradeLoading(true);
    try {
      await apiSend('paper_trading.php?action=trade', 'POST', {
        account_id: activeId, ticker: tradeTarget.ticker, name: tradeTarget.name, type: tradeTarget.type, quantity,
      });
      setTradeTarget(null);
      loadAccounts();
      loadDetail();
      loadHistory();
    } catch (err) {
      setTradeError(err.message);
    } finally {
      setTradeLoading(false);
    }
  }

  async function deleteTrade(id) {
    if (!confirm('Annullare questa operazione simulata? Il saldo e le posizioni del conto verranno ricalcolati.')) return;
    await apiDelete(`paper_trading.php?action=delete_trade&id=${id}`);
    loadAccounts();
    loadDetail();
    loadHistory();
  }

  async function resetAccount() {
    if (!confirm(`Azzerare "${detail.name}"? Tutte le operazioni verranno cancellate e il conto tornerà al capitale iniziale di ${euro(detail.starting_balance)}.`)) return;
    await apiSend('paper_trading.php?action=reset', 'POST', { account_id: activeId });
    loadAccounts();
    loadDetail();
    loadHistory();
  }

  async function deleteAccount() {
    if (!confirm(`Eliminare definitivamente il conto simulato "${detail.name}"?`)) return;
    await apiDelete(`paper_trading.php?id=${activeId}`);
    setActiveId(null);
    loadAccounts();
  }

  const previewNative = tradeQuote && tradeQuantity ? tradeQuote.price * parseFloat(tradeQuantity || 0) : null;

  return (
    <div>
      <div className="mb-6 flex items-start justify-between gap-4">
        <div>
          <h1 className="text-xl font-bold">Simulazione trading</h1>
          <p className="text-sm text-muted">Fai pratica sui mercati con capitale virtuale — nessun impatto sulle finanze reali.</p>
        </div>
      </div>

      <div className="mb-6 flex items-center gap-2 rounded-xl border border-purple-200 bg-purple-50 px-4 py-3 text-xs font-semibold text-purple-900">
        <FlaskConical size={15} className="flex-shrink-0" />
        SOLO SIMULAZIONE — questi conti e queste operazioni non toccano mai i tuoi conti, investimenti o transazioni reali.
      </div>

      {accounts === null && <p className="py-10 text-center text-sm text-muted">Caricamento...</p>}

      {accounts !== null && accounts.length === 0 && (
        <div className="rounded-2xl border border-dashed border-border p-10 text-center">
          <p className="mb-4 text-sm text-muted">Non hai ancora un conto simulato. Creane uno per iniziare a fare pratica.</p>
          <button className="btn-accent" onClick={() => setCreateOpen(true)}>+ Nuovo conto simulato</button>
        </div>
      )}

      {accounts !== null && accounts.length > 0 && (
        <>
          <div className="mb-4 flex items-center justify-between gap-3">
            <Tabs tabs={accounts.map((a) => ({ id: a.id, label: a.name }))} active={activeId} onChange={setActiveId} />
            <button className="btn-outline flex-shrink-0" onClick={() => setCreateOpen(true)}><Plus size={14} className="mr-1 inline" />Nuovo conto</button>
          </div>

          {detail && (
            <>
              <div className="mb-6 grid grid-cols-2 gap-3 sm:grid-cols-4">
                <StatCard label="Valore totale" value={euro(detail.total_value_eur)} hint={`Capitale iniziale ${euro(detail.starting_balance)}`} />
                <StatCard
                  label="Rendimento"
                  value={`${detail.total_gain_pct >= 0 ? '+' : ''}${detail.total_gain_pct.toFixed(2)}%`}
                  valueClass={detail.total_gain_pct >= 0 ? 'text-income' : 'text-expense'}
                />
                <StatCard label="Liquidità virtuale" value={euro(detail.cash_balance)} />
                <StatCard label="Valore posizioni" value={euro(detail.positions_value_eur)} />
              </div>

              <div className="mb-6 rounded-2xl border border-border bg-white p-5">
                <h3 className="mb-3 text-xs font-bold uppercase tracking-wider text-muted">Andamento del conto</h3>
                {history === null && <p className="py-10 text-center text-sm text-muted">Calcolo storico...</p>}
                {history !== null && history.length < 2 && (
                  <p className="py-10 text-center text-sm text-muted">
                    Il conto è stato creato oggi — lo storico si costruisce da solo giorno per giorno, torna a controllare domani.
                  </p>
                )}
                {history !== null && history.length >= 2 && (
                  <div className="h-56">
                    <Line
                      data={{
                        labels: history.map((p) => p.date),
                        datasets: [{
                          data: history.map((p) => p.total_value_eur),
                          borderColor: history[history.length - 1].total_value_eur >= history[0].total_value_eur ? '#10b981' : '#f43f5e',
                          backgroundColor: history[history.length - 1].total_value_eur >= history[0].total_value_eur ? 'rgba(16,185,129,0.08)' : 'rgba(244,63,94,0.08)',
                          fill: true,
                          tension: 0.25,
                          pointRadius: 0,
                          pointHoverRadius: 4,
                        }],
                      }}
                      options={{
                        responsive: true, maintainAspectRatio: false,
                        interaction: { mode: 'index', intersect: false },
                        plugins: {
                          legend: { display: false },
                          tooltip: { callbacks: { label: (ctx) => euro(ctx.parsed.y) } },
                        },
                        scales: {
                          x: { grid: { display: false }, ticks: { maxTicksLimit: 6, font: { size: 10 } } },
                          y: { grid: { color: '#e7e9f0' }, ticks: { font: { size: 10, family: '"IBM Plex Mono", monospace' }, callback: (v) => euro(v) } },
                        },
                      }}
                    />
                  </div>
                )}
                <p className="mt-2 text-[11px] text-muted">Ricostruito dai prezzi di chiusura storici reali — non uno snapshot, cambia se annulli o aggiungi operazioni passate.</p>
              </div>

              <div className="mb-3 flex items-center justify-end gap-2">
                <button className="btn-outline flex items-center gap-1.5 text-xs" onClick={resetAccount}><RotateCcw size={13} />Reset conto</button>
                <button className="btn-outline flex items-center gap-1.5 text-xs text-expense" onClick={deleteAccount}><Trash2 size={13} />Elimina conto</button>
              </div>

              {/* Ricerca titolo da negoziare */}
              <div className="relative mb-8">
                <Search size={16} className="pointer-events-none absolute left-4 top-1/2 -translate-y-1/2 text-muted" />
                <input
                  className="w-full rounded-xl border border-border bg-white py-3 pl-11 pr-4 text-sm text-gray-900 placeholder:text-muted outline-none transition focus:border-accent"
                  placeholder="Cerca un titolo da negoziare — es. Apple, VWCE, Bitcoin"
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
                        onClick={() => openTrade(r.ticker, r.name, 'acquisto')}
                        className="flex w-full items-center justify-between gap-2 border-b border-border px-4 py-2.5 text-left text-sm last:border-0 hover:bg-accentSoft"
                      >
                        <span className="text-gray-900">{r.name} <span className="text-xs text-muted">{r.exchange}</span></span>
                        <span className="flex-shrink-0 font-mono text-xs text-muted">{r.ticker}</span>
                      </button>
                    ))}
                  </div>
                )}
              </div>

              <h2 className="mb-3 text-sm font-bold text-gray-800">Posizioni aperte</h2>
              {detail.positions.length === 0 ? (
                <p className="mb-8 py-6 text-center text-sm text-muted">Nessuna posizione aperta — cerca un titolo sopra per comprarlo.</p>
              ) : (
                <div className="mb-8 overflow-x-auto rounded-2xl border border-border">
                  <table className="w-full min-w-[640px] border-collapse text-sm">
                    <thead>
                      <tr className="border-b border-border text-left text-[11px] uppercase tracking-wider text-muted">
                        <th className="px-3 py-2 font-semibold">Titolo</th>
                        <th className="px-3 py-2 text-right font-semibold">Quantità</th>
                        <th className="px-3 py-2 text-right font-semibold">Costo medio</th>
                        <th className="px-3 py-2 text-right font-semibold">Prezzo attuale</th>
                        <th className="px-3 py-2 text-right font-semibold">Valore (€)</th>
                        <th className="px-3 py-2 text-right font-semibold">Rendimento</th>
                        <th className="px-3 py-2"></th>
                      </tr>
                    </thead>
                    <tbody>
                      {detail.positions.map((p) => (
                        <tr key={p.ticker} className="border-b border-border last:border-0">
                          <td className="px-3 py-2">
                            <div className="font-semibold text-gray-900">{p.name}</div>
                            <div className="text-xs text-muted">{p.ticker}</div>
                          </td>
                          <td className="px-3 py-2 text-right font-mono">{fmtQty(p.quantity)}</td>
                          <td className="px-3 py-2 text-right font-mono">{euro(p.avg_cost, p.currency)}</td>
                          <td className="px-3 py-2 text-right font-mono">{p.current_price != null ? euro(p.current_price, p.currency) : '—'}</td>
                          <td className="px-3 py-2 text-right font-mono">{euro(p.market_value_eur)}</td>
                          <td className="px-3 py-2 text-right"><GainPill pct={p.unrealized_gain_pct} /></td>
                          <td className="px-3 py-2 text-right">
                            <button className="text-xs font-semibold text-expense hover:underline" onClick={() => openTrade(p.ticker, p.name, 'vendita')}>Vendi</button>
                          </td>
                        </tr>
                      ))}
                    </tbody>
                  </table>
                </div>
              )}

              <h2 className="mb-3 text-sm font-bold text-gray-800">Storico operazioni</h2>
              {detail.transactions.length === 0 ? (
                <p className="py-6 text-center text-sm text-muted">Ancora nessuna operazione in questo conto.</p>
              ) : (
                <div className="overflow-x-auto rounded-2xl border border-border">
                  <table className="w-full min-w-[600px] border-collapse text-sm">
                    <thead>
                      <tr className="border-b border-border text-left text-[11px] uppercase tracking-wider text-muted">
                        <th className="px-3 py-2 font-semibold">Data</th>
                        <th className="px-3 py-2 font-semibold">Titolo</th>
                        <th className="px-3 py-2 font-semibold">Tipo</th>
                        <th className="px-3 py-2 text-right font-semibold">Quantità</th>
                        <th className="px-3 py-2 text-right font-semibold">Prezzo</th>
                        <th className="px-3 py-2 text-right font-semibold">Commissione</th>
                        <th className="px-3 py-2 text-right font-semibold">Impatto cassa</th>
                        <th className="px-3 py-2"></th>
                      </tr>
                    </thead>
                    <tbody>
                      {detail.transactions.map((t) => (
                        <tr key={t.id} className="border-b border-border last:border-0">
                          <td className="px-3 py-2 text-xs text-muted">{fmtDateTime(t.executed_at)}</td>
                          <td className="px-3 py-2">
                            <div className="font-semibold text-gray-900">{t.name}</div>
                            <div className="text-xs text-muted">{t.ticker}</div>
                          </td>
                          <td className="px-3 py-2">
                            <span className={`inline-flex items-center gap-1 text-xs font-semibold ${t.type === 'acquisto' ? 'text-income' : 'text-expense'}`}>
                              {t.type === 'acquisto' ? <ArrowUpCircle size={12} /> : <ArrowDownCircle size={12} />}
                              {t.type === 'acquisto' ? 'Acquisto' : 'Vendita'}
                            </span>
                          </td>
                          <td className="px-3 py-2 text-right font-mono">{fmtQty(t.quantity)}</td>
                          <td className="px-3 py-2 text-right font-mono">{euro(parseFloat(t.price_per_unit), t.currency)}</td>
                          <td className="px-3 py-2 text-right font-mono text-muted">{euro(t.fees_eur)}</td>
                          <td className={`px-3 py-2 text-right font-mono ${parseFloat(t.cash_impact_eur) >= 0 ? 'text-income' : 'text-expense'}`}>
                            {parseFloat(t.cash_impact_eur) >= 0 ? '+' : ''}{euro(t.cash_impact_eur)}
                          </td>
                          <td className="px-3 py-2 text-right">
                            <button className="text-xs text-muted hover:text-expense" onClick={() => deleteTrade(t.id)}>Annulla</button>
                          </td>
                        </tr>
                      ))}
                    </tbody>
                  </table>
                </div>
              )}
            </>
          )}
        </>
      )}

      <Modal open={createOpen} onClose={() => setCreateOpen(false)} title="Nuovo conto simulato"
        footer={<>
          <button type="button" className="btn-outline" onClick={() => setCreateOpen(false)}>Annulla</button>
          <button type="submit" form="create-paper-account-form" className="btn-accent">Crea</button>
        </>}
      >
        {createError && <div className="mb-3 rounded-lg bg-red-50 px-3 py-2 text-sm text-red-600">{createError}</div>}
        <form id="create-paper-account-form" onSubmit={submitCreate} className="space-y-3">
          <div>
            <label className="field-label">Nome conto</label>
            <input className="field" placeholder="Es. Strategia growth" value={createForm.name} onChange={(e) => setCreateForm((f) => ({ ...f, name: e.target.value }))} />
          </div>
          <div>
            <label className="field-label">Capitale virtuale iniziale (€)</label>
            <input className="field" type="number" step="0.01" min="0.01" value={createForm.starting_balance} onChange={(e) => setCreateForm((f) => ({ ...f, starting_balance: e.target.value }))} />
          </div>
        </form>
      </Modal>

      <Modal open={!!tradeTarget} onClose={() => setTradeTarget(null)}
        title={tradeTarget ? `${tradeTarget.type === 'acquisto' ? 'Compra' : 'Vendi'} ${tradeTarget.name}` : ''}
        footer={<>
          <button type="button" className="btn-outline" onClick={() => setTradeTarget(null)}>Annulla</button>
          <button type="submit" form="paper-trade-form" className="btn-accent" disabled={tradeLoading}>
            {tradeLoading ? 'Esecuzione...' : (tradeTarget?.type === 'acquisto' ? 'Compra' : 'Vendi')}
          </button>
        </>}
      >
        {tradeTarget && (
          <>
            {tradeError && <div className="mb-3 rounded-lg bg-red-50 px-3 py-2 text-sm text-red-600">{tradeError}</div>}
            <div className="mb-3 flex items-baseline justify-between">
              <span className="text-xs text-muted">{tradeTarget.ticker}</span>
              {tradeQuote ? (
                <span className="font-mono text-lg font-bold text-gray-900">{euro(tradeQuote.price, tradeQuote.currency)}</span>
              ) : (
                <span className="text-xs text-muted">Quotazione in corso...</span>
              )}
            </div>
            {tradeIntraday.length > 1 && (
              <div className="mb-4 h-32">
                <Line
                  data={{
                    labels: tradeIntraday.map((p) => p.date),
                    datasets: [{ data: tradeIntraday.map((p) => p.price), borderColor: '#4f46e5', backgroundColor: 'rgba(79,70,229,0.08)', fill: true, tension: 0.25, pointRadius: 0 }],
                  }}
                  options={{
                    responsive: true, maintainAspectRatio: false,
                    plugins: { legend: { display: false } },
                    scales: { x: { display: false }, y: { display: false } },
                  }}
                />
              </div>
            )}
            <div className="flex gap-2">
              <button type="button" onClick={() => setTradeTarget((t) => ({ ...t, type: 'acquisto' }))}
                className={`flex-1 rounded-lg py-2 text-sm font-semibold ${tradeTarget.type === 'acquisto' ? 'bg-income text-white' : 'bg-gray-100 text-gray-600'}`}>
                Acquisto
              </button>
              <button type="button" onClick={() => setTradeTarget((t) => ({ ...t, type: 'vendita' }))}
                className={`flex-1 rounded-lg py-2 text-sm font-semibold ${tradeTarget.type === 'vendita' ? 'bg-expense text-white' : 'bg-gray-100 text-gray-600'}`}>
                Vendita
              </button>
            </div>
            <form id="paper-trade-form" onSubmit={submitTrade} className="mt-3 space-y-3">
              <div>
                <label className="field-label">Quantità</label>
                <input className="field" type="number" step="0.000001" min="0" required autoFocus value={tradeQuantity} onChange={(e) => setTradeQuantity(e.target.value)} />
              </div>
              {previewNative !== null && (
                <p className="text-xs text-muted">
                  Stima: {fmtQty(tradeQuantity)} × {euro(tradeQuote.price, tradeQuote.currency)} ≈ {euro(previewNative, tradeQuote.currency)}
                  {tradeQuote.currency !== 'EUR' && ' — convertito in € al cambio corrente al momento dell\'esecuzione'}
                  {' '}+ {euro(1)} di commissione simulata
                </p>
              )}
            </form>
          </>
        )}
      </Modal>
    </div>
  );
}
