import { useState, useMemo, useEffect } from 'react';
import { Plus, Trash2, Check } from 'lucide-react';
import { Chart } from 'react-chartjs-2';
import StatCard from './StatCard';
import { euro, formatDate, todayISO } from '../lib/format';
import { apiSend, apiDelete } from '../api';
import { useData } from '../context/DataContext';
import { simulatePrepayment, FREQUENCIES } from '../lib/loanPrepay';

const FREQ_LABEL = { mensile: 'Ogni mese', trimestrale: 'Ogni 3 mesi', semestrale: 'Ogni 6 mesi', annuale: 'Ogni anno' };

const emptyPlan = () => ({
  mode: 'durata',
  penalty: '0',
  tax: { enabled: true, pct: '19', cap: '4000' },
  recurring: { enabled: false, amount: '', frequency: 'annuale', start: todayISO(), end: '' },
  oneOff: [{ date: todayISO(), amount: '' }],
});

// Lo scenario resta salvato nel browser per mutuo: e' una comodita', non un dato.
const storageKey = (loanId) => `reflexi.prepay.${loanId}`;
function loadPlan(loanId) {
  try {
    const saved = JSON.parse(localStorage.getItem(storageKey(loanId)));
    if (saved && saved.recurring && Array.isArray(saved.oneOff)) return { ...emptyPlan(), ...saved };
  } catch { /* storage non disponibile */ }
  return emptyPlan();
}

function months(n) {
  const y = Math.floor(n / 12), m = n % 12;
  return [y && `${y} ${y === 1 ? 'anno' : 'anni'}`, m && `${m} ${m === 1 ? 'mese' : 'mesi'}`].filter(Boolean).join(' e ') || '0 mesi';
}

