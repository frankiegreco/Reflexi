import { useState, useEffect, useCallback } from 'react';
import { Plus, Trash, Trash2, Target, Compass, Pencil, Wallet } from 'lucide-react';
import { useData } from '../context/DataContext';
import { apiGet, apiSend, apiDelete } from '../api';
import { euro, formatDate, todayISO } from '../lib/format';
import Modal from '../components/Modal';
import ProgressBar from '../components/ProgressBar';
import GoalPlan from '../components/GoalPlan';

const empty = { id: null, name: '', target_amount: '', account_id: '', start_date: todayISO(), target_date: '' };

export default function Goals() {
  const { accounts, activeAccounts, signal } = useData();
  const [goals, setGoals] = useState([]);
  const [modalOpen, setModalOpen] = useState(false);
  const [form, setForm] = useState(empty);
  const [error, setError] = useState('');
  const [planGoal, setPlanGoal] = useState(null);

  const load = useCallback(async () => setGoals(await apiGet('savings_goals.php')), []);
  useEffect(() => { load(); }, [load, signal]);

  function openNew() {
    setForm(empty);
    setError('');
    setModalOpen(true);
  }
  function openEdit(g) {
    setForm({ id: g.id, name: g.name, target_amount: g.target_amount, account_id: g.account_id || '', start_date: g.start_date, target_date: g.target_date || '' });
    setError('');
    setModalOpen(true);
  }

  function accountName(id) {
    return accounts.find((a) => a.id === id)?.name || null;
  }

  async function handleSubmit(e) {
    e.preventDefault();
    const payload = {
      name: form.name,
      target_amount: parseFloat(form.target_amount),
      account_id: form.account_id || null,
      start_date: form.start_date,
      target_date: form.target_date || null,
    };
    try {
      if (form.id) await apiSend('savings_goals.php', 'PUT', { ...payload, id: form.id });
      else await apiSend('savings_goals.php', 'POST', payload);
      setModalOpen(false);
      load();
    } catch (err) { setError(err.message); }
  }

  async function handleDelete(id) {
    if (!confirm('Eliminare questo obiettivo?')) return;
    await apiDelete(`savings_goals.php?id=${id}`);
    load();
  }

  async function handleDeleteAll() {
    if (!goals.length) return;
    if (!confirm(`Eliminare tutti i ${goals.length} obiettivi di risparmio?`)) return;
    await apiDelete('savings_goals.php?action=bulk');
    load();
  }

  function daysLeft(targetDate) {
    if (!targetDate) return null;
    const diff = Math.ceil((new Date(targetDate) - new Date()) / 86400000);
    return diff;
  }

  return (
    <div>
      <div className="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <h4 className="text-xl font-bold">Obiettivi di risparmio</h4>
        <div className="flex flex-wrap gap-2">
          <button className="btn-outline text-expense" onClick={handleDeleteAll} disabled={!goals.length}><Trash size={15} /> Elimina tutti</button>
          <button className="btn-accent" onClick={openNew}><Plus size={15} /> Obiettivo</button>
        </div>
      </div>

      <div className="mb-4 rounded-xl border border-accent/20 bg-accentSoft px-4 py-3 text-sm text-gray-700">
        Imposta quanto vuoi mettere da parte ed entro quando. Il progresso si calcola da solo:
        di default sulla base del risparmio reale (entrate meno uscite) accumulato dalla data di
        inizio, oppure — se vuoi accantonare i soldi in modo esplicito — collega l'obiettivo a un
        conto dedicato e trasferiscici quello che vuoi mettere da parte: il progresso diventa il
        saldo di quel conto.
      </div>

      {goals.length === 0 ? (
        <div className="card p-8 text-center text-sm text-muted">
          Nessun obiettivo ancora. Il primo passo è deciderne uno — anche piccolo.
        </div>
      ) : (
        <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
          {goals.map((g) => {
            const left = daysLeft(g.target_date);
            const reached = g.pct >= 100;
            return (
              <div key={g.id} className="card p-5">
                <div className="mb-2 flex items-start justify-between">
                  <div className="flex items-center gap-2">
                    <div className={`flex h-8 w-8 items-center justify-center rounded-lg ${reached ? 'bg-income/15' : 'bg-accentSoft'}`}>
                      <Target size={16} className={reached ? 'text-income' : 'text-accent'} />
                    </div>
                    <span className="font-semibold">{g.name}</span>
                  </div>
                  <div className="flex items-center gap-1">
                    <button onClick={() => openEdit(g)} className="text-muted hover:text-accent"><Pencil size={15} /></button>
                    <button onClick={() => handleDelete(g.id)} className="text-muted hover:text-red-600"><Trash2 size={15} /></button>
                  </div>
                </div>
                <div className="mb-1 flex items-baseline justify-between">
                  <span className="font-mono text-lg font-semibold">{euro(g.current_amount)}</span>
                  <span className="text-sm text-muted">di {euro(g.target_amount)}</span>
                </div>
                {g.account_id && accountName(g.account_id) && (
                  <div className="mb-1 flex items-center gap-1 text-xs text-muted">
                    <Wallet size={12} /> Accantonato in "{accountName(g.account_id)}"
                  </div>
                )}
                <ProgressBar pct={g.pct} color={reached ? '#10b981' : '#4f46e5'} />
                <div className="mt-2 flex items-center justify-between text-xs text-muted">
                  <span>{g.pct}%</span>
                  {g.target_date && (
                    <span>{reached ? 'Obiettivo raggiunto 🎉' : left >= 0 ? `${left} giorni rimasti` : 'Scaduto'}</span>
                  )}
                </div>
                {!reached && (
                  <button className="btn-outline mt-3 w-full justify-center text-sm" onClick={() => setPlanGoal(g)}>
                    <Compass size={15} /> Piano per raggiungerlo
                  </button>
                )}
              </div>
            );
          })}
        </div>
      )}

      <Modal open={modalOpen} onClose={() => setModalOpen(false)} title={form.id ? 'Modifica obiettivo' : 'Nuovo obiettivo'}
        footer={<>
          <button type="button" className="btn-outline" onClick={() => setModalOpen(false)}>Annulla</button>
          <button type="submit" form="goal-form" className="btn-accent">Salva</button>
        </>}
      >
        {error && <div className="mb-3 rounded-lg bg-red-50 px-3 py-2 text-sm text-red-600">{error}</div>}
        <form id="goal-form" onSubmit={handleSubmit} className="space-y-3">
          <div>
            <label className="field-label">Nome obiettivo</label>
            <input className="field" required placeholder="Es. Fondo di emergenza, Vacanza..." value={form.name} onChange={(e) => setForm((f) => ({ ...f, name: e.target.value }))} />
          </div>
          <div>
            <label className="field-label">Importo obiettivo (€)</label>
            <input className="field" type="number" step="0.01" min="0" required value={form.target_amount} onChange={(e) => setForm((f) => ({ ...f, target_amount: e.target.value }))} />
          </div>
          <div>
            <label className="field-label">Conto dedicato (opzionale)</label>
            <select className="field" value={form.account_id} onChange={(e) => setForm((f) => ({ ...f, account_id: e.target.value }))}>
              <option value="">Nessuno — calcolo automatico dal risparmio reale</option>
              {activeAccounts.map((a) => <option key={a.id} value={a.id}>{a.name}</option>)}
            </select>
          </div>
          {form.account_id ? (
            <div>
              <label className="field-label">Scadenza (opzionale)</label>
              <input className="field" type="date" value={form.target_date} onChange={(e) => setForm((f) => ({ ...f, target_date: e.target.value }))} />
              <p className="mt-1 text-xs text-muted">
                Il progresso sarà il saldo reale di questo conto: trasferiscici quello che vuoi
                accantonare per l'obiettivo (dalla vista Transazioni, come trasferimento tra conti).
              </p>
            </div>
          ) : (
            <div className="grid grid-cols-2 gap-3">
              <div>
                <label className="field-label">Inizia a contare dal</label>
                <input className="field" type="date" required value={form.start_date} onChange={(e) => setForm((f) => ({ ...f, start_date: e.target.value }))} />
              </div>
              <div>
                <label className="field-label">Scadenza (opzionale)</label>
                <input className="field" type="date" value={form.target_date} onChange={(e) => setForm((f) => ({ ...f, target_date: e.target.value }))} />
              </div>
            </div>
          )}
          {!form.account_id && (
            <p className="text-xs text-muted">
              Il progresso somma tutte le tue entrate e uscite (esclusi trasferimenti e investimenti) da
              questa data ad oggi — su tutti i conti insieme.
            </p>
          )}
        </form>
      </Modal>

      <GoalPlan goal={planGoal} onClose={() => setPlanGoal(null)} />
    </div>
  );
}
