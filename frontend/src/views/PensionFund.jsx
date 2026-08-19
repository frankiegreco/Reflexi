import { useState, useEffect, useCallback } from 'react';
import { Plus, Pencil, Trash, Trash2, PiggyBank } from 'lucide-react';
import { apiGet, apiSend, apiDelete } from '../api';
import { euro } from '../lib/format';
import { KNOWN_PENSION_FUNDS } from '../lib/pensionFunds';
import Modal from '../components/Modal';
import StatCard from '../components/StatCard';

const empty = { id: null, name: '', fund_type: 'negoziale', current_balance: '', monthly_contribution: '', employer_contribution: '', expected_annual_return: 3, notes: '' };

export default function PensionFund() {
  const [funds, setFunds] = useState([]);
  const [currentAge, setCurrentAge] = useState(35);
  const [retirementAge, setRetirementAge] = useState(67);
  const [modalOpen, setModalOpen] = useState(false);
  const [form, setForm] = useState(empty);
  const [error, setError] = useState('');

  const load = useCallback(async () => {
    setFunds(await apiGet(`pension_funds.php?current_age=${currentAge}&retirement_age=${retirementAge}`));
  }, [currentAge, retirementAge]);
  useEffect(() => { load(); }, [load]);

  function openNew() {
    setForm(empty);
    setError('');
    setModalOpen(true);
  }
  function openEdit(f) {
    setForm({
      id: f.id, name: f.name, fund_type: f.fund_type, current_balance: f.current_balance,
      monthly_contribution: f.monthly_contribution, employer_contribution: f.employer_contribution,
      expected_annual_return: f.expected_annual_return, notes: f.notes || '',
    });
    setError('');
    setModalOpen(true);
  }

  async function handleSubmit(e) {
    e.preventDefault();
    const payload = {
      name: form.name, fund_type: form.fund_type,
      current_balance: parseFloat(form.current_balance || '0'),
      monthly_contribution: parseFloat(form.monthly_contribution || '0'),
      employer_contribution: parseFloat(form.employer_contribution || '0'),
      expected_annual_return: parseFloat(form.expected_annual_return || '3'),
      notes: form.notes || null,
    };
    try {
      if (form.id) await apiSend('pension_funds.php', 'PUT', { ...payload, id: form.id });
      else await apiSend('pension_funds.php', 'POST', payload);
      setModalOpen(false);
      load();
    } catch (err) { setError(err.message); }
  }

  async function handleDelete(id) {
    if (!confirm('Eliminare questo fondo pensione?')) return;
    await apiDelete(`pension_funds.php?id=${id}`);
    load();
  }

  async function handleDeleteAll() {
    if (!funds.length) return;
    if (!confirm(`Eliminare tutti i ${funds.length} fondi pensione?`)) return;
    await apiDelete('pension_funds.php?action=bulk');
    load();
  }

  const totalCurrent = funds.reduce((s, f) => s + f.current_balance, 0);
  const totalProjected = funds.reduce((s, f) => s + f.projected_at_retirement, 0);

  return (
    <div>
      <div className="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <h4 className="text-xl font-bold">Fondo pensione</h4>
        <div className="flex flex-wrap gap-2">
          <button className="btn-outline text-expense" onClick={handleDeleteAll} disabled={!funds.length}><Trash size={15} /> Elimina tutti</button>
          <button className="btn-accent" onClick={openNew}><Plus size={15} /> Fondo</button>
        </div>
      </div>

      <div className="mb-4 rounded-xl border border-accent/20 bg-accentSoft px-4 py-3 text-sm text-gray-700">
        Traccia i tuoi fondi pensione e proietta il capitale atteso alla pensione. <strong>Non abbiamo dati
        di rendimento reali dei fondi italiani</strong> (cambiano nel tempo, servirebbe una fonte ufficiale
        sempre aggiornata) — inserisci tu il rendimento storico dichiarato dal tuo fondo. Per confrontare
        rendimenti e costi reali e aggiornati, usa il{' '}
        <a href="https://www.covip.it" target="_blank" rel="noopener" className="text-accent hover:underline">comparatore ufficiale COVIP</a>.
      </div>

      <div className="card mb-4 p-5">
        <h6 className="mb-3 text-sm font-semibold">La tua proiezione</h6>
        <div className="grid grid-cols-2 gap-3 sm:grid-cols-2">
          <div>
            <label className="field-label">La tua età attuale</label>
            <input className="field" type="number" min="18" max="80" value={currentAge} onChange={(e) => setCurrentAge(e.target.value)} />
          </div>
          <div>
            <label className="field-label">Età di pensionamento prevista</label>
            <input className="field" type="number" min="19" max="90" value={retirementAge} onChange={(e) => setRetirementAge(e.target.value)} />
          </div>
        </div>
      </div>

      {funds.length > 0 && (
        <div className="mb-4 grid grid-cols-1 gap-3 sm:grid-cols-2">
          <StatCard label="Capitale attuale (tutti i fondi)" value={euro(totalCurrent)} />
          <StatCard label={`Capitale proiettato a ${retirementAge} anni`} value={euro(totalProjected)} valueClass="text-income" />
        </div>
      )}

      <div className="card p-5">
        {funds.length === 0 ? (
          <p className="text-sm text-muted">Nessun fondo pensione tracciato ancora. Aggiungine uno per vedere la proiezione al pensionamento.</p>
        ) : (
          <div className="space-y-2">
            {funds.map((f) => (
              <div key={f.id} className="flex flex-col gap-2 rounded-xl border border-border px-4 py-3 sm:flex-row sm:items-center sm:justify-between">
                <div className="flex items-center gap-3">
                  <div className="flex h-9 w-9 flex-shrink-0 items-center justify-center rounded-lg bg-accentSoft"><PiggyBank size={16} className="text-accent" /></div>
                  <div>
                    <div className="font-medium">{f.name}</div>
                    <div className="text-xs text-muted">
                      Capitale: {euro(f.current_balance)} · Versamento {euro(f.monthly_contribution + f.employer_contribution)}/mese · rendimento atteso {f.expected_annual_return}%
                    </div>
                  </div>
                </div>
                <div className="flex flex-shrink-0 items-center gap-3">
                  <div className="text-right">
                    <div className="text-[10px] uppercase text-muted">Proiettato</div>
                    <div className="font-mono text-sm font-semibold text-income">{euro(f.projected_at_retirement)}</div>
                  </div>
                  <button className="btn-outline text-xs" onClick={() => openEdit(f)}><Pencil size={13} /></button>
                  <button className="btn-outline-danger text-xs" onClick={() => handleDelete(f.id)}><Trash2 size={13} /></button>
                </div>
              </div>
            ))}
          </div>
        )}
      </div>

      <Modal open={modalOpen} onClose={() => setModalOpen(false)} title={form.id ? 'Modifica fondo pensione' : 'Nuovo fondo pensione'}
        footer={<>
          <button type="button" className="btn-outline" onClick={() => setModalOpen(false)}>Annulla</button>
          <button type="submit" form="pension-form" className="btn-accent">Salva</button>
        </>}
      >
        {error && <div className="mb-3 rounded-lg bg-red-50 px-3 py-2 text-sm text-red-600">{error}</div>}
        <form id="pension-form" onSubmit={handleSubmit} className="space-y-3">
          <div>
            <label className="field-label">Nome del fondo</label>
            <input className="field" list="known-pension-funds" required value={form.name} onChange={(e) => setForm((f) => ({ ...f, name: e.target.value }))} />
            <datalist id="known-pension-funds">
              {KNOWN_PENSION_FUNDS.map((n) => <option key={n} value={n} />)}
            </datalist>
          </div>
          <div>
            <label className="field-label">Tipo</label>
            <select className="field" value={form.fund_type} onChange={(e) => setForm((f) => ({ ...f, fund_type: e.target.value }))}>
              <option value="negoziale">Negoziale</option>
              <option value="aperto">Aperto</option>
              <option value="pip">PIP</option>
              <option value="tfr_azienda">TFR in azienda</option>
            </select>
          </div>
          <div className="grid grid-cols-2 gap-3">
            <div>
              <label className="field-label">Capitale attuale (€)</label>
              <input className="field" type="number" step="0.01" min="0" value={form.current_balance} onChange={(e) => setForm((f) => ({ ...f, current_balance: e.target.value }))} />
            </div>
            <div>
              <label className="field-label">Rendimento annuo atteso (%)</label>
              <input className="field" type="number" step="0.1" min="0" value={form.expected_annual_return} onChange={(e) => setForm((f) => ({ ...f, expected_annual_return: e.target.value }))} />
            </div>
            <div>
              <label className="field-label">Tuo versamento mensile (€)</label>
              <input className="field" type="number" step="0.01" min="0" value={form.monthly_contribution} onChange={(e) => setForm((f) => ({ ...f, monthly_contribution: e.target.value }))} />
            </div>
            <div>
              <label className="field-label">Contributo datore di lavoro (€)</label>
              <input className="field" type="number" step="0.01" min="0" value={form.employer_contribution} onChange={(e) => setForm((f) => ({ ...f, employer_contribution: e.target.value }))} />
            </div>
          </div>
          <div>
            <label className="field-label">Note (opzionale)</label>
            <input className="field" value={form.notes} onChange={(e) => setForm((f) => ({ ...f, notes: e.target.value }))} />
          </div>
        </form>
      </Modal>
    </div>
  );
}
