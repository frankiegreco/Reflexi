import { useEffect, useState, useCallback } from 'react';
import { Bar, Doughnut } from 'react-chartjs-2';
import Chart from 'chart.js/auto';
import { Landmark, CalendarClock, Percent, LineChart, TrendingUp, Home, Plus, Trash2 } from 'lucide-react';
import { useData } from '../context/DataContext';
import { apiGet, apiSend, apiDelete } from '../api';
import { euro, monthLabel } from '../lib/format';
import StatCard from '../components/StatCard';
import ProgressBar from '../components/ProgressBar';
import Modal from '../components/Modal';

export default function Dashboard() {
  const { signal } = useData();
  const [months, setMonths] = useState(12);
  const [period, setPeriod] = useState(new Date().toISOString().slice(0, 7));
  const [dash, setDash] = useState(null);
  const [loans, setLoans] = useState([]);
  const [budgets, setBudgets] = useState(null);
  const [portfolio, setPortfolio] = useState(null);
  const [realEstate, setRealEstate] = useState([]);
  const [reModalOpen, setReModalOpen] = useState(false);
  const [reForm, setReForm] = useState({ name: '', estimated_value: '', linked_loan_id: '' });

  const load = useCallback(async () => {
    const [y, m] = period.split('-');
    const from = `${y}-${m}-01`;
    const to = new Date(y, m, 0).toISOString().slice(0, 10);
    const [dashData, loansData, budgetData, portfolioData, realEstateData] = await Promise.all([
      apiGet(`dashboard.php?months=${months}&date_from=${from}&date_to=${to}`),
      apiGet('loans.php'),
      apiGet(`budgets.php?month=${new Date().toISOString().slice(0, 7)}`),
      apiGet('portfolio.php'),
      apiGet('real_estate.php'),
    ]);
    setDash(dashData);
    setLoans(loansData.filter((l) => l.status === 'attivo'));
    setBudgets(budgetData.budgets);
    setPortfolio(portfolioData);
    setRealEstate(realEstateData);
  }, [months, period, signal]);

  async function submitRealEstate(e) {
    e.preventDefault();
    await apiSend('real_estate.php', 'POST', {
      name: reForm.name,
      estimated_value: parseFloat(reForm.estimated_value),
      linked_loan_id: reForm.linked_loan_id || null,
    });
    setReModalOpen(false);
    setReForm({ name: '', estimated_value: '', linked_loan_id: '' });
    load();
  }

  async function deleteRealEstate(id) {
    if (!confirm('Eliminare questo immobile dal patrimonio?')) return;
    await apiDelete(`real_estate.php?id=${id}`);
    load();
  }

  useEffect(() => { load(); }, [load]);

  if (!dash) return <div className="text-muted">Caricamento...</div>;

  const activeLoansWithData = loans.filter((l) => l.actual.count !== null);

  let totalResidual = 0, totalMonthly = 0, totalInterestRemaining = 0;
  loans.forEach((l) => {
    const c = l.calc;
    const pct = c.ownership_pct / 100;
    const personalPaidSoFar = c.elapsed_payments * c.personal_monthly_payment - (parseFloat(l.principal) * pct - c.personal_residual_today);
    totalResidual += c.personal_residual_today;
    totalMonthly += c.personal_monthly_payment;
    totalInterestRemaining += Math.max(0, c.personal_total_interest - personalPaidSoFar);
  });

  return (
    <div className="space-y-6">
      {/* Hero saldo */}
      <div className="relative overflow-hidden rounded-2xl bg-gradient-to-br from-accent via-accentDark to-indigo-900 p-8 text-white">
        <div className="pointer-events-none absolute -right-16 -top-16 h-64 w-64 rounded-full bg-white/10 blur-2xl" />
        <div className="pointer-events-none absolute -bottom-20 right-24 h-48 w-48 rounded-full bg-white/5 blur-2xl" />
        <div className="relative flex flex-col items-start gap-3 sm:flex-row sm:items-start sm:justify-between">
          <div>
            <div className="mb-1.5 text-xs font-medium uppercase tracking-wider opacity-70">Saldo complessivo</div>
            <div className="font-mono text-3xl font-semibold leading-none tracking-tight sm:text-[2.75rem]">{euro(dash.total_balance)}</div>
          </div>
          <span className="flex-shrink-0 whitespace-nowrap rounded-full bg-white/15 px-3.5 py-1.5 font-mono text-xs backdrop-blur-sm">
            {new Date().toLocaleDateString('it-IT', { day: '2-digit', month: 'long', year: 'numeric' })}
          </span>
        </div>
      </div>

      <div className="rounded-xl border border-accent/20 bg-accentSoft px-4 py-3 text-sm text-gray-700">
        Questo è il quadro generale: il saldo di tutti i tuoi conti insieme, come sono andate
        entrate e uscite negli ultimi mesi, e dove va la maggior parte dei tuoi soldi ogni mese.
        Per i dettagli, usa le sezioni nel menu a sinistra.
      </div>

      {/* Conti */}
      <div className="flex gap-3 overflow-x-auto pb-1">
        {dash.accounts.length ? dash.accounts.map((a) => (
          <div key={a.id} className="min-w-[170px] flex-shrink-0 rounded-xl border border-border border-l-4 bg-white p-4 transition-shadow hover:shadow-sm" style={{ borderLeftColor: a.color }}>
            <div className="mb-1.5 text-xs text-muted">{a.name}</div>
            <div className="font-mono text-base font-semibold">
              {a.currency && a.currency !== 'EUR' ? `${Number(a.balance).toLocaleString('it-IT', { minimumFractionDigits: 2 })} ${a.currency}` : euro(a.balance)}
            </div>
            {a.currency && a.currency !== 'EUR' && (
              <div className="mt-0.5 text-[11px] text-muted">{a.balance_eur !== null ? `≈ ${euro(a.balance_eur)}` : 'cambio non disponibile'}</div>
            )}
          </div>
        )) : <div className="text-sm text-muted">Nessun conto attivo</div>}
      </div>

      {/* Patrimonio netto */}
      {(() => {
        const portfolioEur = portfolio?.totals_by_currency?.EUR?.current_value || 0;
        const otherCurrencies = Object.entries(portfolio?.totals_by_currency || {}).filter(([cur]) => cur !== 'EUR');
        const realEstateShare = (r) => {
          if (!r.linked_loan_id) return parseFloat(r.estimated_value);
          const linkedLoan = loans.find((l) => l.id === r.linked_loan_id);
          const pct = linkedLoan ? parseFloat(linkedLoan.ownership_pct) : 100;
          return parseFloat(r.estimated_value) * pct / 100;
        };
        const realEstateTotal = realEstate.reduce((sum, r) => sum + realEstateShare(r), 0);
        const netWorth = (dash.total_balance || 0) + portfolioEur + realEstateTotal - totalResidual;
        return (
          <div className="card p-5">
            <div className="mb-3 flex items-center justify-between">
              <h6 className="flex items-center gap-1.5 text-sm font-semibold"><Home size={15} className="text-accent" /> Patrimonio netto</h6>
              <button className="btn-outline text-xs" onClick={() => setReModalOpen(true)}><Plus size={13} /> Immobile</button>
            </div>
            <div className="mb-3 font-mono text-2xl font-bold">{euro(netWorth)}</div>
            <div className="grid grid-cols-2 gap-2 text-xs text-muted sm:grid-cols-4">
              <div>Liquidità: <span className="font-mono text-gray-700">{euro(dash.total_balance || 0)}</span></div>
              <div>Investimenti: <span className="font-mono text-gray-700">{euro(portfolioEur)}</span></div>
              <div>Immobili: <span className="font-mono text-gray-700">{euro(realEstateTotal)}</span></div>
              <div>Mutui residui: <span className="font-mono text-gray-700">-{euro(totalResidual)}</span></div>
            </div>
            {otherCurrencies.length > 0 && (
              <p className="mt-2 text-xs text-muted">
                Non incluso nel totale (valuta diversa da EUR, non convertita):{' '}
                {otherCurrencies.map(([cur, t]) => `${cur} ${t.current_value.toFixed(2)}`).join(', ')}
              </p>
            )}
            {realEstate.length > 0 && (
              <div className="mt-3 space-y-1.5 border-t border-border pt-3">
                {realEstate.map((r) => {
                  const share = realEstateShare(r);
                  const isPartial = Math.abs(share - parseFloat(r.estimated_value)) > 0.01;
                  return (
                    <div key={r.id} className="flex flex-col gap-1 text-sm sm:flex-row sm:items-center sm:justify-between">
                      <span className="min-w-0 break-words">{r.name}{r.loan_name ? ` (mutuo: ${r.loan_name})` : ''}</span>
                      <div className="flex flex-shrink-0 items-center gap-2">
                        <span className="font-mono">
                          {isPartial ? <>{euro(share)} <span className="text-xs text-muted">(quota di {euro(r.estimated_value)})</span></> : euro(r.estimated_value)}
                        </span>
                        <button onClick={() => deleteRealEstate(r.id)} className="text-muted hover:text-red-600"><Trash2 size={13} /></button>
                      </div>
                    </div>
                  );
                })}
              </div>
            )}
          </div>
        );
      })()}

      {/* Andamento mutui */}
      {loans.length > 0 && (
        <div className="grid grid-cols-1 gap-3 sm:grid-cols-3">
          <StatCard label="Debito residuo (tua quota)" value={euro(totalResidual)} icon={Landmark} iconColor="#f59e0b" />
          <StatCard label="Rata mensile (tua quota)" value={euro(totalMonthly)} icon={CalendarClock} iconColor="#4f46e5" />
          <StatCard label="Interessi da pagare (tua quota)" value={euro(totalInterestRemaining)} icon={Percent} iconColor="#f43f5e" />
        </div>
      )}

      {activeLoansWithData.length > 0 && (
        <div className="card p-5">
          <h6 className="mb-3 text-sm font-semibold">Andamento mutui</h6>
          <div className="space-y-3">
            {activeLoansWithData.map((l) => {
              const a = l.actual;
              const c = l.calc;
              const pct = c.ownership_pct / 100;
              const suffix = pct < 1 ? ` (quota ${c.ownership_pct}%)` : '';
              return (
                <div key={l.id} className="space-y-2">
                  <div>
                    <div className="mb-1 flex justify-between text-sm">
                      <span className="font-medium">{l.name} — capitale restituito{suffix}</span>
                      <span className="font-mono text-muted">{euro(a.personal_capital_paid)} / {euro(l.principal * pct)} ({a.capital_pct_of_total}%)</span>
                    </div>
                    <ProgressBar pct={a.capital_pct_of_total} color="#4f46e5" />
                  </div>
                  <div>
                    <div className="mb-1 flex justify-between text-sm">
                      <span className="font-medium">{l.name} — interessi pagati{suffix}</span>
                      <span className="font-mono text-muted">{euro(a.personal_interest_paid)} ({a.interest_pct_of_total}%)</span>
                    </div>
                    <ProgressBar pct={a.interest_pct_of_total} color="#f59e0b" />
                  </div>
                </div>
              );
            })}
          </div>
        </div>
      )}

      {/* Investimenti */}
      {portfolio && Object.keys(portfolio.totals_by_currency).length > 0 && (
        <div className="card p-5">
          <h6 className="mb-3 flex items-center gap-1.5 text-sm font-semibold">
            <LineChart size={15} className="text-accent" /> Investimenti
          </h6>
          <div className="grid grid-cols-1 gap-3 sm:grid-cols-3">
            {Object.entries(portfolio.totals_by_currency).map(([cur, t]) => (
              <div key={cur} className="col-span-full grid grid-cols-1 gap-3 sm:grid-cols-3">
                <StatCard label={`Valore portafoglio (${cur})`} value={new Intl.NumberFormat('it-IT', { style: 'currency', currency: cur }).format(t.current_value)} icon={LineChart} iconColor="#4f46e5" />
                <StatCard
                  label={`Plus/minus non realizzate (${cur})`}
                  value={new Intl.NumberFormat('it-IT', { style: 'currency', currency: cur }).format(t.unrealized_gain)}
                  valueClass={t.unrealized_gain >= 0 ? 'text-income' : 'text-expense'}
                  icon={TrendingUp} iconColor={t.unrealized_gain >= 0 ? '#10b981' : '#f43f5e'}
                />
                <StatCard label={`Capitale investito (${cur})`} value={new Intl.NumberFormat('it-IT', { style: 'currency', currency: cur }).format(t.invested)} />
              </div>
            ))}
          </div>
          {portfolio.tax_paid_total > 0 && (
            <p className="mt-3 text-xs text-muted">Imposte pagate su vendite finora: {euro(portfolio.tax_paid_total)}</p>
          )}
        </div>
      )}

      {/* Budget del mese */}
      {budgets && budgets.length > 0 && (
        <div className="card p-5">
          <h6 className="mb-3 text-sm font-semibold">Budget del mese</h6>
          <div className="space-y-3">
            {budgets.map((b) => {
              const barColor = b.over_budget ? '#f43f5e' : (b.pct >= 80 ? '#f59e0b' : '#10b981');
              return (
                <div key={b.category_id}>
                  <div className="mb-1 flex justify-between text-sm">
                    <span className="font-medium">
                      {b.category_name}
                      {b.over_budget && <span className="ml-1 text-red-500">⚠</span>}
                    </span>
                    <span className={`font-mono ${b.over_budget ? 'font-semibold text-expense' : 'text-muted'}`}>
                      {euro(b.spent)} / {euro(b.monthly_limit)} ({b.pct}%)
                    </span>
                  </div>
                  <ProgressBar pct={b.pct} color={barColor} />
                </div>
              );
            })}
          </div>
        </div>
      )}

      {/* Grafici */}
      <div className="grid grid-cols-1 gap-4 lg:grid-cols-7">
        <div className="card p-5 lg:col-span-4">
          <div className="mb-3 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <h6 className="text-sm font-semibold">Andamento entrate e uscite</h6>
            <select className="field w-auto py-1 text-xs" value={months} onChange={(e) => setMonths(Number(e.target.value))}>
              <option value={6}>Ultimi 6 mesi</option>
              <option value={12}>Ultimi 12 mesi</option>
              <option value={24}>Ultimi 24 mesi</option>
            </select>
          </div>
          <Bar
            data={{
              labels: dash.monthly.map((m) => monthLabel(m.month)),
              datasets: [
                { label: 'Entrate', data: dash.monthly.map((m) => m.entrate), backgroundColor: '#10b981', borderRadius: 4 },
                { label: 'Uscite', data: dash.monthly.map((m) => m.uscite), backgroundColor: '#f43f5e', borderRadius: 4 },
              ],
            }}
            options={{ responsive: true, scales: { x: { grid: { display: false } }, y: { grid: { color: '#e7e9f0' } } } }}
          />
        </div>
        <div className="card p-5 lg:col-span-3">
          <div className="mb-3 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <h6 className="text-sm font-semibold">Per categoria</h6>
            <input className="field w-auto py-1 text-xs" type="month" value={period} onChange={(e) => setPeriod(e.target.value)} />
          </div>
          {dash.category_breakdown.length ? (
            <Doughnut
              data={{
                labels: dash.category_breakdown.map((c) => c.name),
                datasets: [{ data: dash.category_breakdown.map((c) => c.total), backgroundColor: dash.category_breakdown.map((c) => c.color), borderColor: '#fff', borderWidth: 2 }],
              }}
              options={{ responsive: true, plugins: { legend: { position: 'bottom', labels: { boxWidth: 12, padding: 12 } } } }}
            />
          ) : <div className="py-8 text-center text-sm text-muted">Nessun dato per il periodo selezionato</div>}
        </div>
      </div>

      <Modal open={reModalOpen} onClose={() => setReModalOpen(false)} title="Nuovo immobile"
        footer={<>
          <button type="button" className="btn-outline" onClick={() => setReModalOpen(false)}>Annulla</button>
          <button type="submit" form="re-form" className="btn-accent">Salva</button>
        </>}
      >
        <form id="re-form" onSubmit={submitRealEstate} className="space-y-3">
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
        </form>
      </Modal>
    </div>
  );
}
