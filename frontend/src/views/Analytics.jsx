import { useState, useEffect, useCallback } from 'react';
import { Chart } from 'react-chartjs-2';
import 'chart.js/auto';
import { useData } from '../context/DataContext';
import { apiGet, apiSend } from '../api';
import { euro, monthLabel, formatDate } from '../lib/format';
import StatCard from '../components/StatCard';
import ProgressBar from '../components/ProgressBar';
import Modal from '../components/Modal';
import Tabs from '../components/Tabs';

const FREQ_MAP = {
  settimanale: { frequency: 'settimanale', interval: 1 },
  mensile: { frequency: 'mensile', interval: 1 },
  trimestrale: { frequency: 'mensile', interval: 3 },
  semestrale: { frequency: 'mensile', interval: 6 },
  annuale: { frequency: 'annuale', interval: 1 },
};

const TABS = [
  { id: 'proiezione', label: 'Proiezione' },
  { id: 'montecarlo', label: 'Monte Carlo' },
  { id: 'ricorrenze', label: 'Ricorrenze rilevate' },
  { id: 'avvisi', label: 'Avvisi' },
];

export default function Analytics() {
  const { activeAccounts, expenseCategories, incomeCategories, signal, bump } = useData();
  const [tab, setTab] = useState('proiezione');
  const [historyMonths, setHistoryMonths] = useState(6);
  const [forecastMonths, setForecastMonths] = useState(6);
  const [data, setData] = useState(null);
  const [recModalOpen, setRecModalOpen] = useState(false);
  const [recForm, setRecForm] = useState(null);

  const load = useCallback(async () => {
    setData(await apiGet(`analytics.php?history_months=${historyMonths}&forecast_months=${forecastMonths}`));
  }, [historyMonths, forecastMonths]);

  useEffect(() => { load(); }, [load, signal]);

  function openRecurringPrefill(d) {
    const map = FREQ_MAP[d.frequency_detected] || { frequency: 'mensile', interval: 1 };
    setRecForm({
      type: 'uscita', amount: d.avg_amount, category_id: d.category_id,
      frequency: map.frequency, interval_count: map.interval, start_date: d.suggested_start_date,
      description: d.category_name, account_id: activeAccounts[0]?.id || '',
    });
    setRecModalOpen(true);
  }

  async function submitDetectedRecurring(e) {
    e.preventDefault();
    await apiSend('recurring.php', 'POST', {
      type: recForm.type, amount: parseFloat(recForm.amount), account_id: recForm.account_id,
      category_id: recForm.category_id, transfer_account_id: null, frequency: recForm.frequency,
      interval_count: recForm.interval_count, start_date: recForm.start_date, end_date: null, description: recForm.description,
    });
    setRecModalOpen(false);
    bump();
  }

  if (!data) return <div className="text-muted">Caricamento...</div>;

  const { averages, monthly_history, forecast, monte_carlo, current_month, category_alerts, detected_recurring, data_sufficient } = data;

  const historyLabels = monthly_history.map((m) => monthLabel(m.month));
  const forecastLabels = forecast.map((m) => monthLabel(m.month));
  const labels = [...historyLabels, ...forecastLabels];
  const historyNet = monthly_history.map((m) => m.entrate - m.uscite);
  const historyBars = [...historyNet, ...forecast.map(() => null)];
  const forecastBars = [...historyNet.map(() => null), ...forecast.map((m) => m.projected_net)];
  let cum = 0;
  const cumulativeLine = historyNet.map((v) => (cum += v));
  const cumulativeForecastLine = forecast.map((m) => m.cumulative_savings + cum);
  const fullCumulative = [...cumulativeLine, ...cumulativeForecastLine];

  const netCls = averages.avg_net >= 0 ? 'text-income' : 'text-expense';
  const currentNetCls = current_month.projected_net >= 0 ? 'text-income' : 'text-expense';
  const discPct = current_month.discretionary_budget > 0 ? Math.min(100, Math.round((1 - current_month.discretionary_remaining / current_month.discretionary_budget) * 100)) : 0;
  const activeAlerts = category_alerts.filter((a) => a.severity !== 'ok');

  const tabCounts = {
    ricorrenze: detected_recurring.length,
    avvisi: activeAlerts.length,
  };

  return (
    <div>
      <div className="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <h4 className="text-xl font-bold">Analisi predittiva</h4>
        <div className="flex flex-wrap gap-2">
          <select className="field w-auto" value={historyMonths} onChange={(e) => setHistoryMonths(Number(e.target.value))}>
            <option value={6}>Storico: 6 mesi</option><option value={12}>Storico: 12 mesi</option>
          </select>
          <select className="field w-auto" value={forecastMonths} onChange={(e) => setForecastMonths(Number(e.target.value))}>
            <option value={3}>Proietta: 3 mesi</option><option value={6}>Proietta: 6 mesi</option><option value={12}>Proietta: 12 mesi</option>
          </select>
        </div>
      </div>

      <div className="mb-4 rounded-xl border border-accent/20 bg-accentSoft px-4 py-3 text-sm text-gray-700">
        Qui l'app guarda ai tuoi numeri passati per stimare il futuro: quanto probabilmente
        risparmierai nei prossimi mesi, se stai per sforare un budget, e se paghi abbonamenti che
        magari non ricordavi nemmeno di aver dichiarato come ricorrenti.
      </div>

      {!data_sufficient && (
        <div className="mb-4 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
          Hai meno di 3 mesi di storico: le medie e le proiezioni sono calcolate comunque, ma saranno poco affidabili finché non accumuli più dati.
        </div>
      )}

      <div className="mb-4 grid grid-cols-2 gap-3 md:grid-cols-4">
        <StatCard label="Reddito medio mensile" value={euro(averages.avg_income)} />
        <StatCard label="Spesa media mensile" value={euro(averages.avg_expense)} />
        <StatCard label="Risparmio medio mensile" value={euro(averages.avg_net)} valueClass={netCls} />
        <StatCard label="Tasso di risparmio" value={`${averages.savings_rate_pct}%`} />
        <div className="col-span-full text-xs text-muted">
          Di cui fissa/ricorrente: {euro(averages.avg_recurring_income)} entrate, {euro(averages.avg_recurring_expense)} uscite.
          Il resto ({euro(averages.avg_variable_income)} entrate, {euro(averages.avg_variable_expense)} uscite) è variabile, stimato dalla media storica.
        </div>
      </div>

      <Tabs tabs={TABS.map((t) => ({ ...t, count: tabCounts[t.id] }))} active={tab} onChange={setTab} />

      {tab === 'proiezione' && (
        <div className="grid grid-cols-1 gap-4 lg:grid-cols-3">
          <div className="card p-5 lg:col-span-2">
            <h6 className="mb-3 text-sm font-semibold">Storico e proiezione risparmio mensile</h6>
            <div className="h-64 sm:h-80">
              <Chart
                type="bar"
                data={{
                  labels,
                  datasets: [
                    { type: 'bar', label: 'Risparmio mensile (storico)', data: historyBars, backgroundColor: historyNet.map((v) => v >= 0 ? '#10b981' : '#f43f5e'), borderRadius: 4 },
                    { type: 'bar', label: 'Risparmio proiettato', data: forecastBars, backgroundColor: 'rgba(79,70,229,0.35)', borderRadius: 4 },
                    { type: 'line', label: 'Risparmio cumulato', data: fullCumulative, borderColor: '#4f46e5', yAxisID: 'y1', tension: 0.2 },
                  ],
                }}
                options={{ responsive: true, maintainAspectRatio: false, interaction: { mode: 'index', intersect: false }, scales: { x: { grid: { display: false } }, y: { title: { display: true, text: 'Risparmio mensile (€)' } }, y1: { position: 'right', grid: { display: false }, title: { display: true, text: 'Cumulato (€)' } } } }}
              />
            </div>
          </div>
          <div className="card p-5">
            <h6 className="mb-3 text-sm font-semibold">Questo mese</h6>
            <div className="space-y-2.5">
              <StatCard label="Entrate finora → proiettate" value={`${euro(current_month.income_so_far)} → ${euro(current_month.projected_income)}`} />
              <StatCard label="Uscite finora → proiettate" value={`${euro(current_month.expense_so_far)} → ${euro(current_month.projected_expense)}`} />
              <StatCard label="Risparmio netto proiettato" value={euro(current_month.projected_net)} valueClass={currentNetCls} />
              <div className="card p-4">
                <div className="text-[11px] font-medium uppercase tracking-wide text-muted">Margine discrezionale residuo</div>
                <div className="font-mono text-base font-semibold">{euro(current_month.discretionary_remaining)}</div>
                <div className="mt-2"><ProgressBar pct={discPct} color={discPct >= 100 ? '#f43f5e' : '#4f46e5'} /></div>
                <div className="mt-1.5 text-xs text-muted">Quanto puoi ancora spendere in spese variabili questo mese senza superare la tua media storica ({euro(current_month.discretionary_budget)}/mese)</div>
              </div>
            </div>
          </div>
        </div>
      )}

      {tab === 'montecarlo' && (
        <div className="card p-5">
          <h6 className="mb-1 text-sm font-semibold">Proiezione probabilistica (simulazione Monte Carlo)</h6>
          <p className="mb-3 text-sm text-muted">Invece di una singola cifra, migliaia di scenari campionati dalla tua reale variabilità storica. La fascia mostra dove cadrà il risparmio cumulato nell'80% degli scenari simulati.</p>
          {monte_carlo ? (
            <>
              <div className="mb-3">
                <span className={`text-sm font-semibold ${monte_carlo.probability_final_positive_pct >= 70 ? 'text-income' : (monte_carlo.probability_final_positive_pct >= 40 ? '' : 'text-expense')}`}>
                  Probabilità di risparmio cumulato positivo a fine periodo: {monte_carlo.probability_final_positive_pct}%
                </span>
                <span className="ml-2 text-xs text-muted">(su {monte_carlo.simulations.toLocaleString('it-IT')} scenari simulati)</span>
              </div>
              <div className="h-64 sm:h-80">
                <Chart
                  type="line"
                  data={{
                    labels: monte_carlo.percentile_bands.map((b) => monthLabel(b.month)),
                    datasets: [
                      { label: 'Scenario ottimistico (90°)', data: monte_carlo.percentile_bands.map((b) => b.p90), borderColor: 'rgba(16,185,129,0.4)', backgroundColor: 'rgba(16,185,129,0.12)', fill: '+1', tension: 0.2, pointRadius: 0 },
                      { label: 'Scenario mediano', data: monte_carlo.percentile_bands.map((b) => b.p50), borderColor: '#4f46e5', tension: 0.2, pointRadius: 2 },
                      { label: 'Scenario prudente (10°)', data: monte_carlo.percentile_bands.map((b) => b.p10), borderColor: 'rgba(244,63,94,0.4)', backgroundColor: 'rgba(244,63,94,0.08)', tension: 0.2, pointRadius: 0 },
                    ],
                  }}
                  options={{ responsive: true, maintainAspectRatio: false, scales: { x: { grid: { display: false } }, y: { title: { display: true, text: 'Risparmio cumulato (€)' } } } }}
                />
              </div>
            </>
          ) : <span className="text-sm text-muted">Servono almeno 3 mesi di storico per la simulazione probabilistica.</span>}
        </div>
      )}

      {tab === 'ricorrenze' && (
        <div className="card p-5">
          <h6 className="mb-1 text-sm font-semibold">Spese ricorrenti rilevate automaticamente</h6>
          <p className="mb-3 text-sm text-muted">Pattern di spesa regolari trovati nello storico, mai registrati come ricorrenza.</p>
          {detected_recurring.length ? detected_recurring.map((d, idx) => (
            <div key={idx} className={`flex items-center justify-between py-2.5 ${idx > 0 ? 'border-t border-border' : ''}`}>
              <div>
                <span className="mr-2 inline-block h-2 w-2 rounded-full align-middle" style={{ background: d.color }} />
                <strong>{d.category_name}</strong> — {euro(d.avg_amount)} circa ogni {d.frequency_detected}
                <div className="text-xs text-muted">{d.occurrences} occorrenze rilevate · regolarità {d.regularity_pct}% · ultima il {formatDate(d.last_date)}</div>
              </div>
              <button className="btn-outline text-xs" onClick={() => openRecurringPrefill(d)}>Registra come ricorrenza</button>
            </div>
          )) : <span className="text-sm text-muted">Nessun pattern ricorrente non dichiarato trovato nello storico.</span>}
        </div>
      )}

      {tab === 'avvisi' && (
        <div className="card p-5">
          <h6 className="mb-3 text-sm font-semibold">Avvisi di spesa</h6>
          {category_alerts.length ? category_alerts.map((a) => {
            const barColor = a.severity === 'danger' ? '#f43f5e' : (a.severity === 'warning' ? '#f59e0b' : '#10b981');
            return (
              <div key={a.category_id} className="mb-3 last:mb-0">
                <div className="mb-1 flex justify-between text-sm">
                  <span className="font-medium">{a.severity !== 'ok' && '⚠ '}{a.category_name} <span className="text-xs text-muted">(rif: {a.reference_type === 'budget' ? 'budget' : 'media storica'})</span></span>
                  <span className={`font-mono ${a.severity !== 'ok' ? 'font-semibold text-expense' : 'text-muted'}`}>{euro(a.projected_month_end)} / {euro(a.reference)} ({a.pct}%)</span>
                </div>
                <ProgressBar pct={Math.min(a.pct, 100)} color={barColor} />
              </div>
            );
          }) : <span className="text-sm text-muted">Nessuna categoria sta sforando il budget o la media storica questo mese.</span>}
        </div>
      )}

      {recForm && (
        <Modal open={recModalOpen} onClose={() => setRecModalOpen(false)} title="Registra come ricorrenza"
          footer={<>
            <button type="button" className="btn-outline" onClick={() => setRecModalOpen(false)}>Annulla</button>
            <button type="submit" form="detected-rec-form" className="btn-accent">Salva</button>
          </>}
        >
          <form id="detected-rec-form" onSubmit={submitDetectedRecurring} className="space-y-3">
            <div className="grid grid-cols-2 gap-3">
              <div>
                <label className="field-label">Importo (€)</label>
                <input className="field" type="number" step="0.01" value={recForm.amount} onChange={(e) => setRecForm((f) => ({ ...f, amount: e.target.value }))} />
              </div>
              <div>
                <label className="field-label">Conto</label>
                <select className="field" value={recForm.account_id} onChange={(e) => setRecForm((f) => ({ ...f, account_id: e.target.value }))}>
                  {activeAccounts.map((a) => <option key={a.id} value={a.id}>{a.name}</option>)}
                </select>
              </div>
            </div>
            <div className="grid grid-cols-2 gap-3">
              <div>
                <label className="field-label">Frequenza</label>
                <select className="field" value={recForm.frequency} onChange={(e) => setRecForm((f) => ({ ...f, frequency: e.target.value }))}>
                  <option value="mensile">Mensile</option><option value="settimanale">Settimanale</option><option value="annuale">Annuale</option>
                </select>
              </div>
              <div>
                <label className="field-label">Ogni</label>
                <input className="field" type="number" min="1" value={recForm.interval_count} onChange={(e) => setRecForm((f) => ({ ...f, interval_count: e.target.value }))} />
              </div>
            </div>
            <div>
              <label className="field-label">Data inizio</label>
              <input className="field" type="date" value={recForm.start_date} onChange={(e) => setRecForm((f) => ({ ...f, start_date: e.target.value }))} />
            </div>
            <div>
              <label className="field-label">Descrizione</label>
              <input className="field" value={recForm.description} onChange={(e) => setRecForm((f) => ({ ...f, description: e.target.value }))} />
            </div>
          </form>
        </Modal>
      )}
    </div>
  );
}
