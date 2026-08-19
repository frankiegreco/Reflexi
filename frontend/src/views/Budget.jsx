import { useState, useEffect, useCallback } from 'react';
import { ChevronLeft, ChevronRight } from 'lucide-react';
import { Line } from 'react-chartjs-2';
import { apiGet } from '../api';
import { euro } from '../lib/format';
import ProgressBar from '../components/ProgressBar';

const MONTH_NAMES = ['Gennaio', 'Febbraio', 'Marzo', 'Aprile', 'Maggio', 'Giugno', 'Luglio', 'Agosto', 'Settembre', 'Ottobre', 'Novembre', 'Dicembre'];

export default function Budget() {
  const [cursor, setCursor] = useState(() => { const d = new Date(); return { y: d.getFullYear(), m: d.getMonth() + 1 }; });
  const [overview, setOverview] = useState(null);
  const [rows, setRows] = useState([]);

  const monthStr = `${cursor.y}-${String(cursor.m).padStart(2, '0')}`;

  const load = useCallback(async () => {
    const [ov, budgetRes] = await Promise.all([
      apiGet(`budgets.php?action=overview&month=${monthStr}`),
      apiGet(`budgets.php?month=${monthStr}`),
    ]);
    setOverview(ov);
    setRows(budgetRes.budgets || []);
  }, [monthStr]);
  useEffect(() => { load(); }, [load]);

  function shiftMonth(delta) {
    let { y, m } = cursor;
    m += delta;
    if (m > 12) { m = 1; y++; } else if (m < 1) { m = 12; y--; }
    setCursor({ y, m });
  }

  const incomeRows = rows.filter((r) => r.category_type === 'entrata');
  const expenseRows = rows.filter((r) => r.category_type === 'uscita');
  const totalNeeds = overview?.ratios.necessita || 0;
  const totalWants = overview?.ratios.desiderio || 0;
  const totalSavings = overview?.ratios.risparmio || 0;
  const totalRatios = totalNeeds + totalWants + totalSavings;

  return (
    <div>
      <div className="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <h4 className="text-xl font-bold">Budget</h4>
        <div className="flex items-center gap-2">
          <button className="btn-outline" onClick={() => shiftMonth(-1)}><ChevronLeft size={15} /></button>
          <span className="w-40 text-center text-sm font-semibold">{MONTH_NAMES[cursor.m - 1]} {cursor.y}</span>
          <button className="btn-outline" onClick={() => shiftMonth(1)}><ChevronRight size={15} /></button>
        </div>
      </div>

      {overview && (
        <div className="mb-5 grid grid-cols-1 gap-4 lg:grid-cols-3">
          <div className="card p-5 lg:col-span-2">
            <h6 className="mb-1 text-sm font-semibold">Entrate vs Uscite — ultimi 6 mesi</h6>
            {overview.history.length > 0 ? (
              <Line
                data={{
                  labels: overview.history.map((h) => h.ym),
                  datasets: [
                    { label: 'Entrate', data: overview.history.map((h) => h.income), borderColor: '#10b981', backgroundColor: 'rgba(16,185,129,0.08)', tension: 0.25 },
                    { label: 'Uscite', data: overview.history.map((h) => h.expense), borderColor: '#f43f5e', backgroundColor: 'rgba(244,63,94,0.08)', tension: 0.25 },
                  ],
                }}
                options={{ responsive: true, scales: { y: { ticks: { callback: (v) => euro(v) } } } }}
              />
            ) : <p className="py-8 text-center text-sm text-muted">Nessun dato disponibile</p>}
          </div>

          <div className="card p-5">
            <h6 className="mb-3 text-sm font-semibold">Ripartizione spesa del mese</h6>
            {overview.has_classification && totalRatios > 0 ? (
              <div className="space-y-3">
                <div>
                  <div className="mb-1 flex justify-between text-xs"><span>Necessità</span><span className="font-mono">{euro(totalNeeds)} ({((totalNeeds / totalRatios) * 100).toFixed(0)}%)</span></div>
                  <ProgressBar pct={(totalNeeds / totalRatios) * 100} color="#4f46e5" />
                </div>
                <div>
                  <div className="mb-1 flex justify-between text-xs"><span>Desideri</span><span className="font-mono">{euro(totalWants)} ({((totalWants / totalRatios) * 100).toFixed(0)}%)</span></div>
                  <ProgressBar pct={(totalWants / totalRatios) * 100} color="#f59e0b" />
                </div>
                <div>
                  <div className="mb-1 flex justify-between text-xs"><span>Risparmio</span><span className="font-mono">{euro(totalSavings)} ({((totalSavings / totalRatios) * 100).toFixed(0)}%)</span></div>
                  <ProgressBar pct={(totalSavings / totalRatios) * 100} color="#10b981" />
                </div>
              </div>
            ) : (
              <p className="text-sm text-muted">
                Classifica le tue categorie (necessità, desideri, risparmio) su <strong>Categorie</strong> per vedere qui la ripartizione — è facoltativo, senza non cambia nient'altro.
              </p>
            )}
          </div>
        </div>
      )}

      <div className="grid grid-cols-1 gap-5 lg:grid-cols-2">
        <div className="card p-5">
          <h6 className="mb-3 flex items-center gap-1.5 text-sm font-semibold text-income">↓ Entrate</h6>
          {incomeRows.length === 0 ? <p className="text-sm text-muted">Nessun budget impostato per le entrate.</p> : (
            <div className="space-y-3">
              {incomeRows.map((r) => (
                <div key={r.id}>
                  <div className="mb-1 flex justify-between text-sm"><span>{r.category_name}</span><span className="font-mono">{euro(r.spent)} / {euro(r.monthly_limit)}</span></div>
                  <ProgressBar pct={r.monthly_limit > 0 ? (r.spent / r.monthly_limit) * 100 : 0} color="#10b981" />
                </div>
              ))}
            </div>
          )}
        </div>

        <div className="card p-5">
          <h6 className="mb-3 flex items-center gap-1.5 text-sm font-semibold text-expense">↑ Uscite</h6>
          {expenseRows.length === 0 ? <p className="text-sm text-muted">Nessun budget impostato per le uscite. Vai su Categorie per impostarne uno.</p> : (
            <div className="space-y-3">
              {expenseRows.map((r) => {
                const pct = r.monthly_limit > 0 ? (r.spent / r.monthly_limit) * 100 : 0;
                return (
                  <div key={r.id}>
                    <div className="mb-1 flex justify-between text-sm"><span>{r.category_name}</span><span className="font-mono">{euro(r.spent)} / {euro(r.monthly_limit)}</span></div>
                    <ProgressBar pct={pct} color={pct > 100 ? '#f43f5e' : pct > 85 ? '#f59e0b' : '#4f46e5'} />
                  </div>
                );
              })}
            </div>
          )}
        </div>
      </div>
    </div>
  );
}
