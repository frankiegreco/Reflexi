const API = 'api';

// ---------- Helpers ----------
const euro = (n) => new Intl.NumberFormat('it-IT', { style: 'currency', currency: 'EUR' }).format(n || 0);
const el = (id) => document.getElementById(id);
const bsModal = (id) => bootstrap.Modal.getOrCreateInstance(el(id));

async function apiGet(path) {
  const res = await fetch(`${API}/${path}`);
  if (!res.ok) throw new Error(await res.text());
  return res.json();
}
async function apiSend(path, method, body) {
  const res = await fetch(`${API}/${path}`, {
    method,
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(body),
  });
  if (!res.ok) {
    const err = await res.json().catch(() => ({ error: 'Errore sconosciuto' }));
    throw new Error(err.error || 'Errore sconosciuto');
  }
  return res.json();
}
async function apiDelete(path) {
  const res = await fetch(`${API}/${path}`, { method: 'DELETE' });
  if (!res.ok) throw new Error('Errore durante l\'eliminazione');
  return res.json();
}

function formatDate(d) {
  const [y, m, day] = d.split('-');
  return `${day}/${m}/${y}`;
}
function capitalize(s) { return s ? s.charAt(0).toUpperCase() + s.slice(1).replace('_', ' ') : ''; }
function debounce(fn, ms) {
  let t;
  return (...args) => { clearTimeout(t); t = setTimeout(() => fn(...args), ms); };
}

// ---------- State ----------
let state = { accounts: [], categories: [], recurring: [], loans: [], instruments: [] };
let charts = { monthly: null, categories: null, loan: null, allocation: null, analytics: null, montecarlo: null };
let currentLoanId = null;

// ---------- Navigation ----------
document.querySelectorAll('.nav-link[data-view]').forEach(btn => {
  btn.addEventListener('click', () => switchView(btn.dataset.view));
});

function switchView(view) {
  document.querySelectorAll('.nav-link[data-view]').forEach(b => b.classList.toggle('active', b.dataset.view === view));
  document.querySelectorAll('.view').forEach(v => v.classList.toggle('active', v.id === `view-${view}`));
  if (view === 'dashboard') loadDashboard();
  if (view === 'transactions') loadTransactions();
  if (view === 'accounts') renderAccountsGrid();
  if (view === 'categories') { renderCategories(); loadBudgets(); }
  if (view === 'recurring') loadRecurring();
  if (view === 'loans') loadLoans();
  if (view === 'investments') loadInvestmentsView();
  if (view === 'analytics') loadAnalytics();
}

el('btn-add-transaction').addEventListener('click', () => {
  resetTransactionForm();
  bsModal('modal-transaction').show();
});

// ---------- Load base data ----------
async function loadBaseData() {
  state.accounts = await apiGet('accounts.php');
  state.categories = await apiGet('categories.php');
  populateSelects();
}

function populateSelects() {
  const accOptions = state.accounts.map(a => `<option value="${a.id}">${a.name}</option>`).join('');
  ['t-account', 't-transfer-account', 'r-account', 'r-transfer-account', 'l-account', 'inv-account', 'filter-account'].forEach(id => {
    const sel = el(id);
    if (!sel) return;
    const keepFirst = id === 'filter-account' ? '<option value="">Tutti i conti</option>' : '';
    sel.innerHTML = keepFirst + accOptions;
  });

  syncCategoryOptions('t-type', 't-category');
  syncCategoryOptions('r-type', 'r-category');

  const lCat = el('l-category');
  if (lCat) {
    const mutuo = state.categories.find(c => c.name === 'Mutuo/Prestito' && c.type === 'uscita');
    lCat.innerHTML = state.categories.filter(c => c.type === 'uscita')
      .map(c => `<option value="${c.id}" ${mutuo && c.id === mutuo.id ? 'selected' : ''}>${c.name}</option>`).join('');
  }

  const filterCat = el('filter-category');
  if (filterCat) {
    filterCat.innerHTML = '<option value="">Tutte le categorie</option>' +
      state.categories.map(c => `<option value="${c.id}">${c.name} (${c.type})</option>`).join('');
  }
}

function syncCategoryOptions(typeSelectId, categorySelectId) {
  const typeSel = el(typeSelectId);
  const catSel = el(categorySelectId);
  if (!typeSel || !catSel) return;
  const type = typeSel.value === 'entrata' ? 'entrata' : 'uscita';
  catSel.innerHTML = state.categories.filter(c => c.type === type)
    .map(c => `<option value="${c.id}">${c.name}</option>`).join('');
}

['t-type', 'r-type'].forEach(id => {
  el(id).addEventListener('change', () => {
    const prefix = id.split('-')[0];
    const isTransfer = el(id).value === 'trasferimento';
    el(`${prefix}-transfer-wrap`).style.display = isTransfer ? 'block' : 'none';
    el(`${prefix}-category-wrap`).style.display = isTransfer ? 'none' : 'block';
    if (!isTransfer) syncCategoryOptions(id, `${prefix}-category`);
  });
});

// ---------- DASHBOARD ----------
async function loadDashboard() {
  const months = el('months-select').value;
  const period = el('category-period').value;
  let url = `dashboard.php?months=${months}`;
  if (period) {
    const [y, m] = period.split('-');
    const from = `${y}-${m}-01`;
    const to = new Date(y, m, 0).toISOString().slice(0, 10);
    url += `&date_from=${from}&date_to=${to}`;
  }
  const data = await apiGet(url);

  el('total-balance').textContent = euro(data.total_balance);
  el('today-stamp').textContent = new Date().toLocaleDateString('it-IT', { day: '2-digit', month: 'long', year: 'numeric' });

  el('accounts-strip').innerHTML = data.accounts.map(a => `
    <div class="account-chip" style="border-left-color:${a.color}">
      <div class="chip-name">${a.name}</div>
      <div class="chip-balance">${euro(a.balance)}</div>
    </div>
  `).join('') || '<div class="account-chip">Nessun conto attivo</div>';

  renderMonthlyChart(data.monthly);
  renderCategoryChart(data.category_breakdown);
  renderLoansSummary();
  renderBudgetsDashboard();
}

async function renderLoansSummary() {
  const loans = await apiGet('loans.php');
  const active = loans.filter(l => l.status === 'attivo');
  const row = el('loans-summary-row');
  if (!active.length) { row.innerHTML = ''; el('loans-progress-card').style.display = 'none'; return; }

  let totalResidual = 0, totalMonthly = 0, totalInterestRemaining = 0;
  active.forEach(l => {
    const c = l.calc;
    const pct = c.ownership_pct / 100;
    const personalPaidSoFar = c.elapsed_payments * c.personal_monthly_payment - (parseFloat(l.principal) * pct - c.personal_residual_today);
    totalResidual += c.personal_residual_today;
    totalMonthly += c.personal_monthly_payment;
    totalInterestRemaining += Math.max(0, c.personal_total_interest - personalPaidSoFar);
  });

  row.innerHTML = `
    <div class="col-md-4">
      <div class="stat-mini"><div class="stat-label">Debito residuo mutui (tua quota)</div><div class="stat-value">${euro(totalResidual)}</div></div>
    </div>
    <div class="col-md-4">
      <div class="stat-mini"><div class="stat-label">Rata mensile complessiva (tua quota)</div><div class="stat-value">${euro(totalMonthly)}</div></div>
    </div>
    <div class="col-md-4">
      <div class="stat-mini"><div class="stat-label">Interessi ancora da pagare (tua quota)</div><div class="stat-value">${euro(totalInterestRemaining)}</div></div>
    </div>
  `;

  renderLoansProgress(active);
}

