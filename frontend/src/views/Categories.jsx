import { useState, useEffect, useCallback } from 'react';
import { Plus, X, Trash } from 'lucide-react';
import { useData } from '../context/DataContext';
import { apiGet, apiSend, apiDelete } from '../api';
import { euro } from '../lib/format';
import Modal from '../components/Modal';

export default function Categories() {
  const { categories, incomeCategories, expenseCategories, refreshCategories, signal } = useData();
  const [modalOpen, setModalOpen] = useState(false);
  const [form, setForm] = useState({ name: '', type: 'uscita', color: '#8b95a1', classification: null });
  const [budgets, setBudgets] = useState({});
  const [budgetInputs, setBudgetInputs] = useState({});
  const [rolloverInputs, setRolloverInputs] = useState({});

  const loadBudgets = useCallback(async () => {
    const data = await apiGet(`budgets.php?month=${new Date().toISOString().slice(0, 7)}`);
    const byId = {};
    data.budgets.forEach((b) => { byId[b.category_id] = b; });
    setBudgets(byId);
  }, []);

  useEffect(() => { loadBudgets(); }, [loadBudgets, signal]);

  async function handleSubmit(e) {
    e.preventDefault();
    if (form.id) await apiSend('categories.php', 'PUT', form);
    else await apiSend('categories.php', 'POST', form);
    setModalOpen(false);
    setForm({ name: '', type: 'uscita', color: '#8b95a1', classification: null });
    await refreshCategories();
  }

  function openNew() {
    setForm({ name: '', type: 'uscita', color: '#8b95a1', classification: null });
    setModalOpen(true);
  }

  function openEdit(c) {
    setForm({ id: c.id, name: c.name, type: c.type, color: c.color, classification: c.classification || null });
    setModalOpen(true);
  }

  async function handleDeleteCategory(id) {
    if (!confirm('Eliminare questa categoria?')) return;
    try {
      await apiDelete(`categories.php?id=${id}`);
      await refreshCategories();
    } catch {
      alert("Impossibile eliminare: probabilmente è usata da alcune transazioni.");
    }
  }

  async function handleDeleteAllCategories(type, list) {
    if (!list.length) return;
    const label = type === 'entrata' ? 'entrata' : 'uscita';
    if (!confirm(`Eliminare tutte le ${list.length} categorie di ${label}?`)) return;
    const res = await apiDelete(`categories.php?action=bulk&type=${type}`);
    await refreshCategories();
    if (res.skipped) {
      alert(`Eliminate ${res.deleted}, ${res.skipped} non eliminate perché usate da alcune transazioni.`);
    }
  }

  async function saveBudget(categoryId) {
    const value = parseFloat(budgetInputs[categoryId]);
    if (!value || value <= 0) { alert('Inserisci un limite maggiore di zero'); return; }
    const rollover = rolloverInputs[categoryId] ?? budgets[categoryId]?.rollover ?? false;
    await apiSend('budgets.php', 'POST', { category_id: categoryId, monthly_limit: value, rollover });
    loadBudgets();
  }

  async function deleteBudget(categoryId) {
    if (!confirm('Rimuovere il budget per questa categoria?')) return;
    await apiDelete(`budgets.php?category_id=${categoryId}`);
    loadBudgets();
  }

  const TagList = ({ list }) => (
    <div className="flex flex-wrap gap-2">
      {list.length ? list.map((c) => (
        <div key={c.id} className="flex items-center gap-1.5 rounded-full border border-border bg-gray-50 px-3 py-1.5 text-sm">
          <button onClick={() => openEdit(c)} className="flex items-center gap-1.5 hover:text-accent">
            <span className="h-2 w-2 rounded-full" style={{ background: c.color }} />
            {c.name}
          </button>
          <button onClick={() => handleDeleteCategory(c.id)} className="text-muted hover:text-expense"><X size={13} /></button>
        </div>
      )) : <span className="text-sm text-muted">Nessuna categoria</span>}
    </div>
  );

  return (
    <div>
      <div className="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <h4 className="text-xl font-bold">Categorie</h4>
        <button className="btn-accent" onClick={openNew}><Plus size={15} /> Categoria</button>
      </div>

      <div className="mb-4 rounded-xl border border-accent/20 bg-accentSoft px-4 py-3 text-sm text-gray-700">
        Le categorie raggruppano le tue transazioni per tipo (Spesa, Stipendio, Ristoranti...) — sono
        quello che rende utili i grafici e i budget. Ne trovi già alcune pronte, puoi aggiungerne altre
        o modificarle in qualsiasi momento.
      </div>

      <div className="mb-4 grid grid-cols-1 gap-4 md:grid-cols-2">
        <div className="card p-5">
          <div className="mb-3 flex items-center justify-between">
            <h6 className="text-sm font-semibold">Entrate</h6>
            <button className="text-xs text-muted hover:text-expense disabled:opacity-40" onClick={() => handleDeleteAllCategories('entrata', incomeCategories)} disabled={!incomeCategories.length}>
              <Trash size={12} className="mr-1 inline" />Elimina tutte
            </button>
          </div>
          <TagList list={incomeCategories} />
        </div>
        <div className="card p-5">
          <div className="mb-3 flex items-center justify-between">
            <h6 className="text-sm font-semibold">Uscite</h6>
            <button className="text-xs text-muted hover:text-expense disabled:opacity-40" onClick={() => handleDeleteAllCategories('uscita', expenseCategories)} disabled={!expenseCategories.length}>
              <Trash size={12} className="mr-1 inline" />Elimina tutte
            </button>
          </div>
          <TagList list={expenseCategories} />
        </div>
      </div>

      <div className="card p-5">
        <h6 className="mb-1 text-sm font-semibold">Budget mensile per categoria</h6>
        <p className="mb-3 text-xs text-muted">Imposta un limite di spesa per una categoria — l'app ti avvisa quando ti avvicini o lo superi, confrontando quanto hai già speso questo mese.</p>
        <div className="overflow-x-auto">
        <table className="w-full min-w-[640px] text-sm">
          <thead>
            <tr className="border-b border-border text-left text-[11px] uppercase tracking-wide text-muted">
              <th className="whitespace-nowrap py-2">Categoria</th>
              <th className="w-44 whitespace-nowrap py-2">Limite mensile (€)</th>
              <th className="w-28 whitespace-nowrap py-2">Riporta</th>
              <th className="w-32 whitespace-nowrap py-2">Disponibile</th>
              <th></th>
            </tr>
          </thead>
          <tbody>
            {expenseCategories.map((c) => {
              const b = budgets[c.id];
              const rolloverChecked = rolloverInputs[c.id] ?? b?.rollover ?? false;
              return (
                <tr key={c.id} className="border-b border-border last:border-0">
                  <td className="whitespace-nowrap py-2">
                    <span className="mr-2 inline-block h-2 w-2 rounded-full align-middle" style={{ background: c.color }} />
                    {c.name}
                  </td>
                  <td className="py-2">
                    <input
                      type="number" min="0" step="1" className="field"
                      placeholder="Nessun limite"
                      defaultValue={b ? b.monthly_limit : ''}
                      onChange={(e) => setBudgetInputs((s) => ({ ...s, [c.id]: e.target.value }))}
                    />
                  </td>
                  <td className="py-2">
                    <label className="flex items-center gap-1.5 whitespace-nowrap text-xs text-muted" title="L'eccedenza non spesa si riporta al mese successivo">
                      <input type="checkbox" checked={rolloverChecked} onChange={(e) => setRolloverInputs((s) => ({ ...s, [c.id]: e.target.checked }))} />
                      Rollover
                    </label>
                  </td>
                  <td className="whitespace-nowrap py-2 font-mono text-xs">
                    {b && b.rollover ? euro(b.available_this_month) : '—'}
                  </td>
                  <td className="whitespace-nowrap py-2 text-right">
                    <button className="btn-outline mr-2 text-xs" onClick={() => saveBudget(c.id)}>Salva</button>
                    {b && <button className="btn-outline-danger text-xs" onClick={() => deleteBudget(c.id)}>Rimuovi</button>}
                  </td>
                </tr>
              );
            })}
          </tbody>
        </table>
        </div>
      </div>

      <Modal
        open={modalOpen}
        onClose={() => setModalOpen(false)}
        title={form.id ? 'Modifica categoria' : 'Nuova categoria'}
        footer={<>
          <button type="button" className="btn-outline" onClick={() => setModalOpen(false)}>Annulla</button>
          <button type="submit" form="category-form" className="btn-accent">Salva</button>
        </>}
      >
        <form id="category-form" onSubmit={handleSubmit} className="space-y-3">
          <div>
            <label className="field-label">Nome</label>
            <input className="field" required value={form.name} onChange={(e) => setForm((f) => ({ ...f, name: e.target.value }))} />
          </div>
          <div className="grid grid-cols-2 gap-3">
            <div>
              <label className="field-label">Tipo</label>
              <select className="field" value={form.type} onChange={(e) => setForm((f) => ({ ...f, type: e.target.value }))}>
                <option value="uscita">Uscita</option>
                <option value="entrata">Entrata</option>
              </select>
            </div>
            <div>
              <label className="field-label">Colore</label>
              <input type="color" className="h-10 w-16 rounded" value={form.color} onChange={(e) => setForm((f) => ({ ...f, color: e.target.value }))} />
            </div>
          </div>
          {form.type === 'uscita' && (
            <div>
              <label className="field-label">Classificazione (opzionale)</label>
              <select className="field" value={form.classification || ''} onChange={(e) => setForm((f) => ({ ...f, classification: e.target.value || null }))}>
                <option value="">Nessuna</option>
                <option value="necessita">Necessità</option>
                <option value="desiderio">Desiderio</option>
                <option value="risparmio">Risparmio</option>
              </select>
              <p className="mt-1 text-xs text-muted">Usata solo per il riquadro di ripartizione nella pagina Budget — facoltativa.</p>
            </div>
          )}
        </form>
      </Modal>
    </div>
  );
}
