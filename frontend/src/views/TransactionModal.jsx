import { useState, useEffect } from 'react';
import Modal from '../components/Modal';
import { useData } from '../context/DataContext';
import { apiSend } from '../api';
import { todayISO } from '../lib/format';
import { KNOWN_PAYEES } from '../lib/payees';
import ServiceIcon from '../components/ServiceIcon';

const empty = {
  id: null,
  type: 'uscita',
  amount: '',
  account_id: '',
  category_id: '',
  transfer_account_id: '',
  date: todayISO(),
  description: '',
};

export default function TransactionModal({ open, onClose, onSaved, editingTx = null }) {
  const { activeAccounts, categories, expenseCategories, incomeCategories } = useData();
  const [form, setForm] = useState(empty);
  const [error, setError] = useState('');
  const [showPayeeResults, setShowPayeeResults] = useState(false);

  const payeeResults = form.description.trim().length >= 1
    ? KNOWN_PAYEES.filter((p) => p.name.toLowerCase().includes(form.description.trim().toLowerCase())).slice(0, 6)
    : [];

  function pickPayee(payee) {
    const matchedCategory = expenseCategories.find((c) => c.name === payee.category_hint);
    setForm((f) => ({ ...f, description: payee.name, category_id: matchedCategory ? matchedCategory.id : f.category_id }));
    setShowPayeeResults(false);
  }

  useEffect(() => {
    if (!open) return;
    if (editingTx) {
      setForm({
        id: editingTx.id,
        type: editingTx.type,
        amount: editingTx.amount,
        account_id: editingTx.account_id,
        category_id: editingTx.category_id,
        transfer_account_id: editingTx.transfer_account_id || '',
        date: editingTx.date,
        description: editingTx.description || '',
      });
    } else {
      setForm({
        ...empty,
        date: todayISO(),
        account_id: activeAccounts[0]?.id || '',
        category_id: expenseCategories[0]?.id || '',
      });
    }
    setError('');
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [open, editingTx, activeAccounts]);

  const isTransfer = form.type === 'trasferimento';
  const selectedAccount = activeAccounts.find((a) => String(a.id) === String(form.account_id));
  const categoryOptions = form.type === 'entrata' ? incomeCategories : expenseCategories;

  // Se l'utente cambia tipo (entrata/uscita) dopo l'apertura, la categoria
  // corrente potrebbe non appartenere piu' alla lista giusta: la aggiorniamo
  // sempre alla prima disponibile per il nuovo tipo.
  useEffect(() => {
    if (isTransfer || !categoryOptions.length) return;
    const stillValid = categoryOptions.some((c) => c.id === form.category_id);
    if (!stillValid) {
      setForm((f) => ({ ...f, category_id: categoryOptions[0].id }));
    }
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [form.type, categoryOptions.length]);

  function set(field, value) {
    setForm((f) => ({ ...f, [field]: value }));
  }

  async function handleSubmit(e) {
    e.preventDefault();
    setError('');
    const uscitaCategory = categories.find((c) => c.type === 'uscita');
    const payload = {
      type: form.type,
      amount: parseFloat(form.amount),
      account_id: form.account_id,
      category_id: isTransfer ? uscitaCategory?.id : form.category_id,
      transfer_account_id: isTransfer ? form.transfer_account_id : null,
      date: form.date,
      description: form.description,
    };
    try {
      if (form.id) {
        await apiSend('transactions.php', 'PUT', { ...payload, id: form.id });
      } else {
        await apiSend('transactions.php', 'POST', payload);
      }
      onSaved();
    } catch (err) {
      setError(err.message);
    }
  }

  return (
    <Modal open={open} onClose={onClose} title={form.id ? 'Modifica movimento' : 'Nuovo movimento'}
      footer={
        <>
          <button type="button" className="btn-outline" onClick={onClose}>Annulla</button>
          <button type="submit" form="transaction-form" className="btn-accent">Salva</button>
        </>
      }
    >
      {error && <div className="mb-3 rounded-lg bg-red-50 px-3 py-2 text-sm text-red-600">{error}</div>}
      <form id="transaction-form" onSubmit={handleSubmit} className="space-y-3">
        <div className="grid grid-cols-2 gap-3">
          <div>
            <label className="field-label">Tipo</label>
            <select className="field" value={form.type} onChange={(e) => set('type', e.target.value)}>
              <option value="uscita">Uscita</option>
              <option value="entrata">Entrata</option>
              <option value="trasferimento">Trasferimento</option>
            </select>
          </div>
          <div>
            <label className="field-label">Importo {selectedAccount && selectedAccount.currency !== 'EUR' ? `(${selectedAccount.currency})` : '(€)'}</label>
            <input className="field" type="number" step="0.01" min="0" required
              value={form.amount} onChange={(e) => set('amount', e.target.value)} />
            {selectedAccount && selectedAccount.currency !== 'EUR' && selectedAccount.fx_rate && form.amount && (
              <p className="mt-1 text-xs text-muted">≈ {(parseFloat(form.amount) * selectedAccount.fx_rate).toLocaleString('it-IT', { style: 'currency', currency: 'EUR' })} (cambio {selectedAccount.fx_rate.toFixed(4)})</p>
            )}
          </div>
        </div>
        <div className="grid grid-cols-2 gap-3">
          <div>
            <label className="field-label">Conto</label>
            <select className="field" required value={form.account_id} onChange={(e) => set('account_id', e.target.value)}>
              {activeAccounts.map((a) => <option key={a.id} value={a.id}>{a.name}</option>)}
            </select>
          </div>
          {isTransfer ? (
            <div>
              <label className="field-label">Conto destinazione</label>
              <select className="field" value={form.transfer_account_id} onChange={(e) => set('transfer_account_id', e.target.value)}>
                <option value="">Seleziona...</option>
                {activeAccounts.map((a) => <option key={a.id} value={a.id}>{a.name}</option>)}
              </select>
            </div>
          ) : (
            <div>
              <label className="field-label">Categoria</label>
              <select className="field" required value={form.category_id} onChange={(e) => set('category_id', e.target.value)}>
                {categoryOptions.map((c) => <option key={c.id} value={c.id}>{c.name}</option>)}
              </select>
            </div>
          )}
        </div>
        <div className="grid grid-cols-2 gap-3">
          <div>
            <label className="field-label">Data</label>
            <input className="field" type="date" required value={form.date} onChange={(e) => set('date', e.target.value)} />
          </div>
          <div>
            <label className="field-label">Descrizione</label>
            <div className="relative">
              <input
                className="field"
                type="text"
                placeholder={form.type === 'uscita' ? 'Es. Eni, Esselunga, o scrivi liberamente' : 'Opzionale'}
                value={form.description}
                onChange={(e) => { set('description', e.target.value); setShowPayeeResults(true); }}
                onFocus={() => setShowPayeeResults(true)}
                onBlur={() => setTimeout(() => setShowPayeeResults(false), 150)}
              />
              {form.type === 'uscita' && showPayeeResults && payeeResults.length > 0 && (
                <div className="absolute z-10 mt-1 w-full overflow-hidden rounded-xl border border-border bg-white shadow-lg">
                  {payeeResults.map((p) => (
                    <button
                      type="button"
                      key={p.name}
                      onMouseDown={() => pickPayee(p)}
                      className="flex w-full items-center gap-2.5 px-3 py-2 text-left text-sm hover:bg-accentSoft"
                    >
                      <ServiceIcon name={p.name} domain={p.domain} color={p.color} size={22} />
                      <span>{p.name} <span className="text-xs text-muted">→ {p.category_hint}</span></span>
                    </button>
                  ))}
                </div>
              )}
            </div>
            {form.type === 'uscita' && (
              <p className="mt-1 text-xs text-muted">💡 Inizia a scrivere per cercare un esercente noto: compila anche la categoria da sola.</p>
            )}
          </div>
        </div>
      </form>
    </Modal>
  );
}
