import { useEffect, useState } from 'react';
import {
  Upload, AlertTriangle, CheckCircle2, ArrowLeft, Check, Landmark,
  CalendarClock, Coins, Tags, SlidersHorizontal, Sparkles,
} from 'lucide-react';
import Modal from '../components/Modal';
import { useData } from '../context/DataContext';
import { apiSend } from '../api';
import { euro, formatDate } from '../lib/format';

// Elenco puramente informativo (dove trovare l'export nel proprio internet
// banking): l'app mappa comunque qualsiasi colonna manualmente al passo
// successivo, quindi questa lista non deve essere esaustiva o esatta al 100%
// per funzionare — serve solo a orientare l'utente. Gli id di 'trade_republic'
// e 'bancoposta' coincidono con quelli riconosciuti automaticamente dal
// backend (vedi BANK_PRESETS in import.php) cosi' il badge "Rilevato" dopo
// l'analisi puo' precompilare la stessa selezione.
const BANKS = [
  { id: 'trade_republic', name: 'Trade Republic', hint: 'Profilo → Estratti conto → Esporta transazioni (CSV).' },
  { id: 'bancoposta', name: 'BancoPosta / Poste', hint: 'App o sito Poste → Lista movimenti → Esporta (CSV/Excel).' },
  { id: 'fineco', name: 'Fineco', hint: 'Movimenti conto → filtra periodo → "Esporta risultato" (Excel).' },
  { id: 'intesa', name: 'Intesa Sanpaolo', hint: 'Internet banking → Archivio → Documenti → Estratti conto.' },
  { id: 'unicredit', name: 'UniCredit', hint: 'App o sito → Conto corrente → Movimenti → esporta/scarica.' },
  { id: 'n26', name: 'N26', hint: 'App N26 → Conto → Estratti conto (Statements) → CSV.' },
  { id: 'revolut', name: 'Revolut', hint: 'App Revolut → Conto → Estratto conto → esporta CSV.' },
  { id: 'ing', name: 'ING', hint: 'App o sito ING → Movimenti conto → esporta.' },
  { id: 'widiba', name: 'Widiba', hint: 'Sito Widiba → Lista movimenti → esporta.' },
  { id: 'sella', name: 'Banca Sella', hint: 'App o sito → Movimenti conto → esporta.' },
  { id: 'hype', name: 'Hype', hint: 'App Hype → Movimenti → condividi/esporta.' },
  { id: 'chebanca', name: 'CheBanca!', hint: 'App o sito → Movimenti → esporta.' },
  { id: 'bper', name: 'BPER Banca', hint: 'App o sito → Movimenti conto → esporta.' },
  { id: 'directa', name: 'Directa (trading)', hint: 'Area riservata → Rendiconto/Movimenti → esporta CSV.' },
  { id: 'other', name: 'Altra banca / broker', hint: 'Cerca una voce "Esporta", "Scarica movimenti" o "Estratto conto".' },
];

const STEPS = [
  { key: 'upload', label: 'Carica' },
  { key: 'mapping', label: 'Mappa colonne' },
  { key: 'preview', label: 'Anteprima' },
  { key: 'result', label: 'Fine' },
];

function Stepper({ current }) {
  const idx = STEPS.findIndex((s) => s.key === current);
  return (
    <div className="mb-5 flex items-center">
      {STEPS.map((s, i) => (
        <div key={s.key} className={`flex items-center ${i < STEPS.length - 1 ? 'flex-1' : ''}`}>
          <div className="flex flex-col items-center gap-1">
            <div className={`flex h-7 w-7 shrink-0 items-center justify-center rounded-full text-xs font-semibold transition-colors ${
              i < idx ? 'bg-accent text-white' : i === idx ? 'bg-accent text-white ring-4 ring-accentSoft' : 'bg-gray-100 text-muted'
            }`}>
              {i < idx ? <Check size={14} /> : i + 1}
            </div>
            <span className={`whitespace-nowrap text-[11px] ${i === idx ? 'font-semibold text-gray-900' : 'text-muted'}`}>{s.label}</span>
          </div>
          {i < STEPS.length - 1 && (
            <div className={`mx-2 mb-4 h-0.5 flex-1 rounded ${i < idx ? 'bg-accent' : 'bg-gray-100'}`} />
          )}
        </div>
      ))}
    </div>
  );
}

