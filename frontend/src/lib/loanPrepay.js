// Simulazione di rientro anticipato (estinzioni parziali) di un mutuo alla
// francese. Lavora sul piano gia' calcolato dal backend (loans.php?action=schedule),
// cosi' rispetta il tasso in vigore mese per mese, comprese le variazioni future
// gia' inserite per un mutuo variabile.

/** Rata costante per $n mesi residui. */
export function pmt(balance, ratePct, n) {
  const i = ratePct / 100 / 12;
  if (n <= 0) return balance;
  if (i === 0) return balance / n;
  return balance * i / (1 - (1 + i) ** -n);
}

/** Numero di rate (intero, per eccesso) necessarie a estinguere $balance con rata $payment. */
function nper(balance, ratePct, payment) {
  const i = ratePct / 100 / 12;
  if (balance <= 0) return 0;
  if (i === 0) return Math.ceil(balance / payment - 1e-9);
  const x = 1 - (balance * i) / payment;
  if (x <= 0) return Infinity;
  return Math.ceil(-Math.log(x) / Math.log(1 + i) - 1e-9);
}

const FREQ_MONTHS = { mensile: 1, trimestrale: 3, semestrale: 6, annuale: 12 };
export const FREQUENCIES = Object.keys(FREQ_MONTHS);

/**
 * Versamenti volontari per ciascuna rata futura: ognuno viene abbinato alla
 * prima rata con data >= della data del versamento (la banca lo imputa li').
 * I versamenti periodici partono dalla prima rata >= start e si ripetono ogni
 * N rate, fino a end (inclusa) se indicata.
 */
function prepaymentsByRow(rows, { recurring, oneOff }) {
  const extra = new Array(rows.length).fill(0);
  const rowFor = (date) => rows.findIndex((r) => r.date >= date);

  for (const p of oneOff) {
    const amount = parseFloat(p.amount);
    if (!p.date || !(amount > 0)) continue;
    const k = rowFor(p.date);
    if (k >= 0) extra[k] += amount;
  }

  const amount = parseFloat(recurring.amount);
  if (recurring.enabled && amount > 0 && recurring.start) {
    const step = FREQ_MONTHS[recurring.frequency] || 12;
    const first = rowFor(recurring.start);
    if (first >= 0) {
      for (let k = first; k < rows.length; k += step) {
        if (recurring.end && rows[k].date > recurring.end) break;
        extra[k] += amount;
      }
    }
  }
  return extra;
}

/**
 * Proietta il piano dalle rate future di $schedule (data > today), partendo dal
 * residuo dell'ultima rata gia' scaduta.
 * mode = 'durata': la rata resta uguale e il mutuo finisce prima.
 * mode = 'rata':   la scadenza resta uguale e la rata viene ricalcolata in calo.
 * Un cambio di tasso ricalcola la rata sui mesi residui (in modalita' durata,
 * su quelli gia' accorciati), come fanno le banche.
 */
function project(schedule, today, extraByRow, mode) {
  const past = schedule.filter((r) => r.date <= today);
  const rows = schedule.slice(past.length);
  let balance = past.length ? past[past.length - 1].balance_after : null;
  if (balance === null) balance = rows.length ? rows[0].balance_after + rows[0].principal_portion : 0;

  let remaining = rows.length;
  let rate = rows.length ? rows[0].rate_used : 0;
  let payment = pmt(balance, rate, remaining);
  const out = [];

  for (let k = 0; k < rows.length && balance > 0.005; k++) {
    const r = rows[k];
    if (r.rate_used !== rate) {
      rate = r.rate_used;
      payment = pmt(balance, rate, remaining);
    }
    const interest = balance * rate / 100 / 12;
    const due = Math.min(payment, balance + interest);
    const principal = due - interest;
    balance -= principal;
    remaining -= 1;

    const extra = Math.min(extraByRow[k] || 0, Math.max(0, balance));
    balance -= extra;
    if (balance < 0.005) balance = 0;

    if (extra > 0 && balance > 0) {
      if (mode === 'rata') payment = pmt(balance, rate, remaining);
      else remaining = Math.min(remaining, nper(balance, rate, payment));
    }

    out.push({
      date: r.date,
      rate_used: rate,
      payment: due,
      principal_portion: principal,
      interest_portion: interest,
      extra,
      balance_after: balance,
    });
  }
  return out;
}

export function simulatePrepayment(schedule, today, plan, mode, penaltyPct = 0) {
  const future = schedule.filter((r) => r.date > today);
  const extra = prepaymentsByRow(future, plan);
  const base = project(schedule, today, new Array(future.length).fill(0), mode);
  const sim = project(schedule, today, extra, mode);

  const sum = (rows, key) => rows.reduce((s, r) => s + r[key], 0);
  const totalExtra = sum(sim, 'extra');
  const interestBase = sum(base, 'interest_portion');
  const interestSim = sum(sim, 'interest_portion');
  const penalty = totalExtra * (parseFloat(penaltyPct) || 0) / 100;
  const firstWithExtra = sim.findIndex((r) => r.extra > 0);
  const nextRow = firstWithExtra >= 0 ? sim[firstWithExtra + 1] : null;

  return {
    base,
    sim,
    totalExtra,
    interestBase,
    interestSim,
    interestSaved: interestBase - interestSim,
    penalty,
    netSaving: interestBase - interestSim - penalty,
    monthsBase: base.length,
    monthsSim: sim.length,
    endBase: base.length ? base[base.length - 1].date : null,
    endSim: sim.length ? sim[sim.length - 1].date : null,
    paymentNow: base.length ? base[0].payment : 0,
    paymentAfterFirst: nextRow ? nextRow.payment : null,
    paymentLast: sim.length ? sim[sim.length - 1].payment : 0,
  };
}