function renderLoansProgress(activeLoans) {
  const withData = activeLoans.filter(l => l.actual.count !== null);
  const card = el('loans-progress-card');
  const list = el('loans-progress-list');

  if (!withData.length) { card.style.display = 'none'; return; }
  card.style.display = 'block';

  list.innerHTML = withData.map(l => {
    const a = l.actual;
    const c = l.calc;
    const pct = c.ownership_pct / 100;
    const capPct = Math.min(a.capital_pct_of_total, 100);
    const intPct = Math.min(a.interest_pct_of_total, 100);
    const suffix = pct < 1 ? ` (quota ${c.ownership_pct}%)` : '';
    return `
      <div class="budget-row">
        <div class="br-top"><span class="br-name">${l.name} — capitale restituito${suffix}</span><span class="br-figures">${euro(a.personal_capital_paid)} / ${euro(l.principal * pct)} (${a.capital_pct_of_total}%)</span></div>
        <div class="progress"><div class="progress-bar" style="width:${capPct}%; background:var(--accent)"></div></div>
      </div>
      <div class="budget-row">
        <div class="br-top"><span class="br-name">${l.name} — interessi pagati${suffix}</span><span class="br-figures">${euro(a.personal_interest_paid)} (${a.interest_pct_of_total}%)</span></div>
        <div class="progress"><div class="progress-bar" style="width:${intPct}%; background:var(--loan)"></div></div>
      </div>
    `;
  }).join('');
}

function renderMonthlyChart(monthly) {
  const ctx = el('chart-monthly');
  const labels = monthly.map(m => {
    const [y, mo] = m.month.split('-');
    return new Date(y, mo - 1, 1).toLocaleDateString('it-IT', { month: 'short', year: '2-digit' });
  });
  if (charts.monthly) charts.monthly.destroy();
  charts.monthly = new Chart(ctx, {
    type: 'bar',
    data: {
      labels,
      datasets: [
        { label: 'Entrate', data: monthly.map(m => m.entrate), backgroundColor: '#10b981', borderRadius: 4 },
        { label: 'Uscite', data: monthly.map(m => m.uscite), backgroundColor: '#f43f5e', borderRadius: 4 },
      ],
    },
    options: {
      responsive: true,
      plugins: { legend: { labels: { color: '#1b1d29' } } },
      scales: {
        x: { ticks: { color: '#6b7080' }, grid: { display: false } },
        y: { ticks: { color: '#6b7080' }, grid: { color: '#e7e9f0' } },
      },
    },
  });
}

function renderCategoryChart(breakdown) {
  const ctx = el('chart-categories');
  if (charts.categories) charts.categories.destroy();
  if (!breakdown.length) {
    ctx.getContext('2d').clearRect(0, 0, ctx.width, ctx.height);
    return;
  }
  charts.categories = new Chart(ctx, {
    type: 'doughnut',
    data: {
      labels: breakdown.map(c => c.name),
      datasets: [{ data: breakdown.map(c => c.total), backgroundColor: breakdown.map(c => c.color), borderColor: '#fff', borderWidth: 2 }],
    },
    options: {
      responsive: true,
      plugins: { legend: { position: 'bottom', labels: { color: '#1b1d29', boxWidth: 12, padding: 12 } } },
    },
  });
}

el('months-select').addEventListener('change', loadDashboard);
el('category-period').addEventListener('change', loadDashboard);

// ---------- TRANSACTIONS ----------
function resetTransactionForm() {
  el('form-transaction').reset();
  el('t-id').value = '';
  el('t-date').value = new Date().toISOString().slice(0, 10);
  el('transaction-modal-title').textContent = 'Nuovo movimento';
  el('t-transfer-wrap').style.display = 'none';
  el('t-category-wrap').style.display = 'block';
}

async function loadTransactions() {
  const params = new URLSearchParams();
  const map = {
    'filter-account': 'account_id', 'filter-category': 'category_id',
    'filter-type': 'type', 'filter-date-from': 'date_from',
    'filter-date-to': 'date_to', 'filter-search': 'search',
  };
  Object.entries(map).forEach(([elId, param]) => {
    const v = el(elId).value;
    if (v) params.set(param, v);
  });
  el('btn-export').href = `api/export.php?${params.toString()}`;

  const rows = await apiGet(`transactions.php?${params.toString()}`);
  el('transactions-body').innerHTML = rows.map(t => {
    const sign = t.type === 'entrata' ? '+' : (t.type === 'uscita' ? '-' : '');
    const cls = t.type === 'entrata' ? 'amount-income' : (t.type === 'uscita' ? 'amount-expense' : '');
    const catLabel = t.type === 'trasferimento' ? `→ ${t.transfer_account_name}` : t.category_name;
    return `
      <tr>
        <td>${formatDate(t.date)}</td>
        <td>${t.account_name}</td>
        <td>${catLabel}</td>
        <td>${t.description || ''}</td>
        <td class="text-end ${cls}">${sign} ${euro(t.amount)}</td>
        <td class="row-actions text-end">
          <button onclick="editTransaction(${t.id})"><i class="bi bi-pencil"></i></button>
          <button onclick="deleteTransaction(${t.id})"><i class="bi bi-trash"></i></button>
        </td>
      </tr>`;
  }).join('') || '<tr><td colspan="6" class="text-center text-muted py-4">Nessuna transazione</td></tr>';

  window._txCache = rows;
}

['filter-account', 'filter-category', 'filter-type', 'filter-date-from', 'filter-date-to'].forEach(id => {
  el(id).addEventListener('change', loadTransactions);
});
el('filter-search').addEventListener('input', debounce(loadTransactions, 300));

window.editTransaction = (id) => {
  const t = window._txCache.find(x => x.id === id);
  if (!t) return;
  el('t-id').value = t.id;
  el('t-type').value = t.type;
  el('t-type').dispatchEvent(new Event('change'));
  el('t-amount').value = t.amount;
  el('t-account').value = t.account_id;
  if (t.transfer_account_id) el('t-transfer-account').value = t.transfer_account_id;
  el('t-category').value = t.category_id;
  el('t-date').value = t.date;
  el('t-description').value = t.description || '';
  el('transaction-modal-title').textContent = 'Modifica movimento';
  bsModal('modal-transaction').show();
};

window.deleteTransaction = async (id) => {
  if (!confirm('Eliminare questa transazione?')) return;
  await apiDelete(`transactions.php?id=${id}`);
  loadTransactions();
  loadDashboard();
};

el('form-transaction').addEventListener('submit', async (e) => {
  e.preventDefault();
  const isTransfer = el('t-type').value === 'trasferimento';
  const payload = {
    type: el('t-type').value,
    amount: parseFloat(el('t-amount').value),
    account_id: el('t-account').value,
    category_id: isTransfer ? state.categories.find(c => c.type === 'uscita')?.id : el('t-category').value,
    transfer_account_id: isTransfer ? el('t-transfer-account').value : null,
    date: el('t-date').value,
    description: el('t-description').value,
  };
  const id = el('t-id').value;
  try {
    if (id) { payload.id = id; await apiSend('transactions.php', 'PUT', payload); }
    else { await apiSend('transactions.php', 'POST', payload); }
    bsModal('modal-transaction').hide();
    loadTransactions();
    loadDashboard();
    loadBaseData();
  } catch (err) { alert(err.message); }
});

// ---------- ACCOUNTS ----------
function resetAccountForm() {
  el('form-account').reset();
  el('a-id').value = '';
  el('a-color').value = '#4f46e5';
}

async function renderAccountsGrid() {
  state.accounts = await apiGet('accounts.php?include_archived=1');
  el('accounts-grid').innerHTML = state.accounts.map(a => `
    <div class="col-md-4 col-sm-6">
      <div class="account-card" style="border-left-color:${a.color}">
        <div class="ac-name">${a.name}${a.archived ? ' (archiviato)' : ''}</div>
        <div class="ac-type">${capitalize(a.type)}</div>
        <div class="ac-balance">${euro(a.balance)}</div>
        <div class="ac-actions">
          <button class="btn btn-sm btn-outline-secondary" onclick="editAccount(${a.id})">Modifica</button>
          <button class="btn btn-sm btn-outline-danger" onclick="deleteAccount(${a.id})">Elimina</button>
        </div>
      </div>
    </div>
  `).join('');
}

