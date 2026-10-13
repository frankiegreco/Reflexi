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

function addMonths(iso, n) {
  const [y, m, d] = iso.split('-').map(Number);
  const dt = new Date(Date.UTC(y, m - 1 + n, 1));
  const last = new Date(Date.UTC(dt.getUTCFullYear(), dt.getUTCMonth() + 1, 0)).getUTCDate();
  dt.setUTCDate(Math.min(d, last));
  return dt.toISOString().slice(0, 10);
}

/**
 * Versamenti volontari per ciascuna rata futura, con la stessa regola del
 * backend (simulate_amortization): un versamento riduce il capitale dalla sua
 * data, quindi si somma alla rata con data <= della sua. Quelli precedenti alla
 * prima rata futura (indice -1) riducono subito il residuo di partenza.
 * I periodici partono da start e si ripetono ogni N mesi, fino a end se indicata.
 */
function prepaymentsByRow(rows, { recurring, oneOff }) {
  const extra = new Array(rows.length).fill(0);
  let initial = 0;
  const add = (date, amount) => {
    let k = -1;
    while (k + 1 < rows.length && rows[k + 1].date <= date) k++;
    if (k < 0) initial += amount;
    else extra[k] += amount;
  };

  for (const p of oneOff) {
    const amount = parseFloat(p.amount);
    if (p.date && amount > 0) add(p.date, amount);
  }

  const amount = parseFloat(recurring.amount);
  if (recurring.enabled && amount > 0 && recurring.start && rows.length) {
    const step = FREQ_MONTHS[recurring.frequency] || 12;
    const lastDate = rows[rows.length - 1].date;
    for (let i = 0; ; i++) {
      const date = addMonths(recurring.start, i * step);
      if (date > lastDate || (recurring.end && date > recurring.end)) break;
      add(date, amount);
    }
  }
  return { initial, extra };
}

/**
 * Proietta il piano dalle rate future di $schedule (data > today), partendo dal
 * residuo e dalla rata del piano del backend (che comprendono gia' i
 * versamenti registrati).
 * mode = 'durata': la rata resta uguale e il mutuo finisce prima.
 * mode = 'rata':   la scadenza resta uguale e la rata viene ricalcolata in calo.
 * Un cambio di tasso ricalcola la rata sui mesi residui (in modalita' durata,
 * su quelli gia' accorciati), come fanno le banche.
 */
function project(schedule, today, { initial, extra }, mode) {
  const past = schedule.filter((r) => r.date <= today);
  const rows = schedule.slice(past.length);
  if (!rows.length) return [];
  let balance = past.length ? past[past.length - 1].balance_after : rows[0].balance_after + rows[0].principal_portion + (rows[0].extra || 0);

  let remaining = rows.length;
  let rate = rows[0].rate_used;
  let payment = rows[0].payment;
  const out = [];

  const applyExtra = (amount) => {
    const e = Math.min(amount, balance);
    balance -= e;
    if (balance < 0.005) balance = 0;
    else if (e > 0) {
      if (mode === 'rata') payment = pmt(balance, rate, remaining);
      else remaining = Math.min(remaining, nper(balance, rate, payment));
    }
    return e;
  };
  const initialApplied = applyExtra(initial);

  for (let k = 0; k < rows.length && remaining > 0 && balance > 0.005; k++) {
    const r = rows[k];
    if (r.rate_used !== rate) {
      rate = r.rate_used;
      payment = pmt(balance, rate, remaining);
    }
    const interest = balance * rate / 100 / 12;
    const principal = remaining === 1 ? balance : Math.min(balance, payment - interest);
    balance -= principal;
    remaining -= 1;
    const e = applyExtra(extra[k] || 0) + (k === 0 ? initialApplied : 0);

    out.push({
      date: r.date,
      rate_used: rate,
      payment: principal + interest,
      principal_portion: principal,
      interest_portion: interest,
      extra: e,
      balance_after: balance,
    });
  }
  return out;
}

