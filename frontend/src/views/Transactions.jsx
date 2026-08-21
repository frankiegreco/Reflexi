import { useEffect, useState, useCallback } from 'react';
import { Download, Upload, History, Plus, Pencil, Trash2, ChevronLeft, ChevronRight, Trash, TrendingUp, TrendingDown, Scale } from 'lucide-react';
import { useData } from '../context/DataContext';
import { apiGet, apiDelete } from '../api';
import { euro, formatDate, debounce } from '../lib/format';
import TransactionModal from './TransactionModal';
import ImportModal from './ImportModal';
import ImportHistoryModal from './ImportHistoryModal';
import { KNOWN_PAYEES } from '../lib/payees';
import ServiceIcon from '../components/ServiceIcon';
import StatCard from '../components/StatCard';

function matchPayee(description) {
  if (!description) return null;
  const d = description.trim().toLowerCase();
  return KNOWN_PAYEES.find((p) => p.name.toLowerCase() === d) || null;
}

export default function Transactions() {
  const { activeAccounts, categories, signal, bump } = useData();
  const [rows, setRows] = useState([]);
  const [filters, setFilters] = useState({ account_id: '', category_id: '', type: '', date_from: '', date_to: '', search: '', amount: '' });
  const [modalOpen, setModalOpen] = useState(false);
  const [editing, setEditing] = useState(null);
  const [importOpen, setImportOpen] = useState(false);
  const [importHistoryOpen, setImportHistoryOpen] = useState(false);
  const [page, setPage] = useState(1);
  const PAGE_SIZE = 10;

  const [loading, setLoading] = useState(false);

  const load = useCallback(async () => {
    setLoading(true);
    const params = new URLSearchParams();
    Object.entries(filters).forEach(([k, v]) => { if (v) params.set(k, v); });
    params.set('limit', '1000');

    // L'API pagina lato server (max 1000 righe a chiamata): la vista attuale
    // pagina invece lato client sull'array completo, quindi qui carichiamo
    // tutte le pagine finche' non ne troviamo una incompleta, altrimenti con
    // piu' di 1000 transazioni filtrate se ne vedrebbero solo le prime 1000.
    let all = [];
    let offset = 0;
    for (;;) {
      params.set('offset', String(offset));
      const batch = await apiGet(`transactions.php?${params.toString()}`);
      all = all.concat(batch);
      if (batch.length < 1000) break;
      offset += 1000;
    }

    setRows(all);
    setPage(1);
    setLoading(false);
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [filters, signal]);

  useEffect(() => { load(); }, [load]);

  const debouncedSetSearch = useCallback(debounce((v) => setFilters((f) => ({ ...f, search: v })), 300), []);
  const debouncedSetAmount = useCallback(debounce((v) => setFilters((f) => ({ ...f, amount: v })), 300), []);

  // Esclusi i trasferimenti: spostano soldi tra propri conti, non sono ne'
  // entrata ne' uscita reale (stessa convenzione dei grafici in Riepilogo).
  const totalEntrate = rows.reduce((s, t) => s + (t.type === 'entrata' ? parseFloat(t.amount) : 0), 0);
  const totalUscite = rows.reduce((s, t) => s + (t.type === 'uscita' ? parseFloat(t.amount) : 0), 0);

  async function handleDelete(id) {
    if (!confirm('Eliminare questa transazione?')) return;
    await apiDelete(`transactions.php?id=${id}`);
    load();
    bump();
  }

  async function handleDeleteAll() {
    const count = rows.length;
    if (!count) return;
    const hasFilters = Object.values(filters).some((v) => v);
    const scope = hasFilters ? `le ${count} transazioni filtrate` : `TUTTE le ${count} transazioni`;
    if (!confirm(`Eliminare ${scope}? L'operazione è irreversibile.`)) return;

    const params = new URLSearchParams();
    Object.entries(filters).forEach(([k, v]) => { if (v) params.set(k, v); });
    params.set('action', 'bulk');
    await apiDelete(`transactions.php?${params.toString()}`);
    load();
    bump();
  }

  const exportUrl = () => {
    const params = new URLSearchParams();
    Object.entries(filters).forEach(([k, v]) => { if (v) params.set(k, v); });
    return `/api/export.php?${params.toString()}`;
  };

  return (
    <div>
      <div className="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <h4 className="text-xl font-bold">Transazioni</h4>
        <div className="flex flex-wrap gap-2">
          <a href={exportUrl()} className="btn-outline"><Download size={15} /> Esporta CSV</a>
          <button className="btn-outline" onClick={() => setImportOpen(true)}><Upload size={15} /> Importa CSV</button>
          <button className="btn-outline" onClick={() => setImportHistoryOpen(true)}><History size={15} /> Cronologia import</button>
          <button className="btn-outline text-expense" onClick={handleDeleteAll} disabled={!rows.length}><Trash size={15} /> Elimina tutte</button>
          <button className="btn-accent" onClick={() => { setEditing(null); setModalOpen(true); }}><Plus size={15} /> Movimento</button>
        </div>
      </div>

      <div className="mb-4 rounded-xl border border-accent/20 bg-accentSoft px-4 py-3 text-sm text-gray-700">
        Qui registri ogni movimento di denaro: uno stipendio che arriva, una spesa al supermercato,
        un bonifico tra due tuoi conti. Ogni transazione va assegnata a un <span className="font-semibold">conto</span> e
        una <span className="font-semibold">categoria</span> — è quello che rende utili i grafici nel Riepilogo.
      </div>

      <div className="card mb-3 flex flex-wrap gap-2 p-3">
        <select className="field w-full sm:w-auto" value={filters.account_id} onChange={(e) => setFilters((f) => ({ ...f, account_id: e.target.value }))}>
          <option value="">Tutti i conti</option>
          {activeAccounts.map((a) => <option key={a.id} value={a.id}>{a.name}</option>)}
        </select>
        <select className="field w-full sm:w-auto" value={filters.category_id} onChange={(e) => setFilters((f) => ({ ...f, category_id: e.target.value }))}>
          <option value="">Tutte le categorie</option>
          {categories.map((c) => <option key={c.id} value={c.id}>{c.name} ({c.type})</option>)}
        </select>
        <select className="field w-full sm:w-auto" value={filters.type} onChange={(e) => setFilters((f) => ({ ...f, type: e.target.value }))}>
          <option value="">Tutti i tipi</option>
          <option value="entrata">Entrate</option>
          <option value="uscita">Uscite</option>
          <option value="trasferimento">Trasferimenti</option>
        </select>
        <input className="field w-full sm:w-auto" type="date" value={filters.date_from} onChange={(e) => setFilters((f) => ({ ...f, date_from: e.target.value }))} />
        <input className="field w-full sm:w-auto" type="date" value={filters.date_to} onChange={(e) => setFilters((f) => ({ ...f, date_to: e.target.value }))} />
        <input className="field flex-1" type="text" placeholder="Cerca descrizione..." onChange={(e) => debouncedSetSearch(e.target.value)} />
        <input className="field w-full sm:w-36" type="number" step="0.01" placeholder="Cerca importo..." onChange={(e) => debouncedSetAmount(e.target.value)} />
      </div>

      <div className="mb-3 grid grid-cols-1 gap-3 sm:grid-cols-3">
        <StatCard label="Totale entrate" value={euro(totalEntrate)} valueClass="text-income" icon={TrendingUp} iconColor="#7fa876" />
        <StatCard label="Totale uscite" value={euro(totalUscite)} valueClass="text-expense" icon={TrendingDown} iconColor="#c1665a" />
        <StatCard label="Saldo" value={euro(totalEntrate - totalUscite)} valueClass={totalEntrate - totalUscite >= 0 ? 'text-income' : 'text-expense'} icon={Scale} iconColor="#4f46e5" />
      </div>

      <div className="card overflow-hidden">
        <div className="overflow-x-auto">
        <table className="w-full text-sm">
          <thead>
            <tr className="border-b border-border text-left text-[11px] uppercase tracking-wide text-muted">
              <th className="px-4 py-3">Data</th>
              <th className="px-4 py-3">Conto</th>
              <th className="px-4 py-3">Categoria</th>
              <th className="px-4 py-3">Descrizione</th>
              <th className="px-4 py-3 text-right">Importo</th>
              <th className="px-4 py-3"></th>
            </tr>
          </thead>
          <tbody>
            {loading ? (
              <tr><td colSpan={6} className="px-4 py-8 text-center text-sm text-muted">Aggiornamento in corso...</td></tr>
            ) : rows.length ? rows.slice((page - 1) * PAGE_SIZE, page * PAGE_SIZE).map((t) => {
              const sign = t.type === 'entrata' ? '+' : (t.type === 'uscita' ? '-' : '');
              const cls = t.type === 'entrata' ? 'text-income font-semibold' : (t.type === 'uscita' ? 'text-expense font-semibold' : '');
              const catLabel = t.type === 'trasferimento' ? `→ ${t.transfer_account_name}` : t.category_name;
              return (
                <tr key={t.id} className="border-b border-border last:border-0 hover:bg-gray-50">
                  <td className="px-4 py-3">{formatDate(t.date)}</td>
                  <td className="px-4 py-3">{t.account_name}</td>
                  <td className="px-4 py-3">{catLabel}</td>
                  <td className="px-4 py-3">
                    <div className="flex items-center gap-2">
                      {matchPayee(t.description) && <ServiceIcon name={t.description} domain={matchPayee(t.description).domain} color={matchPayee(t.description).color} size={20} />}
                      {t.description}
                    </div>
                  </td>
                  <td className={`px-4 py-3 text-right font-mono ${cls}`}>{sign} {euro(t.amount)}</td>
                  <td className="px-4 py-3 text-right">
                    <button className="mr-2 text-muted hover:text-accent" onClick={() => { setEditing(t); setModalOpen(true); }}><Pencil size={15} /></button>
                    <button className="text-muted hover:text-expense" onClick={() => handleDelete(t.id)}><Trash2 size={15} /></button>
                  </td>
                </tr>
              );
            }) : (
              <tr><td colSpan={6} className="px-4 py-8 text-center text-muted">Nessuna transazione</td></tr>
            )}
          </tbody>
        </table>
        </div>

        {rows.length > PAGE_SIZE && (
          <div className="flex items-center justify-between border-t border-border px-4 py-3 text-sm">
            <span className="text-muted">{rows.length} transazioni — pagina {page} di {Math.ceil(rows.length / PAGE_SIZE)}</span>
            <div className="flex gap-2">
              <button className="btn-outline" disabled={page === 1} onClick={() => setPage((p) => p - 1)}><ChevronLeft size={15} /></button>
              <button className="btn-outline" disabled={page >= Math.ceil(rows.length / PAGE_SIZE)} onClick={() => setPage((p) => p + 1)}><ChevronRight size={15} /></button>
            </div>
          </div>
        )}
      </div>

      <TransactionModal
        open={modalOpen}
        editingTx={editing}
        onClose={() => setModalOpen(false)}
        onSaved={() => { setModalOpen(false); load(); bump(); }}
      />
      <ImportModal
        open={importOpen}
        onClose={() => setImportOpen(false)}
        onSaved={() => { setImportOpen(false); load(); bump(); }}
      />
      <ImportHistoryModal
        open={importHistoryOpen}
        onClose={() => setImportHistoryOpen(false)}
        onUndo={() => { load(); bump(); }}
      />
    </div>
  );
}
