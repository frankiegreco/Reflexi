import { useState, useCallback, useEffect } from 'react';
import { Plus, Trash, Trash2 } from 'lucide-react';
import { useData } from '../context/DataContext';
import { apiGet, apiSend, apiDelete } from '../api';
import { euro, capitalize, formatDate, todayISO } from '../lib/format';
import { KNOWN_BANKS, matchBank } from '../lib/banks';
import ServiceIcon from '../components/ServiceIcon';
import Modal from '../components/Modal';

const TYPES = [
  ['corrente', 'Conto corrente'],
  ['risparmio', 'Risparmio'],
  ['contanti', 'Contanti'],
  ['carta_credito', 'Carta di credito'],
  ['investimento', 'Investimento'],
  ['altro', 'Altro'],
];

const CURRENCIES = ['EUR', 'USD', 'GBP', 'CHF', 'JPY', 'CAD', 'AUD'];

// Giorno precedente a una data ISO (YYYY-MM-DD), per mostrare la fine
// calcolata di un periodo di tasso (l'inizio del successivo meno un giorno).
function dayBefore(dateStr) {
  const [y, m, d] = dateStr.split('-').map(Number);
  const dt = new Date(y, m - 1, d);
  dt.setDate(dt.getDate() - 1);
  return `${dt.getFullYear()}-${String(dt.getMonth() + 1).padStart(2, '0')}-${String(dt.getDate()).padStart(2, '0')}`;
}

const empty = {
  id: null, name: '', type: 'corrente', initial_balance: 0, color: '#4f46e5', currency: 'EUR',
  interest_enabled: false, interest_rate: '', interest_tax_rate: 26, interest_frequency: 'annuale', next_interest_date: todayISO(),
  exclude_from_networth: false,
};

