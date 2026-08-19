import { useState, useEffect, useCallback } from 'react';
import { Plus, Sparkles, Trash, Trash2 } from 'lucide-react';
import { useData } from '../context/DataContext';
import { apiGet, apiSend, apiDelete } from '../api';
import { euro, formatDate, todayISO } from '../lib/format';
import { KNOWN_SUBSCRIPTIONS } from '../lib/subscriptions';
import Modal from '../components/Modal';
import StatCard from '../components/StatCard';
import ServiceIcon from '../components/ServiceIcon';

// Trova la corrispondenza nella lista dei servizi noti in base al nome scritto
// dall'utente (es. "Netflix" o "netflix mensile") — match esatto o parziale.
function matchKnownService(text) {
  if (!text) return null;
  const lower = text.toLowerCase();
  return KNOWN_SUBSCRIPTIONS.find((s) => s.name !== 'Altro abbonamento' && lower.includes(s.name.toLowerCase())) || null;
}

const empty = {
  description: '', amount: '', account_id: '', category_id: '',
  frequency: 'mensile', interval_count: 1, start_date: todayISO(),
};

// Normalizza qualunque frequenza in un costo mensile equivalente, per poter
// sommare abbonamenti mensili, annuali e settimanali nello stesso totale.
function monthlyEquivalent(amount, frequency, intervalCount) {
  const n = Math.max(1, intervalCount || 1);
  switch (frequency) {
    case 'giornaliera': return (amount * 30.44) / n;
    case 'settimanale': return (amount * 4.345) / n;
    case 'annuale': return amount / (12 * n);
    default: return amount / n; // mensile
  }
}

