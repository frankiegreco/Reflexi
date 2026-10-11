import { useState, useEffect } from 'react';
import { Users, BadgeCheck, CreditCard, Hourglass, UserPlus } from 'lucide-react';
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

// I datetime MySQL arrivano come "YYYY-MM-DD HH:MM:SS": formatDate vuole solo la data.
const day = (dt) => (dt ? formatDate(dt.slice(0, 10)) : '—');

function renewal(u) {
  if (u.subscription_status === 'trialing') return u.trial_ends_at ? `Fine prova ${day(u.trial_ends_at)}` : '—';
  if (u.subscription_status === 'active') return u.subscription_current_period_end ? `Rinnovo ${day(u.subscription_current_period_end)}` : '—';
  return '—';
}

export default function AdminUsers() {
  const [data, setData] = useState(null);
  const [error, setError] = useState('');
  const [query, setQuery] = useState('');

  useEffect(() => {
    apiGet('admin_users.php').then(setData).catch((e) => setError(e.message));
  }, []);

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
        Area riservata agli amministratori. Qui ci sono solo i dati dell'account: nessun dato finanziario degli utenti
        (conti, transazioni, investimenti) è visibile.
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
                <th className="px-4 py-3">Digest</th>
              </tr>
            </thead>
            <tbody>
              {rows.length ? rows.map((u) => {
                const st = STATUS[u.subscription_status] || STATUS.none;
                return (
                  <tr key={u.id} className="border-b border-border last:border-0 hover:bg-gray-50">
                    <td className="px-4 py-3">
                      <div className="font-medium">
                        {u.name || <span className="text-muted">Senza nome</span>}
                        {u.is_admin && <span className="ml-2 rounded-full bg-sidebar px-2 py-0.5 text-[10px] font-semibold text-white">ADMIN</span>}
                      </div>
                      <div className="text-xs text-muted">{u.email}</div>
                    </td>
                    <td className="whitespace-nowrap px-4 py-3">{day(u.created_at)}</td>
                    <td className="px-4 py-3">
                      {u.email_verified
                        ? <span className="text-income">Verificata</span>
                        : <span className="text-amber-700">Da verificare</span>}
                    </td>
                    <td className="px-4 py-3">
                      <span className={`whitespace-nowrap rounded-full px-2 py-0.5 text-xs font-semibold ${st.cls}`}>{st.label}</span>
                    </td>
                    <td className="whitespace-nowrap px-4 py-3 text-muted">{renewal(u)}</td>
                    <td className="px-4 py-3 capitalize text-muted">{u.digest_frequency}</td>
                  </tr>
                );
              }) : (
                <tr><td colSpan={6} className="px-4 py-8 text-center text-sm text-muted">Nessun utente trovato</td></tr>
              )}
            </tbody>
          </table>
        </div>
      </div>
    </div>
  );
}
