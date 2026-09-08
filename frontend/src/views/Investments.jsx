import { useState, useEffect, useCallback } from 'react';
import { Plus, RefreshCw, Pencil, Trash, Trash2, LineChart as LineChartIcon, ChevronLeft, ChevronRight, Info } from 'lucide-react';
import { Doughnut, Line } from 'react-chartjs-2';
import { useData } from '../context/DataContext';
import { apiGet, apiSend, apiDelete } from '../api';
import { euro, formatDate, capitalize, todayISO } from '../lib/format';
import { matchInstrumentBrand } from '../lib/instrumentBrands';
import ServiceIcon from '../components/ServiceIcon';
import Modal from '../components/Modal';
import StatCard from '../components/StatCard';
import Tabs from '../components/Tabs';

const PALETTE = ['#4f46e5', '#10b981', '#f59e0b', '#f43f5e', '#0ea5e9', '#8b5cf6', '#ec4899', '#14b8a6'];

const TYPE_ORDER = ['etf', 'azione', 'obbligazione', 'fondo', 'crypto', 'altro'];
const TYPE_LABELS = { etf: 'ETF', azione: 'Azioni', obbligazione: 'Obbligazioni', fondo: 'Fondi', crypto: 'Crypto', altro: 'Altro' };

function groupByType(list, getType) {
  const groups = {};
  list.forEach((item) => {
    const t = getType(item);
    (groups[t] ||= []).push(item);
  });
  return TYPE_ORDER.filter((t) => groups[t]?.length).map((t) => ({ type: t, label: TYPE_LABELS[t], items: groups[t] }));
}

function fmt(n, currency) {
  try { return new Intl.NumberFormat('it-IT', { style: 'currency', currency }).format(n || 0); }
  catch { return euro(n); }
}

function pct(n) {
  if (n === null || n === undefined) return '—';
  return `${n > 0 ? '+' : ''}${n.toFixed(2)}%`;
}

/** Card per un singolo indicatore storico, con icona informativa (tooltip nativo via title). */
function IndicatorCard({ label, tooltip, value, valueClass = '', hint }) {
  return (
    <div className="card min-w-0 p-4">
      <div className="flex items-center gap-1.5 text-[11px] font-semibold uppercase tracking-wide text-muted">
        <span>{label}</span>
        <Info size={12} className="flex-shrink-0 cursor-help text-muted/70" title={tooltip} />
      </div>
      <div className={`mt-1 break-words font-mono text-lg font-semibold ${valueClass}`}>{value}</div>
      {hint && <div className="mt-1 text-xs text-muted">{hint}</div>}
    </div>
  );
}

const emptyInstrument = { id: null, ticker: '', name: '', type: 'etf', currency: 'EUR', tax_rate: 26 };
const emptyTx = {
  id: null, instrument_id: '', type: 'acquisto', quantity: '', price_per_unit: '', fees: 0, tax_amount: 0,
  date: todayISO(), notes: '', record_cash_movement: false, account_id: '',
};
const emptyPlan = {
  id: null, instrument_id: '', account_id: '', amount: '', fees: 0,
  frequency: 'mensile', interval_count: 1, start_date: todayISO(), end_date: '',
};

const TABS = [
  { id: 'posizioni', label: 'Posizioni' },
  { id: 'pac', label: 'PAC' },
  { id: 'strumenti', label: 'Strumenti' },
  { id: 'storico', label: 'Storico' },
  { id: 'immobili', label: 'Immobili' },
  { id: 'interessi', label: 'Interessi' },
];

const emptyRealEstate = { id: null, name: '', estimated_value: '', linked_loan_id: '', notes: '' };