export default function Subscriptions() {
  const { activeAccounts, expenseCategories, signal, bump } = useData();
  const [recurring, setRecurring] = useState([]);
  const [detected, setDetected] = useState([]);
  const [modalOpen, setModalOpen] = useState(false);
  const [form, setForm] = useState(empty);
  const [error, setError] = useState('');

  const load = useCallback(async () => {
    const [rec, an] = await Promise.all([apiGet('recurring.php'), apiGet('analytics.php')]);
    setRecurring(rec.filter((r) => r.category_name === 'Abbonamenti' && r.active));
    setDetected(an.detected_recurring || []);
  }, []);
  useEffect(() => { load(); }, [load, signal]);

  const totalMonthly = recurring.reduce((sum, r) => sum + monthlyEquivalent(parseFloat(r.amount), r.frequency, r.interval_count), 0);

  function openNew() {
    const subsCategory = expenseCategories.find((c) => c.name === 'Abbonamenti');
    setForm({ ...empty, account_id: activeAccounts[0]?.id || '', category_id: subsCategory?.id || '' });
    setError('');
    setModalOpen(true);
  }

  function pickKnownService(name) {
    if (!name) return;
    setForm((f) => ({ ...f, description: name === 'Altro abbonamento' ? '' : name }));
  }

  // Precompila il form con un pattern rilevato automaticamente, cosi' basta
  // confermare invece di reinserire tutto a mano.
  function openFromDetected(d) {
    setForm({
      description: d.category_name,
      amount: d.avg_amount,
      account_id: activeAccounts[0]?.id || '',
      category_id: d.category_id,
      frequency: d.frequency_detected === 'settimanale' ? 'settimanale'
        : d.frequency_detected === 'annuale' ? 'annuale' : 'mensile',
      interval_count: 1,
      start_date: d.suggested_start_date || todayISO(),
    });
    setError('');
    setModalOpen(true);
  }

  async function handleSubmit(e) {
    e.preventDefault();
    const payload = {
      type: 'uscita',
      amount: parseFloat(form.amount),
      account_id: form.account_id,
      category_id: form.category_id,
      frequency: form.frequency,
      interval_count: 1,
      start_date: form.start_date,
      end_date: null,
      description: form.description,
    };
    try {
      await apiSend('recurring.php', 'POST', payload);
      setModalOpen(false);
      load();
      bump();
    } catch (err) { setError(err.message); }
  }

  async function handleDelete(id) {
    if (!confirm('Eliminare questo abbonamento? Le transazioni gia\' generate non verranno cancellate.')) return;
    await apiDelete(`recurring.php?id=${id}`);
    load();
    bump();
  }

  async function handleDeleteAll() {
    if (!recurring.length) return;
    const subsCategory = expenseCategories.find((c) => c.name === 'Abbonamenti');
    if (!subsCategory) return;
    if (!confirm(`Eliminare tutti i ${recurring.length} abbonamenti? Le transazioni già generate non verranno cancellate.`)) return;
    await apiDelete(`recurring.php?action=bulk&category_id=${subsCategory.id}`);
    load();
    bump();
  }

  return (
    <div>
      <div className="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <h4 className="text-xl font-bold">Abbonamenti</h4>
        <div className="flex flex-wrap gap-2">
          <button className="btn-outline text-expense" onClick={handleDeleteAll} disabled={!recurring.length}><Trash size={15} /> Elimina tutti</button>
          <button className="btn-accent" onClick={openNew}><Plus size={15} /> Abbonamento</button>
        </div>
      </div>

      <div className="mb-4 rounded-xl border border-accent/20 bg-accentSoft px-4 py-3 text-sm text-gray-700">
        Qui vedi tutti gli abbonamenti che paghi ogni mese in un unico posto — Netflix, Spotify,
        palestra, tutto quello che si rinnova da solo. L'app segnala anche pattern di spesa che
        sembrano abbonamenti ma non hai ancora dichiarato come tali: capita più spesso di quanto si pensi
        di dimenticarsi di un rinnovo.
      </div>

      <div className="mb-4 grid grid-cols-1 gap-3 sm:grid-cols-2">
        <StatCard label="Spesa mensile in abbonamenti" value={euro(totalMonthly)} />
        <StatCard label="Abbonamenti attivi dichiarati" value={String(recurring.length)} />
      </div>

      <div className="card mb-4 p-5">
        <h6 className="mb-3 text-sm font-semibold">I tuoi abbonamenti</h6>
        {recurring.length ? (
          <div className="space-y-2">
            {recurring.map((r) => {
              const match = matchKnownService(r.description);
              return (
                <div key={r.id} className="flex flex-col gap-2 rounded-xl border border-border px-4 py-3 sm:flex-row sm:items-center sm:justify-between">
                  <div className="flex min-w-0 items-center gap-3">
                    <ServiceIcon name={r.description} slug={match?.slug} color={match?.color} />
                    <div className="min-w-0">
                      <div className="font-medium">{r.description || 'Abbonamento'}</div>
                      <div className="text-xs text-muted">
                        {r.frequency} · dal conto {r.account_name} · prossimo addebito {formatDate(r.next_run)}
                      </div>
                    </div>
                  </div>
                  <div className="flex flex-shrink-0 items-center gap-3 pl-11 sm:pl-0">
                    <div className="font-mono text-sm font-semibold">{euro(r.amount)}</div>
                    <button className="text-muted hover:text-red-600" onClick={() => handleDelete(r.id)}><Trash2 size={15} /></button>
                  </div>
                </div>
              );
            })}
          </div>
        ) : (
          <p className="text-sm text-muted">Non hai ancora dichiarato nessun abbonamento. Aggiungine uno, o guarda qui sotto se l'app ne ha rilevati automaticamente.</p>
        )}
      </div>

      {detected.length > 0 && (
        <div className="card p-5">
          <h6 className="mb-1 flex items-center gap-1.5 text-sm font-semibold">
            <Sparkles size={15} className="text-accent" /> Forse hai anche questi
          </h6>
          <p className="mb-3 text-xs text-muted">
            Spese che si ripetono a intervalli regolari ma non risultano dichiarate come ricorrenti — potrebbero essere abbonamenti dimenticati.
          </p>
          <div className="space-y-2">
            {detected.map((d, idx) => {
              const match = matchKnownService(d.category_name);
              return (
                <div key={idx} className="flex flex-col gap-2 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 sm:flex-row sm:items-center sm:justify-between">
                  <div className="flex min-w-0 items-center gap-3">
                    <ServiceIcon name={d.category_name} slug={match?.slug} color={match?.color || '#f59e0b'} />
                    <div className="min-w-0">
                      <div className="font-medium">{d.category_name}</div>
                      <div className="text-xs text-muted">
                        {d.occurrences} volte rilevate · circa ogni {d.frequency_detected} · ultima il {formatDate(d.last_date)}
                      </div>
                    </div>
                  </div>
                  <div className="flex flex-shrink-0 items-center gap-3 pl-11 sm:pl-0">
                    <div className="font-mono text-sm font-semibold">{euro(d.avg_amount)}</div>
                    <button className="btn-outline text-xs" onClick={() => openFromDetected(d)}>Dichiara</button>
                  </div>
                </div>
              );
            })}
          </div>
        </div>
      )}

      <Modal open={modalOpen} onClose={() => setModalOpen(false)} title="Nuovo abbonamento"
        footer={<>
          <button type="button" className="btn-outline" onClick={() => setModalOpen(false)}>Annulla</button>
          <button type="submit" form="subscription-form" className="btn-accent">Salva</button>
        </>}
      >
        {error && <div className="mb-3 rounded-lg bg-red-50 px-3 py-2 text-sm text-red-600">{error}</div>}
        <div className="mb-4">
          <label className="field-label">È un servizio noto?</label>
          <select className="field" defaultValue="" onChange={(e) => { pickKnownService(e.target.value); e.target.value = ''; }}>
            <option value="">Seleziona un servizio...</option>
            {KNOWN_SUBSCRIPTIONS.map((s) => (
              <option key={s.name} value={s.name}>{s.name}{s.hint ? ` (${s.hint})` : ''}</option>
            ))}
          </select>
        </div>
        <form id="subscription-form" onSubmit={handleSubmit} className="space-y-3">
          <div>
            <label className="field-label">Nome</label>
            <input className="field" required placeholder="Es. Netflix" value={form.description} onChange={(e) => setForm((f) => ({ ...f, description: e.target.value }))} />
          </div>
          <div className="grid grid-cols-2 gap-3">
            <div>
              <label className="field-label">Importo (€)</label>
              <input className="field" type="number" step="0.01" min="0" required value={form.amount} onChange={(e) => setForm((f) => ({ ...f, amount: e.target.value }))} />
            </div>
            <div>
              <label className="field-label">Frequenza</label>
              <select className="field" value={form.frequency} onChange={(e) => setForm((f) => ({ ...f, frequency: e.target.value }))}>
                <option value="mensile">Mensile</option><option value="annuale">Annuale</option><option value="settimanale">Settimanale</option>
              </select>
            </div>
          </div>
          <div className="grid grid-cols-2 gap-3">
            <div>
              <label className="field-label">Conto addebito</label>
              <select className="field" required value={form.account_id} onChange={(e) => setForm((f) => ({ ...f, account_id: e.target.value }))}>
                {activeAccounts.map((a) => <option key={a.id} value={a.id}>{a.name}</option>)}
              </select>
            </div>
            <div>
              <label className="field-label">Categoria</label>
              <select className="field" required value={form.category_id} onChange={(e) => setForm((f) => ({ ...f, category_id: e.target.value }))}>
                {expenseCategories.map((c) => <option key={c.id} value={c.id}>{c.name}</option>)}
              </select>
            </div>
          </div>
          <div>
            <label className="field-label">Prossimo/primo addebito</label>
            <input className="field" type="date" required value={form.start_date} onChange={(e) => setForm((f) => ({ ...f, start_date: e.target.value }))} />
          </div>
        </form>
      </Modal>
    </div>
  );
}