function Section({ icon: Icon, title, subtitle, tone = 'default', children }) {
  const toneClass = tone === 'accent' ? 'border-accent/20 bg-accentSoft' : 'border-border';
  return (
    <div className={`rounded-xl border ${toneClass} p-4`}>
      <div className="mb-3 flex items-start gap-2.5">
        <div className="mt-0.5 flex h-7 w-7 shrink-0 items-center justify-center rounded-lg bg-accent/10 text-accent">
          <Icon size={15} />
        </div>
        <div>
          <h4 className="text-sm font-semibold text-gray-900">{title}</h4>
          {subtitle && <p className="mt-0.5 text-xs text-muted">{subtitle}</p>}
        </div>
      </div>
      <div className="space-y-3">{children}</div>
    </div>
  );
}

const MAX_FILE_BYTES = 5 * 1024 * 1024; // limite lato client, coerente con post_max_size=8M lato PHP (il base64 gonfia ~1.37x)

const DATE_FORMAT_OPTIONS = [
  { value: 'auto', label: 'Rileva automaticamente' },
  { value: 'd/m/Y', label: 'GG/MM/AAAA (31/01/2026)' },
  { value: 'Y-m-d', label: 'AAAA-MM-GG (2026-01-31)' },
  { value: 'd.m.Y', label: 'GG.MM.AAAA (31.01.2026)' },
  { value: 'd-m-Y', label: 'GG-MM-AAAA (31-01-2026)' },
  { value: 'm/d/Y', label: 'MM/GG/AAAA, formato USA (01/31/2026)' },
  { value: 'Y/m/d', label: 'AAAA/MM/GG (2026/01/31)' },
  { value: 'excel_serial', label: 'Numero seriale Excel (colonna data di un .xlsx)' },
];

const DECIMAL_OPTIONS = [
  { value: 'auto', label: 'Rileva automaticamente' },
  { value: ',', label: 'Virgola: 1.234,56' },
  { value: '.', label: 'Punto: 1,234.56' },
];

