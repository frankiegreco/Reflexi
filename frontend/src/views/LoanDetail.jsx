import { useState, useEffect, useCallback } from 'react';
import { Trash2 } from 'lucide-react';
import { Chart as ChartJS } from 'chart.js/auto';
import { Chart } from 'react-chartjs-2';
import Modal from '../components/Modal';
import StatCard from '../components/StatCard';
import ProgressBar from '../components/ProgressBar';
import Tabs from '../components/Tabs';
import LoanPrepaySim from '../components/LoanPrepaySim';
import { apiGet, apiSend, apiDelete } from '../api';
import { euro, formatDate, todayISO } from '../lib/format';

export default function LoanDetail({ loanId, onClose, onChanged, onEdit }) {
  const [tab, setTab] = useState('andamento');
  const [data, setData] = useState(null);
  const [payoffDate, setPayoffDate] = useState(todayISO());
  const [payoffResult, setPayoffResult] = useState(null);
  const [openYear, setOpenYear] = useState(null);
  const [rates, setRates] = useState([]);
  const [rateForm, setRateForm] = useState({ effective_date: todayISO(), rate: '' });
  const [rateError, setRateError] = useState('');

  const load = useCallback(async () => {
    if (!loanId) return;
    const d = await apiGet(`loans.php?action=schedule&id=${loanId}`);
    setData(d);
  }, [loanId]);

  const loadRates = useCallback(async () => {
    if (!loanId) return;
    setRates(await apiGet(`loan_rates.php?loan_id=${loanId}`));
  }, [loanId]);

  useEffect(() => { load(); setPayoffResult(null); setTab('andamento'); }, [load]);
  useEffect(() => { loadRates(); }, [loadRates]);

  if (!loanId || !data) return null;
  const { loan, summary, actual, schedule } = data;
  const isVariabile = loan.rate_type === 'variabile';
  const hasPrepayments = (data.prepayments || []).length > 0;

  const tabs = [
    { id: 'andamento', label: 'Andamento' },
    isVariabile && { id: 'tasso', label: 'Storico tasso', count: rates.length },
    loan.status !== 'estinto' && { id: 'rientro', label: 'Rientro anticipato', count: (data.prepayments || []).length },
    { id: 'estinzione', label: 'Estinzione anticipata' },
    { id: 'piano', label: 'Piano di ammortamento' },
  ].filter(Boolean);
  const activeTab = tabs.some((t) => t.id === tab) ? tab : 'andamento';

  async function addRate(e) {
    e.preventDefault();
    setRateError('');
    try {
      await apiSend('loan_rates.php', 'POST', { loan_id: loanId, effective_date: rateForm.effective_date, rate: parseFloat(rateForm.rate) });
      setRateForm({ effective_date: todayISO(), rate: '' });
      await Promise.all([loadRates(), load()]);
      onChanged();
    } catch (err) { setRateError(err.message); }
  }

  async function deleteRate(id) {
    if (!confirm('Eliminare questa variazione di tasso?')) return;
    setRateError('');
    try {
      await apiDelete(`loan_rates.php?id=${id}`);
      await Promise.all([loadRates(), load()]);
      onChanged();
    } catch (err) { setRateError(err.message); }
  }

  const byYear = {};
  schedule.forEach((row) => {
    const y = row.date.slice(0, 4);
    if (!byYear[y]) byYear[y] = { capitale: 0, interessi: 0, residuo: row.balance_after, rows: [] };
    byYear[y].capitale += row.principal_portion;
    byYear[y].interessi += row.interest_portion;
    byYear[y].residuo = row.balance_after;
    byYear[y].rows.push(row);
  });
  const years = Object.keys(byYear);
  const activeYear = openYear || years[0];

  async function simulatePayoff() {
    const d = await apiGet(`loans.php?action=schedule&id=${loanId}&payoff_date=${payoffDate}`);
    setPayoffResult(d.payoff_simulation);
  }

  async function markPayoff() {
    if (!payoffResult) return;
    if (!confirm(`Segnare il mutuo come estinto in data ${formatDate(payoffDate)}, con capitale versato ${euro(payoffResult.residual_capital)}?`)) return;
    await apiSend('loans.php', 'PUT', { id: loanId, status: 'estinto', early_payoff_date: payoffDate, early_payoff_amount: payoffResult.residual_capital });
    onChanged();
    onClose();
  }

  async function handleDelete() {
    const deleteRecurring = confirm("Eliminare anche la rata ricorrente collegata e tutte le transazioni da essa generate? Annulla = elimina solo il mutuo, ricorrenza e transazioni restano intatte.");
    if (!confirm("Confermi l'eliminazione definitiva di questo mutuo?")) return;
    await apiDelete(`loans.php?id=${loanId}&delete_recurring=${deleteRecurring ? 1 : 0}`);
    onChanged();
    onClose();
  }

  return (
    <Modal open={!!loanId} onClose={onClose} title={loan.name} size="xl"
      footer={<>
        <button className="btn-outline-danger mr-auto" onClick={handleDelete}><Trash2 size={15} /> Elimina mutuo</button>
        <button className="btn-outline" onClick={() => onEdit(loan)}>Modifica</button>
        <button className="btn-outline" onClick={onClose}>Chiudi</button>
      </>}
    >
      <div className="mb-4 grid grid-cols-1 gap-3 sm:grid-cols-2 md:grid-cols-4">
        <StatCard label="Capitale erogato" value={euro(loan.principal)} />
        <StatCard label="Rata mensile" value={euro(summary.monthly_payment)} />
        <StatCard label="Capitale residuo" value={euro(summary.residual_today)} />
        {isVariabile
          ? <StatCard label="Tasso attuale" value={`${summary.current_rate}%`} />
          : <StatCard label="Fine prevista" value={formatDate(summary.end_date)} />}
        <div className="sm:col-span-2"><StatCard label="Rate ad oggi (da calendario)" value={`${summary.elapsed_payments} / ${summary.total_payments}`} /></div>
        <div className="sm:col-span-2"><StatCard label="Rate registrate" value={actual.count !== null ? `${actual.count} · ${euro(actual.amount_paid)}` : 'N/D (nessuna ricorrenza collegata)'} /></div>

        {isVariabile && (
          <div className="sm:col-span-2 md:col-span-4 rounded-xl border border-amber-300/50 bg-amber-50 px-4 py-3 text-sm text-amber-800">
            Proiezione calcolata al tasso attuale ({summary.current_rate}%) — un mutuo variabile può cambiare, questa è una stima.
            Fine prevista (a tasso costante da oggi): {formatDate(summary.end_date)}.
          </div>
        )}

        {actual.count !== null && (
          <>
            <div className="sm:col-span-2">
              <div className="card p-4">
                <div className="mb-1 flex items-baseline justify-between">
                  <span className="text-[11px] font-medium uppercase tracking-wide text-muted">Interessi pagati finora</span>
                  <span className="font-mono text-sm">{euro(actual.interest_paid)} <span className="text-xs text-muted">({actual.interest_pct_of_total}%)</span></span>
                </div>
                <ProgressBar pct={actual.interest_pct_of_total} color="#f59e0b" />
              </div>
            </div>
            <div className="sm:col-span-2">
              <div className="card p-4">
                <div className="mb-1 flex items-baseline justify-between">
                  <span className="text-[11px] font-medium uppercase tracking-wide text-muted">Capitale restituito finora</span>
                  <span className="font-mono text-sm">{euro(actual.capital_paid)} <span className="text-xs text-muted">({actual.capital_pct_of_total}%)</span></span>
                </div>
                <ProgressBar pct={actual.capital_pct_of_total} color="#4f46e5" />
              </div>
            </div>
            {summary.ownership_pct < 100 && (
              <div className="sm:col-span-2 md:col-span-4 text-xs text-muted">
                Tua quota ({summary.ownership_pct}%): interessi {euro(actual.personal_interest_paid)}, capitale {euro(actual.personal_capital_paid)}
              </div>
            )}
          </>
        )}
        {summary.ownership_pct < 100 && (
          <div className="sm:col-span-2 md:col-span-4 card border-l-2 border-l-accent p-4">
            <div className="text-[11px] font-medium uppercase tracking-wide text-muted">Tua quota di proprietà: {summary.ownership_pct}%</div>
            <div className="text-sm">Residuo: {euro(summary.personal_residual_today)} · Rata: {euro(summary.personal_monthly_payment)} · Interessi totali previsti: {euro(summary.personal_total_interest)}</div>
          </div>
        )}
      </div>

      <Tabs tabs={tabs} active={activeTab} onChange={setTab} />

      {activeTab === 'tasso' && isVariabile && (
        <div className="card mb-4 p-4">
          <h6 className="mb-3 text-sm font-semibold">Storico variazioni tasso</h6>
          {rateError && <div className="mb-3 rounded-lg bg-red-50 px-3 py-2 text-sm text-red-600">{rateError}</div>}
          <div className="space-y-1.5">
            {rates.map((r) => (
              <div key={r.id} className="flex items-center justify-between rounded-lg bg-gray-50 px-3 py-2 text-sm">
                <span>{formatDate(r.effective_date)} — <span className="font-mono">{r.rate}%</span></span>
                <button type="button" className="text-muted hover:text-red-600" onClick={() => deleteRate(r.id)} aria-label="Elimina variazione">
                  <Trash2 size={14} />
                </button>
              </div>
            ))}
          </div>
          <form onSubmit={addRate} className="mt-3 flex flex-wrap items-end gap-2">
            <div>
              <label className="field-label">Decorrenza</label>
              <input type="date" className="field" required value={rateForm.effective_date} onChange={(e) => setRateForm((f) => ({ ...f, effective_date: e.target.value }))} />
            </div>
            <div>
              <label className="field-label">Nuovo tasso (%)</label>
              <input type="number" step="0.001" min="0" className="field" required value={rateForm.rate} onChange={(e) => setRateForm((f) => ({ ...f, rate: e.target.value }))} />
            </div>
            <button type="submit" className="btn-outline">Aggiungi variazione</button>
          </form>
        </div>
      )}

      {activeTab === 'andamento' && (
        <div className="card mb-4 p-4">
          <div className="h-64 sm:h-80">
            <Chart
              type="bar"
              data={{
                labels: years,
                datasets: [
                  { type: 'bar', label: 'Quota capitale', data: years.map((y) => byYear[y].capitale), backgroundColor: '#4f46e5', stack: 'a' },
                  { type: 'bar', label: 'Quota interessi', data: years.map((y) => byYear[y].interessi), backgroundColor: '#f59e0b', stack: 'a' },
                  { type: 'line', label: 'Capitale residuo', data: years.map((y) => byYear[y].residuo), borderColor: '#f43f5e', yAxisID: 'y1', tension: 0.25 },
                ],
              }}
              options={{
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                  x: { grid: { display: false } },
                  y: { stacked: true, title: { display: true, text: 'Quote annue (€)' } },
                  y1: { position: 'right', grid: { display: false }, title: { display: true, text: 'Residuo (€)' } },
                },
              }}
            />
          </div>
        </div>
      )}

      {activeTab === 'rientro' && (
        <div className="mb-4"><LoanPrepaySim loan={loan} schedule={schedule} prepayments={data.prepayments || []} ownershipPct={summary.ownership_pct}
          onRegistered={async () => { await load(); onChanged(); }} /></div>
      )}

      {activeTab === 'estinzione' && (
        <div className="card mb-4 p-4">
          <h6 className="mb-3 text-sm font-semibold">Simulazione estinzione anticipata</h6>
          <div className="flex flex-wrap items-end gap-2">
            <div>
              <label className="field-label">Data ipotetica di estinzione</label>
              <input type="date" className="field" value={payoffDate} onChange={(e) => setPayoffDate(e.target.value)} />
            </div>
            <button className="btn-outline" onClick={simulatePayoff}>Calcola</button>
            <button className="btn bg-loan text-white hover:brightness-95" onClick={markPayoff} disabled={!payoffResult}>Segna come estinto a questa data</button>
          </div>
          {payoffResult && (
            <div className="mt-3 grid grid-cols-1 gap-3 sm:grid-cols-3">
              <StatCard label="Rate pagate a quella data" value={payoffResult.payments_made} />
              <StatCard label="Capitale da versare per estinguere" value={euro(payoffResult.residual_capital)} />
              <StatCard label="Interessi risparmiati estinguendo ora" value={euro(payoffResult.interest_saved_if_paid_now)} />
            </div>
          )}
        </div>
      )}

      {activeTab === 'piano' && (
        <div className="space-y-2">
          {years.map((y) => (
            <div key={y} className="card overflow-hidden">
              <button
                className="flex w-full items-center justify-between px-4 py-3 text-left text-sm font-medium hover:bg-gray-50"
                onClick={() => setOpenYear(activeYear === y ? '__none__' : y)}
              >
                <span>{y} — capitale {euro(byYear[y].capitale)}, interessi {euro(byYear[y].interessi)}</span>
                <span className="text-muted">{activeYear === y ? '▲' : '▼'}</span>
              </button>
              {activeYear === y && (
                <div className="overflow-x-auto">
                <table className="w-full text-sm">
                  <thead>
                    <tr className="border-t border-border text-left text-[11px] uppercase tracking-wide text-muted">
                      <th className="px-4 py-2">#</th><th className="px-4 py-2">Data</th>
                      {isVariabile && <th className="px-4 py-2 text-right">Tasso</th>}
                      <th className="px-4 py-2 text-right">Quota capitale</th><th className="px-4 py-2 text-right">Quota interessi</th>
                      <th className="px-4 py-2 text-right">Rata</th>
                      {hasPrepayments && <th className="px-4 py-2 text-right">Versamento</th>}
                      <th className="px-4 py-2 text-right">Residuo</th><th className="px-4 py-2">Stato</th>
                    </tr>
                  </thead>
                  <tbody>
                    {byYear[y].rows.map((r) => (
                      <tr key={r.month_number} className={`border-t border-border ${r.paid === false ? 'bg-amber-50/60' : ''}`}>
                        <td className="px-4 py-2">{r.month_number}</td>
                        <td className="px-4 py-2">{formatDate(r.date)}</td>
                        {isVariabile && <td className="px-4 py-2 text-right font-mono">{r.rate_used}%</td>}
                        <td className="px-4 py-2 text-right font-mono">{euro(r.principal_portion)}</td>
                        <td className="px-4 py-2 text-right font-mono">{euro(r.interest_portion)}</td>
                        <td className="px-4 py-2 text-right font-mono">{euro(r.payment)}</td>
                        {hasPrepayments && <td className="px-4 py-2 text-right font-mono text-emerald-700">{r.extra > 0 ? euro(r.extra) : ''}</td>}
                        <td className="px-4 py-2 text-right font-mono">{euro(r.balance_after)}</td>
                        <td className="px-4 py-2">
                          {r.paid === null ? <span className="text-xs text-muted">N/D</span> :
                            r.paid ? <span className="rounded-full bg-emerald-50 px-2 py-0.5 text-xs text-emerald-700">✓ Pagata</span> :
                              <span className="rounded-full bg-gray-100 px-2 py-0.5 text-xs text-gray-600">Da pagare</span>}
                        </td>
                      </tr>
                    ))}
                  </tbody>
                </table>
                </div>
              )}
            </div>
          ))}
        </div>
      )}
    </Modal>
  );
}
