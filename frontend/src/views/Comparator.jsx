import { useState, useEffect, useCallback } from 'react';
import { Plus, Trash, Trash2, Info, ExternalLink, Pencil, Search } from 'lucide-react';
import { euro, formatDate } from '../lib/format';
import { apiGet, apiSend, apiDelete } from '../api';
import { matchBank } from '../lib/banks';
import ServiceIcon from '../components/ServiceIcon';
import Modal from '../components/Modal';
import Tabs from '../components/Tabs';

const emptyRate = {
  id: null, bank_name: '', rate_type: 'fisso', loan_amount: '', duration_years: '', tan: '', taeg: '',
  profile_description: '', source_name: '', source_url: '', observed_date: '',
};

/** Rata mensile con il metodo di ammortamento francese. */
function computeMonthlyPayment(loanAmount, tan, years) {
  const principal = parseFloat(loanAmount);
  const rate = parseFloat(tan);
  const y = parseFloat(years);
  if (!principal || !rate || !y) return null;
  const monthlyRate = (rate / 100) / 12;
  const months = y * 12;
  if (monthlyRate === 0) return principal / months;
  return (principal * monthlyRate) / (1 - Math.pow(1 + monthlyRate, -months));
}

const TABS = [
  { id: 'confronta', label: 'Confronta offerte' },
  { id: 'gestisci', label: 'Gestisci dati' },
];