const emptyColumns = {
  date: '', amount: '', debit: '', credit: '', description: '', type: '', category: '',
  symbol: '', shares: '', price: '', instrument_name: '', asset_class: '', fee: '', tax: '',
};

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
  const [bankId, setBankId] = useState('');
  const [detectedBank, setDetectedBank] = useState(null);
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
    setBankId('');
    setDetectedBank(null);
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
      const suggested = {};
      Object.keys(emptyColumns).forEach((f) => { suggested[f] = data.suggested_mapping[f] || ''; });
      setColumns(suggested);
      setAmountMode(!data.suggested_mapping.amount && (data.suggested_mapping.debit || data.suggested_mapping.credit) ? 'debit_credit' : 'single');
      setDateFormat(data.suggested_date_format || 'auto');
      setDetectedBank(data.detected_bank || null);
      if (data.detected_bank && !bankId) setBankId(data.detected_bank.id);
      setSource(data.detected_bank?.name || file.name.replace(/\.[Cc][Ss][Vv]$|\.[Xx][Ll][Ss][Xx]$/, ''));
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
    const restored = {};
    Object.keys(emptyColumns).forEach((f) => { restored[f] = keep(cfg.columns?.[f]); });
    setColumns(restored);
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
        ...columns,
        amount: amountMode === 'single' ? columns.amount : '',
        debit: amountMode === 'debit_credit' ? columns.debit : '',
        credit: amountMode === 'debit_credit' ? columns.credit : '',
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
  const hasInvestmentRows = Object.values(typeActions).includes('investimento');
  const canPreview = !!columns.date
    && (amountMode === 'single' ? !!columns.amount : !!(columns.debit || columns.credit))
    && (!needsDefaultCategories || (defaultIncomeCat && defaultExpenseCat))
    && (!hasInvestmentRows || (columns.shares && columns.price && (columns.symbol || columns.instrument_name)));

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
      <Stepper current={step} />
      {error && <div className="mb-3 rounded-lg bg-red-50 px-3 py-2 text-sm text-red-600">{error}</div>}

      {step === 'upload' && (
        <div className="space-y-4">
          <div className="rounded-xl border border-accent/20 bg-accentSoft px-4 py-3 text-sm text-gray-700">
            Carica l'export CSV o Excel (.xlsx) di una banca o broker: al passo successivo potrai confermare o
            correggere come vengono interpretate le colonne. Eventuali righe di intestazione prima della tabella
            (conto, saldo, periodo) vengono riconosciute e ignorate automaticamente. Ogni riga viene assegnata al
            conto scelto qui sotto; i duplicati rispetto a transazioni già presenti vengono rilevati e saltati.
          </div>

          <div>
            <label className="field-label">Da quale banca o broker viene il file? (opzionale)</label>
            <p className="mb-2 text-xs text-muted">
              Serve solo per mostrarti dove trovare l'export nel tuo internet banking — il percorso esatto può
              variare tra versioni dell'app o del sito.
            </p>
            <div className="grid grid-cols-2 gap-1.5 sm:grid-cols-3">
              {BANKS.map((b) => (
                <button
                  key={b.id}
                  type="button"
                  onClick={() => setBankId((cur) => (cur === b.id ? '' : b.id))}
                  className={`flex items-center gap-1.5 rounded-lg border px-2.5 py-1.5 text-left text-xs transition-colors ${
                    bankId === b.id ? 'border-accent bg-accentSoft font-medium text-accentDark' : 'border-border text-gray-700 hover:border-accent/40'
                  }`}
                >
                  <Landmark size={13} className={bankId === b.id ? 'text-accent' : 'text-muted'} />
                  <span className="truncate">{b.name}</span>
                </button>
              ))}
            </div>
            {bankId && (
              <p className="mt-2 flex items-start gap-1.5 rounded-lg bg-gray-50 px-3 py-2 text-xs text-gray-700">
                <Sparkles size={13} className="mt-0.5 shrink-0 text-accent" />
                Dove trovarlo: {BANKS.find((b) => b.id === bankId)?.hint}
              </p>
            )}
          </div>

          <div>
            <label className="field-label">Conto di destinazione</label>
            <select className="field" value={accountId} onChange={(e) => setAccountId(e.target.value)}>
              {activeAccounts.map((a) => <option key={a.id} value={a.id}>{a.name}</option>)}
            </select>
          </div>
          <div>
            <label className="field-label">File CSV o Excel (.xlsx)</label>
            <label
              htmlFor="import-file-input"
              className={`flex cursor-pointer items-center gap-3 rounded-xl border border-dashed px-4 py-4 text-sm transition-colors ${
                file ? 'border-accent bg-accentSoft' : 'border-border hover:border-accent/50 hover:bg-gray-50'
              }`}
            >
              <div className={`flex h-9 w-9 shrink-0 items-center justify-center rounded-lg ${file ? 'bg-accent text-white' : 'bg-gray-100 text-muted'}`}>
                <Upload size={17} />
              </div>
              <div className="min-w-0">
                {file ? (
                  <>
                    <p className="truncate font-medium text-gray-900">{file.name}</p>
                    <p className="text-xs text-muted">{(file.size / 1024).toFixed(0)} KB — clicca per cambiare file</p>
                  </>
                ) : (
                  <>
                    <p className="font-medium text-gray-900">Clicca per scegliere un file</p>
                    <p className="text-xs text-muted">CSV o Excel (.xlsx), fino a {MAX_FILE_BYTES / 1024 / 1024} MB</p>
                  </>
                )}
              </div>
            </label>
            <input
              id="import-file-input"
              className="sr-only"
              type="file"
              accept=".csv,.xlsx,text/csv,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet"
              onChange={(e) => setFile(e.target.files?.[0] || null)}
            />
          </div>
        </div>
      )}

      {step === 'mapping' && parseData && (
        <div className="space-y-4">
          <div className="flex flex-wrap items-center gap-2">
            <p className="text-sm text-muted">
              {parseData.total_rows} righe rilevate
              {parseData.is_xlsx ? ' (foglio Excel)' : <> , separatore <code>{delimiter === '\t' ? 'tab' : delimiter}</code></>}.
            </p>
            {detectedBank && (
              <span className="flex items-center gap-1 rounded-full bg-income/10 px-2.5 py-1 text-xs font-medium text-income">
                <CheckCircle2 size={13} /> Riconosciuto: {detectedBank.name}
              </span>
            )}
          </div>

          {parseData.saved_mappings.length > 0 && (
            <div>
              <label className="field-label">Configurazione salvata</label>
              <select className="field" defaultValue="" onChange={(e) => e.target.value && applySavedMapping(e.target.value)}>
                <option value="">-- nessuna, mappa manualmente --</option>
                {parseData.saved_mappings.map((m) => <option key={m.id} value={m.id}>{m.name}</option>)}
              </select>
            </div>
          )}

          <Section icon={CalendarClock} title="Data e importo" subtitle="I due campi obbligatori per riconoscere ogni movimento.">
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
          </Section>

          <Section icon={Tags} title="Descrizione, tipo e categoria" subtitle="Come vengono etichettate le righe importate.">
            <div>
              <label className="field-label">Colonna descrizione (opzionale)</label>
              <select className="field" value={columns.description} onChange={(e) => setCol('description', e.target.value)}>
                <option value="">-- nessuna --</option>
                {parseData.headers.map((h) => <option key={h} value={h}>{h}</option>)}
              </select>
            </div>

            <div>
              <label className="field-label">Colonna tipo/causale (opzionale)</label>
              <p className="mb-1 text-xs text-muted">Utile per export come quello di Trade Republic che mescolano movimenti di cassa e operazioni di trading: per ogni valore trovato scegli se contarlo come entrata, uscita, operazione su uno strumento (genera acquisto/vendita nel modulo Investimenti, incluse le esecuzioni di un PAC), interesse (per broker esteri che non trattengono l'imposta: registra il netto dopo l'imposta sostitutiva del conto, con lordo/imposta/netto in descrizione — categoria "Interessi attivi") oppure ignorarlo del tutto (es. "Saveback"/"Cash Reward" — il cashback dell'1% di Trade Republic finisce in automatico nel PAC, non resta liquidità sul conto — o eventi senza contropartita in denaro come migrazioni titoli).</p>
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
                        className="field w-52 py-1 text-xs"
                        value={typeActions[v] || 'auto'}
                        onChange={(e) => setTypeActions((t) => ({ ...t, [v]: e.target.value }))}
                      >
                        <option value="auto">Auto (segno importo)</option>
                        <option value="entrata">Entrata</option>
                        <option value="uscita">Uscita</option>
                        <option value="investimento">Investimento (acquisto/vendita titolo)</option>
                        <option value="interesse">Interesse (netta l'imposta sostitutiva)</option>
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
          </Section>

          {hasInvestmentRows && (
            <Section icon={Coins} title="Investimenti" tone="accent" subtitle="Colonne per le righe marcate 'Investimento'.">
              <p className="text-xs text-gray-700">
                Generano un acquisto/vendita nello strumento (creato in automatico se non esiste già, cercato per
                simbolo) più il movimento di cassa collegato sul conto, con la stessa formula usata dall'inserimento
                manuale in Investimenti (netto = valore scambio ± commissioni ± imposta).
              </p>
              <div className="grid grid-cols-2 gap-3">
                <div>
                  <label className="field-label">Simbolo / ISIN</label>
                  <select className="field" value={columns.symbol} onChange={(e) => setCol('symbol', e.target.value)}>
                    <option value="">-- nessuno --</option>
                    {parseData.headers.map((h) => <option key={h} value={h}>{h}</option>)}
                  </select>
                </div>
                <div>
                  <label className="field-label">Nome strumento</label>
                  <select className="field" value={columns.instrument_name} onChange={(e) => setCol('instrument_name', e.target.value)}>
                    <option value="">-- nessuno --</option>
                    {parseData.headers.map((h) => <option key={h} value={h}>{h}</option>)}
                  </select>
                </div>
              </div>
              <div className="grid grid-cols-2 gap-3">
                <div>
                  <label className="field-label">Quote (quantità) *</label>
                  <select className="field" value={columns.shares} onChange={(e) => setCol('shares', e.target.value)}>
                    <option value="">-- seleziona --</option>
                    {parseData.headers.map((h) => <option key={h} value={h}>{h}</option>)}
                  </select>
                </div>
                <div>
                  <label className="field-label">Prezzo per quota *</label>
                  <select className="field" value={columns.price} onChange={(e) => setCol('price', e.target.value)}>
                    <option value="">-- seleziona --</option>
                    {parseData.headers.map((h) => <option key={h} value={h}>{h}</option>)}
                  </select>
                </div>
              </div>
              <div className="grid grid-cols-3 gap-3">
                <div>
                  <label className="field-label">Classe strumento</label>
                  <select className="field" value={columns.asset_class} onChange={(e) => setCol('asset_class', e.target.value)}>
                    <option value="">-- nessuna (altro) --</option>
                    {parseData.headers.map((h) => <option key={h} value={h}>{h}</option>)}
                  </select>
                </div>
                <div>
                  <label className="field-label">Commissioni</label>
                  <select className="field" value={columns.fee} onChange={(e) => setCol('fee', e.target.value)}>
                    <option value="">-- nessuna --</option>
                    {parseData.headers.map((h) => <option key={h} value={h}>{h}</option>)}
                  </select>
                </div>
                <div>
                  <label className="field-label">Imposta</label>
                  <select className="field" value={columns.tax} onChange={(e) => setCol('tax', e.target.value)}>
                    <option value="">-- nessuna --</option>
                    {parseData.headers.map((h) => <option key={h} value={h}>{h}</option>)}
                  </select>
                </div>
              </div>
            </Section>
          )}

          <Section icon={SlidersHorizontal} title="Impostazioni avanzate" subtitle="Duplicati, etichetta della sorgente e mappature riusabili.">
            <div className="flex items-center gap-2">
              <input id="skip-dup" type="checkbox" checked={skipDuplicates} onChange={(e) => setSkipDuplicates(e.target.checked)} />
              <label htmlFor="skip-dup" className="text-sm">Salta automaticamente le righe già presenti (stesso conto, data, importo e descrizione)</label>
            </div>

            <div className="grid grid-cols-2 gap-3">
              <div>
                <label className="field-label">Nome sorgente (etichetta import)</label>
                <input className="field" type="text" value={source} onChange={(e) => setSource(e.target.value)} placeholder="Es. Trade Republic" />
              </div>
              <div>
                <label className="field-label">Salva questa mappatura come (opzionale)</label>
                <input className="field" type="text" value={saveMappingAs} onChange={(e) => setSaveMappingAs(e.target.value)} placeholder="Es. Trade Republic" />
              </div>
            </div>
          </Section>
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

      {result.investment_count > 0 && (
        <div className="rounded-lg bg-accentSoft px-3 py-2 text-xs text-gray-700">
          Di cui {result.investment_count} operazioni di acquisto/vendita titoli generate in Investimenti
          {result.created_instruments.length > 0 && <> — nuovi strumenti: {result.created_instruments.map((s) => `${s.name} (${s.ticker})`).join(', ')}</>}
        </div>
      )}

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