window.editAccount = (id) => {
  const a = state.accounts.find(x => x.id === id);
  if (!a) return;
  el('a-id').value = a.id;
  el('a-name').value = a.name;
  el('a-type').value = a.type;
  el('a-balance').value = a.initial_balance;
  el('a-color').value = a.color;
  bsModal('modal-account').show();
};

window.deleteAccount = async (id) => {
  if (!confirm('Eliminare questo conto e tutte le sue transazioni?')) return;
  await apiDelete(`accounts.php?id=${id}`);
  await loadBaseData();
  renderAccountsGrid();
  loadDashboard();
};

el('form-account').addEventListener('submit', async (e) => {
  e.preventDefault();
  const payload = {
    name: el('a-name').value,
    type: el('a-type').value,
    initial_balance: parseFloat(el('a-balance').value || '0'),
    color: el('a-color').value,
  };
  const id = el('a-id').value;
  try {
    if (id) { payload.id = id; await apiSend('accounts.php', 'PUT', payload); }
    else { await apiSend('accounts.php', 'POST', payload); }
    bsModal('modal-account').hide();
    resetAccountForm();
    await loadBaseData();
    renderAccountsGrid();
    loadDashboard();
  } catch (err) { alert(err.message); }
});

// ---------- CATEGORIES ----------
function renderCategories() {
  const render = (type, containerId) => {
    el(containerId).innerHTML = state.categories.filter(c => c.type === type).map(c => `
      <div class="tag">
        <span class="dot" style="background:${c.color}"></span>
        ${c.name}
        <button onclick="deleteCategory(${c.id})"><i class="bi bi-x"></i></button>
      </div>
    `).join('') || '<span class="text-muted">Nessuna categoria</span>';
  };
  render('entrata', 'categories-entrata');
  render('uscita', 'categories-uscita');
}

window.deleteCategory = async (id) => {
  if (!confirm('Eliminare questa categoria?')) return;
  try {
    await apiDelete(`categories.php?id=${id}`);
    await loadBaseData();
    renderCategories();
  } catch (err) { alert('Impossibile eliminare: probabilmente è usata da alcune transazioni.'); }
};

el('form-category').addEventListener('submit', async (e) => {
  e.preventDefault();
  const payload = { name: el('c-name').value, type: el('c-type').value, color: el('c-color').value };
  await apiSend('categories.php', 'POST', payload);
  bsModal('modal-category').hide();
  el('form-category').reset();
  await loadBaseData();
  renderCategories();
});

// ---------- RECURRING ----------
async function loadRecurring() {
  state.recurring = await apiGet('recurring.php');
  el('recurring-body').innerHTML = state.recurring.map(r => {
    const label = r.type === 'trasferimento' ? `→ ${r.transfer_account_name}` : r.category_name;
    const sign = r.type === 'entrata' ? '+' : (r.type === 'uscita' ? '-' : '');
    const cls = r.type === 'entrata' ? 'amount-income' : (r.type === 'uscita' ? 'amount-expense' : '');
    return `
      <tr>
        <td>${r.description || '—'}</td>
        <td>${r.account_name}</td>
        <td>${label}</td>
        <td>${capitalize(r.frequency)}${r.interval_count > 1 ? ` (ogni ${r.interval_count})` : ''}</td>
        <td>${formatDate(r.next_run)}</td>
        <td class="text-end ${cls}">${sign} ${euro(r.amount)}</td>
        <td class="row-actions text-end"><button onclick="deleteRecurring(${r.id})"><i class="bi bi-trash"></i></button></td>
      </tr>`;
  }).join('') || '<tr><td colspan="7" class="text-center text-muted py-4">Nessuna ricorrenza impostata</td></tr>';
}

window.deleteRecurring = async (id) => {
  if (!confirm('Eliminare questa ricorrenza? Le transazioni già generate non verranno cancellate.')) return;
  await apiDelete(`recurring.php?id=${id}`);
  loadRecurring();
};

el('form-recurring').addEventListener('submit', async (e) => {
  e.preventDefault();
  const isTransfer = el('r-type').value === 'trasferimento';
  const payload = {
    type: el('r-type').value,
    amount: parseFloat(el('r-amount').value),
    account_id: el('r-account').value,
    category_id: isTransfer ? state.categories.find(c => c.type === 'uscita')?.id : el('r-category').value,
    transfer_account_id: isTransfer ? el('r-transfer-account').value : null,
    frequency: el('r-frequency').value,
    interval_count: parseInt(el('r-interval').value || '1', 10),
    start_date: el('r-start').value,
    end_date: el('r-end').value || null,
    description: el('r-description').value,
  };
  try {
    await apiSend('recurring.php', 'POST', payload);
    bsModal('modal-recurring').hide();
    el('form-recurring').reset();
    loadRecurring();
  } catch (err) { alert(err.message); }
});

el('btn-process-recurring').addEventListener('click', async () => {
  const res = await apiSend('recurring.php?action=process', 'POST', {});
  alert(`${res.created} transazioni generate.`);
  loadRecurring();
  loadDashboard();
});

// ---------- LOANS (Mutui) ----------
function loanMonthlyPayment(principal, annualRatePct, years) {
  const i = annualRatePct / 100 / 12;
  const n = years * 12;
  if (n <= 0) return 0;
  if (i === 0) return principal / n;
  return principal * i / (1 - Math.pow(1 + i, -n));
}

function updateLoanPreview() {
  const principal = parseFloat(el('l-principal').value);
  const rate = parseFloat(el('l-rate').value);
  const years = parseInt(el('l-years').value, 10);
  const preview = el('l-preview');
  if (!principal || !years || isNaN(rate)) { preview.style.display = 'none'; return; }
  const rata = loanMonthlyPayment(principal, rate, years);
  const totalInterest = rata * years * 12 - principal;
  preview.style.display = 'block';
  preview.innerHTML = `Rata mensile stimata: <b>${euro(rata)}</b> &nbsp;·&nbsp; Interessi totali: <b>${euro(totalInterest)}</b> &nbsp;·&nbsp; Totale rimborsato: <b>${euro(rata * years * 12)}</b>`;
}
function resetLoanForm() {
  el('form-loan').reset();
  el('l-id').value = '';
  el('l-ownership').value = 100;
  el('l-preview').style.display = 'none';
  el('loan-modal-title').textContent = 'Nuovo mutuo';
  el('l-create-recurring').checked = true;
  el('l-create-recurring').closest('.form-check').style.display = 'block';
  el('l-recurring-fields').style.display = 'flex';
}

['l-principal', 'l-rate', 'l-years'].forEach(id => el(id).addEventListener('input', updateLoanPreview));

el('l-create-recurring').addEventListener('change', () => {
  el('l-recurring-fields').style.display = el('l-create-recurring').checked ? 'flex' : 'none';
});

async function loadLoans() {
  state.loans = await apiGet('loans.php');
  el('loans-grid').innerHTML = state.loans.map(l => {
    const c = l.calc;
    const actualLabel = l.actual.count !== null
      ? `Registrate: ${l.actual.count} (${euro(l.actual.amount_paid)})`
      : 'Nessuna rata ricorrente collegata';
    const ownershipLine = c.ownership_pct < 100
      ? `<div class="text-muted small">Tua quota (${c.ownership_pct}%): ${euro(c.personal_residual_today)} residuo, rata ${euro(c.personal_monthly_payment)}</div>`
      : '';
    return `
      <div class="col-md-4 col-sm-6">
        <div class="loan-card status-${l.status}" onclick="openLoanDetail(${l.id})">
          <div class="lc-name">${l.name}</div>
          <div class="lc-meta">${l.annual_rate}% · ${l.duration_years} anni · dal ${formatDate(l.start_date)}</div>
          <div class="lc-label">${l.status === 'estinto' ? 'Estinto' : 'Capitale residuo'}</div>
          <div class="lc-residual">${euro(c.residual_today)}</div>
          <div class="progress">
            <div class="progress-bar" style="width:${c.progress_pct}%; background:var(--loan)"></div>
          </div>
          <div class="text-muted small mt-1">Rata ${euro(c.monthly_payment)} · previste ${c.elapsed_payments}/${c.total_payments} (da calendario)</div>
          <div class="text-muted small">${actualLabel}</div>
          ${ownershipLine}
        </div>
      </div>
    `;
  }).join('') || '<div class="col-12 text-muted">Nessun mutuo registrato. Aggiungine uno con "+ Nuovo mutuo".</div>';
}