export default function Investments() {
  const { instruments, refreshInstruments, activeAccounts, signal, bump } = useData();
  const [tab, setTab] = useState('posizioni');
  const [portfolio, setPortfolio] = useState(null);
  const [txRows, setTxRows] = useState([]);
  const [txPage, setTxPage] = useState(1);
  const TX_PAGE_SIZE = 10;
  const [plans, setPlans] = useState([]);
  const [interestAccounts, setInterestAccounts] = useState([]);
  const [portfolioHistory, setPortfolioHistory] = useState([]);
  const loadPortfolioHistory = useCallback(async () => setPortfolioHistory(await apiGet('portfolio_history.php')), []);
  const [interestSummary, setInterestSummary] = useState(null);
  const [processingInterest, setProcessingInterest] = useState(false);
  const [realEstate, setRealEstate] = useState([]);
  const [loans, setLoans] = useState([]);
  const [reModalOpen, setReModalOpen] = useState(false);
  const [reForm, setReForm] = useState(emptyRealEstate);
  const [reError, setReError] = useState('');

  const loadRealEstate = useCallback(async () => setRealEstate(await apiGet('real_estate.php')), []);
  const loadLoans = useCallback(async () => setLoans(await apiGet('loans.php')), []);

  function openNewRE() {
    setReForm(emptyRealEstate);
    setReError('');
    setReModalOpen(true);
  }
  function openEditRE(r) {
    setReForm({ id: r.id, name: r.name, estimated_value: r.estimated_value, linked_loan_id: r.linked_loan_id || '', notes: r.notes || '' });
    setReError('');
    setReModalOpen(true);
  }
  async function submitRE(e) {
    e.preventDefault();
    const payload = {
      name: reForm.name,
      estimated_value: parseFloat(reForm.estimated_value),
      linked_loan_id: reForm.linked_loan_id || null,
      notes: reForm.notes || null,
    };
    try {
      if (reForm.id) await apiSend('real_estate.php', 'PUT', { ...payload, id: reForm.id });
      else await apiSend('real_estate.php', 'POST', payload);
      setReModalOpen(false);
      loadRealEstate();
      bump();
    } catch (err) { setReError(err.message); }
  }
  async function deleteRE(id) {
    if (!confirm('Eliminare questo immobile dal patrimonio?')) return;
    await apiDelete(`real_estate.php?id=${id}`);
    loadRealEstate();
    bump();
  }
  async function deleteAllRE() {
    if (!realEstate.length) return;
    if (!confirm(`Eliminare tutti i ${realEstate.length} immobili?`)) return;
    await apiDelete('real_estate.php?action=bulk');
    loadRealEstate();
    bump();
  }

  const loadInterestAccounts = useCallback(async () => {
    const all = await apiGet('accounts.php');
    setInterestAccounts(all.filter((a) => a.interest_enabled));
    setInterestSummary(await apiGet('accounts.php?action=interest_summary'));
  }, []);

  async function processInterest() {
    setProcessingInterest(true);
    try {
      const res = await apiSend('accounts.php?action=process_interest', 'POST', {});
      let msg = `${res.created} accrediti di interesse generati (calcolati sul saldo attuale per ogni periodo arretrato).`;
      if (res.capped?.length) msg += `\n\nQuesti conti avevano più periodi arretrati del limite gestibile in un click — premi di nuovo per continuare:\n${res.capped.join('\n')}`;
      alert(msg);
      loadInterestAccounts(); bump();
    } finally {
      setProcessingInterest(false);
    }
  }

  const [instModalOpen, setInstModalOpen] = useState(false);
  const [instForm, setInstForm] = useState(emptyInstrument);
  const [instError, setInstError] = useState('');
  const [searchQuery, setSearchQuery] = useState('');
  const [searchResults, setSearchResults] = useState([]);
  const [searching, setSearching] = useState(false);
  const [showSearchResults, setShowSearchResults] = useState(false);

  const [txModalOpen, setTxModalOpen] = useState(false);
  const [txForm, setTxForm] = useState(emptyTx);
  const [txError, setTxError] = useState('');

  const [planModalOpen, setPlanModalOpen] = useState(false);
  const [planForm, setPlanForm] = useState(emptyPlan);
  const [planError, setPlanError] = useState('');
  const [processingPlans, setProcessingPlans] = useState(false);
  const [taxBreakdown, setTaxBreakdown] = useState(null);
  const [priceChartOpen, setPriceChartOpen] = useState(false);
  const [priceChartInstrument, setPriceChartInstrument] = useState(null);
  const [priceHistory, setPriceHistory] = useState([]);
  const [priceChartDays, setPriceChartDays] = useState(90);
  const [instrumentMetrics, setInstrumentMetrics] = useState(null);
  const [analystData, setAnalystData] = useState(null);

  async function openPriceChart(instrument) {
    setPriceChartInstrument(instrument);
    setPriceChartOpen(true);
    setInstrumentMetrics(null);
    setAnalystData(null);
    const data = await apiGet(`price_history.php?instrument_id=${instrument.id}&days=${priceChartDays}`);
    setPriceHistory(data);
    apiGet(`instrument_metrics.php?instrument_id=${instrument.id}`).then(setInstrumentMetrics).catch(() => setInstrumentMetrics(null));
    apiGet(`instrument_analyst.php?instrument_id=${instrument.id}`).then(setAnalystData).catch(() => setAnalystData(null));
  }

  useEffect(() => {
    if (priceChartOpen && priceChartInstrument) {
      apiGet(`price_history.php?instrument_id=${priceChartInstrument.id}&days=${priceChartDays}`).then(setPriceHistory);
    }
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [priceChartDays]);

  const loadPortfolio = useCallback(async (refresh = false) => {
    setPortfolio(await apiGet(`portfolio.php${refresh ? '?refresh=1' : ''}`));
  }, []);
  const loadTx = useCallback(async () => setTxRows(await apiGet('investments.php')), []);
  const loadPlans = useCallback(async () => setPlans(await apiGet('investment_plans.php')), []);

  useEffect(() => { loadPortfolio(); loadTx(); loadPlans(); loadInterestAccounts(); loadPortfolioHistory(); loadRealEstate(); loadLoans(); }, [loadPortfolio, loadTx, loadPlans, loadInterestAccounts, loadPortfolioHistory, loadRealEstate, loadLoans, signal]);

  function openNewPlan() {
    setPlanForm({ ...emptyPlan, instrument_id: instruments[0]?.id || '', account_id: activeAccounts[0]?.id || '' });
    setPlanError('');
    setPlanModalOpen(true);
  }

  function openEditPlan(p) {
    setPlanForm({
      id: p.id, instrument_id: p.instrument_id, account_id: p.account_id, amount: p.amount,
      fees: p.fees, frequency: p.frequency, interval_count: p.interval_count,
      start_date: p.start_date, end_date: p.end_date || '',
    });
    setPlanError('');
    setPlanModalOpen(true);
  }

  async function submitPlan(e) {
    e.preventDefault();
    setPlanError('');
    const payload = {
      instrument_id: planForm.instrument_id,
      account_id: planForm.account_id,
      amount: parseFloat(planForm.amount),
      fees: parseFloat(planForm.fees || 0),
      frequency: planForm.frequency,
      interval_count: parseInt(planForm.interval_count || '1', 10),
      start_date: planForm.start_date,
      end_date: planForm.end_date || null,
    };
    try {
      if (planForm.id) await apiSend('investment_plans.php', 'PUT', { ...payload, id: planForm.id });
      else await apiSend('investment_plans.php', 'POST', payload);
      setPlanModalOpen(false);
      loadPlans();
    } catch (err) { setPlanError(err.message); }
  }

  async function togglePlan(p) {
    await apiSend('investment_plans.php', 'PUT', { id: p.id, active: p.active ? 0 : 1 });
    loadPlans();
  }

  async function deletePlan(id) {
    if (!confirm('Eliminare questo PAC? Gli acquisti già generati restano nello storico.')) return;
    await apiDelete(`investment_plans.php?id=${id}`);
    loadPlans();
  }

  async function processPlans() {
    setProcessingPlans(true);
    try {
      const res = await apiSend('investment_plans.php?action=process', 'POST', {});
      let msg = `${res.created} acquisti generati, ciascuno con il prezzo di chiusura reale della propria data.`;
      if (res.skipped?.length) msg += `\n\nNon è stato possibile ottenere il prezzo storico per: ${res.skipped.join(', ')} — il piano riprenderà da lì al prossimo tentativo.`;
      if (res.capped?.length) msg += `\n\nQuesti piani avevano più rate arretrate del limite gestibile in un solo click — premi di nuovo il pulsante per recuperare il resto:\n${res.capped.join('\n')}`;
      alert(msg);
      loadPlans(); loadPortfolio(); loadTx(); bump();
    } finally {
      setProcessingPlans(false);
    }
  }

  function openNewInstrument() {
    setInstForm(emptyInstrument);
    setInstError('');
    setSearchQuery('');
    setSearchResults([]);
    setShowSearchResults(false);
    setInstModalOpen(true);
  }

  // Ricerca con debounce: aspetta che l'utente smetta di digitare per 350ms
  // prima di interrogare Yahoo Finance, per non fare una richiesta ad ogni lettera.
  useEffect(() => {
    if (searchQuery.trim().length < 2) { setSearchResults([]); return; }
    setSearching(true);
    const handle = setTimeout(async () => {
      try {
        const res = await apiGet(`instrument_search.php?q=${encodeURIComponent(searchQuery.trim())}`);
        setSearchResults(res);
      } catch {
        setSearchResults([]);
      } finally {
        setSearching(false);
      }
    }, 350);
    return () => clearTimeout(handle);
  }, [searchQuery]);

  function pickSearchResult(r) {
    setInstForm({
      id: null,
      ticker: r.ticker,
      name: r.name,
      type: r.type,
      currency: r.currency || 'EUR',
    });
    setShowSearchResults(false);
    setSearchQuery('');
  }
  function openEditInstrument(i) { setInstForm(i); setInstError(''); setInstModalOpen(true); }

  async function submitInstrument(e) {
    e.preventDefault();
    const payload = { ticker: instForm.ticker.trim().toUpperCase(), name: instForm.name, type: instForm.type, currency: instForm.currency, tax_rate: parseFloat(instForm.tax_rate ?? 26) };
    try {
      if (instForm.id) await apiSend('instruments.php', 'PUT', { ...payload, id: instForm.id });
      else await apiSend('instruments.php', 'POST', payload);
      setInstModalOpen(false);
      await refreshInstruments();
      loadPortfolio();
    } catch (err) { setInstError(err.message); }
  }

  async function deleteInstrument(id) {
    if (!confirm('Eliminare questo strumento? Verranno eliminate anche tutte le operazioni collegate.')) return;
    await apiDelete(`instruments.php?id=${id}`);
    await refreshInstruments();
    loadPortfolio();
    loadTx();
  }

  function openNewTx() {
    setTxForm({ ...emptyTx, instrument_id: instruments[0]?.id || '', account_id: activeAccounts[0]?.id || '' });
    setTxError('');
    setTaxBreakdown(null);
    setTxModalOpen(true);
  }
  function openEditTx(t) {
    setTxForm({
      id: t.id, instrument_id: t.instrument_id, type: t.type, quantity: t.quantity, price_per_unit: t.price_per_unit,
      fees: t.fees, tax_amount: t.tax_amount || 0, date: t.date, notes: t.notes || '', record_cash_movement: false, account_id: '',
    });
    setTxError('');
    setTaxBreakdown(null);
    setTxModalOpen(true);
  }

  async function recalcTax() {
    if (!txForm.instrument_id || !txForm.quantity || !txForm.price_per_unit) {
      setTxError('Compila strumento, quantità e prezzo prima di calcolare l\'imposta');
      return;
    }
    try {
      const res = await apiSend('investments.php?action=preview_tax', 'POST', {
        instrument_id: txForm.instrument_id,
        quantity: parseFloat(txForm.quantity),
        price_per_unit: parseFloat(txForm.price_per_unit),
        fees: parseFloat(txForm.fees || '0'),
        exclude_id: txForm.id || null,
      });
      setTxForm((f) => ({ ...f, tax_amount: res.tax_amount }));
      setTaxBreakdown(res);
      setTxError('');
    } catch (err) {
      setTxError(err.message);
    }
  }

  async function submitTx(e) {
    e.preventDefault();
    const payload = {
      instrument_id: txForm.instrument_id, type: txForm.type, quantity: parseFloat(txForm.quantity),
      price_per_unit: parseFloat(txForm.price_per_unit), fees: parseFloat(txForm.fees || '0'),
      tax_amount: parseFloat(txForm.tax_amount || '0'),
      date: txForm.date, notes: txForm.notes,
      record_cash_movement: txForm.record_cash_movement, account_id: txForm.record_cash_movement ? txForm.account_id : null,
    };
    try {
      if (txForm.id) {
        const res = await apiSend('investments.php', 'PUT', { ...payload, id: txForm.id });
        if (res.linked_transaction_adjusted) {
          alert('Salvato. Il movimento di cassa collegato in Transazioni è stato aggiornato di conseguenza, per restare allineato.');
        }
      } else {
        await apiSend('investments.php', 'POST', payload);
      }
      setTxModalOpen(false);
      loadPortfolio();
      loadTx();
      bump();
    } catch (err) { setTxError(err.message); }
  }

  async function deleteTx(id) {
    const deleteLinked = confirm("Eliminare anche l'eventuale movimento di cassa collegato? Annulla = elimina solo l'operazione.");
    await apiDelete(`investments.php?id=${id}&delete_linked=${deleteLinked ? 1 : 0}`);
    loadPortfolio();
    loadTx();
    bump();
  }

  async function deleteAllInstruments() {
    if (!instruments.length) return;
    if (!confirm(`Eliminare tutti i ${instruments.length} strumenti? Verranno eliminate anche tutte le operazioni collegate.`)) return;
    await apiDelete('instruments.php?action=bulk');
    await refreshInstruments();
    loadPortfolio();
    loadTx();
  }

  async function deleteAllPlans() {
    if (!plans.length) return;
    if (!confirm(`Eliminare tutti i ${plans.length} PAC? Gli acquisti già generati restano nello storico.`)) return;
    await apiDelete('investment_plans.php?action=bulk');
    loadPlans();
  }

  async function deleteAllTx() {
    if (!txRows.length) return;
    const deleteLinked = confirm("Eliminare anche i movimenti di cassa collegati? Annulla = elimina solo le operazioni.");
    if (!confirm(`Eliminare tutte le ${txRows.length} operazioni dello storico?`)) return;
    await apiDelete(`investments.php?action=bulk&delete_linked=${deleteLinked ? 1 : 0}`);
    loadPortfolio();
    loadTx();
    bump();
  }

  if (!portfolio) return <div className="text-muted">Caricamento...</div>;

  const held = portfolio.positions.filter((p) => p.quantity > 0.000001);
  const heldWithPrice = held.filter((p) => p.current_value !== null);
  const currencies = Object.keys(portfolio.totals_by_currency);
  const heldByType = groupByType(held, (p) => p.instrument.type);
  const instrumentsByType = groupByType(instruments, (i) => i.type);

  const tabCounts = {
    posizioni: held.length,
    pac: plans.length,
    strumenti: instruments.length,
    storico: txRows.length,
    immobili: realEstate.length,
    interessi: interestAccounts.length,
  };

  const realEstateShare = (r) => {
    if (!r.linked_loan_id) return parseFloat(r.estimated_value);
    const linkedLoan = loans.find((l) => l.id === r.linked_loan_id);
    const pct = linkedLoan ? parseFloat(linkedLoan.ownership_pct) : 100;
    return parseFloat(r.estimated_value) * pct / 100;
  };
  const realEstateTotal = realEstate.reduce((sum, r) => sum + realEstateShare(r), 0);

  return (
    <div>
      <div className="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <h4 className="text-xl font-bold">Investimenti</h4>
        <div className="flex flex-wrap gap-2">
          <button className="btn-outline" onClick={() => loadPortfolio(true)}><RefreshCw size={15} /> Aggiorna quotazioni</button>
          <button className="btn-outline" onClick={openNewInstrument}><Plus size={15} /> Strumento</button>
          <button className="btn-outline" onClick={openNewPlan}><Plus size={15} /> PAC</button>
          <button className="btn-accent" onClick={openNewTx}><Plus size={15} /> Operazione</button>
        </div>
      </div>

      {portfolioHistory.length >= 2 && (
        <div className="card mb-4 p-5">
          <h6 className="mb-3 text-sm font-semibold">Andamento complessivo del portafoglio</h6>
          <div className="h-56">
            <Line
              data={{
                labels: portfolioHistory.map((p) => formatDate(p.date)),
                datasets: [{
                  label: 'Valore portafoglio',
                  data: portfolioHistory.map((p) => p.value),
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
                scales: { y: { ticks: { callback: (v) => euro(v) } } },
              }}
            />
          </div>
        </div>
      )}

      <div className="mb-4 rounded-xl border border-accent/20 bg-accentSoft px-4 py-3 text-sm text-gray-700">
        Questa sezione segue i tuoi ETF, azioni, fondi o crypto con <span className="font-semibold">quotazioni in tempo reale da Yahoo Finance</span>.
        Prima aggiungi lo <span className="font-semibold">strumento</span> (il titolo che vuoi seguire — cercalo per nome, non serve conoscere il ticker),
        poi registra le tue <span className="font-semibold">operazioni</span> di acquisto/vendita per costruire la posizione.
      </div>

      <div className="mb-4 grid grid-cols-2 gap-3 md:grid-cols-4">
        {currencies.length ? currencies.map((cur) => {
          const t = portfolio.totals_by_currency[cur];
          const gainCls = t.unrealized_gain >= 0 ? 'text-income' : 'text-expense';
          return (
            <div key={cur} className="contents">
              <StatCard label={`Investito (${cur})`} value={fmt(t.invested, cur)} />
              <StatCard label={`Valore attuale (${cur})`} value={fmt(t.current_value, cur)} />
              <StatCard label={`Plus/minus (${cur})`} value={fmt(t.unrealized_gain, cur)} valueClass={gainCls} />
            </div>
          );
        }) : null}
        <StatCard label="Plusvalenze realizzate (lorde)" value={euro(portfolio.realized_gain_total)} />
        <StatCard label="Imposte pagate su vendite" value={euro(portfolio.tax_paid_total)} />
        <StatCard label="Plusvalenze realizzate (nette)" value={euro(portfolio.realized_gain_net)} valueClass={portfolio.realized_gain_net >= 0 ? 'text-income' : 'text-expense'} />
        {currencies.length > 1 && (
          <div className="col-span-full text-xs text-muted">Nota: i totali non vengono convertiti in un&apos;unica valuta, sono sommati separatamente per valuta di quotazione.</div>
        )}
      </div>

      <Tabs tabs={TABS.map((t) => ({ ...t, count: tabCounts[t.id] }))} active={tab} onChange={setTab} />

      {tab === 'posizioni' && (
        <div className="grid grid-cols-1 gap-4 lg:grid-cols-3">
          <div className="space-y-5 lg:col-span-2">
            {held.length ? heldByType.map((group) => (
              <div key={group.type}>
                <h6 className="mb-2 flex items-center gap-2 text-xs font-semibold uppercase tracking-wide text-muted">
                  {group.label} <span className="font-normal normal-case">({group.items.length})</span>
                </h6>
                <div className="space-y-3">
                  {group.items.map((p) => {
                    const i = p.instrument;
                    const gainCls = (p.unrealized_gain || 0) >= 0 ? 'text-income' : 'text-expense';
                    return (
                      <div key={i.id} className="card border-l-4 border-l-accent p-4">
                        <div className="flex items-start justify-between">
                          <div className="font-mono text-xs text-muted">{i.ticker}</div>
                          <button onClick={() => openEditInstrument(i)} className="text-muted hover:text-accent"><Pencil size={14} /></button>
                        </div>
                        <div className="mb-1 flex items-center gap-2 font-semibold">
                          {(() => { const brand = matchInstrumentBrand(i); return brand ? <ServiceIcon name={i.name} domain={brand.domain} color={brand.color} size={20} /> : null; })()}
                          {i.name}
                        </div>
                        <div className="font-mono text-lg font-semibold">{p.current_value !== null ? fmt(p.current_value, p.price_currency) : fmt(p.cost_basis, i.currency)}</div>
                        {p.unrealized_gain !== null && (
                          <div className={`font-mono text-sm font-semibold ${gainCls}`}>{p.unrealized_gain >= 0 ? '+' : ''}{fmt(p.unrealized_gain, p.price_currency)} ({p.unrealized_gain_pct}%)</div>
                        )}
                        <div className="mt-1.5 text-xs text-muted">Quantità: {p.quantity} · Prezzo medio: {fmt(p.avg_cost, i.currency)}</div>
                        <div className="text-xs text-muted">
                          Prezzo attuale: {p.live_price !== null ? <>{fmt(p.live_price, p.price_currency)}{p.price_stale && <span className="text-loan"> (non aggiornato)</span>}</> : 'Non disponibile'}
                        </div>
                      </div>
                    );
                  })}
                </div>
              </div>
            )) : <div className="text-muted">Nessuna posizione aperta. Registra un acquisto per iniziare.</div>}
          </div>
          <div className="card p-5">
            <h6 className="mb-3 text-sm font-semibold">Allocazione portafoglio</h6>
            {heldWithPrice.length ? (
              <Doughnut
                data={{
                  labels: heldWithPrice.map((p) => p.instrument.ticker),
                  datasets: [{ data: heldWithPrice.map((p) => p.current_value), backgroundColor: heldWithPrice.map((_, idx) => PALETTE[idx % PALETTE.length]), borderColor: '#fff', borderWidth: 2 }],
                }}
                options={{ responsive: true, plugins: { legend: { position: 'bottom', labels: { boxWidth: 12, padding: 12 } } } }}
              />
            ) : <div className="py-8 text-center text-sm text-muted">Nessun dato disponibile</div>}
          </div>
        </div>
      )}

      {tab === 'interessi' && (
        <div>
          <div className="mb-3 flex items-center justify-between">
            <h5 className="font-semibold">Interessi sui conti</h5>
            {interestAccounts.length > 0 && (
              <button className="btn-outline text-xs" onClick={processInterest} disabled={processingInterest}>
                <RefreshCw size={13} /> {processingInterest ? 'Generazione...' : 'Genera interessi maturati'}
              </button>
            )}
          </div>

          {interestSummary && (interestSummary.total_earned_to_date > 0 || interestAccounts.length > 0) && (
            <div className="mb-4 grid grid-cols-1 gap-3 sm:grid-cols-2">
              <StatCard label="Interessi maturati da inizio a oggi" value={euro(interestSummary.total_earned_to_date)} valueClass="text-income" />
              <StatCard label="Proiezione prossimi 12 mesi (ai tassi attuali)" value={euro(interestSummary.total_projected_annual)} />
            </div>
          )}

          <div className="card p-5">
            {interestAccounts.length === 0 ? (
              <p className="text-sm text-muted">
                Nessun conto ha gli interessi attivi. Vai su <strong>Conti</strong>, modifica un conto corrente,
                deposito o vincolato, e attiva "Matura interessi" indicando il tasso — apparirà qui, con
                l'interesse che si accredita da solo come vera transazione sul saldo del conto.
              </p>
            ) : (
              <>
                <div className="mb-3 grid grid-cols-1 gap-3 sm:grid-cols-2">
                  <StatCard label="Capitale in conti con interesse" value={euro(interestAccounts.reduce((s, a) => s + parseFloat(a.balance), 0))} />
                  <StatCard label="Interesse netto annuo proiettato" value={euro(interestAccounts.reduce((s, a) => s + (a.projected_net_annual_interest || 0), 0))} valueClass="text-income" />
                </div>
                <div className="space-y-2">
                  {interestAccounts.map((a) => (
                    <div key={a.id} className="flex flex-col gap-1 rounded-xl border border-border px-4 py-3 sm:flex-row sm:items-center sm:justify-between">
                      <div>
                        <div className="font-medium">{a.name}</div>
                        <div className="text-xs text-muted">
                          Saldo attuale {euro(a.balance)} al {a.interest_rate}% lordo ({a.interest_frequency}) ·
                          netto stimato {euro(a.projected_net_annual_interest)}/anno (imposta {a.interest_tax_rate}%)
                          {a.next_interest_date && ` · prossimo accredito ${formatDate(a.next_interest_date)}`}
                        </div>
                      </div>
                    </div>
                  ))}
                </div>
                <p className="mt-3 text-xs text-muted">Per modificare tasso o frequenza, vai su Conti e modifica il conto interessato.</p>
              </>
            )}
          </div>
        </div>
      )}

      {tab === 'pac' && (
        <div>
          <div className="mb-3 flex items-center justify-between">
            <h5 className="font-semibold">Piani di accumulo (PAC)</h5>
            <div className="flex flex-wrap gap-2">
              {plans.length > 0 && (
                <button className="btn-outline text-xs text-expense" onClick={deleteAllPlans}><Trash size={13} /> Elimina tutti</button>
              )}
              {plans.some((p) => p.active) && (
                <button className="btn-outline text-xs" onClick={processPlans} disabled={processingPlans}>
                  <RefreshCw size={13} /> {processingPlans ? 'Generazione...' : 'Genera acquisti dovuti'}
                </button>
              )}
            </div>
          </div>
          <div className="card p-5">
            {plans.length === 0 ? (
              <p className="text-sm text-muted">
                Nessun PAC impostato. Un piano di accumulo investe automaticamente un importo fisso a
                intervalli regolari — l'app calcola da sola quante quote comprare in base al prezzo del
                giorno, senza bisogno di registrare ogni acquisto a mano.
              </p>
            ) : (
              <div className="space-y-2">
                {plans.map((p) => (
                  <div key={p.id} className={`flex flex-col gap-2 rounded-xl border px-4 py-3 sm:flex-row sm:items-center sm:justify-between ${p.active ? 'border-border' : 'border-border opacity-50'}`}>
                    <div>
                      <div className="font-medium">{p.instrument_name} <span className="font-mono text-xs text-muted">{p.ticker}</span></div>
                      <div className="text-xs text-muted">
                        {euro(p.amount)} · {p.frequency} · dal conto {p.account_name} · prossimo {formatDate(p.next_run)}
                        {!p.active && ' · in pausa'}
                      </div>
                    </div>
                    <div className="flex flex-shrink-0 flex-wrap gap-2">
                      <button className="btn-outline text-xs" onClick={() => openEditPlan(p)}><Pencil size={13} /></button>
                      <button className="btn-outline text-xs" onClick={() => togglePlan(p)}>{p.active ? 'Metti in pausa' : 'Riattiva'}</button>
                      <button className="btn-outline-danger text-xs" onClick={() => deletePlan(p.id)}><Trash2 size={13} /></button>
                    </div>
                  </div>
                ))}
              </div>
            )}
          </div>
        </div>
      )}

      {tab === 'strumenti' && (
        <div>
          <div className="mb-3 flex items-center justify-between">
            <h5 className="font-semibold">Strumenti registrati</h5>
            {instruments.length > 0 && (
              <button className="btn-outline text-xs text-expense" onClick={deleteAllInstruments}><Trash size={13} /> Elimina tutti</button>
            )}
          </div>
          {instruments.length ? instrumentsByType.map((group) => (
            <div key={group.type} className="mb-4">
              <h6 className="mb-2 text-xs font-semibold uppercase tracking-wide text-muted">{group.label} <span className="font-normal normal-case">({group.items.length})</span></h6>
              <div className="card overflow-hidden">
                <div className="overflow-x-auto">
                <table className="w-full text-sm">
                  <thead>
                    <tr className="border-b border-border text-left text-[11px] uppercase tracking-wide text-muted">
                      <th className="px-4 py-3">Ticker</th><th className="px-4 py-3">Nome</th><th className="px-4 py-3">Valuta</th><th className="px-4 py-3">Aliquota</th><th></th>
                    </tr>
                  </thead>
                  <tbody>
                    {group.items.map((i) => (
                      <tr key={i.id} className="border-b border-border last:border-0">
                        <td className="px-4 py-3 font-semibold">{i.ticker}</td>
                        <td className="px-4 py-3">{i.name}</td>
                        <td className="px-4 py-3">{i.currency}</td>
                        <td className="px-4 py-3">{i.tax_rate}%</td>
                        <td className="px-4 py-3 text-right">
                          <button onClick={() => openPriceChart(i)} className="mr-2 text-muted hover:text-accent" title="Andamento prezzo"><LineChartIcon size={15} /></button>
                          <button onClick={() => openEditInstrument(i)} className="mr-2 text-muted hover:text-accent"><Pencil size={15} /></button>
                          <button onClick={() => deleteInstrument(i.id)} className="text-muted hover:text-expense"><Trash2 size={15} /></button>
                        </td>
                      </tr>
                    ))}
                  </tbody>
                </table>
                </div>
              </div>
            </div>
          )) : <div className="card p-8 text-center text-muted">Nessuno strumento registrato</div>}
        </div>
      )}

      {tab === 'storico' && (
        <div>
          <div className="mb-3 flex items-center justify-between">
            <h5 className="font-semibold">Storico operazioni</h5>
            {txRows.length > 0 && (
              <button className="btn-outline text-xs text-expense" onClick={deleteAllTx}><Trash size={13} /> Elimina tutte</button>
            )}
          </div>
          <div className="card overflow-hidden">
            <div className="overflow-x-auto">
            <table className="w-full text-sm">
              <thead>
                <tr className="border-b border-border text-left text-[11px] uppercase tracking-wide text-muted">
                  <th className="px-4 py-3">Data</th><th className="px-4 py-3">Ticker</th><th className="px-4 py-3">Strumento</th><th className="px-4 py-3">Tipo</th>
                  <th className="px-4 py-3 text-right">Quantità</th><th className="px-4 py-3 text-right">Prezzo</th><th className="px-4 py-3 text-right">Imposta</th><th className="px-4 py-3 text-right">Totale</th><th></th>
                </tr>
              </thead>
              <tbody>
                {txRows.length ? txRows.slice((txPage - 1) * TX_PAGE_SIZE, txPage * TX_PAGE_SIZE).map((t) => {
                  const total = t.type === 'acquisto'
                    ? t.quantity * t.price_per_unit + parseFloat(t.fees || 0)
                    : t.quantity * t.price_per_unit - parseFloat(t.fees || 0) - parseFloat(t.tax_amount || 0);
                  const cls = t.type === 'acquisto' ? 'text-expense' : 'text-income';
                  return (
                    <tr key={t.id} className="border-b border-border last:border-0">
                      <td className="px-4 py-3">{formatDate(t.date)}</td>
                      <td className="px-4 py-3">{t.ticker}</td>
                      <td className="px-4 py-3">{t.instrument_name}</td>
                      <td className="px-4 py-3">{capitalize(t.type)}</td>
                      <td className="px-4 py-3 text-right font-mono">{t.quantity}</td>
                      <td className="px-4 py-3 text-right font-mono">{euro(t.price_per_unit)}</td>
                      <td className="px-4 py-3 text-right font-mono text-muted">{parseFloat(t.tax_amount || 0) > 0 ? euro(t.tax_amount) : '—'}</td>
                      <td className={`px-4 py-3 text-right font-mono font-semibold ${cls}`}>{euro(total)}</td>
                      <td className="px-4 py-3 text-right">
                        <button onClick={() => openEditTx(t)} className="mr-2 text-muted hover:text-accent"><Pencil size={15} /></button>
                        <button onClick={() => deleteTx(t.id)} className="text-muted hover:text-expense"><Trash2 size={15} /></button>
                      </td>
                    </tr>
                  );
                }) : <tr><td colSpan={9} className="px-4 py-8 text-center text-muted">Nessuna operazione registrata</td></tr>}
              </tbody>
            </table>
            </div>

            {txRows.length > TX_PAGE_SIZE && (
              <div className="flex items-center justify-between border-t border-border px-4 py-3 text-sm">
                <span className="text-muted">{txRows.length} operazioni — pagina {txPage} di {Math.ceil(txRows.length / TX_PAGE_SIZE)}</span>
                <div className="flex gap-2">
                  <button className="btn-outline" disabled={txPage === 1} onClick={() => setTxPage((p) => p - 1)}><ChevronLeft size={15} /></button>
                  <button className="btn-outline" disabled={txPage >= Math.ceil(txRows.length / TX_PAGE_SIZE)} onClick={() => setTxPage((p) => p + 1)}><ChevronRight size={15} /></button>
                </div>
              </div>
            )}
          </div>
        </div>
      )}

      {tab === 'immobili' && (
        <div>
          <div className="mb-3 flex items-center justify-between">
            <h5 className="font-semibold">Immobili</h5>
            <div className="flex flex-wrap gap-2">
              {realEstate.length > 0 && (
                <button className="btn-outline text-xs text-expense" onClick={deleteAllRE}><Trash size={13} /> Elimina tutti</button>
              )}
              <button className="btn-accent text-xs" onClick={openNewRE}><Plus size={13} /> Immobile</button>
            </div>
          </div>

          <div className="mb-4 rounded-xl border border-accent/20 bg-accentSoft px-4 py-3 text-sm text-gray-700">
            Gli immobili che aggiungi qui confluiscono automaticamente nel <span className="font-semibold">Patrimonio netto</span> in
            Dashboard — se un immobile è collegato a un mutuo, viene contata solo la tua quota di proprietà, coerente col resto dell'app.
          </div>

          {realEstate.length > 0 && (
            <StatCard label="Valore totale immobili (tua quota)" value={euro(realEstateTotal)} />
          )}

          <div className="card mt-4 p-5">
            {realEstate.length === 0 ? (
              <p className="text-sm text-muted">Nessun immobile tracciato ancora. Aggiungine uno per includerlo nel patrimonio netto.</p>
            ) : (
              <div className="space-y-2">
                {realEstate.map((r) => {
                  const share = realEstateShare(r);
                  const isPartial = Math.abs(share - parseFloat(r.estimated_value)) > 0.01;
                  return (
                    <div key={r.id} className="flex flex-col gap-2 rounded-xl border border-border px-4 py-3 sm:flex-row sm:items-center sm:justify-between">
                      <div className="min-w-0">
                        <div className="font-medium">{r.name}{r.loan_name ? ` (mutuo: ${r.loan_name})` : ''}</div>
                        {r.notes && <div className="text-xs text-muted">{r.notes}</div>}
                      </div>
                      <div className="flex flex-shrink-0 items-center gap-3">
                        <span className="font-mono text-sm font-semibold">
                          {isPartial ? <>{euro(share)} <span className="font-normal text-xs text-muted">(quota di {euro(r.estimated_value)})</span></> : euro(r.estimated_value)}
                        </span>
                        <button className="text-muted hover:text-accent" onClick={() => openEditRE(r)}><Pencil size={14} /></button>
                        <button className="text-muted hover:text-expense" onClick={() => deleteRE(r.id)}><Trash2 size={14} /></button>
                      </div>
                    </div>
                  );
                })}
              </div>
            )}
          </div>
        </div>
      )}

      {/* Modale strumento */}
      <Modal open={instModalOpen} onClose={() => setInstModalOpen(false)} title={instForm.id ? 'Modifica strumento' : 'Nuovo strumento finanziario'}
        footer={<>
          <button type="button" className="btn-outline" onClick={() => setInstModalOpen(false)}>Annulla</button>
          <button type="submit" form="instrument-form" className="btn-accent">Salva</button>
        </>}
      >
        {instError && <div className="mb-3 rounded-lg bg-red-50 px-3 py-2 text-sm text-red-600">{instError}</div>}

        {!instForm.id && (
          <div className="relative mb-4">
            <label className="field-label">Cerca per nome (es. "Vanguard All World", "Apple", "Bitcoin")</label>
            <input
              className="field"
              placeholder="Digita il nome del titolo o fondo..."
              value={searchQuery}
              onChange={(e) => { setSearchQuery(e.target.value); setShowSearchResults(true); }}
              onFocus={() => setShowSearchResults(true)}
            />
            {showSearchResults && searchQuery.trim().length >= 2 && (
              <div className="absolute z-10 mt-1 max-h-64 w-full overflow-y-auto rounded-xl border border-border bg-white shadow-lg">
                {searching && <div className="px-3 py-2.5 text-sm text-muted">Ricerca in corso...</div>}
                {!searching && searchResults.length === 0 && (
                  <div className="px-3 py-2.5 text-sm text-muted">Nessun risultato. Puoi comunque inserire i dati a mano qui sotto.</div>
                )}
                {!searching && searchResults.map((r) => (
                  <button
                    type="button"
                    key={r.ticker}
                    onClick={() => pickSearchResult(r)}
                    className="flex w-full items-center justify-between gap-2 px-3 py-2.5 text-left text-sm hover:bg-accentSoft"
                  >
                    <span>
                      <span className="font-semibold">{r.name}</span>
                      <span className="ml-2 text-xs text-muted">{r.exchange}</span>
                    </span>
                    <span className="flex-shrink-0 font-mono text-xs text-muted">{r.ticker}</span>
                  </button>
                ))}
              </div>
            )}
            <p className="mt-1.5 text-xs text-muted">Le quotazioni arrivano in tempo reale da Yahoo Finance — cerca il nome, scegli il risultato giusto, i campi sotto si compilano da soli.</p>
            <div className="my-4 flex items-center gap-3 text-xs text-muted">
              <div className="h-px flex-1 bg-border"></div>oppure inserisci i dati a mano<div className="h-px flex-1 bg-border"></div>
            </div>
          </div>
        )}

        <form id="instrument-form" onSubmit={submitInstrument} className="space-y-3">
          <div className="grid grid-cols-2 gap-3">
            <div>
              <label className="field-label">Ticker</label>
              <input className="field uppercase" required placeholder="Es. VWCE.MI, AAPL" value={instForm.ticker} onChange={(e) => setInstForm((f) => ({ ...f, ticker: e.target.value }))} />
            </div>
            <div>
              <label className="field-label">Tipo</label>
              <select className="field" value={instForm.type} onChange={(e) => setInstForm((f) => ({ ...f, type: e.target.value }))}>
                <option value="etf">ETF</option><option value="azione">Azione</option><option value="obbligazione">Obbligazione</option>
                <option value="fondo">Fondo</option><option value="crypto">Crypto</option><option value="altro">Altro</option>
              </select>
            </div>
          </div>
          <div>
            <label className="field-label">Nome</label>
            <input className="field" required placeholder="Es. Vanguard FTSE All-World UCITS ETF" value={instForm.name} onChange={(e) => setInstForm((f) => ({ ...f, name: e.target.value }))} />
          </div>
          <div>
            <label className="field-label">Valuta di quotazione</label>
            <select className="field" value={instForm.currency} onChange={(e) => setInstForm((f) => ({ ...f, currency: e.target.value }))}>
              <option value="EUR">EUR</option><option value="USD">USD</option><option value="GBP">GBP</option><option value="CHF">CHF</option>
            </select>
          </div>
          <div>
            <label className="field-label">Aliquota imposta plusvalenze (%)</label>
            <input className="field" type="number" step="0.1" min="0" max="100" value={instForm.tax_rate ?? 26}
              onChange={(e) => setInstForm((f) => ({ ...f, tax_rate: e.target.value }))} />
            <p className="mt-1 text-xs text-muted">26% è l'aliquota standard italiana su azioni/ETF/crypto. I titoli di stato ("whitelist") sono al 12,5%.</p>
          </div>
          <p className="text-xs text-muted">Il ticker deve essere quello riconosciuto da Yahoo Finance (es. i titoli di Borsa Italiana terminano in <code>.MI</code>). Verificalo su finance.yahoo.com prima di salvare.</p>
        </form>
      </Modal>

      {/* Modale operazione */}
      <Modal open={txModalOpen} onClose={() => setTxModalOpen(false)} title={txForm.id ? 'Modifica operazione' : 'Nuova operazione'}
        footer={<>
          <button type="button" className="btn-outline" onClick={() => setTxModalOpen(false)}>Annulla</button>
          <button type="submit" form="investment-form" className="btn-accent">Salva</button>
        </>}
      >
        {txError && <div className="mb-3 rounded-lg bg-red-50 px-3 py-2 text-sm text-red-600">{txError}</div>}
        <form id="investment-form" onSubmit={submitTx} className="space-y-3">
          <div className="grid grid-cols-2 gap-3">
            <div>
              <label className="field-label">Strumento</label>
              <select className="field" required value={txForm.instrument_id} onChange={(e) => setTxForm((f) => ({ ...f, instrument_id: e.target.value }))}>
                {instruments.map((i) => <option key={i.id} value={i.id}>{i.ticker} — {i.name}</option>)}
              </select>
            </div>
            <div>
              <label className="field-label">Tipo</label>
              <select className="field" value={txForm.type} onChange={(e) => setTxForm((f) => ({ ...f, type: e.target.value }))}>
                <option value="acquisto">Acquisto</option><option value="vendita">Vendita</option>
              </select>
            </div>
          </div>
          <div className="grid grid-cols-3 gap-3">
            <div>
              <label className="field-label">Quantità</label>
              <input className="field" type="number" step="0.000001" min="0" required value={txForm.quantity} onChange={(e) => setTxForm((f) => ({ ...f, quantity: e.target.value }))} />
            </div>
            <div>
              <label className="field-label">Prezzo unitario</label>
              <input className="field" type="number" step="0.0001" min="0" required value={txForm.price_per_unit} onChange={(e) => setTxForm((f) => ({ ...f, price_per_unit: e.target.value }))} />
            </div>
            <div>
              <label className="field-label">Commissioni</label>
              <input className="field" type="number" step="0.01" min="0" value={txForm.fees} onChange={(e) => setTxForm((f) => ({ ...f, fees: e.target.value }))} />
            </div>
          </div>

          {txForm.type === 'vendita' && (
            <div>
              <label className="field-label">Imposta su plusvalenza (€)</label>
              <div className="flex gap-2">
                <input className="field" type="number" step="0.01" min="0" value={txForm.tax_amount} onChange={(e) => setTxForm((f) => ({ ...f, tax_amount: e.target.value }))} />
                <button type="button" className="btn-outline flex-shrink-0" onClick={recalcTax}>Ricalcola</button>
              </div>
              <p className="mt-1 text-xs text-muted">
                Sulle vendite nuove viene calcolata automaticamente. Su una vendita già esistente
                (modificata da qui), premi "Ricalcola" per aggiornarla in base al costo medio attuale.
                Al salvataggio, se questa operazione ha un movimento di cassa collegato, viene
                allineato automaticamente al nuovo importo netto corretto.
              </p>
              {taxBreakdown && (
                <div className="mt-2 rounded-lg border border-border bg-gray-50 px-3 py-2 text-xs text-gray-700">
                  <div className="flex justify-between"><span>Costo medio di carico (per quota)</span><span className="font-mono">{euro(taxBreakdown.avg_cost)}</span></div>
                  <div className="flex justify-between"><span>Plusvalenza su questa vendita</span><span className="font-mono">{euro(taxBreakdown.gain)}</span></div>
                  <div className="flex justify-between font-semibold"><span>Imposta ({taxBreakdown.tax_rate}% della plusvalenza)</span><span className="font-mono">{euro(taxBreakdown.tax_amount)}</span></div>
                </div>
              )}
            </div>
          )}
          <div>
            <label className="field-label">Data</label>
            <input className="field" type="date" required value={txForm.date} onChange={(e) => setTxForm((f) => ({ ...f, date: e.target.value }))} />
          </div>
          {!txForm.id && (
            <>
              <label className="flex items-center gap-2 text-sm">
                <input type="checkbox" checked={txForm.record_cash_movement} onChange={(e) => setTxForm((f) => ({ ...f, record_cash_movement: e.target.checked }))} />
                Registra anche il movimento di cassa corrispondente
              </label>
              {txForm.record_cash_movement && (
                <div>
                  <label className="field-label">Conto</label>
                  <select className="field" value={txForm.account_id} onChange={(e) => setTxForm((f) => ({ ...f, account_id: e.target.value }))}>
                    {activeAccounts.map((a) => <option key={a.id} value={a.id}>{a.name}</option>)}
                  </select>
                </div>
              )}
            </>
          )}
          <div>
            <label className="field-label">Note</label>
            <input className="field" placeholder="Opzionale" value={txForm.notes} onChange={(e) => setTxForm((f) => ({ ...f, notes: e.target.value }))} />
          </div>
        </form>
      </Modal>

      <Modal open={planModalOpen} onClose={() => setPlanModalOpen(false)} title={planForm.id ? 'Modifica piano di accumulo' : 'Nuovo piano di accumulo'}
        footer={<>
          <button type="button" className="btn-outline" onClick={() => setPlanModalOpen(false)}>Annulla</button>
          <button type="submit" form="plan-form" className="btn-accent">Salva</button>
        </>}
      >
        {planError && <div className="mb-3 rounded-lg bg-red-50 px-3 py-2 text-sm text-red-600">{planError}</div>}
        {instruments.length === 0 ? (
          <p className="text-sm text-muted">Aggiungi prima uno strumento (pulsante "+ Strumento") — un PAC investe in uno strumento che stai già seguendo.</p>
        ) : (
          <form id="plan-form" onSubmit={submitPlan} className="space-y-3">
            <div>
              <label className="field-label">Strumento</label>
              <select className="field" required value={planForm.instrument_id} onChange={(e) => setPlanForm((f) => ({ ...f, instrument_id: e.target.value }))}>
                {instruments.map((i) => <option key={i.id} value={i.id}>{i.name} ({i.ticker})</option>)}
              </select>
            </div>
            <div className="grid grid-cols-2 gap-3">
              <div>
                <label className="field-label">Importo per rata (€)</label>
                <input className="field" type="number" step="0.01" min="0" required value={planForm.amount} onChange={(e) => setPlanForm((f) => ({ ...f, amount: e.target.value }))} />
              </div>
              <div>
                <label className="field-label">Commissione fissa (€, opzionale)</label>
                <input className="field" type="number" step="0.01" min="0" value={planForm.fees} onChange={(e) => setPlanForm((f) => ({ ...f, fees: e.target.value }))} />
              </div>
            </div>
            <div className="grid grid-cols-2 gap-3">
              <div>
                <label className="field-label">Conto di addebito</label>
                <select className="field" required value={planForm.account_id} onChange={(e) => setPlanForm((f) => ({ ...f, account_id: e.target.value }))}>
                  {activeAccounts.map((a) => <option key={a.id} value={a.id}>{a.name}</option>)}
                </select>
              </div>
              <div>
                <label className="field-label">Frequenza</label>
                <select className="field" value={planForm.frequency} onChange={(e) => setPlanForm((f) => ({ ...f, frequency: e.target.value }))}>
                  <option value="mensile">Mensile</option><option value="settimanale">Settimanale</option><option value="annuale">Annuale</option>
                </select>
              </div>
            </div>
            <div className="grid grid-cols-2 gap-3">
              <div>
                <label className="field-label">Primo acquisto</label>
                <input className="field" type="date" required value={planForm.start_date} onChange={(e) => setPlanForm((f) => ({ ...f, start_date: e.target.value }))} />
              </div>
              <div>
                <label className="field-label">Fine (opzionale)</label>
                <input className="field" type="date" value={planForm.end_date} onChange={(e) => setPlanForm((f) => ({ ...f, end_date: e.target.value }))} />
              </div>
            </div>
            <p className="text-xs text-muted">
              Alla data prevista, l'app userà il prezzo di mercato di quel giorno per calcolare quante
              quote acquistare con l'importo indicato — la stessa logica di un PAC vero.
            </p>
          </form>
        )}
      </Modal>

      <Modal open={priceChartOpen} onClose={() => setPriceChartOpen(false)} title={priceChartInstrument ? `Andamento ${priceChartInstrument.ticker}` : 'Andamento prezzo'}>
        <div className="mb-3 flex justify-end gap-2">
          {[30, 90, 365].map((d) => (
            <button key={d} onClick={() => setPriceChartDays(d)} className={`rounded-lg px-3 py-1 text-xs font-semibold ${priceChartDays === d ? 'bg-accent text-white' : 'bg-gray-100 text-gray-600'}`}>
              {d === 365 ? '1 anno' : `${d}gg`}
            </button>
          ))}
        </div>
        {priceHistory.length < 2 ? (
          <p className="py-8 text-center text-sm text-muted">
            Non c'è ancora abbastanza storico per questo strumento. Lo storico si costruisce da solo
            ogni volta che l'app aggiorna le quotazioni — torna a controllare tra qualche giorno.
          </p>
        ) : (
          <>
            {(() => {
              const daysSpanned = Math.round((new Date(priceHistory[priceHistory.length - 1].date) - new Date(priceHistory[0].date)) / 86400000);
              return daysSpanned < priceChartDays - 5 ? (
                <p className="mb-2 text-center text-xs text-muted">
                  Lo storico raccolto finora copre {daysSpanned} {daysSpanned === 1 ? 'giorno' : 'giorni'} — per questo il grafico è uguale
                  tra i periodi finché non si accumula più storico.
                </p>
              ) : null;
            })()}
          <div className="h-64">
            <Line
              data={{
                labels: priceHistory.map((p) => formatDate(p.date)),
                datasets: [{
                  label: 'Prezzo',
                  data: priceHistory.map((p) => p.price),
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
                scales: { y: { ticks: { callback: (v) => euro(v) } } },
              }}
            />
          </div>
          </>
        )}

        <div className="mt-6 border-t border-border pt-5">
          <h4 className="mb-3 text-sm font-semibold text-gray-800">Indicatori</h4>

          {!instrumentMetrics ? (
            <p className="text-xs text-muted">Calcolo indicatori...</p>
          ) : instrumentMetrics.insufficient_history ? (
            <p className="text-sm text-muted">{instrumentMetrics.message}</p>
          ) : (
            <div className="grid grid-cols-2 gap-3 sm:grid-cols-3">
              {['1y', '3y', '5y'].map((key) => {
                const label = { '1y': 'Rendimento storico (1 anno)', '3y': 'Rendimento storico (3 anni)', '5y': 'Rendimento storico (5 anni)' }[key];
                const r = instrumentMetrics.returns?.[key];
                if (!r) return null;
                if (!r.available) {
                  return (
                    <IndicatorCard key={key} label={label}
                      tooltip="Rendimento totale e annualizzato realmente registrato nel periodo, calcolato sui dati storici presenti in Reflexi."
                      value="—" hint={r.message} />
                  );
                }
                return (
                  <IndicatorCard key={key} label={label}
                    tooltip="Rendimento totale e annualizzato realmente registrato nel periodo, calcolato sui dati storici presenti in Reflexi."
                    value={pct(r.total_return_pct)}
                    valueClass={r.total_return_pct >= 0 ? 'text-income' : 'text-expense'}
                    hint={`Annualizzato: ${pct(r.annualized_return_pct)} · dal ${formatDate(r.start_date)}`} />
                );
              })}

              {instrumentMetrics.volatility && (
                <IndicatorCard label="Volatilità annualizzata"
                  tooltip="Deviazione standard dei rendimenti giornalieri, annualizzata (radice di 252 giorni di borsa) — quanto oscilla il prezzo, non un giudizio di rischio."
                  value={`${instrumentMetrics.volatility.annualized_pct.toFixed(2)}%`}
                  hint={`Su ultimi ${instrumentMetrics.volatility.window_days} giorni`} />
              )}

              <IndicatorCard label="Max drawdown"
                tooltip="Il calo percentuale più grande registrato dal punto più alto raggiunto al punto più basso successivo, nello storico disponibile."
                value={pct(instrumentMetrics.max_drawdown.pct)}
                valueClass="text-expense"
                hint={`${formatDate(instrumentMetrics.max_drawdown.peak_date)} → ${formatDate(instrumentMetrics.max_drawdown.trough_date)}`} />

              {instrumentMetrics.sharpe_ratio && (
                <IndicatorCard label="Sharpe ratio approssimato"
                  tooltip={`(Rendimento annualizzato − tasso privo di rischio ipotizzato ${instrumentMetrics.sharpe_ratio.risk_free_rate_pct}%) / volatilità annualizzata.`}
                  value={instrumentMetrics.sharpe_ratio.value.toFixed(2)}
                  hint={`Tasso privo di rischio ipotizzato: ${instrumentMetrics.sharpe_ratio.risk_free_rate_pct}%`} />
              )}
            </div>
          )}

          {analystData?.available && analystData.data && (
            <div className="mt-4">
              <h5 className="mb-2 text-xs font-semibold uppercase tracking-wide text-muted">Opinioni di analisti esterni</h5>
              <div className="grid grid-cols-2 gap-3 sm:grid-cols-3">
                {analystData.data.target_mean_price != null && (
                  <IndicatorCard label="Target price medio analisti"
                    tooltip="Prezzo obiettivo medio stimato dagli analisti coperti da Yahoo Finance — un'opinione di terzi, non un'elaborazione di Reflexi."
                    value={fmt(analystData.data.target_mean_price, analystData.data.currency)}
                    hint={`Range ${fmt(analystData.data.target_low_price, analystData.data.currency)} – ${fmt(analystData.data.target_high_price, analystData.data.currency)} · ${analystData.data.number_of_analysts} analisti (dato Yahoo Finance)`} />
                )}
                {analystData.data.recommendation_mean != null && (
                  <IndicatorCard label="Raccomandazione media analisti"
                    tooltip="Media delle raccomandazioni degli analisti su scala Yahoo Finance: 1 = Strong Buy, 5 = Strong Sell."
                    value={analystData.data.recommendation_mean.toFixed(2)}
                    hint="Scala 1 (Strong Buy) – 5 (Strong Sell) · dato Yahoo Finance" />
                )}
              </div>
            </div>
          )}

          <div className="mt-4 flex items-start gap-2 rounded-xl border border-border bg-gray-50 p-3 text-xs text-muted">
            <Info size={14} className="mt-0.5 flex-shrink-0 text-muted" />
            <p>Dati storici e opinioni di terzi, non previsioni. Le performance passate non garantiscono risultati futuri.
              Reflexi non fornisce consulenza finanziaria né raccomandazioni di acquisto o vendita.</p>
          </div>
        </div>
      </Modal>

      <Modal open={reModalOpen} onClose={() => setReModalOpen(false)} title={reForm.id ? 'Modifica immobile' : 'Nuovo immobile'}
        footer={<>
          <button type="button" className="btn-outline" onClick={() => setReModalOpen(false)}>Annulla</button>
          <button type="submit" form="re-form" className="btn-accent">Salva</button>
        </>}
      >
        {reError && <div className="mb-3 rounded-lg bg-red-50 px-3 py-2 text-sm text-red-600">{reError}</div>}
        <form id="re-form" onSubmit={submitRE} className="space-y-3">
          <div>
            <label className="field-label">Nome</label>
            <input className="field" required placeholder="Es. Casa di residenza" value={reForm.name} onChange={(e) => setReForm((f) => ({ ...f, name: e.target.value }))} />
          </div>
          <div>
            <label className="field-label">Valore stimato (€)</label>
            <input className="field" type="number" step="0.01" min="0" required value={reForm.estimated_value} onChange={(e) => setReForm((f) => ({ ...f, estimated_value: e.target.value }))} />
          </div>
          <div>
            <label className="field-label">Mutuo collegato (opzionale)</label>
            <select className="field" value={reForm.linked_loan_id} onChange={(e) => setReForm((f) => ({ ...f, linked_loan_id: e.target.value }))}>
              <option value="">Nessuno</option>
              {loans.map((l) => <option key={l.id} value={l.id}>{l.name}</option>)}
            </select>
            <p className="mt-1 text-xs text-muted">Se colleghi un mutuo, il suo debito residuo viene già sottratto automaticamente nel calcolo del patrimonio netto complessivo — collegarlo qui è solo per tenerne traccia visivamente, non cambia il calcolo.</p>
          </div>
          <div>
            <label className="field-label">Note (opzionale)</label>
            <input className="field" value={reForm.notes} onChange={(e) => setReForm((f) => ({ ...f, notes: e.target.value }))} />
          </div>
        </form>
      </Modal>
    </div>
  );
}
