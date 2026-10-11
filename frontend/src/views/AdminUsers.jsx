import { useState, useEffect } from 'react';
import { Users, BadgeCheck, CreditCard, Hourglass, UserPlus, ArrowLeft, LogIn, Timer, Clock, Globe, ChevronRight } from 'lucide-react';
import { apiGet } from '../api';
import { formatDate } from '../lib/format';
import StatCard from '../components/StatCard';

const STATUS = {
  active: { label: 'Attivo', cls: 'bg-emerald-50 text-emerald-700' },
  trialing: { label: 'In prova', cls: 'bg-accentSoft text-accent' },
  past_due: { label: 'Pagamento scaduto', cls: 'bg-amber-50 text-amber-800' },
  canceled: { label: 'Cancellato', cls: 'bg-gray-100 text-gray-600' },
  none: { label: 'Nessuno', cls: 'bg-gray-100 text-gray-600' },
};

const SESSION_STATUS = {
  attiva: { label: 'Attiva', cls: 'bg-emerald-50 text-emerald-700' },
  logout: { label: 'Logout', cls: 'bg-gray-100 text-gray-600' },
  scaduta: { label: 'Scaduta', cls: 'bg-amber-50 text-amber-800' },
};

// I datetime MySQL arrivano come "YYYY-MM-DD HH:MM:SS": formatDate vuole solo la data.
const day = (dt) => (dt ? formatDate(dt.slice(0, 10)) : '—');
const dayTime = (dt) => (dt ? `${day(dt)} ${dt.slice(11, 16)}` : '—');

function duration(sec) {
  if (sec == null) return '—';
  if (sec < 60) return '< 1 min';
  const h = Math.floor(sec / 3600);
  const m = Math.round((sec % 3600) / 60);
  return h ? `${h} h ${m} min` : `${m} min`;
}

// Riassunto leggibile dello user agent: abbastanza per distinguere app/browser
// e dispositivo, senza una libreria di parsing dedicata.
function device(ua) {
  if (!ua) return 'Sconosciuto';
  const os = /Android/.test(ua) ? 'Android'
    : /iPhone|iPad/.test(ua) ? 'iOS'
    : /Windows/.test(ua) ? 'Windows'
    : /Mac OS X/.test(ua) ? 'macOS'
    : /Linux/.test(ua) ? 'Linux' : 'altro';
  if (/; wv\)/.test(ua)) return `App ${os}`;
  const browser = /Edg\//.test(ua) ? 'Edge'
    : /OPR\//.test(ua) ? 'Opera'
    : /Firefox\//.test(ua) ? 'Firefox'
    : /Chrome\//.test(ua) ? 'Chrome'
    : /Safari\//.test(ua) ? 'Safari' : 'Browser';
  return `${browser} su ${os}`;
}

function renewal(u) {
  if (u.subscription_status === 'trialing') return u.trial_ends_at ? `Fine prova ${day(u.trial_ends_at)}` : '—';
  if (u.subscription_status === 'active') return u.subscription_current_period_end ? `Rinnovo ${day(u.subscription_current_period_end)}` : '—';
  return '—';
}

function Badge({ status, map }) {
  const st = map[status] || Object.values(map).at(-1);
  return <span className={`whitespace-nowrap rounded-full px-2 py-0.5 text-xs font-semibold ${st.cls}`}>{st.label}</span>;
}

function UserName({ u }) {
  return (
    <>
      {u.name || <span className="text-muted">Senza nome</span>}
      {u.is_admin && <span className="ml-2 rounded-full bg-sidebar px-2 py-0.5 text-[10px] font-semibold text-white">ADMIN</span>}
    </>
  );
}

function Field({ label, children }) {
  return (
    <div className="min-w-0">
      <div className="text-[11px] font-semibold uppercase tracking-wide text-muted">{label}</div>
      <div className="mt-0.5 break-words text-sm">{children}</div>
    </div>
  );
}

