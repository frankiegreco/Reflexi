const API = '/api';

// Le chiamate agli endpoint auth/* (login, registrazione, ecc.) gestiscono i
// propri 401 localmente nel form (es. "password errata"). Per tutte le altre
// chiamate, un 401 significa che la sessione e' scaduta: lo segnaliamo con un
// evento globale cosi' l'app puo' riportare l'utente al login in modo pulito,
// invece di mostrare errori generici sparsi per l'interfaccia.
function isAuthEndpoint(path) {
  return path.startsWith('auth/');
}

function notifyUnauthorized(path) {
  if (!isAuthEndpoint(path)) {
    window.dispatchEvent(new CustomEvent('auth:unauthorized'));
  }
}

// 402 = trial scaduto o abbonamento non valido (require_auth lato PHP):
// AuthContext mette has_access a false e Gate mostra il Paywall.
function notifyPaymentRequired() {
  window.dispatchEvent(new CustomEvent('auth:payment_required'));
}

async function parseError(res, fallback) {
  const err = await res.json().catch(() => ({ error: fallback }));
  const e = new Error(err.error || fallback);
  e.code = err.code;
  return e;
}

export async function apiGet(path) {
  const res = await fetch(`${API}/${path}`);
  if (res.status === 401) notifyUnauthorized(path);
  if (res.status === 402) notifyPaymentRequired();
  if (!res.ok) throw await parseError(res, 'Errore sconosciuto');
  return res.json();
}

export async function apiSend(path, method, body) {
  const res = await fetch(`${API}/${path}`, {
    method,
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(body),
  });
  if (res.status === 401) notifyUnauthorized(path);
  if (res.status === 402) notifyPaymentRequired();
  if (!res.ok) throw await parseError(res, 'Errore sconosciuto');
  return res.json();
}

export async function apiDelete(path) {
  const res = await fetch(`${API}/${path}`, { method: 'DELETE' });
  if (res.status === 401) notifyUnauthorized(path);
  if (res.status === 402) notifyPaymentRequired();
  if (!res.ok) throw await parseError(res, "Errore durante l'eliminazione");
  return res.json();
}