el('form-loan').addEventListener('submit', async (e) => {
  e.preventDefault();
  const id = el('l-id').value;

  if (id) {
    const payload = {
      id,
      name: el('l-name').value,
      principal: parseFloat(el('l-principal').value),
      annual_rate: parseFloat(el('l-rate').value),
      duration_years: parseInt(el('l-years').value, 10),
      ownership_pct: parseFloat(el('l-ownership').value || '100'),
      start_date: el('l-start').value,
      notes: el('l-notes').value,
    };
    try {
      await apiSend('loans.php', 'PUT', payload);
      bsModal('modal-loan').hide();
      resetLoanForm();
      loadLoans();
      loadDashboard();
      if (currentLoanId === parseInt(id, 10)) refreshLoanDetail();
    } catch (err) { alert(err.message); }
    return;
  }

  const payload = {
    name: el('l-name').value,
    principal: parseFloat(el('l-principal').value),
    annual_rate: parseFloat(el('l-rate').value),
    duration_years: parseInt(el('l-years').value, 10),
    ownership_pct: parseFloat(el('l-ownership').value || '100'),
    start_date: el('l-start').value,
    create_recurring: el('l-create-recurring').checked,
    account_id: el('l-account').value || null,
    category_id: el('l-category').value || null,
    notes: el('l-notes').value,
  };
  try {
    await apiSend('loans.php', 'POST', payload);
    bsModal('modal-loan').hide();
    resetLoanForm();
    await loadBaseData();
    loadLoans();
  } catch (err) { alert(err.message); }
});

window.editLoan = (id) => {
  const l = state.loans.find(x => x.id === id);
  if (!l) return;

  const openEditForm = () => {
    resetLoanForm();
    el('l-id').value = l.id;
    el('l-name').value = l.name;
    el('l-principal').value = l.principal;
    el('l-rate').value = l.annual_rate;
    el('l-years').value = l.duration_years;
    el('l-ownership').value = l.ownership_pct;
    el('l-start').value = l.start_date;
    el('l-notes').value = l.notes || '';
    el('loan-modal-title').textContent = 'Modifica mutuo';
    el('l-create-recurring').closest('.form-check').style.display = 'none';
    el('l-recurring-fields').style.display = 'none';
    updateLoanPreview();
    bsModal('modal-loan').show();
  };

  const detailModalEl = el('modal-loan-detail');
  detailModalEl.addEventListener('hidden.bs.modal', openEditForm, { once: true });
  bsModal(detailModalEl.id).hide();
};

el('btn-edit-loan').addEventListener('click', () => editLoan(currentLoanId));

window.openLoanDetail = async (id) => {
  currentLoanId = id;
  el('payoff-result').innerHTML = '';
  el('payoff-date').value = new Date().toISOString().slice(0, 10);
  await refreshLoanDetail();
  bsModal('modal-loan-detail').show();
};

async function refreshLoanDetail() {
  const data = await apiGet(`loans.php?action=schedule&id=${currentLoanId}`);
  const { loan, summary, actual, schedule } = data;

  el('loan-detail-title').textContent = loan.name;
  el('loan-detail-summary').innerHTML = `
    <div class="col-md-3 col-6"><div class="stat-mini"><div class="stat-label">Capitale erogato</div><div class="stat-value">${euro(loan.principal)}</div></div></div>
    <div class="col-md-3 col-6"><div class="stat-mini"><div class="stat-label">Rata mensile</div><div class="stat-value">${euro(summary.monthly_payment)}</div></div></div>
    <div class="col-md-3 col-6"><div class="stat-mini"><div class="stat-label">Capitale residuo</div><div class="stat-value">${euro(summary.residual_today)}</div></div></div>
    <div class="col-md-3 col-6"><div class="stat-mini"><div class="stat-label">Fine prevista</div><div class="stat-value">${formatDate(summary.end_date)}</div></div></div>
    <div class="col-md-6 col-6"><div class="stat-mini"><div class="stat-label">Rate previste ad oggi (da calendario)</div><div class="stat-value">${summary.elapsed_payments} / ${summary.total_payments}</div></div></div>
    <div class="col-md-6 col-6"><div class="stat-mini"><div class="stat-label">Rate effettivamente registrate</div><div class="stat-value">${actual.count !== null ? `${actual.count} · ${euro(actual.amount_paid)}` : 'N/D (nessuna ricorrenza collegata)'}</div></div></div>
  `;

  if (summary.ownership_pct < 100) {
    el('loan-detail-summary').innerHTML += `
      <div class="col-12">
        <div class="stat-mini" style="border-left:3px solid var(--accent)">
          <div class="stat-label">Tua quota di proprietà: ${summary.ownership_pct}%</div>
          <div class="stat-value" style="font-size:16px">Residuo: ${euro(summary.personal_residual_today)} · Rata: ${euro(summary.personal_monthly_payment)} · Interessi totali previsti: ${euro(summary.personal_total_interest)}</div>
        </div>
      </div>
    `;
  }

  if (actual.count !== null) {
    const interestPct = Math.min(actual.interest_pct_of_total, 100);
    const capitalPct = Math.min(actual.capital_pct_of_total, 100);
    const personalNote = summary.ownership_pct < 100
      ? `<span class="text-muted" style="font-size:12px"> (tua quota: interessi ${euro(actual.personal_interest_paid)}, capitale ${euro(actual.personal_capital_paid)})</span>`
      : '';
    el('loan-detail-summary').innerHTML += `
      <div class="col-md-6">
        <div class="stat-mini">
          <div class="d-flex justify-content-between align-items-baseline mb-1">
            <div class="stat-label">Interessi pagati finora</div>
            <div class="stat-value">${euro(actual.interest_paid)} <span class="text-muted" style="font-size:13px">(${actual.interest_pct_of_total}%)</span></div>
          </div>
          <div class="progress"><div class="progress-bar" style="width:${interestPct}%; background:var(--loan)"></div></div>
        </div>
      </div>
      <div class="col-md-6">
        <div class="stat-mini">
          <div class="d-flex justify-content-between align-items-baseline mb-1">
            <div class="stat-label">Capitale restituito finora</div>
            <div class="stat-value">${euro(actual.capital_paid)} <span class="text-muted" style="font-size:13px">(${actual.capital_pct_of_total}%)</span></div>
          </div>
          <div class="progress"><div class="progress-bar" style="width:${capitalPct}%; background:var(--accent)"></div></div>
        </div>
      </div>
      ${personalNote ? `<div class="col-12">${personalNote}</div>` : ''}
    `;
  }

  // Aggregazione annuale per il grafico
  const byYear = {};
  schedule.forEach(row => {
    const y = row.date.slice(0, 4);
    if (!byYear[y]) byYear[y] = { capitale: 0, interessi: 0, residuo: row.residuo };
    byYear[y].capitale += row.quota_capitale;
    byYear[y].interessi += row.quota_interessi;
    byYear[y].residuo = row.residuo;
  });
  const years = Object.keys(byYear);

  if (charts.loan) charts.loan.destroy();
  charts.loan = new Chart(el('chart-loan'), {
    data: {
      labels: years,
      datasets: [
        { type: 'bar', label: 'Quota capitale', data: years.map(y => byYear[y].capitale), backgroundColor: '#4f46e5', stack: 'a' },
        { type: 'bar', label: 'Quota interessi', data: years.map(y => byYear[y].interessi), backgroundColor: '#f59e0b', stack: 'a' },
        { type: 'line', label: 'Capitale residuo', data: years.map(y => byYear[y].residuo), borderColor: '#f43f5e', backgroundColor: '#f43f5e', yAxisID: 'y1', tension: 0.25 },
      ],
    },
    options: {
      responsive: true,
      interaction: { mode: 'index', intersect: false },
      scales: {
        x: { grid: { display: false } },
        y: { stacked: true, title: { display: true, text: 'Quote annue (€)' } },
        y1: { position: 'right', grid: { display: false }, title: { display: true, text: 'Residuo (€)' } },
      },
    },
  });

  // Accordion piano di ammortamento, raggruppato per anno
  el('loan-schedule-accordion').innerHTML = years.map((y, idx) => {
    const rows = schedule.filter(r => r.date.slice(0, 4) === y);
    return `
      <div class="accordion-item">
        <h2 class="accordion-header">
          <button class="accordion-button ${idx === 0 ? '' : 'collapsed'}" type="button" data-bs-toggle="collapse" data-bs-target="#year-${y}">
            ${y} — capitale ${euro(byYear[y].capitale)}, interessi ${euro(byYear[y].interessi)}
          </button>
        </h2>
        <div id="year-${y}" class="accordion-collapse collapse ${idx === 0 ? 'show' : ''}" data-bs-parent="#loan-schedule-accordion">
          <div class="accordion-body p-0">
            <table class="table table-sm mb-0 ledger-table">
              <thead><tr><th>#</th><th>Data</th><th class="text-end">Quota capitale</th><th class="text-end">Quota interessi</th><th class="text-end">Rata</th><th class="text-end">Residuo</th><th>Stato</th></tr></thead>
              <tbody>
                ${rows.map(r => `<tr class="${r.paid === false ? 'table-warning' : ''}"><td>${r.n}</td><td>${formatDate(r.date)}</td><td class="text-end">${euro(r.quota_capitale)}</td><td class="text-end">${euro(r.quota_interessi)}</td><td class="text-end">${euro(r.rata)}</td><td class="text-end">${euro(r.residuo)}</td><td>${loanRowStatusBadge(r.paid)}</td></tr>`).join('')}
              </tbody>
            </table>
          </div>
        </div>
      </div>
    `;
  }).join('');
}

