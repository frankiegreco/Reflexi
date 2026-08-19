import { useState, useEffect, useCallback } from 'react';
import { Line, Bar, Doughnut } from 'react-chartjs-2';
import { apiGet } from '../api';
import { euro, formatDate } from '../lib/format';

export default function Report() {
  const [months, setMonths] = useState(12);
  const [netWorthData, setNetWorthData] = useState([]);
  const [incomeExpenseData, setIncomeExpenseData] = useState([]);
  const [categoryData, setCategoryData] = useState([]);
  const [payeeData, setPayeeData] = useState([]);

  const load = useCallback(async () => {
    const [nw, ie, cat, payee] = await Promise.all([
      apiGet(`reports.php?type=net_worth_trend&months=${months}`),
      apiGet(`reports.php?type=income_expense&months=${months}`),
      apiGet(`reports.php?type=spending_by_category&months=${months}`),
      apiGet(`reports.php?type=spending_by_payee&months=${months}`),
    ]);
    setNetWorthData(nw); setIncomeExpenseData(ie); setCategoryData(cat); setPayeeData(payee);
  }, [months]);
  useEffect(() => { load(); }, [load]);

  return (
    <div>
      <div className="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <h4 className="text-xl font-bold">Report</h4>
        <select className="field w-auto" value={months} onChange={(e) => setMonths(Number(e.target.value))}>
          <option value={3}>Ultimi 3 mesi</option>
          <option value={6}>Ultimi 6 mesi</option>
          <option value={12}>Ultimi 12 mesi</option>
          <option value={24}>Ultimi 24 mesi</option>
        </select>
      </div>

      <div className="grid grid-cols-1 gap-5 lg:grid-cols-2">

        <div className="card p-5">
          <h6 className="mb-3 text-sm font-semibold">Andamento patrimonio netto</h6>
          {netWorthData.length >= 2 ? (
            <Line
              data={{
                labels: netWorthData.map((d) => formatDate(d.date)),
                datasets: [{ label: 'Patrimonio netto', data: netWorthData.map((d) => d.net_worth), borderColor: '#4f46e5', backgroundColor: 'rgba(79,70,229,0.08)', fill: true, tension: 0.25 }],
              }}
              options={{ responsive: true, plugins: { legend: { display: false } }, scales: { y: { ticks: { callback: (v) => euro(v) } } } }}
            />
          ) : (
            <p className="py-8 text-center text-sm text-muted">Serve qualche giorno in più di utilizzo dell'app per costruire uno storico — ogni visita a questa pagina salva uno snapshot di oggi.</p>
          )}
        </div>

        <div className="card p-5">
          <h6 className="mb-3 text-sm font-semibold">Entrate vs Uscite</h6>
          {incomeExpenseData.length > 0 ? (
            <Bar
              data={{
                labels: incomeExpenseData.map((d) => d.ym),
                datasets: [
                  { label: 'Entrate', data: incomeExpenseData.map((d) => d.income), backgroundColor: '#10b981' },
                  { label: 'Uscite', data: incomeExpenseData.map((d) => d.expense), backgroundColor: '#f43f5e' },
                ],
              }}
              options={{ responsive: true, scales: { y: { ticks: { callback: (v) => euro(v) } } } }}
            />
          ) : <p className="py-8 text-center text-sm text-muted">Nessun dato disponibile</p>}
        </div>

        <div className="card p-5">
          <h6 className="mb-3 text-sm font-semibold">Spesa per categoria</h6>
          {categoryData.length > 0 ? (
            <div className="flex flex-col items-center gap-4 sm:flex-row">
              <div className="h-48 w-48 flex-shrink-0">
                <Doughnut
                  data={{ labels: categoryData.map((c) => c.name), datasets: [{ data: categoryData.map((c) => c.total), backgroundColor: categoryData.map((c) => c.color) }] }}
                  options={{ plugins: { legend: { display: false } } }}
                />
              </div>
              <div className="w-full space-y-1.5 text-sm">
                {categoryData.slice(0, 8).map((c) => (
                  <div key={c.name} className="flex items-center justify-between">
                    <span className="flex items-center gap-1.5"><span className="h-2.5 w-2.5 rounded-full" style={{ background: c.color }} />{c.name}</span>
                    <span className="font-mono">{euro(c.total)}</span>
                  </div>
                ))}
              </div>
            </div>
          ) : <p className="py-8 text-center text-sm text-muted">Nessuna spesa nel periodo</p>}
        </div>

        <div className="card p-5">
          <h6 className="mb-3 text-sm font-semibold">Spesa per esercente</h6>
          {payeeData.length > 0 ? (
            <div className="space-y-2">
              {payeeData.map((p) => (
                <div key={p.payee} className="flex items-center justify-between border-b border-border pb-1.5 text-sm last:border-0">
                  <span>{p.payee} <span className="text-xs text-muted">× {p.occurrences}</span></span>
                  <span className="font-mono font-semibold">{euro(p.total)}</span>
                </div>
              ))}
            </div>
          ) : <p className="py-8 text-center text-sm text-muted">Nessuna spesa nel periodo</p>}
        </div>

      </div>
    </div>
  );
}
