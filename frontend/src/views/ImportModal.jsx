import { useEffect, useState } from 'react';
import { Upload, AlertTriangle, CheckCircle2, ArrowLeft } from 'lucide-react';
import Modal from '../components/Modal';
import { useData } from '../context/DataContext';
import { apiSend } from '../api';
import { euro, formatDate } from '../lib/format';

const MAX_FILE_BYTES = 5 * 1024 * 1024; // limite lato client, coerente con post_max_size=8M lato PHP (il base64 gonfia ~1.37x)

const DATE_FORMAT_OPTIONS = [
  { value: 'auto', label: 'Rileva automaticamente' },
  { value: 'd/m/Y', label: 'GG/MM/AAAA (31/01/2026)' },
  { value: 'Y-m-d', label: 'AAAA-MM-GG (2026-01-31)' },
  { value: 'd.m.Y', label: 'GG.MM.AAAA (31.01.2026)' },
  { value: 'd-m-Y', label: 'GG-MM-AAAA (31-01-2026)' },
  { value: 'm/d/Y', label: 'MM/GG/AAAA, formato USA (01/31/2026)' },
  { value: 'Y/m/d', label: 'AAAA/MM/GG (2026/01/31)' },
];

const DECIMAL_OPTIONS = [
  { value: 'auto', label: 'Rileva automaticamente' },
  { value: ',', label: 'Virgola: 1.234,56' },
  { value: '.', label: 'Punto: 1,234.56' },
];

const emptyColumns = { date: '', amount: '', debit: '', credit: '', description: '', type: '', category: '' };

async function fileToBase64(file) {
  const buf = await file.arrayBuffer();
  const bytes = new Uint8Array(buf);
  let binary = '';
  const chunkSize = 0x8000;
  for (let i = 0; i < bytes.length; i += chunkSize) {
    binary += String.fromCharCode.apply(null, bytes.subarray(i, i + chunkSize));
  }
  return btoa(binary);
}