export default function LoanPrepaySim({ loan, schedule, prepayments, ownershipPct, onRegistered }) {
  const loanId = loan.id;
  const { activeAccounts, expenseCategories } = useData();
  const [plan, setPlan] = useState(() => loadPlan(loanId));
  const [showTable, setShowTable] = useState(false);
  const [showYears, setShowYears] = useState(false);
  const [reg, setReg] = useState(null);
  const [regError, setRegError] = useState('');
  const [saving, setSaving] = useState(false);

  // Apre il modulo di registrazione, eventualmente precompilato da un versamento simulato.
  function openRegister(date = todayISO(), amount = '') {
    setRegError('');
    setReg({
      date: date > todayISO() ? todayISO() : date,
      amount,
      mode: plan.mode,
      create_transaction: activeAccounts.length > 0,
      account_id: loan.account_id || activeAccounts[0]?.id || '',
      category_id: loan.category_id || expenseCategories[0]?.id || '',
    });
  }

  async function register(e) {
    e.preventDefault();
    setRegError('');
    setSaving(true);
    try {
      await apiSend('loan_prepayments.php', 'POST', {
        loan_id: loanId,
        payment_date: reg.date,
        amount: parseFloat(reg.amount),
        mode: reg.mode,
        create_transaction: reg.create_transaction,
        account_id: reg.account_id,
        category_id: reg.category_id,
      });
      // Il versamento e' ora nel piano reale: lo tolgo dalle ipotesi se veniva da li'.
      setPlan((p) => ({ ...p, oneOff: p.oneOff.filter((o) => !(o.date === reg.date && parseFloat(o.amount) === parseFloat(reg.amount))) }));
      setReg(null);
      await onRegistered();
    } catch (err) { setRegError(err.message); }
    setSaving(false);
  }

  async function removePrepayment(p) {
    if (!confirm(`Eliminare il versamento di ${euro(p.amount)} del ${formatDate(p.payment_date)}? Il piano del mutuo verrà ricalcolato.`)) return;
    const deleteTx = p.transaction_id ? confirm("Eliminare anche l'uscita registrata sul conto per questo versamento? Annulla = la transazione resta.") : false;
    try {
      await apiDelete(`loan_prepayments.php?id=${p.id}&delete_transaction=${deleteTx ? 1 : 0}`);
      await onRegistered();
    } catch (err) { alert(err.message); }
  }

  useEffect(() => { setPlan(loadPlan(loanId)); }, [loanId]);
  useEffect(() => {
    try { localStorage.setItem(storageKey(loanId), JSON.stringify(plan)); } catch { /* ignora */ }
  }, [loanId, plan]);

  const today = todayISO();
  const res = useMemo(
    () => simulatePrepayment(schedule, today, { recurring: plan.recurring, oneOff: plan.oneOff }, plan.mode, plan.penalty, plan.tax),
    [schedule, today, plan],
  );

  const setTax = (patch) => setPlan((p) => ({ ...p, tax: { ...p.tax, ...patch } }));
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
        <div className="mb-2 flex flex-wrap items-center justify-between gap-2">
          <h6 className="text-sm font-semibold">Versamenti registrati</h6>
          {!reg && <button type="button" className="btn-outline" onClick={() => openRegister()}><Plus size={14} /> Registra versamento</button>}
        </div>
        {prepayments.length === 0 && !reg && (
          <p className="text-xs text-muted">Nessun versamento registrato. Quando ne fai uno, registralo qui: riduce il debito residuo e il piano del mutuo viene ricalcolato.</p>
        )}
        {prepayments.length > 0 && (
          <div className="space-y-1.5">
            {prepayments.map((p) => (
              <div key={p.id} className="flex items-center justify-between gap-2 rounded-lg bg-gray-50 px-3 py-2 text-sm">
                <span>
                  {formatDate(p.payment_date)} — <span className="font-mono">{euro(p.amount)}</span>
                  <span className="ml-2 text-xs text-muted">riduce {p.mode === 'rata' ? 'la rata' : 'la durata'}{p.transaction_id ? ' · uscita registrata' : ''}</span>
                </span>
                <button type="button" className="text-muted hover:text-red-600" onClick={() => removePrepayment(p)} aria-label="Elimina versamento">
                  <Trash2 size={14} />
                </button>
              </div>
            ))}
          </div>
        )}
        {reg && (
          <form onSubmit={register} className="mt-3 rounded-xl border border-border p-3">
            {regError && <div className="mb-3 rounded-lg bg-red-50 px-3 py-2 text-sm text-red-600">{regError}</div>}
            <div className="grid grid-cols-1 gap-2 sm:grid-cols-3">
              <div>
                <label className="field-label">Data del versamento</label>
                <input type="date" className="field" required max={todayISO()} value={reg.date} onChange={(e) => setReg((r) => ({ ...r, date: e.target.value }))} />
              </div>
              <div>
                <label className="field-label">Importo versato (€)</label>
                <input type="number" min="0.01" step="0.01" className="field" required value={reg.amount} onChange={(e) => setReg((r) => ({ ...r, amount: e.target.value }))} />
              </div>
              <div>
                <label className="field-label">Riduce</label>
                <select className="field" value={reg.mode} onChange={(e) => setReg((r) => ({ ...r, mode: e.target.value }))}>
                  <option value="durata">La durata (rata invariata)</option>
                  <option value="rata">La rata (scadenza invariata)</option>
                </select>
              </div>
            </div>
            <label className="mt-3 flex items-center gap-2 text-sm">
              <input type="checkbox" checked={reg.create_transaction} disabled={!activeAccounts.length} onChange={(e) => setReg((r) => ({ ...r, create_transaction: e.target.checked }))} />
              Registra anche l'uscita sul conto
            </label>
            {reg.create_transaction && (
              <div className="mt-2 grid grid-cols-1 gap-2 sm:grid-cols-2">
                <div>
                  <label className="field-label">Conto</label>
                  <select className="field" value={reg.account_id} onChange={(e) => setReg((r) => ({ ...r, account_id: e.target.value }))}>
                    {activeAccounts.map((a) => <option key={a.id} value={a.id}>{a.name}</option>)}
                  </select>
                </div>
                <div>
                  <label className="field-label">Categoria</label>
                  <select className="field" value={reg.category_id} onChange={(e) => setReg((r) => ({ ...r, category_id: e.target.value }))}>
                    {expenseCategories.map((c) => <option key={c.id} value={c.id}>{c.name}</option>)}
                  </select>
                </div>
                {ownershipPct < 100 && parseFloat(reg.amount) > 0 && (
                  <p className="text-xs text-muted sm:col-span-2">Uscita sul conto: la tua quota ({ownershipPct}%), {euro(parseFloat(reg.amount) * ownershipPct / 100)}.</p>
                )}
              </div>
            )}
            <p className="mt-3 text-xs text-muted">
              Il debito residuo, il piano e il patrimonio netto vengono ricalcolati
              {loan.recurring_id ? ", e la rata ricorrente collegata si aggiorna (importo e data di fine)" : ''}.
            </p>
            <div className="mt-3 flex justify-end gap-2">
              <button type="button" className="btn-outline" onClick={() => setReg(null)}>Annulla</button>
              <button type="submit" className="btn bg-loan text-white hover:brightness-95" disabled={saving}><Check size={14} /> Registra</button>
            </div>
          </form>
        )}
      </div>

      <div className="card p-4">
        <h6 className="mb-1 text-sm font-semibold">Simulazione rientro anticipato</h6>
        <p className="mb-4 text-xs text-muted">
          Ipotizza versamenti volontari in conto capitale e guarda quanto risparmi di interessi. Il calcolo parte dal residuo
          di oggi (già al netto dei versamenti registrati) e usa il tasso del piano mese per mese. Ogni versamento riduce il
          debito dalla sua data: la rata successiva paga già gli interessi sul residuo più basso.
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
                  <button type="button" className="btn-outline mb-0.5 px-2 py-1 text-xs" disabled={!o.date || o.date > today || !(parseFloat(o.amount) > 0)}
                    title={o.date > today ? 'Si possono registrare solo versamenti già fatti' : 'Registra come versamento reale'}
                    onClick={() => openRegister(o.date, o.amount)}>
                    Registra
                  </button>
                  <button type="button" className="mb-2 text-muted hover:text-red-600" onClick={() => removeOne(i)} aria-label="Rimuovi versamento">
                    <Trash2 size={15} />
                  </button>
                </div>
              ))}
            </div>
            <button type="button" className="btn-outline mt-2" onClick={addOne}><Plus size={14} /> Aggiungi versamento</button>
          </div>
        </div>

        <div className="mt-4 rounded-xl border border-border p-3">
          <label className="mb-2 flex items-center gap-2 text-sm font-medium">
            <input type="checkbox" checked={plan.tax.enabled} onChange={(e) => setTax({ enabled: e.target.checked })} />
            Detrazione IRPEF sugli interessi (mutuo abitazione principale)
          </label>
          <div className={`flex flex-wrap items-end gap-3 ${plan.tax.enabled ? '' : 'pointer-events-none opacity-50'}`}>
            <div>
              <label className="field-label">Detrazione (%)</label>
              <input type="number" min="0" max="100" step="1" className="field w-24" value={plan.tax.pct} onChange={(e) => setTax({ pct: e.target.value })} />
            </div>
            <div>
              <label className="field-label">Tetto interessi annuo (€)</label>
              <input type="number" min="0" step="100" className="field w-32" value={plan.tax.cap} onChange={(e) => setTax({ cap: e.target.value })} />
            </div>
            <p className="max-w-md text-xs text-muted">
              Standard: 19% degli interessi pagati nell'anno, su massimo 4.000 € di interessi per mutuo (da dividere tra i cointestatari).
              Pagando meno interessi si perde parte della detrazione: il risparmio netto ne tiene conto.
            </p>
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
            <StatCard label="Risparmio netto" value={euro(res.netSaving)}
              valueClass={res.netSaving < 0 ? 'text-red-600' : 'text-emerald-600'}
              hint={[
                res.deductionLost > 0 && `detrazione persa ${euro(res.deductionLost)}`,
                res.penalty > 0 && `penale ${euro(res.penalty)}`,
                ownershipPct < 100 && `tua quota ${euro(res.netSaving * ownershipPct / 100)}`,
              ].filter(Boolean).join(' · ') || undefined} />
          </div>

          <div className="card p-4">
            <h6 className="mb-3 text-sm font-semibold">Dettaglio del calcolo</h6>
            <div className="max-w-xl space-y-1 text-sm">
              {[
                ['Interessi da pagare senza versamenti', res.interestBase],
                ['Interessi da pagare con i versamenti', -res.interestSim],
                ['= Interessi risparmiati', res.interestSaved, 'border-t border-border pt-1 font-semibold'],
                plan.tax.enabled && [`− Detrazione IRPEF persa (${plan.tax.pct}% su max ${euro(plan.tax.cap)}/anno)`, -res.deductionLost],
                res.penalty > 0 && [`− Penale (${plan.penalty}% dei versamenti)`, -res.penalty],
                ['= Risparmio netto', res.netSaving, 'border-t border-border pt-1 font-semibold text-emerald-700'],
                ownershipPct < 100 && [`Tua quota (${ownershipPct}%)`, res.netSaving * ownershipPct / 100, 'text-muted'],
              ].filter(Boolean).map(([label, value, cls = '']) => (
                <div key={label} className={`flex justify-between gap-4 ${cls}`}>
                  <span>{label}</span><span className="font-mono">{euro(value)}</span>
                </div>
              ))}
            </div>
            <div className="mt-3 grid max-w-xl grid-cols-1 gap-1 text-xs text-muted sm:grid-cols-2">
              <span>Risparmio netto ogni 1.000 € versati: <b className="font-mono">{euro(res.netSaving / res.totalExtra * 1000)}</b></span>
              {plan.tax.enabled && <span>Detrazione totale: {euro(res.deductionBase)} → {euro(res.deductionSim)}</span>}
            </div>
            {plan.tax.enabled && (
              <p className="mt-3 text-xs text-muted">
                La detrazione spetta solo per il mutuo sull'abitazione principale e solo fino all'IRPEF che devi pagare (se ne paghi poca,
                ne recuperi meno e il risparmio netto reale è più alto). Si recupera l'anno dopo con il 730: qui è attribuita all'anno
                degli interessi. Dal 2025, con redditi oltre 75.000 € c'è un tetto complessivo alle detrazioni: verifica il tuo caso.
              </p>
            )}

            <button className="mt-3 text-sm font-medium text-accent hover:underline" onClick={() => setShowYears((v) => !v)}>
              {showYears ? '▲ Nascondi' : '▼ Mostra'} il dettaglio anno per anno
            </button>
            {showYears && (
              <div className="mt-2 max-h-96 overflow-auto">
                <table className="w-full text-sm">
                  <thead className="sticky top-0 bg-white">
                    <tr className="text-left text-[11px] uppercase tracking-wide text-muted">
                      <th className="px-2 py-2">Anno</th>
                      <th className="px-2 py-2 text-right">Interessi senza</th><th className="px-2 py-2 text-right">Interessi con</th>
                      {plan.tax.enabled && <><th className="px-2 py-2 text-right">Detraz. senza</th><th className="px-2 py-2 text-right">Detraz. con</th></>}
                      <th className="px-2 py-2 text-right">Risparmio netto</th>
                    </tr>
                  </thead>
                  <tbody>
                    {res.years.map((y) => (
                      <tr key={y.year} className="border-t border-border">
                        <td className="px-2 py-1.5">{y.year}</td>
                        <td className="px-2 py-1.5 text-right font-mono">{euro(y.interestBase)}</td>
                        <td className="px-2 py-1.5 text-right font-mono">{euro(y.interestSim)}</td>
                        {plan.tax.enabled && <>
                          <td className="px-2 py-1.5 text-right font-mono">{euro(y.deductionBase)}</td>
                          <td className="px-2 py-1.5 text-right font-mono">{euro(y.deductionSim)}</td>
                        </>}
                        <td className="px-2 py-1.5 text-right font-mono">{euro(y.interestBase - y.interestSim - y.deductionLost)}</td>
                      </tr>
                    ))}
                  </tbody>
                </table>
                <p className="mt-2 text-xs text-muted">Il risparmio per anno non comprende la penale, che si paga al momento del versamento.</p>
              </div>
            )}
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
