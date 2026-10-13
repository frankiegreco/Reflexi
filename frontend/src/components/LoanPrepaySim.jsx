import { useState, useMemo, useEffect } from 'react';
import { Plus, Trash2 } from 'lucide-react';
import { Chart } from 'react-chartjs-2';
import StatCard from './StatCard';
import { euro, formatDate, todayISO } from '../lib/format';
import { simulatePrepayment, FREQUENCIES } from '../lib/loanPrepay';

const FREQ_LABEL = { mensile: 'Ogni mese', trimestrale: 'Ogni 3 mesi', semestrale: 'Ogni 6 mesi', annuale: 'Ogni anno' };

const emptyPlan = () => ({
  mode: 'durata',
  penalty: '0',
  recurring: { enabled: false, amount: '', frequency: 'annuale', start: todayISO(), end: '' },
  oneOff: [{ date: todayISO(), amount: '' }],
});

// Lo scenario resta salvato nel browser per mutuo: e' una comodita', non un dato.
const storageKey = (loanId) => `reflexi.prepay.${loanId}`;
function loadPlan(loanId) {
  try {
    const saved = JSON.parse(localStorage.getItem(storageKey(loanId)));
    if (saved && saved.recurring && Array.isArray(saved.oneOff)) return saved;
  } catch { /* storage non disponibile */ }
  return emptyPlan();
}

function months(n) {
  const y = Math.floor(n / 12), m = n % 12;
  return [y && `${y} ${y === 1 ? 'anno' : 'anni'}`, m && `${m} ${m === 1 ? 'mese' : 'mesi'}`].filter(Boolean).join(' e ') || '0 mesi';
}