export default function ImportModal({ open, onClose, onSaved }) {
  const { activeAccounts, incomeCategories, expenseCategories } = useData();

  const [step, setStep] = useState('upload');
  const [file, setFile] = useState(null);
  const [accountId, setAccountId] = useState('');
  const [csvBase64, setCsvBase64] = useState('');
  const [delimiter, setDelimiter] = useState(';');
  const [parseData, setParseData] = useState(null);

  const [columns, setColumns] = useState(emptyColumns);
  const [amountMode, setAmountMode] = useState('single');
  const [dateFormat, setDateFormat] = useState('auto');
  const [decimalSeparator, setDecimalSeparator] = useState('auto');
  const [typeActions, setTypeActions] = useState({});
  const [defaultIncomeCat, setDefaultIncomeCat] = useState('');
  const [defaultExpenseCat, setDefaultExpenseCat] = useState('');
  const [skipDuplicates, setSkipDuplicates] = useState(true);
  const [source, setSource] = useState('');
  const [saveMappingAs, setSaveMappingAs] = useState('');

  const [previewResult, setPreviewResult] = useState(null);
  const [finalResult, setFinalResult] = useState(null);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState('');

  useEffect(() => {
    if (!open) return;
    setStep('upload');
    setFile(null);
    setAccountId(activeAccounts[0]?.id || '');
    setCsvBase64('');
    setParseData(null);
    setColumns(emptyColumns);
    setAmountMode('single');
    setDateFormat('auto');
    setDecimalSeparator('auto');
    setTypeActions({});
    setDefaultIncomeCat('');
    setDefaultExpenseCat('');
    setSkipDuplicates(true);
    setSource('');
    setSaveMappingAs('');
    setPreviewResult(null);
    setFinalResult(null);
    setError('');
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [open]);

  function setCol(field, value) {
    setColumns((c) => ({ ...c, [field]: value }));
  }

  async function handleAnalyze() {
    if (!file || !accountId) return;
    if (file.size > MAX_FILE_BYTES) {
      setError(`File troppo grande (${(file.size / 1024 / 1024).toFixed(1)} MB). Limite: ${MAX_FILE_BYTES / 1024 / 1024} MB — prova a esportare un periodo più corto.`);
      return;
    }
    setLoading(true);
    setError('');
    try {
      const b64 = await fileToBase64(file);
      setCsvBase64(b64);
      const data = await apiSend('import.php?action=parse', 'POST', { csv_base64: b64, filename: file.name });
      if (!data.headers.length) throw new Error('Nessuna colonna trovata nel file');
      setParseData(data);
      setDelimiter(data.delimiter);
      setColumns({
        date: data.suggested_mapping.date || '',
        amount: data.suggested_mapping.amount || '',
        debit: data.suggested_mapping.debit || '',
        credit: data.suggested_mapping.credit || '',
        description: data.suggested_mapping.description || '',
        type: data.suggested_mapping.type || '',
        category: data.suggested_mapping.category || '',
      });
      setAmountMode(!data.suggested_mapping.amount && (data.suggested_mapping.debit || data.suggested_mapping.credit) ? 'debit_credit' : 'single');
      setSource(file.name.replace(/\.[Cc][Ss][Vv]$/, ''));
      setStep('mapping');
    } catch (err) {
      setError(err.message);
    }
    setLoading(false);
  }

  function applySavedMapping(id) {
    const m = parseData.saved_mappings.find((x) => String(x.id) === String(id));
    if (!m) return;
    const cfg = m.config;
    const headers = parseData.headers;
    const keep = (v) => (v && headers.includes(v) ? v : '');
    setColumns({
      date: keep(cfg.columns?.date), amount: keep(cfg.columns?.amount),
      debit: keep(cfg.columns?.debit), credit: keep(cfg.columns?.credit),
      description: keep(cfg.columns?.description), type: keep(cfg.columns?.type),
      category: keep(cfg.columns?.category),
    });
    setAmountMode(!keep(cfg.columns?.amount) && (keep(cfg.columns?.debit) || keep(cfg.columns?.credit)) ? 'debit_credit' : 'single');
    setDateFormat(cfg.date_format || 'auto');
    setDecimalSeparator(cfg.decimal_separator || 'auto');
    setTypeActions(cfg.type_actions || {});
    setDefaultIncomeCat(cfg.default_category_income_id || '');
    setDefaultExpenseCat(cfg.default_category_expense_id || '');
    if (cfg.source) setSource(cfg.source);
  }

  function buildPayload(dryRun) {
    return {
      csv_base64: csvBase64,
      filename: file?.name,
      delimiter,
      account_id: accountId,
      source: source || 'CSV',
      columns: {
        date: columns.date,
        amount: amountMode === 'single' ? columns.amount : '',
        debit: amountMode === 'debit_credit' ? columns.debit : '',
        credit: amountMode === 'debit_credit' ? columns.credit : '',
        description: columns.description,
        type: columns.type,
        category: columns.category,
      },
      date_format: dateFormat,
      decimal_separator: decimalSeparator,
      type_actions: typeActions,
      default_category_income_id: defaultIncomeCat || null,
      default_category_expense_id: defaultExpenseCat || null,
      skip_duplicates: skipDuplicates,
      dry_run: dryRun,
      ...(!dryRun && saveMappingAs ? { save_mapping_as: saveMappingAs } : {}),
    };
  }

  const typeColumnValues = columns.type ? parseData?.distinct_values?.[columns.type] : null;
  const needsDefaultCategories = !columns.category;
  const canPreview = !!columns.date
    && (amountMode === 'single' ? !!columns.amount : !!(columns.debit || columns.credit))
    && (!needsDefaultCategories || (defaultIncomeCat && defaultExpenseCat));

  async function handlePreview() {
    setLoading(true);
    setError('');
    try {
      const res = await apiSend('import.php?action=commit', 'POST', buildPayload(true));
      setPreviewResult(res);
      setStep('preview');
    } catch (err) {
      setError(err.message);
    }
    setLoading(false);
  }

  async function handleConfirm() {
    setLoading(true);
    setError('');
    try {
      const res = await apiSend('import.php?action=commit', 'POST', buildPayload(false));
      setFinalResult(res);
      setStep('result');
    } catch (err) {
      setError(err.message);
    }
    setLoading(false);
  }

  const title = { upload: 'Importa transazioni da CSV', mapping: 'Configura la mappatura colonne', preview: 'Anteprima import', result: 'Import completato' }[step];

  return (
    <Modal open={open} onClose={onClose} title={title} size="xl" footer={
      <>
        {step === 'upload' && (
          <>
            <button className="btn-outline" onClick={onClose}>Annulla</button>
            <button className="btn-accent" disabled={!file || !accountId || loading} onClick={handleAnalyze}>
              {loading ? 'Analisi in corso...' : 'Analizza file'}
            </button>
          </>
        )}
        {step === 'mapping' && (
          <>
            <button className="btn-outline" onClick={() => setStep('upload')}><ArrowLeft size={15} /> Indietro</button>
            <button className="btn-accent" disabled={!canPreview || loading} onClick={handlePreview}>
              {loading ? 'Calcolo anteprima...' : 'Anteprima'}
            </button>
          </>
        )}
        {step === 'preview' && (
          <>
            <button className="btn-outline" onClick={() => setStep('mapping')}><ArrowLeft size={15} /> Modifica mapping</button>
            <button className="btn-accent" disabled={loading || !previewResult?.imported} onClick={handleConfirm}>
              {loading ? 'Importazione in corso...' : `Conferma import (${previewResult?.imported ?? 0})`}
            </button>
          </>
        )}
        {step === 'result' && (
          <button className="btn-accent" onClick={onSaved}>Chiudi</button>
        )}
      </>
    }>
      {error && <div className="mb-3 rounded-lg bg-red-50 px-3 py-2 text-sm text-red-600">{error}</div>}

      {step === 'upload' && (
        <div className="space-y-4">
          <div className="rounded-xl border border-accent/20 bg-accentSoft px-4 py-3 text-sm text-gray-700">
            Carica l'export CSV di una banca o broker (es. Trade Republic — Profilo → Estratti conto → Esporta
            transazioni) e conferma la mappatura delle colonne al passo successivo. Ogni riga viene assegnata al
            conto scelto qui sotto; i duplicati rispetto a transazioni già presenti vengono rilevati e saltati
            automaticamente.
          </div>
          <div>
            <label className="field-label">Conto di destinazione</label>
            <select className="field" value={accountId} onChange={(e) => setAccountId(e.target.value)}>
              {activeAccounts.map((a) => <option key={a.id} value={a.id}>{a.name}</option>)}
            </select>
          </div>
          <div>
            <label className="field-label">File CSV</label>
            <input
              className="field"
              type="file"
              accept=".csv,text/csv"
              onChange={(e) => setFile(e.target.files?.[0] || null)}
            />
            {file && <p className="mt-1 text-xs text-muted">{file.name} — {(file.size / 1024).toFixed(0)} KB</p>}
          </div>
        </div>
      )}

      {step === 'mapping' && parseData && (
        <div className="space-y-4">
          <p className="text-sm text-muted">{parseData.total_rows} righe rilevate, separatore <code>{delimiter === '\t' ? 'tab' : delimiter}</code>.</p>

          {parseData.saved_mappings.length > 0 && (
            <div>
              <label className="field-label">Configurazione salvata</label>
              <select className="field" defaultValue="" onChange={(e) => e.target.value && applySavedMapping(e.target.value)}>
                <option value="">-- nessuna, mappa manualmente --</option>
                {parseData.saved_mappings.map((m) => <option key={m.id} value={m.id}>{m.name}</option>)}
              </select>
            </div>
          )}

          <div className="grid grid-cols-2 gap-3">
            <div>
              <label className="field-label">Colonna data</label>
              <select className="field" value={columns.date} onChange={(e) => setCol('date', e.target.value)}>
                <option value="">-- seleziona --</option>
                {parseData.headers.map((h) => <option key={h} value={h}>{h}</option>)}
              </select>
            </div>
            <div>
              <label className="field-label">Formato data</label>
              <select className="field" value={dateFormat} onChange={(e) => setDateFormat(e.target.value)}>
                {DATE_FORMAT_OPTIONS.map((o) => <option key={o.value} value={o.value}>{o.label}</option>)}
              </select>
            </div>
          </div>

          <div>
            <label className="field-label">Importo</label>
            <div className="mb-2 flex gap-4 text-sm">
              <label className="flex items-center gap-1.5">
                <input type="radio" checked={amountMode === 'single'} onChange={() => setAmountMode('single')} /> Colonna unica (segno +/-)
              </label>
              <label className="flex items-center gap-1.5">
                <input type="radio" checked={amountMode === 'debit_credit'} onChange={() => setAmountMode('debit_credit')} /> Colonne separate dare/avere
              </label>
            </div>
            {amountMode === 'single' ? (
              <select className="field" value={columns.amount} onChange={(e) => setCol('amount', e.target.value)}>
                <option value="">-- seleziona --</option>
                {parseData.headers.map((h) => <option key={h} value={h}>{h}</option>)}
              </select>
            ) : (
              <div className="grid grid-cols-2 gap-3">
                <select className="field" value={columns.credit} onChange={(e) => setCol('credit', e.target.value)}>
                  <option value="">Avere / entrate: --</option>
                  {parseData.headers.map((h) => <option key={h} value={h}>{h}</option>)}
                </select>
                <select className="field" value={columns.debit} onChange={(e) => setCol('debit', e.target.value)}>
                  <option value="">Dare / uscite: --</option>
                  {parseData.headers.map((h) => <option key={h} value={h}>{h}</option>)}
                </select>
              </div>
            )}
          </div>

          {amountMode === 'single' && (
            <div>
              <label className="field-label">Separatore decimale</label>
              <select className="field" value={decimalSeparator} onChange={(e) => setDecimalSeparator(e.target.value)}>
                {DECIMAL_OPTIONS.map((o) => <option key={o.value} value={o.value}>{o.label}</option>)}
              </select>
            </div>
          )}

          <div>
            <label className="field-label">Colonna descrizione (opzionale)</label>
            <select className="field" value={columns.description} onChange={(e) => setCol('description', e.target.value)}>
              <option value="">-- nessuna --</option>
              {parseData.headers.map((h) => <option key={h} value={h}>{h}</option>)}
            </select>
          </div>

          <div>
            <label className="field-label">Colonna tipo/causale (opzionale)</label>
            <p className="mb-1 text-xs text-muted">Utile per export come quello di Trade Republic che mescolano movimenti di cassa e operazioni di trading: per ogni valore trovato scegli se contarlo come entrata, uscita, o ignorarlo del tutto (es. "Buy"/"Sell" se tieni già traccia degli investimenti nel modulo Investimenti, oppure "Saveback"/"Cash Reward" — il cashback dell'1% di Trade Republic finisce in automatico nel PAC, non resta liquidità sul conto).</p>
            <select className="field" value={columns.type} onChange={(e) => setCol('type', e.target.value)}>
              <option value="">-- nessuna, usa solo il segno dell'importo --</option>
              {parseData.headers.map((h) => <option key={h} value={h}>{h}</option>)}
            </select>
            {columns.type && typeColumnValues === null && (
              <p className="mt-1 flex items-center gap-1 text-xs text-amber-600"><AlertTriangle size={13} /> Troppi valori distinti in questa colonna: verrà usato solo il segno dell'importo per tutte le righe.</p>
            )}
            {columns.type && Array.isArray(typeColumnValues) && (
              <div className="mt-2 max-h-40 space-y-1.5 overflow-y-auto rounded-lg border border-border p-2">
                {typeColumnValues.map((v) => (
                  <div key={v} className="flex items-center justify-between gap-2 text-sm">
                    <span className="truncate" title={v}>{v}</span>
                    <select
                      className="field w-44 py-1 text-xs"
                      value={typeActions[v] || 'auto'}
                      onChange={(e) => setTypeActions((t) => ({ ...t, [v]: e.target.value }))}
                    >
                      <option value="auto">Auto (segno importo)</option>
                      <option value="entrata">Entrata</option>
                      <option value="uscita">Uscita</option>
                      <option value="ignora">Ignora (non importare)</option>
                    </select>
                  </div>
                ))}
              </div>
            )}
          </div>

          <div>
            <label className="field-label">Colonna categoria (opzionale)</label>
            <p className="mb-1 text-xs text-muted">Se mappata, le categorie non ancora esistenti vengono create automaticamente. Le categorie predefinite qui sotto si usano comunque quando il valore è vuoto o la colonna non è mappata.</p>
            <select className="field" value={columns.category} onChange={(e) => setCol('category', e.target.value)}>
              <option value="">-- nessuna --</option>
              {parseData.headers.map((h) => <option key={h} value={h}>{h}</option>)}
            </select>
          </div>

          <div className="grid grid-cols-2 gap-3">
            <div>
              <label className="field-label">Categoria entrate predefinita{needsDefaultCategories && ' *'}</label>
              <select className="field" value={defaultIncomeCat} onChange={(e) => setDefaultIncomeCat(e.target.value)}>
                <option value="">-- nessuna --</option>
                {incomeCategories.map((c) => <option key={c.id} value={c.id}>{c.name}</option>)}
              </select>
            </div>
            <div>
              <label className="field-label">Categoria uscite predefinita{needsDefaultCategories && ' *'}</label>
              <select className="field" value={defaultExpenseCat} onChange={(e) => setDefaultExpenseCat(e.target.value)}>
                <option value="">-- nessuna --</option>
                {expenseCategories.map((c) => <option key={c.id} value={c.id}>{c.name}</option>)}
              </select>
            </div>
          </div>

          <div className="flex items-center gap-2">
            <input id="skip-dup" type="checkbox" checked={skipDuplicates} onChange={(e) => setSkipDuplicates(e.target.checked)} />
            <label htmlFor="skip-dup" className="text-sm">Salta automaticamente le righe già presenti (stesso conto, data, importo e descrizione)</label>
          </div>

          <div className="grid grid-cols-2 gap-3 border-t border-border pt-3">
            <div>
              <label className="field-label">Nome sorgente (etichetta import)</label>
              <input className="field" type="text" value={source} onChange={(e) => setSource(e.target.value)} placeholder="Es. Trade Republic" />
            </div>
            <div>
              <label className="field-label">Salva questa mappatura come (opzionale)</label>
              <input className="field" type="text" value={saveMappingAs} onChange={(e) => setSaveMappingAs(e.target.value)} placeholder="Es. Trade Republic" />
            </div>
          </div>
        </div>
      )}

      {step === 'preview' && previewResult && (
        <ResultSummary result={previewResult} label="Verrebbero importate" />
      )}

      {step === 'result' && finalResult && (
        <div className="space-y-4">
          <div className="flex items-center gap-2 rounded-xl bg-green-50 px-4 py-3 text-sm text-green-700">
            <CheckCircle2 size={18} /> Import completato: {finalResult.imported} transazioni aggiunte.
          </div>
          <ResultSummary result={finalResult} label="Importate" />
          {finalResult.imported > 0 && (
            <p className="text-xs text-muted">Hai sbagliato file o mappatura? Puoi annullare questo import dalla Cronologia importazioni (pulsante accanto a "Importa CSV").</p>
          )}
        </div>
      )}
    </Modal>
  );
}

function ResultSummary({ result, label }) {
  return (
    <div className="space-y-4">
      <div className="grid grid-cols-4 gap-2 text-center text-sm">
        <Stat value={result.imported} label={label} tone="text-income" />
        <Stat value={result.duplicates} label="Duplicati saltati" />
        <Stat value={result.ignored} label="Ignorate (tipo)" />
        <Stat value={result.errors.length} label="Errori" tone={result.errors.length ? 'text-expense' : ''} />
      </div>

      {result.created_categories.length > 0 && (
        <div className="rounded-lg bg-accentSoft px-3 py-2 text-xs text-gray-700">
          Nuove categorie: {result.created_categories.map((c) => `${c.name} (${c.type})`).join(', ')}
        </div>
      )}

      {result.errors.length > 0 && (
        <div className="max-h-32 overflow-y-auto rounded-lg border border-red-200 bg-red-50 p-2 text-xs text-red-700">
          {result.errors.slice(0, 30).map((e, i) => <div key={i}>Riga {e.row}: {e.reason}</div>)}
          {result.errors.length > 30 && <div>... e altri {result.errors.length - 30} errori</div>}
        </div>
      )}

      {result.sample.length > 0 && (
        <div className="overflow-x-auto rounded-lg border border-border">
          <table className="w-full text-xs">
            <thead>
              <tr className="border-b border-border bg-gray-50 text-left uppercase tracking-wide text-muted">
                <th className="px-3 py-2">Data</th>
                <th className="px-3 py-2">Tipo</th>
                <th className="px-3 py-2">Categoria</th>
                <th className="px-3 py-2">Descrizione</th>
                <th className="px-3 py-2 text-right">Importo</th>
              </tr>
            </thead>
            <tbody>
              {result.sample.map((r, i) => (
                <tr key={i} className="border-b border-border last:border-0">
                  <td className="px-3 py-1.5">{formatDate(r.date)}</td>
                  <td className="px-3 py-1.5">{r.type}</td>
                  <td className="px-3 py-1.5">{r.category_name || '—'}</td>
                  <td className="px-3 py-1.5">{r.description || '—'}</td>
                  <td className={`px-3 py-1.5 text-right font-mono ${r.type === 'entrata' ? 'text-income' : 'text-expense'}`}>{euro(r.amount)}</td>
                </tr>
              ))}
            </tbody>
          </table>
          {result.imported > result.sample.length && (
            <p className="px-3 py-1.5 text-xs text-muted">... e altre {result.imported - result.sample.length} righe</p>
          )}
        </div>
      )}
    </div>
  );
}

function Stat({ value, label, tone = '' }) {
  return (
    <div className="rounded-lg border border-border p-2">
      <div className={`text-lg font-bold ${tone}`}>{value}</div>
      <div className="text-[11px] text-muted">{label}</div>
    </div>
  );
}