/**
 * Detrazione IRPEF sugli interessi passivi (mutuo abitazione principale): pct%
 * degli interessi pagati in ciascun anno solare, fino a cap euro di interessi
 * l'anno per mutuo (19% su max 4.000 euro). Pagando meno interessi si perde
 * parte della detrazione: il risparmio reale e' la differenza al netto di questa.
 * Gli interessi gia' pagati nell'anno in corso contano per il tetto annuo.
 */
function taxByYear(base, sim, paidThisYear, thisYear, { pct, cap }) {
  const years = {};
  const add = (rows, key) => rows.forEach((r) => {
    const y = r.date.slice(0, 4);
    years[y] ??= { year: y, interestBase: 0, interestSim: 0 };
    years[y][key] += r.interest_portion;
  });
  add(base, 'interestBase');
  add(sim, 'interestSim');
  const rate = (parseFloat(pct) || 0) / 100;
  const limit = parseFloat(cap) > 0 ? parseFloat(cap) : Infinity;
  return Object.values(years).sort((a, b) => a.year.localeCompare(b.year)).map((y) => {
    const already = y.year === thisYear ? paidThisYear : 0;
    // Solo la parte di tetto non ancora usata dagli interessi gia' pagati quest'anno.
    const deductible = (i) => Math.max(0, Math.min(i + already, limit) - Math.min(already, limit));
    const deductionBase = deductible(y.interestBase) * rate;
    const deductionSim = deductible(y.interestSim) * rate;
    return { ...y, deductionBase, deductionSim, deductionLost: deductionBase - deductionSim };
  });
}

export function simulatePrepayment(schedule, today, plan, mode, penaltyPct = 0, tax = null) {
  const future = schedule.filter((r) => r.date > today);
  const base = project(schedule, today, { initial: 0, extra: [] }, mode);
  const prep = prepaymentsByRow(future, plan);
  const sim = project(schedule, today, prep, mode);

  const sum = (rows, key) => rows.reduce((s, r) => s + r[key], 0);
  const totalExtra = sum(sim, 'extra');
  const interestBase = sum(base, 'interest_portion');
  const interestSim = sum(sim, 'interest_portion');
  const penalty = totalExtra * (parseFloat(penaltyPct) || 0) / 100;
  const thisYear = today.slice(0, 4);
  const paidThisYear = schedule.filter((r) => r.date <= today && r.date.startsWith(thisYear)).reduce((s, r) => s + r.interest_portion, 0);
  const years = taxByYear(base, sim, paidThisYear, thisYear, tax && tax.enabled ? tax : { pct: 0, cap: 0 });
  const deductionLost = years.reduce((s, y) => s + y.deductionLost, 0);

  // Rata dopo il primo versamento: se cade prima della prima rata futura, e' gia' la prima.
  const firstWithExtra = sim.findIndex((r) => r.extra > 0);
  const nextRow = firstWithExtra < 0 ? null : (prep.initial > 0 ? sim[0] : sim[firstWithExtra + 1]);

  return {
    base,
    sim,
    totalExtra,
    interestBase,
    interestSim,
    interestSaved: interestBase - interestSim,
    penalty,
    years,
    deductionBase: years.reduce((s, y) => s + y.deductionBase, 0),
    deductionSim: years.reduce((s, y) => s + y.deductionSim, 0),
    deductionLost,
    netSaving: interestBase - interestSim - deductionLost - penalty,
    monthsBase: base.length,
    monthsSim: sim.length,
    endBase: base.length ? base[base.length - 1].date : null,
    endSim: sim.length ? sim[sim.length - 1].date : null,
    paymentNow: base.length ? base[0].payment : 0,
    paymentAfterFirst: nextRow ? nextRow.payment : null,
    paymentLast: sim.length ? sim[sim.length - 1].payment : 0,
  };
}