function loanRowStatusBadge(paid) {
  if (paid === null) return '<span class="text-muted small">N/D</span>';
  return paid
    ? '<span class="badge bg-success-subtle text-success-emphasis"><i class="bi bi-check-lg"></i> Pagata</span>'
    : '<span class="badge bg-secondary-subtle text-secondary-emphasis">Da pagare</span>';
}

el('btn-simulate-payoff').addEventListener('click', async () => {
  const date = el('payoff-date').value;
  const data = await apiGet(`loans.php?action=schedule&id=${currentLoanId}&payoff_date=${date}`);
  const p = data.payoff_simulation;
  if (!p) return;
  el('payoff-result').innerHTML = `
    <div class="row g-3">
      <div class="col-md-4"><div class="stat-mini"><div class="stat-label">Rate pagate a quella data</div><div class="stat-value">${p.payments_made}</div></div></div>
      <div class="col-md-4"><div class="stat-mini"><div class="stat-label">Capitale da versare per estinguere</div><div class="stat-value">${euro(p.residual_capital)}</div></div></div>
      <div class="col-md-4"><div class="stat-mini"><div class="stat-label">Interessi risparmiati estinguendo ora</div><div class="stat-value">${euro(p.interest_saved_if_paid_now)}</div></div></div>
    </div>`;
});

el('btn-delete-loan').addEventListener('click', async () => {
  const deleteRecurring = confirm('Eliminare anche la rata ricorrente collegata e tutte le transazioni da essa generate? Annulla = elimina solo il mutuo, ricorrenza e transazioni restano intatte.');
  if (!confirm('Confermi l\'eliminazione definitiva di questo mutuo?')) return;
  await apiDelete(`loans.php?id=${currentLoanId}&delete_recurring=${deleteRecurring ? 1 : 0}`);
  bsModal('modal-loan-detail').hide();
  loadLoans();
  loadDashboard();
});

el('btn-mark-payoff').addEventListener('click', async () => {
  const date = el('payoff-date').value;
  const data = await apiGet(`loans.php?action=schedule&id=${currentLoanId}&payoff_date=${date}`);
  const p = data.payoff_simulation;
  if (!confirm(`Segnare il mutuo come estinto in data ${formatDate(date)}, con capitale versato ${euro(p.residual_capital)}?`)) return;
  await apiSend('loans.php', 'PUT', {
    id: currentLoanId, status: 'estinto', early_payoff_date: date, early_payoff_amount: p.residual_capital,
  });
  bsModal('modal-loan-detail').hide();
  loadLoans();
  loadDashboard();
});

// ---------- INVESTIMENTI ----------
function resetInvestmentForm() {
  el('form-investment').reset();
  el('inv-id').value = '';
  el('inv-date').value = new Date().toISOString().slice(0, 10);
  el('inv-account-wrap').style.display = 'none';
}

el('inv-record-cash').addEventListener('change', () => {
  el('inv-account-wrap').style.display = el('inv-record-cash').checked ? 'block' : 'none';
});

async function loadInstruments() {
  state.instruments = await apiGet('instruments.php');
  const sel = el('inv-instrument');
  sel.innerHTML = state.instruments.map(i => `<option value="${i.id}">${i.ticker} — ${i.name}</option>`).join('')
    || '<option value="">Aggiungi prima uno strumento</option>';
  renderInstrumentsList();
}

function renderInstrumentsList() {
  el('instruments-body').innerHTML = state.instruments.map(i => `
    <tr>
      <td><span class="fw-semibold">${i.ticker}</span></td>
      <td>${i.name}</td>
      <td>${capitalize(i.type)}</td>
      <td>${i.currency}</td>
      <td class="row-actions text-end">
        <button onclick="editInstrument(${i.id})"><i class="bi bi-pencil"></i></button>
        <button onclick="deleteInstrument(${i.id})"><i class="bi bi-trash"></i></button>
      </td>
    </tr>
  `).join('') || '<tr><td colspan="5" class="text-center text-muted py-4">Nessuno strumento registrato</td></tr>';
}

window.deleteInstrument = async (id) => {
  if (!confirm('Eliminare questo strumento? Verranno eliminate anche tutte le operazioni di acquisto/vendita collegate.')) return;
  try {
    await apiDelete(`instruments.php?id=${id}`);
    await loadInstruments();
    await loadPortfolio();
    await loadInvestmentTransactions();
  } catch (err) { alert(err.message); }
};

el('form-instrument').addEventListener('submit', async (e) => {
  e.preventDefault();
  const payload = {
    ticker: el('i-ticker').value.trim().toUpperCase(),
    name: el('i-name').value,
    type: el('i-type').value,
    currency: el('i-currency').value,
  };
  const id = el('i-id').value;
  try {
    if (id) { payload.id = id; await apiSend('instruments.php', 'PUT', payload); }
    else { await apiSend('instruments.php', 'POST', payload); }
    bsModal('modal-instrument').hide();
    resetInstrumentForm();
    await loadInstruments();
    await loadPortfolio();
  } catch (err) { alert(err.message); }
});

function resetInstrumentForm() {
  el('form-instrument').reset();
  el('i-id').value = '';
}

window.editInstrument = (id) => {
  const i = state.instruments.find(x => x.id === id) || (window._portfolioCache || []).map(p => p.instrument).find(x => x.id === id);
  if (!i) return;
  el('i-id').value = i.id;
  el('i-ticker').value = i.ticker;
  el('i-name').value = i.name;
  el('i-type').value = i.type;
  el('i-currency').value = i.currency;
  bsModal('modal-instrument').show();
};

