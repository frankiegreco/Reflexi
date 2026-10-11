import { useState, useEffect } from 'react';
import { KeyRound, Ban, ServerCrash, ShieldAlert, RefreshCw } from 'lucide-react';
import { apiGet } from '../api';
import { debounce } from '../lib/format';
import { dayTime, device } from '../lib/admin';
import StatCard from '../components/StatCard';

const TYPE_LABELS = {
  login_failed: 'Login fallito',
  login_unverified: 'Login email non verificata',
  rate_limited: 'Troppi tentativi',
  admin_denied: 'Accesso admin negato',
  password_check_failed: 'Password errata (profilo)',
  password_changed: 'Password cambiata',
  password_reset: 'Password reimpostata',
  reset_invalid: 'Link reset non valido',
  account_deleted: 'Account eliminato',
  stripe_webhook_invalid: 'Webhook Stripe non valido',
  server_error: 'Errore server',
};

const LEVELS = {
  error: { label: 'Errore', cls: 'bg-red-50 text-red-700' },
  warning: { label: 'Avviso', cls: 'bg-amber-50 text-amber-800' },
  info: { label: 'Info', cls: 'bg-gray-100 text-gray-600' },
};

const typeLabel = (t) => TYPE_LABELS[t] || t;

export default function AdminLogs() {
  const [filters, setFilters] = useState({ days: '7', type: '', level: '', q: '' });
  const [search, setSearch] = useState('');
  const [data, setData] = useState(null);
  const [error, setError] = useState('');
  const [reloadKey, setReloadKey] = useState(0);
  const [expanded, setExpanded] = useState(null);

  // Il testo libero aggiorna il filtro con debounce, per non chiamare l'API a ogni tasto.
  const [applySearch] = useState(() => debounce((v) => setFilters((f) => ({ ...f, q: v })), 350));

  useEffect(() => {
    const qs = new URLSearchParams(Object.entries(filters).filter(([, v]) => v !== '')).toString();
    apiGet(`admin_logs.php?${qs}`).then((d) => { setData(d); setError(''); }).catch((e) => setError(e.message));
  }, [filters, reloadKey]);

  if (error && !data) return <div className="text-expense">{error}</div>;
  if (!data) return <div className="text-muted">Caricamento...</div>;

  const s = data.summary_24h;
  const set = (k) => (e) => setFilters((f) => ({ ...f, [k]: e.target.value }));
  const filterByIp = (ip) => { setSearch(ip); setFilters((f) => ({ ...f, q: ip })); };

  return (
    <div>
      <div className="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <h4 className="text-xl font-bold">Log di sicurezza ed errori</h4>
        <button className="btn-outline flex items-center gap-1.5 self-start" onClick={() => setReloadKey((k) => k + 1)}>
          <RefreshCw size={14} /> Aggiorna
        </button>
      </div>

      <div className="mb-4 grid grid-cols-2 gap-3 lg:grid-cols-4">
        <StatCard label="Login falliti (24 h)" value={s.failed_logins} valueClass={s.failed_logins ? 'text-expense' : ''} icon={KeyRound} iconColor="#c1665a" />
        <StatCard label="Bloccati per troppi tentativi (24 h)" value={s.rate_limited} valueClass={s.rate_limited ? 'text-expense' : ''} icon={Ban} iconColor="#c1665a" />
        <StatCard label="Errori server (24 h)" value={s.errors} valueClass={s.errors ? 'text-expense' : ''} icon={ServerCrash} iconColor="#c1665a" />
        <StatCard label="Altri avvisi (24 h)" value={s.other_warnings} icon={ShieldAlert} iconColor="#d4a24c" />
      </div>

      {data.top_ips.length > 0 && (
        <div className="card mb-4 p-4">
          <h2 className="mb-1 text-sm font-bold text-gray-800">IP con più tentativi sospetti (7 giorni)</h2>
          <p className="mb-3 text-xs text-muted">Login falliti, blocchi per troppi tentativi, accessi admin negati e password errate. Clicca un IP per filtrare il log.</p>
          <div className="flex flex-wrap gap-2">
            {data.top_ips.map((r) => (
              <button
                key={r.ip}
                onClick={() => filterByIp(r.ip)}
                className="rounded-lg border border-border px-3 py-1.5 text-left text-xs hover:border-accent"
                title={`Ultimo evento ${dayTime(r.last_at)}`}
              >
                <span className="font-mono font-semibold">{r.ip}</span>
                <span className="ml-2 text-muted">{r.events} eventi · {r.emails} email</span>
              </button>
            ))}
          </div>
        </div>
      )}

      <div className="mb-3 grid grid-cols-1 gap-2 sm:grid-cols-4">
        <select className="field" value={filters.days} onChange={set('days')}>
          <option value="1">Ultime 24 ore</option>
          <option value="7">Ultimi 7 giorni</option>
          <option value="30">Ultimi 30 giorni</option>
          <option value="90">Ultimi 90 giorni</option>
        </select>
        <select className="field" value={filters.type} onChange={set('type')}>
          <option value="">Tutti i tipi</option>
          {[...new Set([...Object.keys(TYPE_LABELS), ...data.types])].map((t) => (
            <option key={t} value={t}>{typeLabel(t)}</option>
          ))}
        </select>
        <select className="field" value={filters.level} onChange={set('level')}>
          <option value="">Tutti i livelli</option>
          <option value="error">Errori</option>
          <option value="warning">Avvisi</option>
          <option value="info">Info</option>
        </select>
        <input
          className="field"
          placeholder="Cerca email, IP, messaggio..."
          value={search}
          onChange={(e) => { setSearch(e.target.value); applySearch(e.target.value.trim()); }}
        />
      </div>

      <div className="card overflow-hidden">
        <div className="overflow-x-auto">
          <table className="w-full text-sm">
            <thead>
              <tr className="border-b border-border text-left text-[11px] uppercase tracking-wide text-muted">
                <th className="px-4 py-3">Quando</th>
                <th className="px-4 py-3">Livello</th>
                <th className="px-4 py-3">Evento</th>
                <th className="px-4 py-3">Utente / email</th>
                <th className="px-4 py-3">IP</th>
                <th className="px-4 py-3">Dettaglio</th>
              </tr>
            </thead>
            <tbody>
              {data.events.length ? data.events.map((e) => {
                const lv = LEVELS[e.level] || LEVELS.info;
                const open = expanded === e.id;
                return (
                  <tr
                    key={e.id}
                    onClick={() => setExpanded(open ? null : e.id)}
                    className="cursor-pointer border-b border-border align-top last:border-0 hover:bg-gray-50"
                  >
                    <td className="whitespace-nowrap px-4 py-3">{dayTime(e.created_at)}</td>
                    <td className="px-4 py-3">
                      <span className={`whitespace-nowrap rounded-full px-2 py-0.5 text-xs font-semibold ${lv.cls}`}>{lv.label}</span>
                    </td>
                    <td className="whitespace-nowrap px-4 py-3 font-medium">{typeLabel(e.type)}</td>
                    <td className="px-4 py-3">
                      {e.email || <span className="text-muted">—</span>}
                      {e.user_name && <div className="text-xs text-muted">{e.user_name}</div>}
                    </td>
                    <td className="whitespace-nowrap px-4 py-3 font-mono text-xs text-muted">{e.ip || '—'}</td>
                    <td className="max-w-md px-4 py-3 text-muted">
                      <div className={open ? 'whitespace-pre-wrap break-words' : 'truncate'}>{e.message || '—'}</div>
                      {open && (
                        <div className="mt-2 space-y-0.5 text-xs">
                          <div><span className="font-semibold">Endpoint:</span> <span className="font-mono">{e.endpoint || '—'}</span></div>
                          <div><span className="font-semibold">Dispositivo:</span> {device(e.user_agent)}</div>
                          {e.user_agent && <div className="break-all font-mono">{e.user_agent}</div>}
                        </div>
                      )}
                    </td>
                  </tr>
                );
              }) : (
                <tr><td colSpan={6} className="px-4 py-8 text-center text-sm text-muted">Nessun evento nel periodo selezionato</td></tr>
              )}
            </tbody>
          </table>
        </div>
      </div>
      <p className="mt-2 text-xs text-muted">
        {data.events.length >= data.limit
          ? `Mostrati i ${data.limit} eventi più recenti: restringi i filtri per vedere gli altri. `
          : ''}
        Clicca una riga per i dettagli. Log conservato per 90 giorni; password e token non vengono mai registrati.
      </p>
    </div>
  );
}
