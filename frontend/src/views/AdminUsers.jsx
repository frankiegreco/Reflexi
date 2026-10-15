import { useState, useEffect } from 'react';
import { Users, BadgeCheck, CreditCard, Hourglass, UserPlus, ArrowLeft, LogIn, Timer, Clock, Globe, ChevronRight, ShieldAlert, Pencil, MailCheck, KeyRound, CalendarClock, Trash2, Send, BellRing } from 'lucide-react';
import { apiGet, apiSend } from '../api';
import Modal from '../components/Modal';
import { useAuth } from '../context/AuthContext';
import { day, dayTime, duration, device } from '../lib/admin';
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

// Dati del modulo di registrazione: NULL per gli account creati prima.
const EXPERIENCE = { principiante: 'Principiante', intermedio: 'Intermedio', avanzato: 'Avanzato' };
const PLAN = { mensile: 'Mensile', annuale: 'Annuale' };
const notGiven = <span className="text-muted">Non indicato</span>;

function Phone({ phone }) {
  if (!phone) return notGiven;
  return <a href={`tel:${phone.replace(/[^\d+]/g, '')}`} className="whitespace-nowrap text-accent hover:underline" onClick={(e) => e.stopPropagation()}>{phone}</a>;
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

// Account precedenti alla migrazione 062: nome e cognome dal vecchio campo unico.
function splitName(u) {
  const parts = (u.name || '').trim().split(/\s+/);
  return { first: u.first_name ?? parts[0] ?? '', last: u.last_name ?? parts.slice(1).join(' ') };
}

function EditUserModal({ user, onClose, onSaved }) {
  const { first, last } = splitName(user);
  const [form, setForm] = useState({
    first_name: first,
    last_name: last,
    email: user.email,
    phone: user.phone || '',
    finance_experience: user.finance_experience || '',
    digest_frequency: user.digest_frequency,
  });
  const [error, setError] = useState('');
  const [saving, setSaving] = useState(false);
  const set = (key) => (e) => setForm((f) => ({ ...f, [key]: e.target.value }));

  async function save(e) {
    e.preventDefault();
    setError(''); setSaving(true);
    try {
      const res = await apiSend(`admin_users.php?id=${user.id}`, 'PUT', { ...form, finance_experience: form.finance_experience || null });
      onSaved(res);
    } catch (err) {
      setError(err.message);
      setSaving(false);
    }
  }

  return (
    <Modal
      open
      onClose={onClose}
      title="Modifica dati utente"
      footer={(
        <>
          <button type="button" className="btn-outline" onClick={onClose}>Annulla</button>
          <button type="submit" form="admin-edit-user" className="btn-accent" disabled={saving}>{saving ? 'Salvataggio...' : 'Salva'}</button>
        </>
      )}
    >
      <form id="admin-edit-user" onSubmit={save} className="space-y-3">
        {error && <div className="rounded-lg bg-red-50 px-3 py-2 text-sm text-red-600">{error}</div>}
        <div className="grid gap-3 sm:grid-cols-2">
          <div>
            <label className="field-label" htmlFor="adm-first-name">Nome</label>
            <input id="adm-first-name" className="field" required maxLength={75} value={form.first_name} onChange={set('first_name')} />
          </div>
          <div>
            <label className="field-label" htmlFor="adm-last-name">Cognome</label>
            <input id="adm-last-name" className="field" required maxLength={75} value={form.last_name} onChange={set('last_name')} />
          </div>
        </div>
        <div>
          <label className="field-label" htmlFor="adm-email">Email</label>
          <input id="adm-email" className="field" type="email" required maxLength={190} value={form.email} onChange={set('email')} />
          {form.email.trim().toLowerCase() !== user.email && (
            <p className="mt-1 text-xs text-muted">L'utente dovrà accedere con il nuovo indirizzo. Se ha un abbonamento, l'email viene aggiornata anche su Stripe.</p>
          )}
        </div>
        <div className="grid gap-3 sm:grid-cols-2">
          <div>
            <label className="field-label" htmlFor="adm-phone">Telefono</label>
            <input id="adm-phone" className="field" type="tel" maxLength={25} pattern="\+?[0-9 .\(\)\/\-]{6,25}"
              title="Solo cifre, con + iniziale facoltativo" value={form.phone} onChange={set('phone')} />
          </div>
          <div>
            <label className="field-label" htmlFor="adm-experience">Esperienza in finanza</label>
            <select id="adm-experience" className="field" value={form.finance_experience} onChange={set('finance_experience')}>
              <option value="">Non indicata</option>
              {Object.entries(EXPERIENCE).map(([value, label]) => <option key={value} value={value}>{label}</option>)}
            </select>
          </div>
        </div>
        <div>
          <label className="field-label" htmlFor="adm-digest">Riepilogo email</label>
          <select id="adm-digest" className="field" value={form.digest_frequency} onChange={set('digest_frequency')}>
            <option value="nessuno">Disattivato</option>
            <option value="settimanale">Settimanale</option>
            <option value="mensile">Mensile</option>
          </select>
        </div>
      </form>
    </Modal>
  );
}

// Data di fine prova per l'input date: quella attuale, o tra 30 giorni se non c'è.
function trialInputValue(u) {
  if (u.trial_ends_at) return u.trial_ends_at.slice(0, 10);
  const d = new Date(Date.now() + 30 * 86400000);
  return d.toISOString().slice(0, 10);
}

function UserActions({ user, onDone, onRemind }) {
  const [busy, setBusy] = useState('');
  const [trialDate, setTrialDate] = useState(trialInputValue(user));
  const trialEditable = !user.has_subscription && !['active', 'past_due'].includes(user.subscription_status);

  async function run(action, confirmText, body = {}) {
    if (confirmText && !window.confirm(confirmText)) return;
    setBusy(action);
    try {
      const res = await apiSend(`admin_users.php?id=${user.id}&action=${action}`, 'POST', body);
      onDone({ user: res.user, message: res.message });
    } catch (err) {
      onDone({ error: err.message });
    } finally {
      setBusy('');
    }
  }

  return (
    <div className="card mb-4 p-5">
      <h2 className="mb-3 text-sm font-bold text-gray-800">Azioni</h2>
      <div className="flex flex-wrap gap-2">
        {!user.email_verified && (
          <button className="btn-outline flex items-center gap-1.5" disabled={!!busy}
            onClick={() => run('verify_email', `Segnare come verificata l'email ${user.email}? L'utente potrà accedere senza il link di conferma.`)}>
            <MailCheck size={16} /> Segna email come verificata
          </button>
        )}
        <button className="btn-outline flex items-center gap-1.5" disabled={!!busy}
          onClick={() => run('send_password_reset', `Inviare a ${user.email} un link per reimpostare la password (valido 24 ore)?`)}>
          <KeyRound size={16} /> {busy === 'send_password_reset' ? 'Invio...' : 'Invia link reset password'}
        </button>
        <button className="btn-outline flex items-center gap-1.5" disabled={!!busy || !user.reminders_enabled} onClick={onRemind}
          title={user.reminders_enabled ? '' : "L'utente ha disattivato i promemoria"}>
          <BellRing size={16} /> Invia promemoria
        </button>
      </div>

      <div className="mt-4 border-t border-border pt-4">
        <div className="mb-1 flex items-center gap-1.5 text-sm font-semibold"><CalendarClock size={16} /> Fine del periodo di prova</div>
        {trialEditable ? (
          <form className="flex flex-wrap items-end gap-2"
            onSubmit={(e) => { e.preventDefault(); run('set_trial_end', `Impostare la fine della prova al ${trialDate.split('-').reverse().join('/')}? L'utente tornerà in prova fino a quella data.`, { trial_ends_on: trialDate }); }}>
            <input type="date" className="field w-auto" required value={trialDate} onChange={(e) => setTrialDate(e.target.value)} />
            <button className="btn-outline" disabled={!!busy}>Imposta</button>
          </form>
        ) : (
          <p className="text-sm text-muted">L'utente ha un abbonamento: la prova e i rinnovi si gestiscono da Stripe.</p>
        )}
      </div>
    </div>
  );
}

// Promemoria per tornare a usare l'app (lib_reminders.php). Con `user`: al
// singolo utente; senza: a tutti gli inattivi da N giorni, con anteprima.
const REMINDER_DAYS = [7, 14, 30, 60, 90];

function ReminderModal({ user, onClose, onSent }) {
  const [days, setDays] = useState(30);
  const [includeUnknown, setIncludeUnknown] = useState(false);
  const [preview, setPreview] = useState(null);
  const [message, setMessage] = useState('');
  const [error, setError] = useState('');
  const [sending, setSending] = useState(false);

  useEffect(() => {
    if (user) return;
    setPreview(null);
    apiGet(`admin_reminders.php?days=${days}&include_unknown=${includeUnknown ? 1 : 0}`)
      .then(setPreview).catch((e) => setError(e.message));
  }, [user, days, includeUnknown]);

  async function send(e) {
    e.preventDefault();
    if (!user && !window.confirm(`Inviare il promemoria a ${preview.count} ${preview.count === 1 ? 'utente' : 'utenti'}?`)) return;
    setError(''); setSending(true);
    try {
      const res = user
        ? await apiSend(`admin_users.php?id=${user.id}&action=send_reminder`, 'POST', { message })
        : await apiSend('admin_reminders.php', 'POST', { days, include_unknown: includeUnknown, message });
      onSent(res);
    } catch (err) {
      setError(err.message);
      setSending(false);
    }
  }

  const canSend = user || (preview && preview.count > 0);
  return (
    <Modal
      open
      onClose={onClose}
      title={user ? 'Invia promemoria' : 'Promemoria agli utenti inattivi'}
      footer={(
        <>
          <button type="button" className="btn-outline" onClick={onClose}>Annulla</button>
          <button type="submit" form="admin-reminder" className="btn-accent flex items-center gap-1.5" disabled={!canSend || sending}>
            <Send size={16} /> {sending ? 'Invio...' : user ? 'Invia promemoria' : `Invia a ${preview?.count ?? '…'}`}
          </button>
        </>
      )}
    >
      <form id="admin-reminder" onSubmit={send} className="space-y-3 text-sm">
        {error && <div className="rounded-lg bg-red-50 px-3 py-2 text-red-600">{error}</div>}
        {user ? (
          <p className="text-muted">
            {user.email} riceverà un'email con l'invito a tornare su Reflexi
            {!user.email_verified && ', con il link per confermare l\'email (non ancora verificata)'}.
          </p>
        ) : (
          <>
            <p className="text-muted">
              Un'email che invita a tornare su Reflexi. Esclusi gli admin, chi ha disattivato i promemoria dal Profilo
              e chi ne ha già ricevuto uno negli ultimi {preview?.bulk_gap_days ?? 7} giorni.
            </p>
            <div>
              <label className="field-label" htmlFor="rem-days">Utenti senza accessi da almeno</label>
              <select id="rem-days" className="field" value={days} onChange={(e) => setDays(Number(e.target.value))}>
                {REMINDER_DAYS.map((d) => <option key={d} value={d}>{d} giorni</option>)}
              </select>
            </div>
            <label className="flex items-start gap-2 text-gray-700">
              <input type="checkbox" className="mt-0.5" checked={includeUnknown} onChange={(e) => setIncludeUnknown(e.target.checked)} />
              <span>
                Includi chi non ha accessi registrati: lo storico parte dal {preview ? day(preview.history_start) : '11/10/2026'},
                quindi potrebbe anche usare l'app ogni giorno.
              </span>
            </label>
            <div className="rounded-lg border border-border bg-gray-50 px-3 py-2">
              {!preview ? <span className="text-muted">Calcolo dei destinatari...</span> : preview.count === 0 ? (
                <span className="text-muted">Nessun utente da avvisare con questi criteri.</span>
              ) : (
                <>
                  <div className="mb-1 font-semibold">{preview.count} {preview.count === 1 ? 'destinatario' : 'destinatari'}{preview.capped && ' (massimo per invio)'}</div>
                  <ul className="max-h-36 space-y-0.5 overflow-y-auto text-xs text-muted">
                    {preview.users.map((x) => (
                      <li key={x.id} className="flex justify-between gap-2">
                        <span className="min-w-0 truncate">{x.name || x.email}</span>
                        <span className="whitespace-nowrap">{x.last_activity ? `ultimo accesso ${day(x.last_activity)}` : 'accessi non registrati'}</span>
                      </li>
                    ))}
                  </ul>
                </>
              )}
            </div>
          </>
        )}
        <div>
          <label className="field-label" htmlFor="rem-message">Messaggio personale (facoltativo)</label>
          <textarea id="rem-message" className="field min-h-[90px]" maxLength={1000} value={message} onChange={(e) => setMessage(e.target.value)}
            placeholder="Compare nell'email, sopra il pulsante" />
        </div>
      </form>
    </Modal>
  );
}

function RemindersSection({ reloadKey }) {
  const [rows, setRows] = useState(null);
  const [error, setError] = useState('');

  useEffect(() => {
    apiGet('admin_reminders.php').then((d) => setRows(d.reminders)).catch((e) => setError(e.message));
  }, [reloadKey]);

  if (error) return <div className="mt-6 text-sm text-expense">{error}</div>;
  if (!rows) return null;

  return (
    <div className="mt-6">
      <h2 className="mb-3 text-sm font-bold text-gray-800">Promemoria inviati</h2>
      <div className="card overflow-hidden">
        <div className="overflow-x-auto">
          <table className="w-full text-sm">
            <thead>
              <tr className="border-b border-border text-left text-[11px] uppercase tracking-wide text-muted">
                <th className="px-4 py-3">Utente</th>
                <th className="px-4 py-3">Inviato il</th>
                <th className="px-4 py-3">Dopo il promemoria</th>
              </tr>
            </thead>
            <tbody>
              {rows.length ? rows.map((r) => (
                <tr key={r.id} className="border-b border-border last:border-0">
                  <td className="px-4 py-3">
                    <div className="font-medium">{r.name || <span className="text-muted">Senza nome</span>}</div>
                    <div className="text-xs text-muted">{r.email}</div>
                  </td>
                  <td className="whitespace-nowrap px-4 py-3 text-muted">
                    {dayTime(r.sent_at)}
                    {r.sent_by_name && <div className="text-xs">da {r.sent_by_name}</div>}
                  </td>
                  <td className="px-4 py-3">
                    {r.returned
                      ? <span className="whitespace-nowrap rounded-full bg-emerald-50 px-2 py-0.5 text-xs font-semibold text-emerald-700">Tornato il {day(r.last_seen_at)}</span>
                      : <span className="whitespace-nowrap rounded-full bg-gray-100 px-2 py-0.5 text-xs font-semibold text-gray-600">Non ancora</span>}
                  </td>
                </tr>
              )) : (
                <tr><td colSpan={3} className="px-4 py-8 text-center text-sm text-muted">Nessun promemoria inviato. Usa "Promemoria" per avvisare gli utenti inattivi.</td></tr>
              )}
            </tbody>
          </table>
        </div>
      </div>
    </div>
  );
}

function DeleteUserModal({ user, onClose, onDeleted }) {
  const [confirmEmail, setConfirmEmail] = useState('');
  const [error, setError] = useState('');
  const [deleting, setDeleting] = useState(false);
  const matches = confirmEmail.trim().toLowerCase() === user.email;

  async function remove(e) {
    e.preventDefault();
    setError(''); setDeleting(true);
    try {
      const res = await apiSend(`admin_users.php?id=${user.id}&action=delete`, 'POST', { confirm_email: confirmEmail });
      onDeleted(res.message);
    } catch (err) {
      setError(err.message);
      setDeleting(false);
    }
  }

  return (
    <Modal
      open
      onClose={onClose}
      title="Elimina utente"
      footer={(
        <>
          <button type="button" className="btn-outline" onClick={onClose}>Annulla</button>
          <button type="submit" form="admin-delete-user" className="btn-accent bg-expense hover:bg-red-600" disabled={!matches || deleting}>
            {deleting ? 'Eliminazione...' : 'Elimina definitivamente'}
          </button>
        </>
      )}
    >
      <form id="admin-delete-user" onSubmit={remove} className="space-y-3 text-sm">
        {error && <div className="rounded-lg bg-red-50 px-3 py-2 text-red-600">{error}</div>}
        <p>
          Stai per eliminare <span className="font-semibold">{user.name || user.email}</span> e tutti i suoi dati
          (conti, transazioni, mutui, investimenti, storico accessi). L'operazione non si può annullare.
        </p>
        {user.has_subscription && (
          <p className="rounded-lg bg-amber-50 px-3 py-2 text-amber-800">
            L'utente ha un abbonamento: viene annullato subito su Stripe, senza rimborso della parte non usata.
          </p>
        )}
        <div>
          <label className="field-label" htmlFor="adm-delete-confirm">Per confermare scrivi l'email dell'utente</label>
          <input id="adm-delete-confirm" className="field" autoComplete="off" placeholder={user.email}
            value={confirmEmail} onChange={(e) => setConfirmEmail(e.target.value)} />
        </div>
      </form>
    </Modal>
  );
}

function UserDetail({ id, onBack, currentUserId }) {
  const [data, setData] = useState(null);
  const [error, setError] = useState('');
  const [editing, setEditing] = useState(false);
  const [deleting, setDeleting] = useState(false);
  const [reminding, setReminding] = useState(false);
  const [notice, setNotice] = useState(null); // { type: 'ok' | 'warning' | 'error', text }

  useEffect(() => {
    apiGet(`admin_users.php?id=${id}`).then(setData).catch((e) => setError(e.message));
  }, [id]);

  function applyResult({ user, message, warning, error: err }) {
    if (user) setData((d) => ({ ...d, user }));
    if (err) setNotice({ type: 'error', text: err });
    else if (warning) setNotice({ type: 'warning', text: warning });
    else setNotice({ type: 'ok', text: message });
  }

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
      <div className="mb-4 flex flex-wrap items-start justify-between gap-3">
        <div className="min-w-0">
          <h4 className="text-xl font-bold"><UserName u={u} /></h4>
          <p className="break-all text-sm text-muted">{u.email}</p>
        </div>
        <button className="btn-outline flex items-center gap-1.5" onClick={() => { setNotice(null); setEditing(true); }}>
          <Pencil size={16} /> Modifica dati
        </button>
      </div>

      {notice && (
        <div className={`mb-4 rounded-lg px-3 py-2 text-sm ${{ ok: 'bg-emerald-50 text-emerald-700', warning: 'bg-amber-50 text-amber-800', error: 'bg-red-50 text-red-600' }[notice.type]}`}>
          {notice.text}
        </div>
      )}
      {editing && (
        <EditUserModal user={u} onClose={() => setEditing(false)} onSaved={(res) => { setEditing(false); applyResult(res); }} />
      )}

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
        <Field label="Telefono"><Phone phone={u.phone} /></Field>
        <Field label="Esperienza in finanza">{EXPERIENCE[u.finance_experience] || notGiven}</Field>
        <Field label="Piano scelto all'iscrizione">{PLAN[u.preferred_plan] || notGiven}</Field>
        <Field label="Promemoria">
          {u.reminders_enabled ? 'Attivi' : <span className="text-amber-700">Disattivati dall'utente</span>}
          {u.last_reminder_at && <div className="text-xs text-muted">Ultimo: {dayTime(u.last_reminder_at)}</div>}
        </Field>
        <Field label="Ultimo login">{dayTime(u.last_login_at)}</Field>
        <Field label="Ultima attività">{dayTime(u.last_seen_at)}</Field>
      </div>

      <UserActions key={`${u.id}-${u.trial_ends_at}`} user={u} onDone={applyResult} onRemind={() => { setNotice(null); setReminding(true); }} />
      {reminding && (
        <ReminderModal user={u} onClose={() => setReminding(false)} onSent={(res) => { setReminding(false); applyResult(res); }} />
      )}

      <div className="mb-4 grid grid-cols-2 gap-3 lg:grid-cols-5">
        <StatCard label="Login totali" value={s.logins_total} hint={`${s.logins_30d} negli ultimi 30 giorni`} icon={LogIn} />
        <StatCard label="Durata media" value={duration(s.avg_session_seconds)} icon={Timer} iconColor="#7fa876" />
        <StatCard label="Tempo totale" value={duration(s.total_session_seconds)} icon={Clock} iconColor="#d4a24c" />
        <StatCard label="IP diversi" value={s.distinct_ips} icon={Globe} />
        <StatCard
          label="Tentativi falliti (30 gg)"
          value={s.failed_logins_30d}
          valueClass={s.failed_logins_30d > 0 ? 'text-expense' : ''}
          icon={ShieldAlert}
          iconColor="#c1665a"
        />
      </div>

      {deleting && (
        <DeleteUserModal user={u} onClose={() => setDeleting(false)} onDeleted={(message) => onBack(message)} />
      )}

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

      <div className="card mt-6 border-expense/30 p-5">
        <h2 className="mb-1 text-sm font-bold text-expense">Elimina utente</h2>
        {u.is_admin || u.id === currentUserId ? (
          <p className="text-sm text-muted">Gli account admin non si eliminano da qui (solo via SQL; il tuo dal Profilo).</p>
        ) : (
          <>
            <p className="mb-3 text-sm text-muted">Cancella definitivamente l'account e tutti i suoi dati; se c'è un abbonamento viene annullato su Stripe.</p>
            <button className="btn-outline-danger flex items-center gap-1.5" onClick={() => { setNotice(null); setDeleting(true); }}>
              <Trash2 size={16} /> Elimina utente
            </button>
          </>
        )}
      </div>
    </div>
  );
}

export default function AdminUsers() {
  const [data, setData] = useState(null);
  const [error, setError] = useState('');
  const [query, setQuery] = useState('');
  const [statusFilter, setStatusFilter] = useState('');
  const [selectedId, setSelectedId] = useState(null);
  const [listNotice, setListNotice] = useState(null); // { type: 'ok' | 'error', text }
  const [reminderOpen, setReminderOpen] = useState(false);
  const [remindersKey, setRemindersKey] = useState(0);
  const { user: me } = useAuth();

  // Si ricarica a ogni ritorno dal dettaglio, dove i dati possono essere cambiati.
  useEffect(() => {
    if (selectedId) return;
    apiGet('admin_users.php').then(setData).catch((e) => setError(e.message));
  }, [selectedId]);

  if (selectedId) {
    return (
      <UserDetail
        id={selectedId}
        currentUserId={me?.id}
        onBack={(message) => { setListNotice(typeof message === 'string' ? { type: 'ok', text: message } : null); setSelectedId(null); }}
      />
    );
  }
  if (error) return <div className="text-expense">{error}</div>;
  if (!data) return <div className="text-muted">Caricamento...</div>;

  const q = query.trim().toLowerCase();
  // Ricerca per telefono solo se la query è fatta di cifre e separatori.
  const qPhone = /^[\d\s+().\/-]+$/.test(q) ? q.replace(/\D/g, '') : '';
  const rows = data.users.filter((u) => (!statusFilter || u.subscription_status === statusFilter) && (!q
    || u.email.toLowerCase().includes(q) || (u.name || '').toLowerCase().includes(q)
    || (qPhone !== '' && (u.phone || '').replace(/\D/g, '').includes(qPhone))));
  const s = data.summary;

  return (
    <div>
      <div className="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <h4 className="text-xl font-bold">Utenti registrati</h4>
        <div className="flex flex-col gap-2 sm:flex-row">
          <button className="btn-accent flex items-center justify-center gap-1.5" onClick={() => { setListNotice(null); setReminderOpen(true); }}>
            <BellRing size={16} /> Promemoria
          </button>
          <select className="field w-full sm:w-44" value={statusFilter} onChange={(e) => setStatusFilter(e.target.value)} aria-label="Filtra per abbonamento">
            <option value="">Tutti gli stati</option>
            {Object.entries(STATUS).map(([value, st]) => <option key={value} value={value}>{st.label}</option>)}
          </select>
          <input
            className="field w-full sm:w-64"
            placeholder="Cerca email, nome o telefono..."
            value={query}
            onChange={(e) => setQuery(e.target.value)}
          />
        </div>
      </div>

      {listNotice && (
        <div className={`mb-4 rounded-lg px-3 py-2 text-sm ${listNotice.type === 'error' ? 'bg-red-50 text-red-600' : 'bg-emerald-50 text-emerald-700'}`}>{listNotice.text}</div>
      )}
      {reminderOpen && (
        <ReminderModal
          onClose={() => setReminderOpen(false)}
          onSent={(res) => { setReminderOpen(false); setListNotice({ type: 'ok', text: res.message }); setRemindersKey((k) => k + 1); }}
        />
      )}

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
                <th className="px-4 py-3">Esperienza</th>
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
                    {u.phone && <div className="text-xs"><Phone phone={u.phone} /></div>}
                  </td>
                  <td className="whitespace-nowrap px-4 py-3">{day(u.created_at)}</td>
                  <td className="px-4 py-3">
                    {u.email_verified
                      ? <span className="text-income">Verificata</span>
                      : <span className="text-amber-700">Da verificare</span>}
                  </td>
                  <td className="px-4 py-3"><Badge status={u.subscription_status} map={STATUS} /></td>
                  <td className="whitespace-nowrap px-4 py-3 text-muted">{EXPERIENCE[u.finance_experience] || '—'}</td>
                  <td className="whitespace-nowrap px-4 py-3 text-muted">{renewal(u)}</td>
                  <td className="whitespace-nowrap px-4 py-3 text-muted">{dayTime(u.last_seen_at)}</td>
                  <td className="px-4 py-3 text-right text-muted"><ChevronRight size={16} /></td>
                </tr>
              )) : (
                <tr><td colSpan={8} className="px-4 py-8 text-center text-sm text-muted">Nessun utente trovato</td></tr>
              )}
            </tbody>
          </table>
        </div>
      </div>

      <RemindersSection reloadKey={remindersKey} />
    </div>
  );
}
