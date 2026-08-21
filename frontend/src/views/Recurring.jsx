import { useState, useEffect, useCallback } from 'react';
import { Plus, RefreshCw, Trash, Trash2, Pencil } from 'lucide-react';
import { useData } from '../context/DataContext';
import { apiGet, apiSend, apiDelete } from '../api';
import { euro, formatDate, capitalize, todayISO } from '../lib/format';
import { KNOWN_SUBSCRIPTIONS } from '../lib/subscriptions';
import Modal from '../components/Modal';

const empty = {
  type: 'uscita', amount: '', account_id: '', category_id: '', transfer_account_id: '',
  frequency: 'mensile', interval_count: 1, start_date: todayISO(), end_date: '', description: '',
};

export default function Recurring() {
  const { activeAccounts, categories, expenseCategories, incomeCategories, signal, bump } = useData();
  const [rows, setRows] = useState([]);
  const [modalOpen, setModalOpen] = useState(false);
  const [editing, setEditing] = useState(null);
  const [form, setForm] = useState(empty);
  const [error, setError] = useState('');

  const load = useCallback(async () => setRows(await apiGet('recurring.php')), []);
  useEffect(() => { load(); }, [load, signal]);

  const isTransfer = form.type === 'trasferimento';
  const categoryOptions = form.type === 'entrata' ? incomeCategories : expenseCategories;

  function openNew() {
    setEditing(null);
    setForm({ ...empty, account_id: activeAccounts[0]?.id || '', category_id: expenseCategories[0]?.id || '' });
    setError('');
    setModalOpen(true);
  }

  function openEdit(r) {
    setEditing(r);
    setForm({
      type: r.type,
      amount: r.amount,
      account_id: r.account_id,
      category_id: r.category_id,
      transfer_account_id: r.transfer_account_id || '',
      frequency: r.frequency,
      interval_count: r.interval_count,
      start_date: r.start_date,
      end_date: r.end_date || '',
      description: r.description || '',
    });
    setError('');
    setModalOpen(true);
  }

  function pickKnownService(name) {
    if (!name) return;
    const subsCategory = expenseCategories.find((c) => c.name === 'Abbonamenti');
    setForm((f) => ({
      ...f,
      type: 'uscita',
      description: name === 'Altro abbonamento' ? '' : name,
      category_id: subsCategory?.id || f.category_id,
      frequency: 'mensile',
    }));
  }

  async function handleSubmit(e) {
    e.preventDefault();
    const uscitaCategory = categories.find((c) => c.type === 'uscita');
    const payload = {
      type: form.type,
      amount: parseFloat(form.amount),
      account_id: form.account_id,
      category_id: isTransfer ? uscitaCategory?.id : form.category_id,
      transfer_account_id: isTransfer ? form.transfer_account_id : null,
      frequency: form.frequency,
      interval_count: parseInt(form.interval_count || '1', 10),
      start_date: form.start_date,
      end_date: form.end_date || null,
      description: form.description,
    };
    try {
      if (editing) {
        await apiSend('recurring.php', 'PUT', { ...payload, id: editing.id });
      } else {
        await apiSend('recurring.php', 'POST', payload);
      }
      setModalOpen(false);
      load();
    } catch (err) { setError(err.message); }
  }

  async function handleDelete(id) {
    if (!confirm('Eliminare questa ricorrenza? Le transazioni già generate non verranno cancellate.')) return;
    await apiDelete(`recurring.php?id=${id}`);
    load();
  }

  async function handleProcess() {
    const res = await apiSend('recurring.php?action=process', 'POST', {});
    alert(`${res.created} transazioni generate.`);
    load();
    bump();
  }

  async function handleDeleteAll() {
    if (!rows.length) return;
    if (!confirm(`Eliminare tutte le ${rows.length} ricorrenze? Le transazioni già generate non verranno cancellate.`)) return;
    await apiDelete('recurring.php?action=bulk');
    load();
  }

  return (
    <div>
      <div className="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <h4 className="text-xl font-bold">Transazioni ricorrenti</h4>
        <div className="flex flex-wrap gap-2">
          <button className="btn-outline" onClick={handleProcess}><RefreshCw size={15} /> Genera dovute ora</button>
          <button className="btn-outline text-expense" onClick={handleDeleteAll} disabled={!rows.length}><Trash size={15} /> Elimina tutte</button>
          <button className="btn-accent" onClick={openNew}><Plus size={15} /> Ricorrenza</button>
        </div>
      </div>

      <div className="mb-4 rounded-xl border border-accent/20 bg-accentSoft px-4 py-3 text-sm text-gray-700">
        Le ricorrenze sono movimenti che si ripetono da soli — lo stipendio ogni mese, l'affitto, un
        abbonamento streaming. Impostale una volta sola e l'app genera automaticamente la transazione
        quando è il momento, senza che tu debba inserirla ogni volta a mano.
      </div>

      <div className="card overflow-hidden">
        <div className="overflow-x-auto">
        <table className="w-full text-sm">
          <thead>
            <tr className="border-b border-border text-left text-[11px] uppercase tracking-wide text-muted">
              <th className="px-4 py-3">Descrizione</th><th className="px-4 py-3">Conto</th><th className="px-4 py-3">Categoria</th>
              <th className="px-4 py-3">Frequenza</th><th className="px-4 py-3">Prossima</th><th className="px-4 py-3 text-right">Importo</th><th></th>
            </tr>
          </thead>
          <tbody>
            {rows.length ? rows.map((r) => {
              const label = r.type === 'trasferimento' ? `→ ${r.transfer_account_name}` : r.category_name;
              const sign = r.type === 'entrata' ? '+' : (r.type === 'uscita' ? '-' : '');
              const cls = r.type === 'entrata' ? 'text-income font-semibold' : (r.type === 'uscita' ? 'text-expense font-semibold' : '');
              return (
                <tr key={r.id} className="border-b border-border last:border-0 hover:bg-gray-50">
                  <td className="px-4 py-3">{r.description || '—'}</td>
                  <td className="px-4 py-3">{r.account_name}</td>
                  <td className="px-4 py-3">{label}</td>
                  <td className="px-4 py-3">{capitalize(r.frequency)}{r.interval_count > 1 ? ` (ogni ${r.interval_count})` : ''}</td>
                  <td className="px-4 py-3">{formatDate(r.next_run)}</td>
                  <td className={`px-4 py-3 text-right font-mono ${cls}`}>{sign} {euro(r.amount)}</td>
                  <td className="px-4 py-3 text-right">
                    <button onClick={() => openEdit(r)} className="mr-2 text-muted hover:text-accent"><Pencil size={15} /></button>
                    <button onClick={() => handleDelete(r.id)} className="text-muted hover:text-expense"><Trash2 size={15} /></button>
                  </td>
                </tr>
              );
            }) : <tr><td colSpan={7} className="px-4 py-8 text-center text-muted">Nessuna ricorrenza impostata</td></tr>}
          </tbody>
        </table>
        </div>
      </div>

      <Modal open={modalOpen} onClose={() => setModalOpen(false)} title={editing ? 'Modifica ricorrenza' : 'Nuova transazione ricorrente'}
        footer={<>
          <button type="button" className="btn-outline" onClick={() => setModalOpen(false)}>Annulla</button>
          <button type="submit" form="recurring-form" className="btn-accent">Salva</button>
        </>}
      >
        {error && <div className="mb-3 rounded-lg bg-red-50 px-3 py-2 text-sm text-red-600">{error}</div>}

        {!editing && <div className="mb-4">
          <label className="field-label">È un abbonamento noto? (opzionale, compila da solo i campi sotto)</label>
          <select className="field" defaultValue="" onChange={(e) => { pickKnownService(e.target.value); e.target.value = ''; }}>
            <option value="">Seleziona un servizio...</option>
            {KNOWN_SUBSCRIPTIONS.map((s) => (
              <option key={s.name} value={s.name}>{s.name}{s.hint ? ` (${s.hint})` : ''}</option>
            ))}
          </select>
          <div className="my-4 flex items-center gap-3 text-xs text-muted">
            <div className="h-px flex-1 bg-border"></div>oppure compila tutto a mano<div className="h-px flex-1 bg-border"></div>
          </div>
        </div>}

        <form id="recurring-form" onSubmit={handleSubmit} className="space-y-3">
          <div className="grid grid-cols-2 gap-3">
            <div>
              <label className="field-label">Tipo</label>
              <select className="field" value={form.type} onChange={(e) => setForm((f) => ({ ...f, type: e.target.value }))}>
                <option value="uscita">Uscita</option><option value="entrata">Entrata</option><option value="trasferimento">Trasferimento</option>
              </select>
            </div>
            <div>
              <label className="field-label">Importo (€)</label>
              <input className="field" type="number" step="0.01" min="0" required value={form.amount} onChange={(e) => setForm((f) => ({ ...f, amount: e.target.value }))} />
            </div>
          </div>
          <div className="grid grid-cols-2 gap-3">
            <div>
              <label className="field-label">Conto</label>
              <select className="field" required value={form.account_id} onChange={(e) => setForm((f) => ({ ...f, account_id: e.target.value }))}>
                {activeAccounts.map((a) => <option key={a.id} value={a.id}>{a.name}</option>)}
              </select>
            </div>
            {isTransfer ? (
              <div>
                <label className="field-label">Conto destinazione</label>
                <select className="field" value={form.transfer_account_id} onChange={(e) => setForm((f) => ({ ...f, transfer_account_id: e.target.value }))}>
                  <option value="">Seleziona...</option>
                  {activeAccounts.map((a) => <option key={a.id} value={a.id}>{a.name}</option>)}
                </select>
              </div>
            ) : (
              <div>
                <label className="field-label">Categoria</label>
                <select className="field" required value={form.category_id} onChange={(e) => setForm((f) => ({ ...f, category_id: e.target.value }))}>
                  {categoryOptions.map((c) => <option key={c.id} value={c.id}>{c.name}</option>)}
                </select>
              </div>
            )}
          </div>
          <div className="grid grid-cols-2 gap-3">
            <div>
              <label className="field-label">Frequenza</label>
              <select className="field" value={form.frequency} onChange={(e) => setForm((f) => ({ ...f, frequency: e.target.value }))}>
                <option value="mensile">Mensile</option><option value="settimanale">Settimanale</option>
                <option value="giornaliera">Giornaliera</option><option value="annuale">Annuale</option>
              </select>
            </div>
            <div>
              <label className="field-label">Ogni</label>
              <input className="field" type="number" min="1" value={form.interval_count} onChange={(e) => setForm((f) => ({ ...f, interval_count: e.target.value }))} />
            </div>
          </div>
          <div className="grid grid-cols-2 gap-3">
            <div>
              <label className="field-label">Data inizio</label>
              <input className="field" type="date" required value={form.start_date} onChange={(e) => setForm((f) => ({ ...f, start_date: e.target.value }))} />
            </div>
            <div>
              <label className="field-label">Data fine (opzionale)</label>
              <input className="field" type="date" value={form.end_date} onChange={(e) => setForm((f) => ({ ...f, end_date: e.target.value }))} />
            </div>
          </div>
          <div>
            <label className="field-label">Descrizione</label>
            <input className="field" placeholder="Es. Affitto, Netflix, Stipendio" value={form.description} onChange={(e) => setForm((f) => ({ ...f, description: e.target.value }))} />
          </div>
        </form>
      </Modal>
    </div>
  );
}