export default function LoanPrepaySim({ loanId, schedule, ownershipPct }) {
  const [plan, setPlan] = useState(() => loadPlan(loanId));
  const [showTable, setShowTable] = useState(false);

  useEffect(() => { setPlan(loadPlan(loanId)); }, [loanId]);
  useEffect(() => {
    try { localStorage.setItem(storageKey(loanId), JSON.stringify(plan)); } catch { /* ignora */ }
  }, [loanId, plan]);

  const today = todayISO();
  const res = useMemo(
    () => simulatePrepayment(schedule, today, { recurring: plan.recurring, oneOff: plan.oneOff }, plan.mode, plan.penalty),
    [schedule, today, plan],
  );

  const setRec = (patch) => setPlan((p) => ({ ...p, recurring: { ...p.recurring, ...patch } }));
  const setOne = (i, patch) => setPlan((p) => ({ ...p, oneOff: p.oneOff.map((o, j) => (j === i ? { ...o, ...patch } : o)) }));
  const addOne = () => setPlan((p) => ({ ...p, oneOff: [...p.oneOff, { date: today, amount: '' }] }));
  const removeOne = (i) => setPlan((p) => ({ ...p, oneOff: p.oneOff.filter((_, j) => j !== i) }));

  if (!res.base.length) {
    return <div className="card mb-4 p-4 text-sm text-muted">Non ci sono rate future da simulare.</div>;
  }

  const hasExtra = res.totalExtra > 0;
  const monthsSaved = res.monthsBase - res.monthsSim;

  // Residuo a fine anno: scenario base vs con versamenti.
  const byYear = {};
  res.base.forEach((r) => { byYear[r.date.slice(0, 4)] = { base: r.balance_after, sim: 0, extra: 0 }; });
  res.sim.forEach((r) => {
    const y = r.date.slice(0, 4);
    byYear[y].sim = r.balance_after;
    byYear[y].extra += r.extra;
  });
  const years = Object.keys(byYear);
  const simEndYear = res.endSim ? res.endSim.slice(0, 4) : null;

  const share = ownershipPct < 100 ? (v) => ` · tua quota ${euro(v * ownershipPct / 100)}` : () => '';

  return (
    <div className="space-y-4">
      <div className="card p-4">
        <h6 className="mb-1 text-sm font-semibold">Simulazione rientro anticipato</h6>
        <p className="mb-4 text-xs text-muted">
          Ipotizza versamenti volontari in conto capitale e guarda quanto risparmi di interessi. Il calcolo parte dal residuo
          di oggi e usa il tasso del piano mese per mese. Ogni versamento viene imputato alla prima rata successiva alla sua data.
        </p>

        <div className="mb-4">
          <label className="field-label">Cosa vuoi ridurre con i versamenti</label>
          <div className="inline-flex flex-wrap gap-1 rounded-xl bg-gray-100 p-1">
            {[['durata', 'La durata (rata invariata)'], ['rata', 'La rata (scadenza invariata)']].map(([id, label]) => (
              <button key={id} type="button" onClick={() => setPlan((p) => ({ ...p, mode: id }))}
                className={`rounded-lg px-3 py-1.5 text-sm font-semibold ${plan.mode === id ? 'bg-white text-accent shadow-sm' : 'text-muted hover:text-gray-700'}`}>
                {label}
              </button>
            ))}
          </div>
        </div>

        <div className="grid grid-cols-1 gap-4 lg:grid-cols-2">
          <div className="rounded-xl border border-border p-3">
            <label className="mb-2 flex items-center gap-2 text-sm font-medium">
              <input type="checkbox" checked={plan.recurring.enabled} onChange={(e) => setRec({ enabled: e.target.checked })} />
              Versamento periodico fisso
            </label>
            <div className={`grid grid-cols-2 gap-2 ${plan.recurring.enabled ? '' : 'pointer-events-none opacity-50'}`}>
              <div>
                <label className="field-label">Importo (€)</label>
                <input type="number" min="0" step="100" className="field" value={plan.recurring.amount} onChange={(e) => setRec({ amount: e.target.value })} />
              </div>
              <div>
                <label className="field-label">Frequenza</label>
                <select className="field" value={plan.recurring.frequency} onChange={(e) => setRec({ frequency: e.target.value })}>
                  {FREQUENCIES.map((f) => <option key={f} value={f}>{FREQ_LABEL[f]}</option>)}
                </select>
              </div>
              <div>
                <label className="field-label">Dal</label>
                <input type="date" className="field" value={plan.recurring.start} onChange={(e) => setRec({ start: e.target.value })} />
              </div>
              <div>
                <label className="field-label">Fino al (facoltativo)</label>
                <input type="date" className="field" value={plan.recurring.end} onChange={(e) => setRec({ end: e.target.value })} />
              </div>
            </div>
          </div>

          <div className="rounded-xl border border-border p-3">
            <div className="mb-2 text-sm font-medium">Versamenti sporadici</div>
            <div className="space-y-2">
              {plan.oneOff.map((o, i) => (
                <div key={i} className="flex items-end gap-2">
                  <div className="flex-1">
                    {i === 0 && <label className="field-label">Data</label>}
                    <input type="date" className="field" value={o.date} onChange={(e) => setOne(i, { date: e.target.value })} />
                  </div>
                  <div className="flex-1">
                    {i === 0 && <label className="field-label">Importo (€)</label>}
                    <input type="number" min="0" step="100" className="field" value={o.amount} onChange={(e) => setOne(i, { amount: e.target.value })} />
                  </div>
                  <button type="button" className="mb-2 text-muted hover:text-red-600" onClick={() => removeOne(i)} aria-label="Rimuovi versamento">
                    <Trash2 size={15} />
                  </button>
                </div>
              ))}
            </div>
            <button type="button" className="btn-outline mt-2" onClick={addOne}><Plus size={14} /> Aggiungi versamento</button>
          </div>
        </div>

        <div className="mt-4 flex flex-wrap items-end gap-3">
          <div>
            <label className="field-label">Penale su estinzione parziale (%)</label>
            <input type="number" min="0" step="0.1" className="field w-32" value={plan.penalty} onChange={(e) => setPlan((p) => ({ ...p, penalty: e.target.value }))} />
          </div>
          <p className="max-w-md text-xs text-muted">Per i mutui prima casa stipulati da febbraio 2007 la penale non è ammessa: lascia 0. Controlla il contratto negli altri casi.</p>
          <button type="button" className="btn-outline ml-auto" onClick={() => setPlan(emptyPlan())}>Azzera</button>
        </div>
      </div>

      {!hasExtra ? (
        <div className="card p-4 text-sm text-muted">Inserisci almeno un versamento per vedere il risultato.</div>
      ) : (
        <>
          <div className="grid grid-cols-1 gap-3 sm:grid-cols-2 md:grid-cols-4">
            <StatCard label="Versamenti volontari" value={euro(res.totalExtra)} hint={share(res.totalExtra) || undefined} />
            <StatCard label="Interessi risparmiati" value={euro(res.interestSaved)} valueClass="text-emerald-600"
              hint={`${euro(res.interestSim)} invece di ${euro(res.interestBase)}${share(res.interestSaved)}`} />
            {plan.mode === 'durata' ? (
              <StatCard label="Nuova fine" value={formatDate(res.endSim)} hint={`${months(monthsSaved)} prima (era ${formatDate(res.endBase)})`} />
            ) : (
              <StatCard label="Rata dopo il 1° versamento" value={euro(res.paymentAfterFirst ?? res.paymentLast)}
                hint={`Oggi ${euro(res.paymentNow)} · ultima ${euro(res.paymentLast)}`} />
            )}
            <StatCard label={res.penalty > 0 ? 'Risparmio netto penale' : 'Rate rimanenti'}
              value={res.penalty > 0 ? euro(res.netSaving) : `${res.monthsSim} su ${res.monthsBase}`}
              valueClass={res.penalty > 0 && res.netSaving < 0 ? 'text-red-600' : ''}
              hint={res.penalty > 0 ? `Penale ${euro(res.penalty)}` : undefined} />
          </div>
          {res.monthsSim < res.monthsBase && plan.mode === 'rata' && (
            <div className="text-xs text-muted">Con questi versamenti il debito si estingue già il {formatDate(res.endSim)}.</div>
          )}

          <div className="card p-4">
            <div className="h-64 sm:h-72">
              <Chart
                type="bar"
                data={{
                  labels: years,
                  datasets: [
                    { type: 'line', label: 'Residuo senza versamenti', data: years.map((y) => byYear[y].base), borderColor: '#9ca3af', borderDash: [5, 4], pointRadius: 0, tension: 0.2 },
                    { type: 'line', label: 'Residuo con versamenti', data: years.map((y) => (simEndYear && y > simEndYear ? null : byYear[y].sim)), borderColor: '#4f46e5', pointRadius: 0, tension: 0.2 },
                    { type: 'bar', label: 'Versamenti volontari', data: years.map((y) => byYear[y].extra), backgroundColor: '#10b981' },
                  ],
                }}
                options={{
                  responsive: true,
                  maintainAspectRatio: false,
                  interaction: { mode: 'index', intersect: false },
                  plugins: { tooltip: { callbacks: { label: (c) => `${c.dataset.label}: ${euro(c.raw)}` } } },
                  scales: { x: { grid: { display: false } }, y: { title: { display: true, text: '€' } } },
                }}
              />
            </div>
          </div>

          <div className="card overflow-hidden">
            <button className="flex w-full items-center justify-between px-4 py-3 text-left text-sm font-medium hover:bg-gray-50" onClick={() => setShowTable((s) => !s)}>
              <span>Piano simulato rata per rata ({res.sim.length} rate)</span>
              <span className="text-muted">{showTable ? '▲' : '▼'}</span>
            </button>
            {showTable && (
              <div className="max-h-96 overflow-auto">
                <table className="w-full text-sm">
                  <thead className="sticky top-0 bg-white">
                    <tr className="border-t border-border text-left text-[11px] uppercase tracking-wide text-muted">
                      <th className="px-4 py-2">Data</th><th className="px-4 py-2 text-right">Rata</th>
                      <th className="px-4 py-2 text-right">Quota capitale</th><th className="px-4 py-2 text-right">Quota interessi</th>
                      <th className="px-4 py-2 text-right">Versamento</th><th className="px-4 py-2 text-right">Residuo</th>
                    </tr>
                  </thead>
                  <tbody>
                    {res.sim.map((r) => (
                      <tr key={r.date} className={`border-t border-border ${r.extra > 0 ? 'bg-emerald-50/60' : ''}`}>
                        <td className="px-4 py-2">{formatDate(r.date)}</td>
                        <td className="px-4 py-2 text-right font-mono">{euro(r.payment)}</td>
                        <td className="px-4 py-2 text-right font-mono">{euro(r.principal_portion)}</td>
                        <td className="px-4 py-2 text-right font-mono">{euro(r.interest_portion)}</td>
                        <td className="px-4 py-2 text-right font-mono">{r.extra > 0 ? euro(r.extra) : ''}</td>
                        <td className="px-4 py-2 text-right font-mono">{euro(r.balance_after)}</td>
                      </tr>
                    ))}
                  </tbody>
                </table>
              </div>
            )}
          </div>
        </>
      )}
    </div>
  );
}
