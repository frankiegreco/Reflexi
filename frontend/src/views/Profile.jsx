import { useState } from 'react';
import { useAuth } from '../context/AuthContext';
import { apiSend } from '../api';

export default function Profile() {
  const { user, setUser, logout } = useAuth();
  const [name, setName] = useState(user?.name || '');
  const [nameMsg, setNameMsg] = useState('');
  const [digest, setDigest] = useState(user?.digest_frequency || 'nessuno');
  const [digestMsg, setDigestMsg] = useState('');
  const [subLoading, setSubLoading] = useState(false);
  const [subError, setSubError] = useState('');
  const [pwd, setPwd] = useState({ current_password: '', new_password: '', confirm: '' });
  const [pwdMsg, setPwdMsg] = useState('');
  const [pwdErr, setPwdErr] = useState('');
  const [deleteOpen, setDeleteOpen] = useState(false);
  const [deletePassword, setDeletePassword] = useState('');
  const [deleteError, setDeleteError] = useState('');
  const [deleting, setDeleting] = useState(false);

  async function handleDeleteAccount(e) {
    e.preventDefault();
    setDeleteError('');
    setDeleting(true);
    try {
      await apiSend('auth/delete_account.php', 'POST', { password: deletePassword });
      await logout();
    } catch (err) {
      setDeleteError(err.message);
      setDeleting(false);
    }
  }

  async function subscribe(plan) {
    setSubLoading(true); setSubError('');
    try {
      const res = await apiSend('stripe_checkout.php', 'POST', { plan });
      window.location.href = res.url;
    } catch (err) {
      setSubError(err.message);
      setSubLoading(false);
    }
  }

  async function openBillingPortal() {
    setSubLoading(true); setSubError('');
    try {
      const res = await apiSend('stripe_portal.php', 'POST', {});
      window.location.href = res.url;
    } catch (err) {
      setSubError(err.message);
      setSubLoading(false);
    }
  }

  async function saveName(e) {
    e.preventDefault();
    setNameMsg('');
    const updated = await apiSend('auth/me.php', 'PUT', { name });
    if (setUser) setUser(updated);
    setNameMsg('Nome aggiornato.');
  }

  async function saveDigest(value) {
    setDigest(value);
    setDigestMsg('');
    const updated = await apiSend('auth/me.php', 'PUT', { digest_frequency: value });
    if (setUser) setUser(updated);
    setDigestMsg('Preferenza salvata.');
  }

  async function changePassword(e) {
    e.preventDefault();
    setPwdErr(''); setPwdMsg('');
    if (pwd.new_password !== pwd.confirm) { setPwdErr('Le due password non coincidono.'); return; }
    try {
      await apiSend('auth/change_password.php', 'POST', {
        current_password: pwd.current_password, new_password: pwd.new_password,
      });
      setPwdMsg('Password aggiornata.');
      setPwd({ current_password: '', new_password: '', confirm: '' });
    } catch (err) {
      setPwdErr(err.message);
    }
  }

  return (
    <div className="max-w-xl">
      <h4 className="mb-4 text-xl font-bold">Profilo</h4>

      <div className="card mb-4 p-5">
        <h6 className="mb-3 text-sm font-semibold">Dati account</h6>
        <p className="mb-3 text-sm text-muted">Email: <span className="font-medium text-gray-900">{user?.email}</span></p>
        {nameMsg && <div className="mb-3 rounded-lg bg-emerald-50 px-3 py-2 text-sm text-emerald-700">{nameMsg}</div>}
        <form onSubmit={saveName} className="flex items-end gap-2">
          <div className="flex-1">
            <label className="field-label">Nome</label>
            <input className="field" value={name} onChange={(e) => setName(e.target.value)} />
          </div>
          <button className="btn-accent">Salva</button>
        </form>
      </div>

      <div className="card mb-4 p-5">
        <h6 className="mb-3 text-sm font-semibold">Abbonamento</h6>
        {subError && <div className="mb-3 rounded-lg bg-red-50 px-3 py-2 text-sm text-red-600">{subError}</div>}

        {user?.subscription_status === 'trialing' && (
          <>
            <p className="mb-3 text-sm text-muted">
              Sei nel periodo di prova gratuito — <span className="font-semibold text-gray-900">{user.trial_days_left} giorni rimasti</span>.
            </p>
            <div className="flex flex-wrap gap-2">
              <button onClick={() => subscribe('mensile')} disabled={subLoading} className="btn-outline">Passa al mensile — 7,99€/mese</button>
              <button onClick={() => subscribe('annuale')} disabled={subLoading} className="btn-accent">Passa all'annuale — 79€/anno</button>
            </div>
          </>
        )}

        {(user?.subscription_status === 'active' || user?.subscription_status === 'past_due') && (
          <>
            <p className="mb-3 text-sm text-muted">
              Abbonamento <span className="font-semibold text-income">attivo</span>
              {user.subscription_status === 'past_due' && <span className="font-semibold text-expense"> — pagamento in ritardo</span>}.
            </p>
            <button onClick={openBillingPortal} disabled={subLoading} className="btn-outline">Gestisci abbonamento</button>
          </>
        )}

        {(user?.subscription_status === 'none' || user?.subscription_status === 'canceled') && (
          <>
            <p className="mb-3 text-sm text-muted">Nessun abbonamento attivo.</p>
            <div className="flex flex-wrap gap-2">
              <button onClick={() => subscribe('mensile')} disabled={subLoading} className="btn-outline">Abbonati — 7,99€/mese</button>
              <button onClick={() => subscribe('annuale')} disabled={subLoading} className="btn-accent">Abbonati — 79€/anno</button>
            </div>
          </>
        )}

        <div className="mt-4 flex items-center gap-1.5 border-t border-border pt-3 text-xs text-muted">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2"><rect x="3" y="11" width="18" height="10" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
          Pagamenti gestiti in sicurezza da Stripe — non conserviamo mai i dati della tua carta
        </div>
      </div>

      <div className="card mb-4 p-5">
        <h6 className="mb-1 text-sm font-semibold">Riepilogo periodico via email</h6>
        <p className="mb-3 text-xs text-muted">Ricevi un'email con quanto hai speso, risparmiato e lo stato dei tuoi mutui, senza dover aprire l'app.</p>
        {digestMsg && <div className="mb-3 rounded-lg bg-emerald-50 px-3 py-2 text-sm text-emerald-700">{digestMsg}</div>}
        <div className="flex flex-wrap gap-2">
          {[['nessuno', 'Disattivato'], ['settimanale', 'Settimanale'], ['mensile', 'Mensile']].map(([value, label]) => (
            <button
              key={value}
              onClick={() => saveDigest(value)}
              className={`rounded-lg px-4 py-2 text-sm font-semibold ${digest === value ? 'bg-accent text-white' : 'border border-border text-gray-700 hover:bg-gray-50'}`}
            >
              {label}
            </button>
          ))}
        </div>
      </div>

      <div className="card p-5">
        <h6 className="mb-3 text-sm font-semibold">Cambia password</h6>
        {pwdErr && <div className="mb-3 rounded-lg bg-red-50 px-3 py-2 text-sm text-red-600">{pwdErr}</div>}
        {pwdMsg && <div className="mb-3 rounded-lg bg-emerald-50 px-3 py-2 text-sm text-emerald-700">{pwdMsg}</div>}
        <form onSubmit={changePassword} className="space-y-3">
          <div>
            <label className="field-label">Password attuale</label>
            <input className="field" type="password" required value={pwd.current_password}
              onChange={(e) => setPwd((p) => ({ ...p, current_password: e.target.value }))} />
          </div>
          <div>
            <label className="field-label">Nuova password (min. 8 caratteri)</label>
            <input className="field" type="password" required minLength={8} value={pwd.new_password}
              onChange={(e) => setPwd((p) => ({ ...p, new_password: e.target.value }))} />
          </div>
          <div>
            <label className="field-label">Conferma nuova password</label>
            <input className="field" type="password" required value={pwd.confirm}
              onChange={(e) => setPwd((p) => ({ ...p, confirm: e.target.value }))} />
          </div>
          <button className="btn-accent">Aggiorna password</button>
        </form>
      </div>

      <div className="card mb-4 border-expense/30 p-5">
        <h6 className="mb-1 text-sm font-semibold text-expense">Elimina account</h6>
        <p className="mb-3 text-sm text-muted">
          Cancella definitivamente il tuo account e tutti i dati collegati (conti, transazioni, mutui, investimenti).
          Questa azione non può essere annullata.
        </p>
        {!deleteOpen ? (
          <button className="btn-outline-danger" onClick={() => setDeleteOpen(true)}>Elimina il mio account</button>
        ) : (
          <form onSubmit={handleDeleteAccount} className="space-y-3">
            {deleteError && <div className="rounded-lg bg-red-50 px-3 py-2 text-sm text-red-600">{deleteError}</div>}
            <div>
              <label className="field-label">Conferma con la tua password</label>
              <input className="field" type="password" required value={deletePassword} onChange={(e) => setDeletePassword(e.target.value)} />
            </div>
            <div className="flex gap-2">
              <button type="button" className="btn-outline" onClick={() => { setDeleteOpen(false); setDeletePassword(''); setDeleteError(''); }}>Annulla</button>
              <button type="submit" className="btn-accent bg-expense hover:bg-red-600" disabled={deleting}>
                {deleting ? 'Eliminazione in corso...' : 'Conferma eliminazione definitiva'}
              </button>
            </div>
          </form>
        )}
      </div>
    </div>
  );
}