async function loadInvestmentsView() {
  await loadInstruments();
  await loadPortfolio();
  await loadInvestmentTransactions();
}

el('form-investment').addEventListener('submit', async (e) => {
  e.preventDefault();
  const payload = {
    instrument_id: el('inv-instrument').value,
    type: el('inv-type').value,
    quantity: parseFloat(el('inv-quantity').value),
    price_per_unit: parseFloat(el('inv-price').value),
    fees: parseFloat(el('inv-fees').value || '0'),
    date: el('inv-date').value,
    notes: el('inv-notes').value,
    record_cash_movement: el('inv-record-cash').checked,
    account_id: el('inv-record-cash').checked ? el('inv-account').value : null,
  };
  const id = el('inv-id').value;
  try {
    if (id) { payload.id = id; await apiSend('investments.php', 'PUT', payload); }
    else { await apiSend('investments.php', 'POST', payload); }
    bsModal('modal-investment').hide();
    resetInvestmentForm();
    await loadPortfolio();
    await loadInvestmentTransactions();
    loadBaseData();
  } catch (err) { alert(err.message); }
});

window.editInvestment = (id) => {
  const t = window._invCache.find(x => x.id === id);
  if (!t) return;
  el('inv-id').value = t.id;
  el('inv-instrument').value = t.instrument_id;
  el('inv-type').value = t.type;
  el('inv-quantity').value = t.quantity;
  el('inv-price').value = t.price_per_unit;
  el('inv-fees').value = t.fees;
  el('inv-date').value = t.date;
  el('inv-notes').value = t.notes || '';
  el('inv-record-cash').checked = false;
  el('inv-account-wrap').style.display = 'none';
  bsModal('modal-investment').show();
};

window.deleteInvestment = async (id) => {
  const deleteLinked = confirm('Eliminare anche l\'eventuale movimento di cassa collegato? Annulla = elimina solo l\'operazione.');
  await apiDelete(`investments.php?id=${id}&delete_linked=${deleteLinked ? 1 : 0}`);
  await loadPortfolio();
  await loadInvestmentTransactions();
};

async function loadInvestmentTransactions() {
  const rows = await apiGet('investments.php');
  window._invCache = rows;
  el('investments-body').innerHTML = rows.map(t => {
    const total = t.quantity * t.price_per_unit;
    const cls = t.type === 'acquisto' ? 'amount-expense' : 'amount-income';
    return `
      <tr>
        <td>${formatDate(t.date)}</td>
        <td>${t.ticker}</td>
        <td>${t.instrument_name}</td>
        <td>${capitalize(t.type)}</td>
        <td class="text-end">${t.quantity}</td>
        <td class="text-end">${euro(t.price_per_unit)}</td>
        <td class="text-end ${cls}">${euro(total)}</td>
        <td class="row-actions text-end">
          <button onclick="editInvestment(${t.id})"><i class="bi bi-pencil"></i></button>
          <button onclick="deleteInvestment(${t.id})"><i class="bi bi-trash"></i></button>
        </td>
      </tr>`;
  }).join('') || '<tr><td colspan="8" class="text-center text-muted py-4">Nessuna operazione registrata</td></tr>';
}

el('btn-refresh-prices').addEventListener('click', async () => {
  el('btn-refresh-prices').disabled = true;
  await loadPortfolio(true);
  el('btn-refresh-prices').disabled = false;
});

async function loadPortfolio(refresh = false) {
  const data = await apiGet(`portfolio.php${refresh ? '?refresh=1' : ''}`);
  window._portfolioCache = data.positions;
  renderPortfolioTotals(data.totals_by_currency, data.realized_gain_total);
  renderPositions(data.positions);
  renderAllocationChart(data.positions);
}

function renderPortfolioTotals(totalsByCurrency, realizedTotal) {
  const currencies = Object.keys(totalsByCurrency);
  const row = el('portfolio-totals-row');
  if (!currencies.length) {
    row.innerHTML = `<div class="col-12"><div class="stat-mini"><div class="stat-label">Plusvalenze realizzate</div><div class="stat-value">${euro(realizedTotal)}</div></div></div>`;
    return;
  }
  let html = '';
  currencies.forEach(cur => {
    const t = totalsByCurrency[cur];
    const gainCls = t.unrealized_gain >= 0 ? 'amount-income' : 'amount-expense';
    html += `
      <div class="col-md-3 col-sm-6">
        <div class="stat-mini"><div class="stat-label">Investito (${cur})</div><div class="stat-value">${fmt(t.invested, cur)}</div></div>
      </div>
      <div class="col-md-3 col-sm-6">
        <div class="stat-mini"><div class="stat-label">Valore attuale (${cur})</div><div class="stat-value">${fmt(t.current_value, cur)}</div></div>
      </div>
      <div class="col-md-3 col-sm-6">
        <div class="stat-mini"><div class="stat-label">Plus/minus non realizz. (${cur})</div><div class="stat-value ${gainCls}">${fmt(t.unrealized_gain, cur)}</div></div>
      </div>
    `;
  });
  html += `
    <div class="col-md-3 col-sm-6">
      <div class="stat-mini"><div class="stat-label">Plusvalenze realizzate</div><div class="stat-value">${euro(realizedTotal)}</div></div>
    </div>
  `;
  row.innerHTML = html;
  if (currencies.length > 1) {
    row.innerHTML += `<div class="col-12"><small class="text-muted">Nota: i totali non vengono convertiti in un'unica valuta, sono sommati separatamente per valuta di quotazione.</small></div>`;
  }
}

function fmt(n, currency) {
  try { return new Intl.NumberFormat('it-IT', { style: 'currency', currency }).format(n || 0); }
  catch { return euro(n); }
}

function renderPositions(positions) {
  const held = positions.filter(p => p.quantity > 0.000001);
  el('positions-grid').innerHTML = held.map(p => {
    const i = p.instrument;
    const gainCls = (p.unrealized_gain || 0) >= 0 ? 'amount-income' : 'amount-expense';
    const priceLabel = p.live_price !== null
      ? `${fmt(p.live_price, p.price_currency)}${p.price_stale ? ' <span class="pc-stale">(quotazione non aggiornata)</span>' : ''}`
      : 'Quotazione non disponibile';
    return `
      <div class="col-md-6">
        <div class="position-card">
          <div class="d-flex justify-content-between align-items-start">
            <div class="pc-ticker">${i.ticker} · ${capitalize(i.type)}</div>
            <button class="btn btn-sm p-0 text-muted" style="border:none;background:none" onclick="editInstrument(${i.id})" title="Modifica strumento"><i class="bi bi-pencil"></i></button>
          </div>
          <div class="pc-name">${i.name}</div>
          <div class="pc-value">${p.current_value !== null ? fmt(p.current_value, p.price_currency) : fmt(p.cost_basis, i.currency)}</div>
          ${p.unrealized_gain !== null ? `<div class="pc-gain ${gainCls}">${p.unrealized_gain >= 0 ? '+' : ''}${fmt(p.unrealized_gain, p.price_currency)} (${p.unrealized_gain_pct}%)</div>` : ''}
          <div class="pc-meta">Quantità: ${p.quantity} · Prezzo medio: ${fmt(p.avg_cost, i.currency)}</div>
          <div class="pc-meta">Prezzo attuale: ${priceLabel}</div>
        </div>
      </div>
    `;
  }).join('') || '<div class="col-12 text-muted">Nessuna posizione aperta. Registra un acquisto per iniziare.</div>';
}

