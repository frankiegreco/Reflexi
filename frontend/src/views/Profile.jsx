import { useRef, useState } from 'react';
import { Capacitor } from '@capacitor/core';
import { useAuth } from '../context/AuthContext';
import { apiSend } from '../api';
import CheckoutForm from '../components/CheckoutForm';
import GuideCard from '../components/GuideCard';
import { usePricing, planLabels, PRICE_UNAVAILABLE } from '../lib/pricing';

// Nell'app nativa niente prezzi né pulsanti d'acquisto: l'abbonamento si
// gestisce solo dal sito web.
const isNative = Capacitor.isNativePlatform();

// "2026-11-30 10:00:00" (DATETIME dal server) -> "30 novembre 2026"
function formatDate(value) {
  const d = new Date(String(value).replace(' ', 'T'));
  return Number.isNaN(d.getTime()) ? value : d.toLocaleDateString('it-IT', { day: 'numeric', month: 'long', year: 'numeric' });
}

export default function Profile() {
  const { user, setUser, logout } = useAuth();
  // Account creati prima della migrazione 062: nome e cognome ricavati dal
  // vecchio campo unico "name" (prima parola = nome), da confermare salvando.
  const legacyName = (user?.name || '').trim().split(/\s+/);
  const [profile, setProfile] = useState({
    first_name: user?.first_name ?? legacyName[0] ?? '',
    last_name: user?.last_name ?? legacyName.slice(1).join(' '),
    phone: user?.phone || '',
    finance_experience: user?.finance_experience || '',
  });
  const [profileMsg, setProfileMsg] = useState('');
  const [profileErr, setProfileErr] = useState('');
  const [digest, setDigest] = useState(user?.digest_frequency || 'nessuno');
  const [digestMsg, setDigestMsg] = useState('');
  const [reminders, setReminders] = useState(user?.reminders_enabled !== false);
  const [subLoading, setSubLoading] = useState(false);
  const [subError, setSubError] = useState('');
  const [clientSecret, setClientSecret] = useState(null);
  // Blocca un secondo click prima che il re-render disabiliti il pulsante.
  const subBusy = useRef(false);
  const prices = planLabels(usePricing({ enabled: !isNative }));
  const monthlyLabel = prices.monthly ? `${prices.monthly}/mese` : PRICE_UNAVAILABLE;
  const annualLabel = prices.annual
    ? `${prices.annual}/anno${prices.annualPerMonth ? ` (circa ${prices.annualPerMonth}/mese)` : ''}`
    : PRICE_UNAVAILABLE;
  // Piano scelto su /piani prima di registrarsi: è quello messo in evidenza.
  // Senza preferenza resta in evidenza l'annuale.
  const preferMonthly = user?.preferred_plan === 'mensile';
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
    if (subBusy.current) return;
    subBusy.current = true;
    setSubLoading(true); setSubError('');
    try {
      const res = await apiSend('stripe_checkout.php', 'POST', { plan });
      if (res.client_secret) {
        setClientSecret(res.client_secret);
        setSubLoading(false);
        subBusy.current = false;
      } else {
        window.location.href = res.url;
      }
    } catch (err) {
      subBusy.current = false;
      // Abbonamento già esistente: niente secondo checkout, si va al portale.
      if (err.code === 'subscription_exists') {
        setSubError(err.message);
        openBillingPortal({ keepMessage: true });
        return;
      }
      setSubError(err.message);
      setSubLoading(false);
    }
  }

  async function openBillingPortal({ keepMessage = false } = {}) {
    if (subBusy.current) return;
    subBusy.current = true;
    setSubLoading(true);
    if (!keepMessage) setSubError('');
    try {
      const res = await apiSend('stripe_portal.php', 'POST', {});
      window.location.href = res.url;
    } catch (err) {
      setSubError(err.message);
      setSubLoading(false);
      subBusy.current = false;
    }
  }

  async function saveProfile(e) {
    e.preventDefault();
    setProfileMsg(''); setProfileErr('');
    try {
      const updated = await apiSend('auth/me.php', 'PUT', { ...profile, finance_experience: profile.finance_experience || null });
      if (setUser) setUser(updated);
      setProfileMsg('Dati aggiornati.');
    } catch (err) {
      setProfileErr(err.message);
    }
  }

  const setField = (key) => (e) => setProfile((p) => ({ ...p, [key]: e.target.value }));

  async function saveReminders(enabled) {
    setReminders(enabled);
    setDigestMsg('');
    const updated = await apiSend('auth/me.php', 'PUT', { reminders_enabled: enabled });
    if (setUser) setUser(updated);
    setDigestMsg(enabled ? 'Promemoria attivati.' : 'Promemoria disattivati.');
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
    <div className="max-w-6xl">
      <h4 className="mb-4 text-xl font-bold">Profilo</h4>

      {/* Spazio tra le card dato dai contenitori (gap/space-y), non dalle singole
          card. Da lg due colonne; sotto, una colonna nello stesso ordine. */}
      <div className="space-y-4">
        <div className="grid items-start gap-4 lg:grid-cols-2">
          <div className="min-w-0 space-y-4">
            <div className="card p-5">
              <h6 className="mb-3 text-sm font-semibold">Dati account</h6>
              <p className="mb-3 text-sm text-muted">Email: <span className="font-medium text-gray-900">{user?.email}</span></p>
              {profileErr && <div className="mb-3 rounded-lg bg-red-50 px-3 py-2 text-sm text-red-600">{profileErr}</div>}
              {profileMsg && <div className="mb-3 rounded-lg bg-emerald-50 px-3 py-2 text-sm text-emerald-700">{profileMsg}</div>}
              <form onSubmit={saveProfile} className="space-y-3">
                <div className="grid gap-3 sm:grid-cols-2">
                  <div>
                    <label className="field-label" htmlFor="profile-first-name">Nome</label>
                    <input id="profile-first-name" className="field" required maxLength={75} autoComplete="given-name" value={profile.first_name} onChange={setField('first_name')} />
                  </div>
                  <div>
                    <label className="field-label" htmlFor="profile-last-name">Cognome</label>
                    <input id="profile-last-name" className="field" required maxLength={75} autoComplete="family-name" value={profile.last_name} onChange={setField('last_name')} />
                  </div>
                  <div>
                    <label className="field-label" htmlFor="profile-phone">Telefono (facoltativo)</label>
                    <input id="profile-phone" className="field" type="tel" maxLength={25} autoComplete="tel" pattern="\+?[0-9 .\(\)\/\-]{6,25}"
                      title="Solo cifre, con + iniziale facoltativo" value={profile.phone} onChange={setField('phone')} />
                  </div>
                  <div>
                    <label className="field-label" htmlFor="profile-experience">Esperienza in finanza</label>
                    <select id="profile-experience" className="field" value={profile.finance_experience} onChange={setField('finance_experience')}>
                      <option value="">Non indicata</option>
                      <option value="principiante">Principiante</option>
                      <option value="intermedio">Intermedio</option>
                      <option value="avanzato">Avanzato</option>
                    </select>
                  </div>
                </div>
                <button className="btn-accent">Salva</button>
              </form>
            </div>

            <div className="card p-5">
              <h6 className="mb-3 text-sm font-semibold">Abbonamento</h6>
              {subError && <div className="mb-3 rounded-lg bg-red-50 px-3 py-2 text-sm text-red-600">{subError}</div>}

              {user?.subscription_cancel_at && ['trialing', 'active', 'past_due'].includes(user.subscription_status) && user.has_subscription && (
                <>
                  <p className="mb-3 text-sm text-muted">
                    Abbonamento <span className="font-semibold text-expense">annullato</span>: resta attivo fino al <span className="font-semibold text-gray-900">{formatDate(user.subscription_cancel_at)}</span>.
                    {!isNative && ' Puoi riattivarlo da "Gestisci abbonamento".'}
                  </p>
                  {!isNative && <button onClick={() => openBillingPortal()} disabled={subLoading} className="btn-outline">Gestisci abbonamento</button>}
                </>
              )}

              {!user?.subscription_cancel_at && user?.subscription_status === 'trialing' && user.has_subscription && (
                <>
                  <p className="mb-3 text-sm text-muted">
                    Abbonamento <span className="font-semibold text-income">attivato</span> — la prova gratuita continua: <span className="font-semibold text-gray-900">{user.trial_days_left} giorni rimasti</span> prima del primo addebito.
                  </p>
                  {!isNative && <button onClick={() => openBillingPortal()} disabled={subLoading} className="btn-outline">Gestisci abbonamento</button>}
                </>
              )}

              {user?.subscription_status === 'trialing' && !user.has_subscription && (
                <>
                  <p className="mb-3 text-sm text-muted">
                    Sei nel periodo di prova gratuito — <span className="font-semibold text-gray-900">{user.trial_days_left} giorni rimasti</span>.
                    {!isNative && user.preferred_plan && <> Quando ti sei registrato avevi scelto il piano <span className="font-semibold text-gray-900">{user.preferred_plan}</span>.</>}
                  </p>
                  {!isNative && !clientSecret && (
                    <div className="flex flex-wrap gap-2">
                      {prices.showMonthly && <button onClick={() => subscribe('mensile')} disabled={subLoading} className={preferMonthly ? 'btn-accent' : 'btn-outline'}>Passa al mensile — {monthlyLabel}</button>}
                      {prices.showAnnual && <button onClick={() => subscribe('annuale')} disabled={subLoading} className={preferMonthly ? 'btn-outline' : 'btn-accent'}>Passa all'annuale — {annualLabel}</button>}
                    </div>
                  )}
                </>
              )}

              {!user?.subscription_cancel_at && (user?.subscription_status === 'active' || user?.subscription_status === 'past_due') && (
                <>
                  <p className="mb-3 text-sm text-muted">
                    Abbonamento <span className="font-semibold text-income">attivo</span>
                    {user.subscription_status === 'past_due' && <span className="font-semibold text-expense"> — pagamento in ritardo</span>}.
                  </p>
                  {!isNative && <button onClick={() => openBillingPortal()} disabled={subLoading} className="btn-outline">Gestisci abbonamento</button>}
                </>
              )}

              {(user?.subscription_status === 'none' || user?.subscription_status === 'canceled') && (
                <>
                  <p className="mb-3 text-sm text-muted">Nessun abbonamento attivo.</p>
                  {!isNative && !clientSecret && (
                    <div className="flex flex-wrap gap-2">
                      {prices.showMonthly && <button onClick={() => subscribe('mensile')} disabled={subLoading} className="btn-outline">Abbonati al mensile — {monthlyLabel}</button>}
                      {prices.showAnnual && <button onClick={() => subscribe('annuale')} disabled={subLoading} className="btn-accent">Abbonati all'annuale — {annualLabel}</button>}
                    </div>
                  )}
                </>
              )}

              {!isNative && clientSecret && (
                <div className="mt-4">
                  <CheckoutForm clientSecret={clientSecret} onCancel={() => setClientSecret(null)} />
                </div>
              )}

              {isNative ? (
                <p className="mt-4 border-t border-border pt-3 text-sm text-muted">Gestisci il tuo abbonamento dal tuo account web.</p>
              ) : (
                <div className="mt-4 flex items-center gap-1.5 border-t border-border pt-3 text-xs text-muted">
                  <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2"><rect x="3" y="11" width="18" height="10" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                  Pagamenti gestiti in sicurezza da Stripe — non conserviamo mai i dati della tua carta
                </div>
              )}
            </div>
          </div>

          <div className="min-w-0 space-y-4">
            <GuideCard compact />

            <div className="card p-5">
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
              <label className="mt-4 flex items-start gap-2 border-t border-border pt-3 text-sm text-gray-700">
                <input type="checkbox" className="mt-0.5" checked={reminders} onChange={(e) => saveReminders(e.target.checked)} />
                <span>Ricevi un promemoria via email se non accedi a Reflexi da un po'</span>
              </label>
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
          </div>
        </div>

        <div className="card border-expense/30 p-5">
          <h6 className="mb-1 text-sm font-semibold text-expense">Elimina account</h6>
          <p className="mb-3 text-sm text-muted">
            Cancella definitivamente il tuo account e tutti i dati collegati (conti, transazioni, mutui, investimenti).
            Se hai un abbonamento, viene annullato subito e non riceverai altri addebiti.
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
    </div>
  );
}
