import { useState, useEffect, useCallback } from 'react';
import { Flame } from 'lucide-react';
import { apiGet, apiSend } from '../api';
import { euro } from '../lib/format';
import StatCard from '../components/StatCard';
import ProgressBar from '../components/ProgressBar';

export default function Fire() {
  const [data, setData] = useState(null);
  const [rate, setRate] = useState(4);
  const [returnRate, setReturnRate] = useState(6);

  const load = useCallback(async () => {
    const res = await apiGet('fire.php');
    setData(res);
    setRate(res.withdrawal_rate);
    setReturnRate(res.expected_return);
  }, []);
  useEffect(() => { load(); }, [load]);

  async function updateAssumptions(newRate, newReturn) {
    const res = await apiSend('fire.php', 'PUT', { fire_withdrawal_rate: newRate, fire_expected_return: newReturn });
    setData(res);
  }

  if (!data) return <div className="text-muted">Caricamento...</div>;

  const hasEnoughData = data.months_with_data >= 3;

  return (
    <div>
      <div className="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <h4 className="text-xl font-bold">FIRE — Indipendenza finanziaria</h4>
      </div>

      <div className="mb-4 rounded-xl border border-accent/20 bg-accentSoft px-4 py-3 text-sm text-gray-700">
        FIRE (Financial Independence, Retire Early) è il traguardo in cui il tuo capitale investito genera
        abbastanza per coprire le tue spese, senza dover più lavorare per necessità. Il "numero FIRE" è calcolato
        sulla regola classica: la tua spesa annua reale moltiplicata per 25 (equivalente a un prelievo del 4%
        l'anno) — puoi cambiare questa assunzione qui sotto.
      </div>

      {!hasEnoughData && (
        <div className="mb-4 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
          Hai ancora poco storico di transazioni ({data.months_with_data} {data.months_with_data === 1 ? 'mese' : 'mesi'}) — questi numeri sono solo indicativi finché non accumuli qualche mese in più di dati reali.
        </div>
      )}

      <div className="card mb-4 p-6 text-center">
        <div className="mb-1 flex items-center justify-center gap-2 text-sm font-semibold uppercase tracking-wide text-accent">
          <Flame size={16} /> Il tuo numero FIRE
        </div>
        <div className="font-mono text-4xl font-bold text-accent">{euro(data.fire_number)}</div>
        <p className="mt-1 text-xs text-muted">Spesa annua reale ({euro(data.annual_expense)}) × {(100 / data.withdrawal_rate).toFixed(1)}</p>

        <div className="mx-auto mt-5 max-w-md">
          <div className="mb-1 flex justify-between text-sm font-semibold">
            <span>{euro(data.investable_assets)}</span>
            <span>{data.progress_pct}%</span>
          </div>
          <ProgressBar pct={data.progress_pct} color="#4f46e5" height={12} />
          <p className="mt-2 text-xs text-muted">Capitale investibile attuale (liquidità + investimenti) rispetto al numero FIRE</p>
        </div>
      </div>

      <div className="mb-4 grid grid-cols-1 gap-3 sm:grid-cols-3">
        <StatCard label="Risparmio mensile medio" value={euro(data.monthly_savings)} valueClass="text-income" />
        <StatCard
          label="Anni stimati al traguardo"
          value={data.years_to_fire === 0 ? 'Già raggiunto! 🎉' : data.years_to_fire === null ? 'Non raggiungibile a questo ritmo' : `${data.years_to_fire} anni`}
        />
        <StatCard label="Spesa mensile media" value={euro(data.monthly_expense)} />
      </div>

      <div className="card p-5">
        <h6 className="mb-3 text-sm font-semibold">Assunzioni della proiezione</h6>
        <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
          <div>
            <label className="field-label">Tasso di prelievo sicuro (%)</label>
            <input
              className="field" type="number" step="0.1" min="1" max="10" value={rate}
              onChange={(e) => setRate(e.target.value)}
              onBlur={() => updateAssumptions(parseFloat(rate), parseFloat(returnRate))}
            />
            <p className="mt-1 text-xs text-muted">4% è la regola classica (Trinity Study). Un tasso più prudente (es. 3,5%) alza il numero FIRE richiesto.</p>
          </div>
          <div>
            <label className="field-label">Rendimento annuo atteso sugli investimenti (%)</label>
            <input
              className="field" type="number" step="0.1" min="0" max="20" value={returnRate}
              onChange={(e) => setReturnRate(e.target.value)}
              onBlur={() => updateAssumptions(parseFloat(rate), parseFloat(returnRate))}
            />
            <p className="mt-1 text-xs text-muted">Usato solo per proiettare gli anni mancanti, non per il numero FIRE stesso.</p>
          </div>
        </div>
        <p className="mt-4 text-xs text-muted">
          Questa è una stima semplificata basata sul tuo storico recente — non tiene conto di inflazione,
          cambi di reddito futuri, o della sequenza reale dei rendimenti di mercato. Non sostituisce una
          consulenza finanziaria o previdenziale professionale.
        </p>
      </div>
    </div>
  );
}