function renderAllocationChart(positions) {
  const held = positions.filter(p => p.quantity > 0.000001 && p.current_value !== null);
  const ctx = el('chart-allocation');
  if (charts.allocation) charts.allocation.destroy();
  if (!held.length) { ctx.getContext('2d').clearRect(0, 0, ctx.width, ctx.height); return; }
  const palette = ['#4f46e5', '#10b981', '#f59e0b', '#f43f5e', '#0ea5e9', '#8b5cf6', '#ec4899', '#14b8a6'];
  charts.allocation = new Chart(ctx, {
    type: 'doughnut',
    data: {
      labels: held.map(p => p.instrument.ticker),
      datasets: [{ data: held.map(p => p.current_value), backgroundColor: held.map((_, idx) => palette[idx % palette.length]), borderColor: '#fff', borderWidth: 2 }],
    },
    options: {
      responsive: true,
      plugins: { legend: { position: 'bottom', labels: { color: '#1b1d29', boxWidth: 12, padding: 12 } } },
    },
  });
}

// ---------- BUDGET ----------
async function loadBudgets() {
  const uscite = state.categories.filter(c => c.type === 'uscita');
  const data = await apiGet(`budgets.php?month=${new Date().toISOString().slice(0, 7)}`);
  const byCategoryId = {};
  data.budgets.forEach(b => { byCategoryId[b.category_id] = b; });

  el('budgets-body').innerHTML = uscite.map(c => {
    const b = byCategoryId[c.id];
    return `
      <tr data-category-id="${c.id}">
        <td><span class="dot d-inline-block me-2" style="width:8px;height:8px;border-radius:50%;background:${c.color}"></span>${c.name}</td>
        <td>
          <input type="number" class="form-control form-control-sm budget-input" min="0" step="1"
                 value="${b ? b.monthly_limit : ''}" placeholder="Nessun limite">
        </td>
        <td>
          <button class="btn btn-sm btn-outline-secondary" onclick="saveBudget(${c.id})">Salva</button>
          ${b ? `<button class="btn btn-sm btn-outline-danger" onclick="deleteBudget(${c.id})">Rimuovi</button>` : ''}
        </td>
      </tr>`;
  }).join('');
}

window.saveBudget = async (categoryId) => {
  const row = document.querySelector(`#budgets-body tr[data-category-id="${categoryId}"]`);
  const value = parseFloat(row.querySelector('.budget-input').value);
  if (!value || value <= 0) { alert('Inserisci un limite maggiore di zero'); return; }
  await apiSend('budgets.php', 'POST', { category_id: categoryId, monthly_limit: value });
  loadBudgets();
  if (document.getElementById('view-dashboard').classList.contains('active')) renderBudgetsDashboard();
};

window.deleteBudget = async (categoryId) => {
  if (!confirm('Rimuovere il budget per questa categoria?')) return;
  await apiDelete(`budgets.php?category_id=${categoryId}`);
  loadBudgets();
};

async function renderBudgetsDashboard() {
  const data = await apiGet(`budgets.php?month=${new Date().toISOString().slice(0, 7)}`);
  const card = el('budgets-dashboard-card');
  const list = el('budgets-dashboard-list');

  if (!data.budgets.length) { card.style.display = 'none'; return; }
  card.style.display = 'block';

  list.innerHTML = data.budgets.map(b => {
    const pct = Math.min(b.pct, 100);
    const barColor = b.over_budget ? '#f43f5e' : (b.pct >= 80 ? '#f59e0b' : '#10b981');
    return `
      <div class="budget-row">
        <div class="br-top">
          <span class="br-name">${b.category_name}${b.over_budget ? ' <i class="bi bi-exclamation-triangle-fill text-danger"></i>' : ''}</span>
          <span class="br-figures ${b.over_budget ? 'over' : ''}">${euro(b.spent)} / ${euro(b.monthly_limit)} (${b.pct}%)</span>
        </div>
        <div class="progress"><div class="progress-bar" style="width:${pct}%; background:${barColor}"></div></div>
      </div>
    `;
  }).join('');
}

// ---------- ANALISI PREDITTIVA ----------
el('analytics-history-select').addEventListener('change', loadAnalytics);
el('analytics-forecast-select').addEventListener('change', loadAnalytics);

async function loadAnalytics() {
  const historyMonths = el('analytics-history-select').value;
  const forecastMonths = el('analytics-forecast-select').value;
  const data = await apiGet(`analytics.php?history_months=${historyMonths}&forecast_months=${forecastMonths}`);

  el('analytics-insufficient-banner').style.display = data.data_sufficient ? 'none' : 'block';
  renderAnalyticsAverages(data.averages);
  renderAnalyticsChart(data.monthly_history, data.forecast);
  renderCurrentMonth(data.current_month);
  renderAnalyticsAlerts(data.category_alerts);
  renderMonteCarlo(data.monte_carlo);
  renderDetectedRecurring(data.detected_recurring);
}

function renderAnalyticsAverages(a) {
  const netCls = a.avg_net >= 0 ? 'amount-income' : 'amount-expense';
  el('analytics-averages-row').innerHTML = `
    <div class="col-md-3 col-sm-6">
      <div class="stat-mini"><div class="stat-label">Reddito medio mensile</div><div class="stat-value">${euro(a.avg_income)}</div></div>
    </div>
    <div class="col-md-3 col-sm-6">
      <div class="stat-mini"><div class="stat-label">Spesa media mensile</div><div class="stat-value">${euro(a.avg_expense)}</div></div>
    </div>
    <div class="col-md-3 col-sm-6">
      <div class="stat-mini"><div class="stat-label">Risparmio medio mensile</div><div class="stat-value ${netCls}">${euro(a.avg_net)}</div></div>
    </div>
    <div class="col-md-3 col-sm-6">
      <div class="stat-mini"><div class="stat-label">Tasso di risparmio</div><div class="stat-value">${a.savings_rate_pct}%</div></div>
    </div>
    <div class="col-12">
      <small class="text-muted">Di cui fissa/ricorrente: ${euro(a.avg_recurring_income)} entrate, ${euro(a.avg_recurring_expense)} uscite. Il resto (${euro(a.avg_variable_income)} entrate, ${euro(a.avg_variable_expense)} uscite) è variabile, stimato dalla media storica.</small>
    </div>
  `;
}

function renderAnalyticsChart(history, forecast) {
  const historyLabels = history.map(m => {
    const [y, mo] = m.month.split('-');
    return new Date(y, mo - 1, 1).toLocaleDateString('it-IT', { month: 'short', year: '2-digit' });
  });
  const forecastLabels = forecast.map(m => {
    const [y, mo] = m.month.split('-');
    return new Date(y, mo - 1, 1).toLocaleDateString('it-IT', { month: 'short', year: '2-digit' });
  });
  const labels = [...historyLabels, ...forecastLabels];

  const historyNet = history.map(m => m.entrate - m.uscite);
  const historyBars = [...historyNet, ...forecast.map(() => null)];
  const forecastBars = [...historyNet.map(() => null), ...forecast.map(m => m.projected_net)];

  let cumulative = 0;
  const cumulativeLine = history.map(m => (cumulative += (m.entrate - m.uscite)));
  const cumulativeForecastLine = forecast.map(m => m.cumulative_savings + cumulative);
  const fullCumulative = [...cumulativeLine, ...cumulativeForecastLine];

  const ctx = el('chart-analytics');
  if (charts.analytics) charts.analytics.destroy();
  charts.analytics = new Chart(ctx, {
    data: {
      labels,
      datasets: [
        { type: 'bar', label: 'Risparmio mensile (storico)', data: historyBars, backgroundColor: historyNet.map(v => v >= 0 ? '#10b981' : '#f43f5e'), borderRadius: 4 },
        { type: 'bar', label: 'Risparmio proiettato', data: forecastBars, backgroundColor: 'rgba(79,70,229,0.35)', borderRadius: 4 },
        { type: 'line', label: 'Risparmio cumulato', data: fullCumulative, borderColor: '#4f46e5', backgroundColor: '#4f46e5', yAxisID: 'y1', tension: 0.2 },
      ],
    },
    options: {
      responsive: true,
      interaction: { mode: 'index', intersect: false },
      plugins: { legend: { labels: { color: '#1b1d29' } } },
      scales: {
        x: { grid: { display: false } },
        y: { title: { display: true, text: 'Risparmio mensile (€)' }, grid: { color: '#e7e9f0' } },
        y1: { position: 'right', grid: { display: false }, title: { display: true, text: 'Cumulato (€)' } },
      },
    },
  });
}