export default function Comparator() {
  const [rates, setRates] = useState([]);
  const [tab, setTab] = useState('confronta');

  // Form di ricerca (come un vero comparatore)
  const [search, setSearch] = useState({
    purpose: 'prima_casa', property_value: 200000, loan_amount: 150000, duration_years: 25,
    rate_type: 'tutti', birth_date: '', income: '',
  });
  const [searched, setSearched] = useState(false);

  const [rateModalOpen, setRateModalOpen] = useState(false);
  const [rateForm, setRateForm] = useState(emptyRate);
  const [rateError, setRateError] = useState('');

  const loadRates = useCallback(async () => setRates(await apiGet('reference_rates.php')), []);
  useEffect(() => { loadRates(); }, [loadRates]);

  function openNewRate() {
    setRateForm(emptyRate);
    setRateError('');
    setRateModalOpen(true);
  }
  function openEditRate(r) {
    setRateForm({
      id: r.id, bank_name: r.bank_name, rate_type: r.rate_type,
      loan_amount: r.loan_amount ?? '', duration_years: r.duration_years ?? '', tan: r.tan ?? '', taeg: r.taeg ?? '',
      profile_description: r.profile_description || '',
      source_name: r.source_name, source_url: r.source_url, observed_date: r.observed_date,
    });
    setRateError('');
    setRateModalOpen(true);
  }

  const formComputedPayment = computeMonthlyPayment(rateForm.loan_amount, rateForm.tan, rateForm.duration_years);

  async function submitRate(e) {
    e.preventDefault();
    const payload = {
      bank_name: rateForm.bank_name, rate_type: rateForm.rate_type,
      loan_amount: rateForm.loan_amount === '' ? null : parseFloat(rateForm.loan_amount),
      duration_years: rateForm.duration_years === '' ? null : parseInt(rateForm.duration_years),
      tan: rateForm.tan === '' ? null : parseFloat(rateForm.tan),
      taeg: rateForm.taeg === '' ? null : parseFloat(rateForm.taeg),
      monthly_payment: formComputedPayment !== null ? Math.round(formComputedPayment * 100) / 100 : null,
      profile_description: rateForm.profile_description || null,
      source_name: rateForm.source_name, source_url: rateForm.source_url, observed_date: rateForm.observed_date,
    };
    try {
      if (rateForm.id) await apiSend('reference_rates.php', 'PUT', { ...payload, id: rateForm.id });
      else await apiSend('reference_rates.php', 'POST', payload);
      setRateModalOpen(false);
      loadRates();
    } catch (err) { setRateError(err.message); }
  }
  async function deleteRate(id) {
    if (!confirm('Eliminare questa riga?')) return;
    await apiDelete(`reference_rates.php?id=${id}`);
    loadRates();
  }

  async function deleteAllRates() {
    if (!rates.length) return;
    if (!confirm(`Eliminare tutte le ${rates.length} righe? Questa tabella è condivisa tra tutti gli utenti.`)) return;
    await apiDelete('reference_rates.php?action=bulk');
    loadRates();
  }

  // Risultati del comparatore: per ogni tasso in tabella (filtrato per tipo),
  // ricalcola la rata sulle cifre richieste dall'utente, ordinando dalla
  // rata più bassa — il cuore del "vero comparatore".
  const results = rates
    .filter((r) => r.tan !== null)
    .filter((r) => search.rate_type === 'tutti' || r.rate_type === search.rate_type)
    .map((r) => ({ ...r, computed_rata: computeMonthlyPayment(search.loan_amount, r.tan, search.duration_years) }))
    .filter((r) => r.computed_rata !== null)
    .sort((a, b) => a.computed_rata - b.computed_rata);

  const ltv = search.property_value > 0 ? (search.loan_amount / search.property_value) * 100 : null;
  const age = search.birth_date ? Math.floor((Date.now() - new Date(search.birth_date).getTime()) / (365.25 * 24 * 3600 * 1000)) : null;
  const ageAtPayoff = age !== null ? age + parseInt(search.duration_years || 0) : null;

  function handleSearch(e) {
    e.preventDefault();
    setSearched(true);
  }

  return (
    <div>
      <div className="mb-4">
        <h4 className="text-xl font-bold">Comparatore mutui</h4>
        <p className="text-sm text-muted">Inserisci i dati del mutuo che ti serve — vedrai le offerte in tabella ordinate dalla rata più bassa.</p>
      </div>

      <div className="mb-4 rounded-xl border border-accent/20 bg-accentSoft px-4 py-3 text-sm text-gray-700">
        Non è una comparazione automatica in tempo reale — è una <strong>tabella di riferimento</strong> (stesso
        principio dei portali di comparazione mutui: dati raccolti periodicamente, non un'interrogazione live alle
        banche). Ogni rata qui sotto è però <strong>ricalcolata davvero</strong> sulle tue cifre, usando il TAN
        registrato per ciascuna offerta — non è un numero statico copiato dall'articolo originale.
      </div>

      <Tabs tabs={TABS.map((t) => ({ ...t, count: t.id === 'gestisci' ? rates.length : 0 }))} active={tab} onChange={setTab} />

      {tab === 'confronta' && (
      <>
      {/* FORM DI RICERCA */}
      <form onSubmit={handleSearch} className="card mb-6 p-5">
        <div className="grid grid-cols-1 gap-4 sm:grid-cols-3">
          <div>
            <label className="field-label">Finalità del mutuo</label>
            <select className="field" value={search.purpose} onChange={(e) => setSearch((s) => ({ ...s, purpose: e.target.value }))}>
              <option value="prima_casa">Acquisto prima casa</option>
              <option value="seconda_casa">Acquisto seconda casa</option>
              <option value="surroga">Surroga</option>
              <option value="liquidita">Liquidità</option>
            </select>
          </div>
          <div>
            <label className="field-label">Valore dell'immobile (€)</label>
            <input className="field" type="number" step="1000" min="1000" required
              value={search.property_value} onChange={(e) => setSearch((s) => ({ ...s, property_value: e.target.value }))} />
          </div>
          <div>
            <label className="field-label">Importo mutuo richiesto (€)</label>
            <input className="field" type="number" step="1000" min="1000" required
              value={search.loan_amount} onChange={(e) => setSearch((s) => ({ ...s, loan_amount: e.target.value }))} />
          </div>
          <div>
            <label className="field-label">Durata (anni)</label>
            <input className="field" type="number" step="1" min="1" max="40" required
              value={search.duration_years} onChange={(e) => setSearch((s) => ({ ...s, duration_years: e.target.value }))} />
          </div>
          <div>
            <label className="field-label">Tipo di tasso</label>
            <select className="field" value={search.rate_type} onChange={(e) => setSearch((s) => ({ ...s, rate_type: e.target.value }))}>
              <option value="tutti">Tutti</option>
              <option value="fisso">Fisso</option>
              <option value="variabile">Variabile</option>
            </select>
          </div>
          <div>
            <label className="field-label">Data di nascita (del richiedente)</label>
            <input className="field" type="date" value={search.birth_date} onChange={(e) => setSearch((s) => ({ ...s, birth_date: e.target.value }))} />
          </div>
          <div>
            <label className="field-label">Reddito netto mensile (€) — dei richiedenti</label>
            <input className="field" type="number" step="50" min="0" value={search.income} onChange={(e) => setSearch((s) => ({ ...s, income: e.target.value }))} />
          </div>
        </div>

        {(ltv !== null || age !== null) && (
          <div className="mt-4 flex flex-wrap gap-4 rounded-xl border border-border bg-gray-50 p-3 text-xs">
            {ltv !== null && (
              <span className={ltv > 80 ? 'font-semibold text-loan' : 'text-muted'}>
                LTV (mutuo/immobile): <strong>{ltv.toFixed(1)}%</strong>{ltv > 80 && ' — sopra l\'80%, alcune delle migliori offerte potrebbero non essere disponibili'}
              </span>
            )}
            {age !== null && (
              <span className={ageAtPayoff > 80 ? 'font-semibold text-loan' : 'text-muted'}>
                Età a fine mutuo: <strong>{ageAtPayoff} anni</strong>{ageAtPayoff > 80 && ' — molte banche hanno un limite intorno agli 80'}
              </span>
            )}
          </div>
        )}

        <button type="submit" className="btn-accent mt-4"><Search size={15} /> Confronta le offerte</button>

        <p className="mt-3 text-xs text-muted">
          Finalità, valore immobile, età e reddito ti aiutano a valutare se un'offerta è realistica per te (LTV,
          margine d'età, sostenibilità della rata) — non filtrano ancora i risultati, perché la tabella non registra
          questi dettagli per singola offerta. La classifica sotto si basa solo su capitale, durata e TAN.
        </p>
      </form>

      {/* RISULTATI */}
      {searched && (
        <div className="mb-8">
          <h5 className="mb-3 font-semibold">
            {results.length} {results.length === 1 ? 'offerta trovata' : 'offerte trovate'}, ordinate dalla rata più bassa
          </h5>
          {results.length === 0 ? (
            <div className="card p-6 text-center text-sm text-muted">
              Nessun tasso disponibile in tabella per questo filtro — aggiungine uno dalla tab "Gestisci dati".
            </div>
          ) : (
            <div className="space-y-3">
              {results.map((r, i) => {
                const bank = matchBank(r.bank_name);
                return (
                <div key={r.id} className={`card flex flex-col gap-3 p-5 sm:flex-row sm:items-center sm:justify-between ${i === 0 ? 'border-2 border-income' : ''}`}>
                  <div className="flex gap-3">
                    <ServiceIcon name={r.bank_name} domain={bank?.domain} color={bank?.color || '#8b95a1'} size={36} />
                    <div>
                      <div className="flex items-center gap-2">
                        {i === 0 && <span className="rounded-full bg-income/15 px-2 py-0.5 text-[10px] font-bold uppercase text-income">Rata più bassa</span>}
                        <span className="font-semibold">{r.bank_name}</span>
                        <span className="text-xs capitalize text-muted">· {r.rate_type}</span>
                      </div>
                      <div className="mt-1 text-xs text-muted">
                        TAN {r.tan}%{r.taeg !== null && ` · TAEG ${r.taeg}%`}
                        {r.profile_description && ` · ${r.profile_description}`}
                      </div>
                      <a href={r.source_url} target="_blank" rel="noopener" className="mt-1 flex items-center gap-1 text-xs text-accent hover:underline">
                        {r.source_name} ({formatDate(r.observed_date)}) <ExternalLink size={10} />
                      </a>
                    </div>
                  </div>
                  <div className="text-right">
                    <div className="text-[10px] uppercase text-muted">Rata stimata</div>
                    <div className="font-mono text-2xl font-bold text-accent">{euro(r.computed_rata)}<span className="text-sm font-normal text-muted">/mese</span></div>
                    {search.income > 0 && (
                      <div className={`mt-0.5 text-xs ${(r.computed_rata / search.income) * 100 > 35 ? 'font-semibold text-loan' : 'text-muted'}`}>
                        {((r.computed_rata / search.income) * 100).toFixed(0)}% del reddito indicato
                      </div>
                    )}
                  </div>
                </div>
                );
              })}
            </div>
          )}
        </div>
      )}
      </>
      )}

      {tab === 'gestisci' && (
        <>
          <div className="mb-3 flex justify-end gap-2">
            <button className="btn-outline text-expense" onClick={deleteAllRates} disabled={!rates.length}><Trash size={15} /> Elimina tutti</button>
            <button className="btn-outline" onClick={openNewRate}><Plus size={15} /> Aggiungi tasso</button>
          </div>
          <div className="card overflow-x-auto p-0">
            <table className="w-full min-w-[700px] text-sm">
              <thead>
                <tr className="border-b border-border text-left text-[11px] uppercase tracking-wide text-muted">
                  <th className="px-4 py-3">Banca</th>
                  <th className="px-4 py-3">Tipo</th>
                  <th className="px-4 py-3 text-right">TAN</th>
                  <th className="px-4 py-3 text-right">TAEG</th>
                  <th className="px-4 py-3">Fonte</th>
                  <th className="px-4 py-3"></th>
                </tr>
              </thead>
              <tbody>
                {rates.length ? rates.map((r) => (
                  <tr key={r.id} className="border-b border-border last:border-0">
                    <td className="px-4 py-3 font-medium">
                      <div className="flex items-center gap-2">
                        <ServiceIcon name={r.bank_name} domain={matchBank(r.bank_name)?.domain} color={matchBank(r.bank_name)?.color || '#8b95a1'} size={22} />
                        {r.bank_name}
                      </div>
                    </td>
                    <td className="px-4 py-3 capitalize">{r.rate_type}</td>
                    <td className="px-4 py-3 text-right font-mono">{r.tan !== null ? `${r.tan}%` : '—'}</td>
                    <td className="px-4 py-3 text-right font-mono">{r.taeg !== null ? `${r.taeg}%` : '—'}</td>
                    <td className="px-4 py-3 text-xs">
                      <a href={r.source_url} target="_blank" rel="noopener" className="flex items-center gap-1 text-accent hover:underline">
                        {r.source_name} <ExternalLink size={11} />
                      </a>
                      <div className="text-muted">{formatDate(r.observed_date)}</div>
                    </td>
                    <td className="px-4 py-3">
                      <div className="flex gap-1.5">
                        <button className="text-muted hover:text-accent" onClick={() => openEditRate(r)}><Pencil size={14} /></button>
                        <button className="text-muted hover:text-expense" onClick={() => deleteRate(r.id)}><Trash2 size={14} /></button>
                      </div>
                    </td>
                  </tr>
                )) : (
                  <tr><td colSpan="6" className="px-4 py-8 text-center text-sm text-muted">Nessun dato ancora inserito</td></tr>
                )}
              </tbody>
            </table>
          </div>
        </>
      )}

      <div className="mt-6 flex items-start gap-3 rounded-xl border border-border bg-white p-4 text-xs text-muted">
        <Info size={16} className="mt-0.5 flex-shrink-0 text-accent" />
        <p>La rata è calcolata con il metodo di ammortamento francese (rata costante), lo stesso usato per i tuoi mutui
          reali su Reflexi — non tiene conto di spese accessorie, polizze obbligatorie o istruttoria, che possono
          cambiare il costo reale rispetto al solo TAN. Verifica sempre il foglio informativo reale prima di decidere.</p>
      </div>

      <Modal
        open={rateModalOpen}
        onClose={() => setRateModalOpen(false)}
        title={rateForm.id ? 'Modifica riferimento tasso' : 'Nuovo riferimento tasso'}
        footer={<>
          <button type="button" className="btn-outline" onClick={() => setRateModalOpen(false)}>Annulla</button>
          <button type="submit" form="rate-form" className="btn-accent">Salva</button>
        </>}
      >
        {rateError && <div className="mb-3 rounded-lg bg-red-50 px-3 py-2 text-sm text-red-600">{rateError}</div>}
        <form id="rate-form" onSubmit={submitRate} className="space-y-3">
          <div className="grid grid-cols-2 gap-3">
            <div>
              <label className="field-label">Banca</label>
              <input className="field" required value={rateForm.bank_name} onChange={(e) => setRateForm((f) => ({ ...f, bank_name: e.target.value }))} />
            </div>
            <div>
              <label className="field-label">Tipo tasso</label>
              <select className="field" value={rateForm.rate_type} onChange={(e) => setRateForm((f) => ({ ...f, rate_type: e.target.value }))}>
                <option value="fisso">Fisso</option>
                <option value="variabile">Variabile</option>
              </select>
            </div>
          </div>

          <div>
            <label className="field-label">TAN (%)</label>
            <input className="field max-w-[150px]" type="number" step="0.01" min="0" required value={rateForm.tan} onChange={(e) => setRateForm((f) => ({ ...f, tan: e.target.value }))} />
            <p className="mt-1 text-xs text-muted">È l'unico dato usato per ricalcolare la rata nel comparatore, qualunque cifra l'utente richieda.</p>
          </div>

          <div className="grid grid-cols-2 gap-3 rounded-xl border border-border bg-gray-50 p-3">
            <div>
              <label className="field-label">Capitale osservato (€) — opzionale</label>
              <input className="field" type="number" step="1000" min="0" value={rateForm.loan_amount} onChange={(e) => setRateForm((f) => ({ ...f, loan_amount: e.target.value }))} />
            </div>
            <div>
              <label className="field-label">Durata osservata (anni) — opzionale</label>
              <input className="field" type="number" step="1" min="1" max="40" value={rateForm.duration_years} onChange={(e) => setRateForm((f) => ({ ...f, duration_years: e.target.value }))} />
            </div>
            <p className="col-span-2 text-xs text-muted">Solo per riferimento (il profilo dell'articolo originale) — non influenzano il comparatore, che ricalcola sempre sulle cifre richieste dall'utente.</p>
          </div>

          <div>
            <label className="field-label">TAEG (%) — opzionale</label>
            <input className="field max-w-[150px]" type="number" step="0.01" min="0" value={rateForm.taeg} onChange={(e) => setRateForm((f) => ({ ...f, taeg: e.target.value }))} />
          </div>
          <div>
            <label className="field-label">Note (opzionale)</label>
            <input className="field" placeholder="Es. Prima casa, Milano, immobile classe A" value={rateForm.profile_description} onChange={(e) => setRateForm((f) => ({ ...f, profile_description: e.target.value }))} />
          </div>
          <div className="grid grid-cols-2 gap-3">
            <div>
              <label className="field-label">Nome fonte</label>
              <input className="field" required placeholder="Es. MutuiOnline.it" value={rateForm.source_name} onChange={(e) => setRateForm((f) => ({ ...f, source_name: e.target.value }))} />
            </div>
            <div>
              <label className="field-label">Data rilevazione</label>
              <input className="field" type="date" required value={rateForm.observed_date} onChange={(e) => setRateForm((f) => ({ ...f, observed_date: e.target.value }))} />
            </div>
          </div>
          <div>
            <label className="field-label">Link alla fonte</label>
            <input className="field" type="url" required placeholder="https://..." value={rateForm.source_url} onChange={(e) => setRateForm((f) => ({ ...f, source_url: e.target.value }))} />
          </div>
        </form>
      </Modal>
    </div>
  );
}