export default function Accounts() {
  const { accounts, refreshAccounts, bump } = useData();
  const [modalOpen, setModalOpen] = useState(false);
  const [form, setForm] = useState(empty);
  const [error, setError] = useState('');
  const [rates, setRates] = useState([]);
  const [rateForm, setRateForm] = useState({ effective_date: todayISO(), rate: '' });
  const [rateError, setRateError] = useState('');

  const loadRates = useCallback(async () => {
    if (!form.id) { setRates([]); return; }
    setRates(await apiGet(`account_interest_rates.php?account_id=${form.id}`));
  }, [form.id]);

  useEffect(() => { loadRates(); }, [loadRates]);

  function openNew() {
    setForm(empty);
    setError('');
    setModalOpen(true);
  }
  function openEdit(a) {
    setForm({
      id: a.id, name: a.name, type: a.type, initial_balance: a.initial_balance, color: a.color, currency: a.currency || 'EUR',
      interest_enabled: !!a.interest_enabled, interest_rate: a.interest_rate ?? '', interest_tax_rate: a.interest_tax_rate ?? 26,
      interest_frequency: a.interest_frequency || 'annuale', next_interest_date: a.next_interest_date || todayISO(),
      exclude_from_networth: !!a.exclude_from_networth,
    });
    setError('');
    setRateForm({ effective_date: todayISO(), rate: '' });
    setRateError('');
    setModalOpen(true);
  }

  async function addRate(e) {
    e.preventDefault();
    setRateError('');
    try {
      await apiSend('account_interest_rates.php', 'POST', { account_id: form.id, effective_date: rateForm.effective_date, rate: parseFloat(rateForm.rate) });
      setRateForm({ effective_date: todayISO(), rate: '' });
      await loadRates();
    } catch (err) { setRateError(err.message); }
  }

  async function deleteRate(id) {
    if (!confirm('Eliminare questa variazione di tasso?')) return;
    setRateError('');
    try {
      await apiDelete(`account_interest_rates.php?id=${id}`);
      await loadRates();
    } catch (err) { setRateError(err.message); }
  }

  async function handleSubmit(e) {
    e.preventDefault();
    const payload = {
      name: form.name, type: form.type, initial_balance: parseFloat(form.initial_balance || '0'), color: form.color, currency: form.currency,
      interest_enabled: form.interest_enabled,
      interest_rate: form.interest_enabled ? parseFloat(form.interest_rate || '0') : null,
      interest_tax_rate: parseFloat(form.interest_tax_rate || '26'),
      interest_frequency: form.interest_frequency,
      next_interest_date: form.interest_enabled ? form.next_interest_date : null,
      exclude_from_networth: form.exclude_from_networth,
    };
    try {
      if (form.id) await apiSend('accounts.php', 'PUT', { ...payload, id: form.id });
      else await apiSend('accounts.php', 'POST', payload);
      setModalOpen(false);
      await refreshAccounts();
      bump();
    } catch (err) {
      setError(err.message);
    }
  }

  async function handleDelete(id) {
    if (!confirm('Eliminare questo conto e tutte le sue transazioni?')) return;
    await apiDelete(`accounts.php?id=${id}`);
    await refreshAccounts();
    bump();
  }

  async function handleDeleteAll() {
    if (!accounts.length) return;
    if (!confirm(`Eliminare TUTTI i ${accounts.length} conti e TUTTE le transazioni collegate? L'operazione è irreversibile e cancella l'intero storico.`)) return;
    await apiDelete('accounts.php?action=bulk');
    await refreshAccounts();
    bump();
  }

  return (
    <div>
      <div className="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <h4 className="text-xl font-bold">Conti</h4>
        <div className="flex flex-wrap gap-2">
          <button className="btn-outline text-expense" onClick={handleDeleteAll} disabled={!accounts.length}><Trash size={15} /> Elimina tutti</button>
          <button className="btn-accent" onClick={openNew}><Plus size={15} /> Conto</button>
        </div>
      </div>

      <div className="mb-4 rounded-xl border border-accent/20 bg-accentSoft px-4 py-3 text-sm text-gray-700">
        Un conto è un contenitore di denaro reale: il tuo conto corrente, il contante che hai in
        tasca, una carta di credito. Il saldo si calcola da solo sommando le transazioni al saldo
        iniziale che imposti alla creazione — non serve aggiornarlo a mano. Se un conto matura
        interessi (conto deposito, vincolato o corrente remunerato), attivalo qui sotto: troverai
        poi la gestione in <strong>Investimenti</strong>.
      </div>

      <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
        {accounts.map((a) => {
          const bank = matchBank(a.name);
          return (
          <div key={a.id} className="card border-l-4 p-4" style={{ borderLeftColor: a.color }}>
            <div className="mb-1 flex items-center gap-2">
              {bank && <ServiceIcon name={bank.name} domain={bank.domain} color={bank.color} size={24} />}
              <div className="font-semibold">{a.name}{a.archived ? ' (archiviato)' : ''}</div>
            </div>
            <div className="mb-2.5 text-xs text-muted">
              {capitalize(a.type)}
              {!!a.interest_enabled && <span className="ml-1.5 rounded-full bg-income/15 px-1.5 py-0.5 text-[10px] font-semibold text-income">{a.interest_rate}% interesse</span>}
              {!!a.exclude_from_networth && <span className="ml-1.5 rounded-full bg-gray-200 px-1.5 py-0.5 text-[10px] font-semibold text-gray-600">escluso dal patrimonio</span>}
            </div>
            <div className="font-mono text-xl font-semibold">
              {a.currency && a.currency !== 'EUR' ? `${a.balance.toLocaleString('it-IT', { minimumFractionDigits: 2 })} ${a.currency}` : euro(a.balance)}
            </div>
            {a.currency && a.currency !== 'EUR' && (
              <div className="mt-0.5 text-xs text-muted">
                {a.balance_eur !== null ? (
                  <>≈ {euro(a.balance_eur)} <span className={a.fx_stale ? 'text-loan' : ''}>(cambio {a.fx_rate?.toFixed(4)}{a.fx_stale ? ', non aggiornato' : ''})</span></>
                ) : 'Cambio non disponibile al momento'}
              </div>
            )}
            <div className="mt-3 flex gap-2">
              <button className="btn-outline text-xs" onClick={() => openEdit(a)}>Modifica</button>
              <button className="btn-outline-danger text-xs" onClick={() => handleDelete(a.id)}>Elimina</button>
            </div>
          </div>
          );
        })}
      </div>

      <Modal
        open={modalOpen}
        onClose={() => setModalOpen(false)}
        title={form.id ? 'Modifica conto' : 'Nuovo conto'}
        footer={<>
          <button type="button" className="btn-outline" onClick={() => setModalOpen(false)}>Annulla</button>
          <button type="submit" form="account-form" className="btn-accent">Salva</button>
        </>}
      >
        {error && <div className="mb-3 rounded-lg bg-red-50 px-3 py-2 text-sm text-red-600">{error}</div>}
        <form id="account-form" onSubmit={handleSubmit} className="space-y-3">
          <div>
            <label className="field-label">Nome</label>
            <input className="field" required list="known-banks" placeholder="Es. UniCredit, Fineco..." value={form.name} onChange={(e) => setForm((f) => ({ ...f, name: e.target.value }))} />
            <datalist id="known-banks">
              {KNOWN_BANKS.map((b) => <option key={b.name} value={b.name} />)}
            </datalist>
            <p className="mt-1 text-xs text-muted">Se includi il nome di una banca riconosciuta, mostreremo la sua icona automaticamente.</p>
          </div>
          <div className="grid grid-cols-3 gap-3">
            <div>
              <label className="field-label">Tipo</label>
              <select className="field" value={form.type} onChange={(e) => setForm((f) => ({ ...f, type: e.target.value }))}>
                {TYPES.map(([v, l]) => <option key={v} value={v}>{l}</option>)}
              </select>
            </div>
            <div>
              <label className="field-label">Valuta</label>
              <select className="field" value={form.currency} onChange={(e) => setForm((f) => ({ ...f, currency: e.target.value }))}>
                {CURRENCIES.map((c) => <option key={c} value={c}>{c}</option>)}
              </select>
            </div>
            <div>
              <label className="field-label">Saldo iniziale</label>
              <input className="field" type="number" step="0.01" value={form.initial_balance} onChange={(e) => setForm((f) => ({ ...f, initial_balance: e.target.value }))} />
            </div>
          </div>
          {form.currency !== 'EUR' && (
            <p className="text-xs text-muted">
              Il saldo e le transazioni di questo conto restano in {form.currency}. Nel patrimonio netto e nei
              riepiloghi verrà convertito in euro automaticamente, al cambio più recente disponibile.
            </p>
          )}
          <div>
            <label className="field-label">Colore</label>
            <input type="color" className="h-10 w-16 rounded" value={form.color} onChange={(e) => setForm((f) => ({ ...f, color: e.target.value }))} />
          </div>

          <label className="flex items-center gap-2 rounded-xl border border-border bg-gray-50 px-3 py-2.5 text-sm font-medium">
            <input type="checkbox" checked={form.interest_enabled} onChange={(e) => setForm((f) => ({ ...f, interest_enabled: e.target.checked }))} />
            Matura interessi (conto deposito, vincolato o corrente remunerato)
          </label>

          <label className="flex items-center gap-2 rounded-xl border border-border bg-gray-50 px-3 py-2.5 text-sm font-medium">
            <input type="checkbox" checked={form.exclude_from_networth} onChange={(e) => setForm((f) => ({ ...f, exclude_from_networth: e.target.checked }))} />
            Escludi dal patrimonio netto
          </label>
          {form.exclude_from_networth && (
            <p className="text-xs text-muted">
              Il conto resta pienamente utilizzabile (transazioni, saldo mostrato qui) ma il suo saldo non
              entra nel totale di Riepilogo, FIRE e Report — utile per un conto che non è davvero tuo, o che
              tieni traccia separatamente per altri motivi.
            </p>
          )}

          {form.interest_enabled && (
            <div className="space-y-3 rounded-xl border border-accent/20 bg-accentSoft p-3">
              <div className="grid grid-cols-2 gap-3">
                <div>
                  <label className="field-label">Tasso lordo annuo (%){form.id ? ' — attuale' : ''}</label>
                  <input className="field" type="number" step="0.001" min="0" required value={form.interest_rate} onChange={(e) => setForm((f) => ({ ...f, interest_rate: e.target.value }))} />
                  {form.id && <p className="mt-1 text-xs text-muted">Modificarlo qui vale da oggi. Per una variazione futura o per correggere il passato, usa lo storico qui sotto.</p>}
                </div>
                <div>
                  <label className="field-label">Frequenza accredito</label>
                  <select className="field" value={form.interest_frequency} onChange={(e) => setForm((f) => ({ ...f, interest_frequency: e.target.value }))}>
                    <option value="mensile">Mensile</option>
                    <option value="trimestrale">Trimestrale</option>
                    <option value="annuale">Annuale</option>
                  </select>
                </div>
              </div>
              <div className="grid grid-cols-2 gap-3">
                <div>
                  <label className="field-label">Imposta sugli interessi (%)</label>
                  <input className="field" type="number" step="0.1" min="0" max="100" value={form.interest_tax_rate} onChange={(e) => setForm((f) => ({ ...f, interest_tax_rate: e.target.value }))} />
                  <p className="mt-1 text-xs text-muted">26% è l'aliquota standard italiana sugli interessi da conti/depositi.</p>
                </div>
                <div>
                  <label className="field-label">Primo accredito previsto</label>
                  <input className="field" type="date" value={form.next_interest_date} onChange={(e) => setForm((f) => ({ ...f, next_interest_date: e.target.value }))} />
                </div>
              </div>
              <p className="text-xs text-muted">
                L'interesse si calcola sul saldo reale del conto in quel momento, e si accredita come
                vera transazione — vai su <strong>Investimenti</strong> per generarlo quando è dovuto.
              </p>

              {form.id && (
                <div className="border-t border-accent/20 pt-3">
                  <h6 className="mb-2 text-xs font-semibold uppercase tracking-wide text-muted">Storico tasso</h6>
                  <p className="mb-2 text-xs text-muted">
                    Se il tasso è cambiato nel tempo (es. promozione scaduta, rinnovo), registra ogni
                    periodo qui: l'interesse arretrato userà il tasso realmente in vigore in ciascuna data.
                  </p>
                  {rateError && <div className="mb-2 rounded-lg bg-red-50 px-3 py-2 text-sm text-red-600">{rateError}</div>}
                  <div className="space-y-1.5">
                    {rates.map((r, i) => {
                      const next = rates[i + 1];
                      const endLabel = next ? formatDate(dayBefore(next.effective_date)) : 'in corso';
                      return (
                        <div key={r.id} className="flex items-center justify-between rounded-lg bg-white px-3 py-2 text-sm">
                          <span>dal {formatDate(r.effective_date)} al {endLabel} — <span className="font-mono">{r.rate}%</span></span>
                          <button type="button" className="text-muted hover:text-red-600" onClick={() => deleteRate(r.id)} aria-label="Elimina variazione">
                            <Trash2 size={14} />
                          </button>
                        </div>
                      );
                    })}
                  </div>
                  <div className="mt-3 flex flex-wrap items-end gap-2">
                    <div>
                      <label className="field-label">Decorrenza</label>
                      <input type="date" className="field" value={rateForm.effective_date} onChange={(e) => setRateForm((f) => ({ ...f, effective_date: e.target.value }))} />
                    </div>
                    <div>
                      <label className="field-label">Tasso (%)</label>
                      <input type="number" step="0.001" min="0" className="field" value={rateForm.rate} onChange={(e) => setRateForm((f) => ({ ...f, rate: e.target.value }))} />
                    </div>
                    <button type="button" className="btn-outline" onClick={addRate}>Aggiungi periodo</button>
                  </div>
                </div>
              )}
            </div>
          )}
        </form>
      </Modal>
    </div>
  );
}