function renderCurrentMonth(m) {
  const netCls = m.projected_net >= 0 ? 'amount-income' : 'amount-expense';
  const discPct = m.discretionary_budget > 0 ? Math.min(100, Math.round((1 - m.discretionary_remaining / m.discretionary_budget) * 100)) : 0;
  el('analytics-current-month').innerHTML = `
    <div class="stat-mini mb-2">
      <div class="stat-label">Entrate finora / proiettate</div>
      <div class="stat-value" style="font-size:16px">${euro(m.income_so_far)} → ${euro(m.projected_income)}</div>
    </div>
    <div class="stat-mini mb-2">
      <div class="stat-label">Uscite finora / proiettate</div>
      <div class="stat-value" style="font-size:16px">${euro(m.expense_so_far)} → ${euro(m.projected_expense)}</div>
    </div>
    <div class="stat-mini mb-2">
      <div class="stat-label">Risparmio netto proiettato a fine mese</div>
      <div class="stat-value ${netCls}" style="font-size:16px">${euro(m.projected_net)}</div>
    </div>
    <div class="stat-mini">
      <div class="stat-label">Margine discrezionale residuo</div>
      <div class="stat-value" style="font-size:16px">${euro(m.discretionary_remaining)}</div>
      <div class="progress mt-2"><div class="progress-bar" style="width:${discPct}%; background:${discPct >= 100 ? '#f43f5e' : '#4f46e5'}"></div></div>
      <small class="text-muted">Quanto puoi ancora spendere in spese variabili questo mese senza superare la tua media storica (${euro(m.discretionary_budget)}/mese)</small>
    </div>
  `;
}

function renderAnalyticsAlerts(alerts) {
  const list = el('analytics-alerts-list');
  if (!alerts.length) {
    list.innerHTML = '<span class="text-muted">Nessuna categoria sta sforando il budget o la media storica questo mese.</span>';
    return;
  }
  list.innerHTML = alerts.map(a => {
    const barColor = a.severity === 'danger' ? '#f43f5e' : (a.severity === 'warning' ? '#f59e0b' : '#10b981');
    const icon = a.severity !== 'ok' ? '<i class="bi bi-exclamation-triangle-fill text-danger"></i> ' : '';
    return `
      <div class="budget-row">
        <div class="br-top">
          <span class="br-name">${icon}${a.category_name} <span class="text-muted" style="font-size:11px">(rif: ${a.reference_type === 'budget' ? 'budget' : 'media storica'})</span></span>
          <span class="br-figures ${a.severity !== 'ok' ? 'over' : ''}">${euro(a.projected_month_end)} / ${euro(a.reference)} (${a.pct}%)</span>
        </div>
        <div class="progress"><div class="progress-bar" style="width:${Math.min(a.pct, 100)}%; background:${barColor}"></div></div>
      </div>
    `;
  }).join('');
}

function renderMonteCarlo(mc) {
  const headline = el('montecarlo-headline');
  const ctx = el('chart-montecarlo');
  if (charts.montecarlo) charts.montecarlo.destroy();

  if (!mc) {
    headline.innerHTML = '<span class="text-muted">Servono almeno 3 mesi di storico per la simulazione probabilistica.</span>';
    ctx.getContext('2d').clearRect(0, 0, ctx.width, ctx.height);
    return;
  }

  const probCls = mc.probability_final_positive_pct >= 70 ? 'amount-income' : (mc.probability_final_positive_pct >= 40 ? '' : 'amount-expense');
  headline.innerHTML = `<span class="fw-semibold ${probCls}" style="font-size:15px">Probabilità di risparmio cumulato positivo a fine periodo: ${mc.probability_final_positive_pct}%</span> <span class="text-muted small">(su ${mc.simulations.toLocaleString('it-IT')} scenari simulati)</span>`;

  const labels = mc.percentile_bands.map(b => {
    const [y, mo] = b.month.split('-');
    return new Date(y, mo - 1, 1).toLocaleDateString('it-IT', { month: 'short', year: '2-digit' });
  });

  charts.montecarlo = new Chart(ctx, {
    type: 'line',
    data: {
      labels,
      datasets: [
        { label: 'Scenario ottimistico (90° percentile)', data: mc.percentile_bands.map(b => b.p90), borderColor: 'rgba(16,185,129,0.4)', backgroundColor: 'rgba(16,185,129,0.12)', fill: '+1', tension: 0.2, pointRadius: 0 },
        { label: 'Scenario mediano', data: mc.percentile_bands.map(b => b.p50), borderColor: '#4f46e5', backgroundColor: '#4f46e5', fill: false, tension: 0.2, pointRadius: 2 },
        { label: 'Scenario prudente (10° percentile)', data: mc.percentile_bands.map(b => b.p10), borderColor: 'rgba(244,63,94,0.4)', backgroundColor: 'rgba(244,63,94,0.08)', fill: false, tension: 0.2, pointRadius: 0 },
      ],
    },
    options: {
      responsive: true,
      plugins: { legend: { labels: { color: '#1b1d29', boxWidth: 12 } } },
      scales: {
        x: { grid: { display: false } },
        y: { title: { display: true, text: 'Risparmio cumulato (€)' }, grid: { color: '#e7e9f0' } },
      },
    },
  });
}

const FREQ_MAP = {
  settimanale: { frequency: 'settimanale', interval: 1 },
  mensile: { frequency: 'mensile', interval: 1 },
  trimestrale: { frequency: 'mensile', interval: 3 },
  semestrale: { frequency: 'mensile', interval: 6 },
  annuale: { frequency: 'annuale', interval: 1 },
};

function renderDetectedRecurring(items) {
  window._detectedRecurringCache = items;
  const list = el('detected-recurring-list');
  if (!items.length) {
    list.innerHTML = '<span class="text-muted">Nessun pattern ricorrente non dichiarato trovato nello storico.</span>';
    return;
  }
  list.innerHTML = items.map((d, idx) => `
    <div class="d-flex justify-content-between align-items-center py-2 ${idx > 0 ? 'border-top' : ''}">
      <div>
        <span class="dot d-inline-block me-2" style="width:8px;height:8px;border-radius:50%;background:${d.color}"></span>
        <strong>${d.category_name}</strong> — ${euro(d.avg_amount)} circa ogni ${d.frequency_detected}
        <div class="text-muted small">${d.occurrences} occorrenze rilevate · regolarità ${d.regularity_pct}% · ultima il ${formatDate(d.last_date)}</div>
      </div>
      <button class="btn btn-sm btn-outline-secondary" onclick="prefillDetectedRecurring(${idx})">Registra come ricorrenza</button>
    </div>
  `).join('');
}

window.prefillDetectedRecurring = (idx) => {
  const d = window._detectedRecurringCache[idx];
  el('r-type').value = 'uscita';
  el('r-type').dispatchEvent(new Event('change'));
  el('r-amount').value = d.avg_amount;
  el('r-category').value = d.category_id;
  const map = FREQ_MAP[d.frequency_detected] || { frequency: 'mensile', interval: 1 };
  el('r-frequency').value = map.frequency;
  el('r-interval').value = map.interval;
  el('r-start').value = d.suggested_start_date;
  el('r-description').value = d.category_name;
  bsModal('modal-recurring').show();
};

// ---------- Boot ----------
(async function init() {
  el('t-date').value = new Date().toISOString().slice(0, 10);
  el('category-period').value = new Date().toISOString().slice(0, 7);
  await loadBaseData();
  loadDashboard();
})();
