import { useState, useEffect, useCallback } from 'react';
import { TrendingUp, PiggyBank } from 'lucide-react';
import { apiGet } from '../api';
import { euro } from '../lib/format';
import Modal from './Modal';

function formatMonths(months) {
  if (months === null || months === undefined) return 'Non raggiungibile in tempi ragionevoli';
  if (months <= 0) return 'Già raggiunto';
  if (months < 12) return `${months} mesi`;
  const years = Math.floor(months / 12);
  const rem = Math.round(months % 12);
  return rem > 0 ? `${years} anni e ${rem} mesi` : `${years} anni`;
}

export default function GoalPlan({ goal, onClose }) {
  const [data, setData] = useState(null);
  const [returnRate, setReturnRate] = useState(5);
  const [loading, setLoading] = useState(false);

  const load = useCallback(async (rate) => {
    if (!goal) return;
    setLoading(true);
    try {
      const res = await apiGet(`goal_plan.php?goal_id=${goal.id}&expected_return=${rate}`);
      setData(res);
    } finally {
      setLoading(false);
    }
  }, [goal]);

  useEffect(() => {
    if (goal) {
      setReturnRate(5);
      setData(null);
      load(5);
    }
  }, [goal, load]);

  if (!goal) return null;

  return (
    <Modal open={!!goal} onClose={onClose} title={`Piano per "${goal.name}"`} size="lg">
      {loading && !data && <div className="text-muted">Calcolo in corso...</div>}

      {data && data.reached && (
        <div className="rounded-xl border border-income/30 bg-income/10 px-4 py-6 text-center">
          <p className="text-lg font-semibold text-income">Obiettivo già raggiunto! 🎉</p>
          <p className="mt-1 text-sm text-muted">Non serve nessun piano — hai già messo da parte quanto ti eri prefissato.</p>
        </div>
      )}

      {data && !data.reached && (
        <div className="space-y-4">
          <div className="rounded-xl border border-accent/20 bg-accentSoft px-4 py-3 text-sm">
            Ti mancano <strong>{euro(data.remaining_amount)}</strong> per raggiungere l'obiettivo.
            Risparmio medio mensile attuale: <strong>{euro(data.avg_monthly_savings)}</strong>.
          </div>

          {data.low_data && (
            <div className="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
              Hai ancora poco storico di transazioni ({data.months_with_data} {data.months_with_data === 1 ? 'mese' : 'mesi'}) — questi numeri sono solo indicativi finché non accumuli qualche mese in più di dati reali.
            </div>
          )}

          {data.unreachable ? (
            <div className="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
              Al ritmo attuale l'obiettivo non è raggiungibile — spendi in media più di quanto risparmi.
              Prova ad esplorare le categorie qui sotto per trovare margine.
            </div>
          ) : (
            <div className="grid grid-cols-1 gap-3 sm:grid-cols-2">
              <div className="card p-4">
                <div className="mb-1 flex items-center gap-2 text-sm font-semibold uppercase tracking-wide text-muted">
                  <PiggyBank size={16} /> Solo risparmiando
                </div>
                <div className="font-mono text-2xl font-bold">{formatMonths(data.scenario_savings_only?.months_to_goal)}</div>
                <p className="mt-1 text-xs text-muted">Mettendo da parte {euro(data.avg_monthly_savings)} al mese, senza investire</p>
              </div>

              <div className="card p-4">
                <div className="mb-1 flex items-center gap-2 text-sm font-semibold uppercase tracking-wide text-accent">
                  <TrendingUp size={16} /> Investendo il risparmio
                </div>
                <div className="font-mono text-2xl font-bold text-accent">{formatMonths(data.scenario_invested?.months_to_goal)}</div>
                <div className="mt-2">
                  <label className="field-label">Rendimento annuo atteso (%)</label>
                  <input
                    className="field" type="number" step="0.1" min="0" max="20" value={returnRate}
                    onChange={(e) => setReturnRate(e.target.value)}
                    onBlur={() => load(parseFloat(returnRate) || 0)}
                  />
                  <p className="mt-1 text-xs text-muted">Ipotesi che puoi modificare — non un rendimento garantito.</p>
                </div>
              </div>
            </div>
          )}

          {data.category_opportunities && data.category_opportunities.length > 0 && (
            <div className="card p-4">
              <h6 className="mb-3 text-sm font-semibold">Dove potresti recuperare margine</h6>
              <div className="space-y-2">
                {data.category_opportunities.map((c) => (
                  <div key={c.category_name} className="flex items-center justify-between rounded-lg bg-gray-50 px-3 py-2 text-sm">
                    <span className="font-medium">{c.category_name}</span>
                    <span className="text-muted">
                      {euro(c.recent_avg)}/mese, {euro(c.baseline_avg)} di norma
                      <span className="ml-2 font-semibold text-expense">+{euro(c.potential_monthly_saving)}/mese</span>
                    </span>
                  </div>
                ))}
              </div>

              {data.scenario_with_adjustment && (
                <div className="mt-4 rounded-lg border border-income/30 bg-income/10 px-3 py-3">
                  <div className="text-sm font-semibold text-income">Con questo aggiustamento</div>
                  <p className="mt-1 text-xs text-muted">
                    Recuperando metà di quel margine ({euro(data.scenario_with_adjustment.extra_monthly)}/mese in più) e investendolo:
                  </p>
                  <div className="mt-1 font-mono text-xl font-bold text-income">{formatMonths(data.scenario_with_adjustment.months_to_goal)}</div>
                </div>
              )}
            </div>
          )}

          <p className="text-xs text-muted">
            Proiezione basata sul tuo storico recente, non una promessa — i mercati reali oscillano e le tue
            spese possono cambiare. Non sostituisce una consulenza finanziaria personalizzata.
          </p>
        </div>
      )}
    </Modal>
  );
}