function UserDetail({ id, onBack }) {
  const [data, setData] = useState(null);
  const [error, setError] = useState('');

  useEffect(() => {
    apiGet(`admin_users.php?id=${id}`).then(setData).catch((e) => setError(e.message));
  }, [id]);

  const back = (
    <button onClick={onBack} className="mb-4 flex items-center gap-1.5 text-sm font-medium text-muted hover:text-accent">
      <ArrowLeft size={16} /> Tutti gli utenti
    </button>
  );
  if (error) return <div>{back}<div className="text-expense">{error}</div></div>;
  if (!data) return <div>{back}<div className="text-muted">Caricamento...</div></div>;

  const { user: u, stats: s, sessions } = data;

  return (
    <div>
      {back}
      <div className="mb-4">
        <h4 className="text-xl font-bold"><UserName u={u} /></h4>
        <p className="text-sm text-muted">{u.email}</p>
      </div>

      <div className="card mb-4 grid grid-cols-2 gap-4 p-5 lg:grid-cols-4">
        <Field label="ID utente">{u.id}</Field>
        <Field label="Iscritto il">{dayTime(u.created_at)}</Field>
        <Field label="Email">
          {u.email_verified
            ? <span className="text-income">Verificata il {day(u.email_verified_at)}</span>
            : <span className="text-amber-700">Da verificare</span>}
        </Field>
        <Field label="Abbonamento"><Badge status={u.subscription_status} map={STATUS} /></Field>
        <Field label="Scadenza">{renewal(u)}</Field>
        <Field label="Digest email"><span className="capitalize">{u.digest_frequency}</span></Field>
        <Field label="Ultimo login">{dayTime(u.last_login_at)}</Field>
        <Field label="Ultima attività">{dayTime(u.last_seen_at)}</Field>
      </div>

      <div className="mb-4 grid grid-cols-2 gap-3 lg:grid-cols-4">
        <StatCard label="Login totali" value={s.logins_total} hint={`${s.logins_30d} negli ultimi 30 giorni`} icon={LogIn} />
        <StatCard label="Durata media" value={duration(s.avg_session_seconds)} icon={Timer} iconColor="#7fa876" />
        <StatCard label="Tempo totale" value={duration(s.total_session_seconds)} icon={Clock} iconColor="#d4a24c" />
        <StatCard label="IP diversi" value={s.distinct_ips} icon={Globe} />
      </div>

      <h2 className="mb-3 text-sm font-bold text-gray-800">Accessi</h2>
      <div className="card overflow-hidden">
        <div className="overflow-x-auto">
          <table className="w-full text-sm">
            <thead>
              <tr className="border-b border-border text-left text-[11px] uppercase tracking-wide text-muted">
                <th className="px-4 py-3">Login</th>
                <th className="px-4 py-3">Ultima attività</th>
                <th className="px-4 py-3">Durata</th>
                <th className="px-4 py-3">Stato</th>
                <th className="px-4 py-3">Dispositivo</th>
                <th className="px-4 py-3">IP</th>
              </tr>
            </thead>
            <tbody>
              {sessions.length ? sessions.map((x) => (
                <tr key={x.id} className="border-b border-border last:border-0">
                  <td className="whitespace-nowrap px-4 py-3">{dayTime(x.started_at)}</td>
                  <td className="whitespace-nowrap px-4 py-3 text-muted">{dayTime(x.ended_at || x.last_seen_at)}</td>
                  <td className="whitespace-nowrap px-4 py-3 font-mono">{duration(x.duration_seconds)}</td>
                  <td className="px-4 py-3"><Badge status={x.status} map={SESSION_STATUS} /></td>
                  <td className="whitespace-nowrap px-4 py-3" title={x.user_agent || ''}>{device(x.user_agent)}</td>
                  <td className="whitespace-nowrap px-4 py-3 font-mono text-xs text-muted">{x.ip || '—'}</td>
                </tr>
              )) : (
                <tr><td colSpan={6} className="px-4 py-8 text-center text-sm text-muted">Nessun accesso registrato (lo storico parte dall'11/10/2026)</td></tr>
              )}
            </tbody>
          </table>
        </div>
      </div>
      <p className="mt-2 text-xs text-muted">
        Una sessione senza logout risulta scaduta dopo 24 minuti di inattività; la sua durata arriva fino all'ultima
        attività registrata. Storico conservato per 180 giorni.
      </p>
    </div>
  );
}

export default function AdminUsers() {
  const [data, setData] = useState(null);
  const [error, setError] = useState('');
  const [query, setQuery] = useState('');
  const [selectedId, setSelectedId] = useState(null);

  useEffect(() => {
    apiGet('admin_users.php').then(setData).catch((e) => setError(e.message));
  }, []);

  if (selectedId) return <UserDetail id={selectedId} onBack={() => setSelectedId(null)} />;
  if (error) return <div className="text-expense">{error}</div>;
  if (!data) return <div className="text-muted">Caricamento...</div>;

  const q = query.trim().toLowerCase();
  const rows = q
    ? data.users.filter((u) => u.email.toLowerCase().includes(q) || (u.name || '').toLowerCase().includes(q))
    : data.users;
  const s = data.summary;

  return (
    <div>
      <div className="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <h4 className="text-xl font-bold">Utenti registrati</h4>
        <input
          className="field w-full sm:w-64"
          placeholder="Cerca email o nome..."
          value={query}
          onChange={(e) => setQuery(e.target.value)}
        />
      </div>

      <div className="mb-4 rounded-xl border border-accent/20 bg-accentSoft px-4 py-3 text-sm text-gray-700">
        Area riservata agli amministratori. Qui ci sono solo i dati dell'account e degli accessi: nessun dato
        finanziario degli utenti (conti, transazioni, investimenti) è visibile. Clicca un utente per il dettaglio.
      </div>

      <div className="mb-4 grid grid-cols-2 gap-3 lg:grid-cols-5">
        <StatCard label="Totale" value={s.total} icon={Users} />
        <StatCard label="Email verificata" value={s.verified} icon={BadgeCheck} iconColor="#7fa876" />
        <StatCard label="Abbonati" value={s.active} icon={CreditCard} iconColor="#7fa876" />
        <StatCard label="In prova" value={s.trialing} icon={Hourglass} iconColor="#d4a24c" />
        <StatCard label="Nuovi (30 gg)" value={s.new_30d} icon={UserPlus} />
      </div>

      <div className="card overflow-hidden">
        <div className="overflow-x-auto">
          <table className="w-full text-sm">
            <thead>
              <tr className="border-b border-border text-left text-[11px] uppercase tracking-wide text-muted">
                <th className="px-4 py-3">Utente</th>
                <th className="px-4 py-3">Iscritto il</th>
                <th className="px-4 py-3">Email</th>
                <th className="px-4 py-3">Abbonamento</th>
                <th className="px-4 py-3">Scadenza</th>
                <th className="px-4 py-3">Ultimo accesso</th>
                <th className="px-4 py-3"></th>
              </tr>
            </thead>
            <tbody>
              {rows.length ? rows.map((u) => (
                <tr
                  key={u.id}
                  onClick={() => setSelectedId(u.id)}
                  className="cursor-pointer border-b border-border last:border-0 hover:bg-gray-50"
                >
                  <td className="px-4 py-3">
                    <div className="font-medium"><UserName u={u} /></div>
                    <div className="text-xs text-muted">{u.email}</div>
                  </td>
                  <td className="whitespace-nowrap px-4 py-3">{day(u.created_at)}</td>
                  <td className="px-4 py-3">
                    {u.email_verified
                      ? <span className="text-income">Verificata</span>
                      : <span className="text-amber-700">Da verificare</span>}
                  </td>
                  <td className="px-4 py-3"><Badge status={u.subscription_status} map={STATUS} /></td>
                  <td className="whitespace-nowrap px-4 py-3 text-muted">{renewal(u)}</td>
                  <td className="whitespace-nowrap px-4 py-3 text-muted">{dayTime(u.last_seen_at)}</td>
                  <td className="px-4 py-3 text-right text-muted"><ChevronRight size={16} /></td>
                </tr>
              )) : (
                <tr><td colSpan={7} className="px-4 py-8 text-center text-sm text-muted">Nessun utente trovato</td></tr>
              )}
            </tbody>
          </table>
        </div>
      </div>
    </div>
  );
}
