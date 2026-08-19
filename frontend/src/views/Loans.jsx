import { useState, useEffect, useCallback } from 'react';
import { Plus } from 'lucide-react';
import { useData } from '../context/DataContext';
import { apiGet, apiSend } from '../api';
import { euro, formatDate } from '../lib/format';
import Modal from '../components/Modal';
import ProgressBar from '../components/ProgressBar';
import LoanDetail from './LoanDetail';

function loanMonthlyPayment(principal, annualRatePct, years) {
  const i = annualRatePct / 100 / 12;
  const n = years * 12;
  if (n <= 0) return 0;
  if (i === 0) return principal / n;
  return principal * i / (1 - Math.pow(1 + i, -n));
}

const empty = {
  id: null, name: '', principal: '', annual_rate: '', duration_years: '', ownership_pct: 100,
  start_date: '', account_id: '', category_id: '', notes: '', create_recurring: true, rate_type: 'fisso',
};

export default function Loans() {
  const { activeAccounts, expenseCategories, signal, bump } = useData();
  const [loans, setLoans] = useState([]);
  const [modalOpen, setModalOpen] = useState(false);
  const [form, setForm] = useState(empty);
  const [error, setError] = useState('');
  const [detailId, setDetailId] = useState(null);

  const load = useCallback(async () => setLoans(await apiGet('loans.php')), []);
  useEffect(() => { load(); }, [load, signal]);

  const mutuoCategory = expenseCategories.find((c) => c.name === 'Mutuo/Prestito');

  function openNew() {
    setForm({ ...empty, account_id: activeAccounts[0]?.id || '', category_id: mutuoCategory?.id || expenseCategories[0]?.id || '' });
    setError('');
    setModalOpen(true);
  }

  function openEdit(loan) {
    setForm({
      id: loan.id, name: loan.name, principal: loan.principal, annual_rate: loan.annual_rate,
      duration_years: loan.duration_years, ownership_pct: loan.ownership_pct, start_date: loan.start_date,
      notes: loan.notes || '', create_recurring: false, account_id: '', category_id: '',
      rate_type: loan.rate_type || 'fisso',
    });
    setError('');
    setDetailId(null);
    setModalOpen(true);
  }

  const preview = (() => {
    const p = parseFloat(form.principal), r = parseFloat(form.annual_rate), y = parseInt(form.duration_years, 10);
    if (!p || !y || isNaN(r)) return null;
    const rata = loanMonthlyPayment(p, r, y);
    return { rata, totalInterest: rata * y * 12 - p, totalPaid: rata * y * 12 };
  })();

  async function handleSubmit(e) {
    e.preventDefault();
    setError('');
    try {
      if (form.id) {
        await apiSend('loans.php', 'PUT', {
          id: form.id, name: form.name, principal: parseFloat(form.principal), annual_rate: parseFloat(form.annual_rate),
          duration_years: parseInt(form.duration_years, 10), ownership_pct: parseFloat(form.ownership_pct || '100'),
          start_date: form.start_date, notes: form.notes, rate_type: form.rate_type,
        });
      } else {
        await apiSend('loans.php', 'POST', {
          name: form.name, principal: parseFloat(form.principal), annual_rate: parseFloat(form.annual_rate),
          duration_years: parseInt(form.duration_years, 10), ownership_pct: parseFloat(form.ownership_pct || '100'),
          start_date: form.start_date, create_recurring: form.create_recurring,
          account_id: form.account_id || null, category_id: form.category_id || null, notes: form.notes,
          rate_type: form.rate_type,
        });
      }
      setModalOpen(false);
      load();
      bump();
    } catch (err) { setError(err.message); }
  }

  return (
    <div>
      <div className="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <h4 className="text-xl font-bold">Mutui e prestiti</h4>
        <button className="btn-accent" onClick={openNew}><Plus size={15} /> Nuovo mutuo</button>
      </div>

      <div className="mb-4 rounded-xl border border-accent/20 bg-accentSoft px-4 py-3 text-sm text-gray-700">
        Qui tieni traccia di un mutuo o prestito: quanto hai preso in prestito, a che tasso, per
        quanti anni. L'app calcola da sola il piano di rate (quanto capitale e quanto interesse paghi
        ogni mese) e, se il mutuo è cointestato, quanto è davvero a tuo carico.
      </div>

      <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
        {loans.length ? loans.map((l) => {
          const c = l.calc;
          const actualLabel = l.actual.count !== null ? `Registrate: ${l.actual.count} (${euro(l.actual.amount_paid)})` : 'Nessuna rata ricorrente collegata';
          const ownershipLine = c.ownership_pct < 100 ? `Tua quota (${c.ownership_pct}%): ${euro(c.personal_residual_today)} residuo, rata ${euro(c.personal_monthly_payment)}` : null;
          return (
            <div key={l.id} onClick={() => setDetailId(l.id)}
              className={`card cursor-pointer border-l-4 p-4 hover:shadow-md ${l.status === 'estinto' ? 'border-l-muted opacity-60' : 'border-l-loan'}`}>
              <div className="font-bold">{l.name}</div>
              <div className="mb-2.5 text-xs text-muted">
                {c.rate_type === 'variabile' ? `${c.current_rate}% (variabile)` : `${l.annual_rate}%`} · {l.duration_years} anni · dal {formatDate(l.start_date)}
              </div>
              <div className="text-[11px] uppercase tracking-wide text-muted">{l.status === 'estinto' ? 'Estinto' : 'Capitale residuo'}</div>
              <div className="font-mono text-xl font-semibold">{euro(c.residual_today)}</div>
              <div className="mt-2"><ProgressBar pct={c.progress_pct} color="#f59e0b" /></div>
              <div className="mt-1 text-xs text-muted">Rata {euro(c.monthly_payment)} · previste {c.elapsed_payments}/{c.total_payments} (da calendario)</div>
              <div className="text-xs text-muted">{actualLabel}</div>
              {ownershipLine && <div className="text-xs text-muted">{ownershipLine}</div>}
            </div>
          );
        }) : <div className="col-span-full text-muted">Nessun mutuo registrato. Aggiungine uno con &quot;+ Nuovo mutuo&quot;.</div>}
      </div>

      <Modal open={modalOpen} onClose={() => setModalOpen(false)} title={form.id ? 'Modifica mutuo' : 'Nuovo mutuo'}
        footer={<>
          <button type="button" className="btn-outline" onClick={() => setModalOpen(false)}>Annulla</button>
          <button type="submit" form="loan-form" className="btn-accent">Salva</button>
        </>}
      >
        {error && <div className="mb-3 rounded-lg bg-red-50 px-3 py-2 text-sm text-red-600">{error}</div>}
        <form id="loan-form" onSubmit={handleSubmit} className="space-y-3">
          <div>
            <label className="field-label">Nome</label>
            <input className="field" required placeholder="Es. Mutuo prima casa" value={form.name} onChange={(e) => setForm((f) => ({ ...f, name: e.target.value }))} />
          </div>
          <div className="grid grid-cols-2 gap-3">
            <div>
              <label className="field-label">Capitale erogato (€)</label>
              <input className="field" type="number" step="0.01" min="0" required value={form.principal} onChange={(e) => setForm((f) => ({ ...f, principal: e.target.value }))} />
            </div>
            <div>
              <label className="field-label">Tasso di partenza (%)</label>
              <input className="field" type="number" step="0.001" min="0" required value={form.annual_rate} onChange={(e) => setForm((f) => ({ ...f, annual_rate: e.target.value }))} />
            </div>
          </div>
          <div className="grid grid-cols-2 gap-3">
            <div>
              <label className="field-label">Durata (anni)</label>
              <input className="field" type="number" min="1" required value={form.duration_years} onChange={(e) => setForm((f) => ({ ...f, duration_years: e.target.value }))} />
            </div>
            <div>
              <label className="field-label">Data erogazione</label>
              <input className="field" type="date" required value={form.start_date} onChange={(e) => setForm((f) => ({ ...f, start_date: e.target.value }))} />
            </div>
          </div>
          <div>
            <label className="field-label">Tipo di tasso</label>
            <select className="field" value={form.rate_type} onChange={(e) => setForm((f) => ({ ...f, rate_type: e.target.value }))}>
              <option value="fisso">Fisso</option>
              <option value="variabile">Variabile</option>
            </select>
            {form.rate_type === 'variabile' && (
              <p className="mt-1 text-xs text-muted">Dopo aver creato il mutuo, potrai registrare le variazioni di tasso nel dettaglio.</p>
            )}
          </div>
          <div>
            <label className="field-label">Quota di proprietà (%)</label>
            <input className="field" type="number" min="1" max="100" step="0.01" value={form.ownership_pct} onChange={(e) => setForm((f) => ({ ...f, ownership_pct: e.target.value }))} />
            <p className="mt-1 text-xs text-muted">Se cointestato, indica la tua quota (es. 50 per metà). Non cambia la rata reale che esce dal conto, ma nel Riepilogo e nel dettaglio vedrai anche la tua quota personale.</p>
          </div>
          {preview && (
            <div className="rounded-lg bg-accentSoft px-3.5 py-2.5 text-sm">
              Rata mensile stimata: <b className="font-mono">{euro(preview.rata)}</b> · Interessi totali: <b className="font-mono">{euro(preview.totalInterest)}</b> · Totale rimborsato: <b className="font-mono">{euro(preview.totalPaid)}</b>
            </div>
          )}
          {!form.id && (
            <>
              <label className="flex items-center gap-2 text-sm">
                <input type="checkbox" checked={form.create_recurring} onChange={(e) => setForm((f) => ({ ...f, create_recurring: e.target.checked }))} />
                Crea automaticamente la rata mensile come transazione ricorrente
              </label>
              {form.create_recurring && (
                <div className="grid grid-cols-2 gap-3">
                  <div>
                    <label className="field-label">Conto di addebito</label>
                    <select className="field" value={form.account_id} onChange={(e) => setForm((f) => ({ ...f, account_id: e.target.value }))}>
                      {activeAccounts.map((a) => <option key={a.id} value={a.id}>{a.name}</option>)}
                    </select>
                  </div>
                  <div>
                    <label className="field-label">Categoria</label>
                    <select className="field" value={form.category_id} onChange={(e) => setForm((f) => ({ ...f, category_id: e.target.value }))}>
                      {expenseCategories.map((c) => <option key={c.id} value={c.id}>{c.name}</option>)}
                    </select>
                  </div>
                </div>
              )}
            </>
          )}
          <div>
            <label className="field-label">Note</label>
            <input className="field" placeholder="Opzionale" value={form.notes} onChange={(e) => setForm((f) => ({ ...f, notes: e.target.value }))} />
          </div>
        </form>
      </Modal>

      <LoanDetail
        loanId={detailId}
        onClose={() => setDetailId(null)}
        onChanged={() => { load(); bump(); }}
        onEdit={(loan) => { setDetailId(null); openEdit(loan); }}
      />
    </div>
  );
}
