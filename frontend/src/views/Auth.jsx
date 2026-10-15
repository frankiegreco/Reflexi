import { useState, useEffect } from 'react';
import { Capacitor } from '@capacitor/core';
import { useAuth } from '../context/AuthContext';
import { apiSend } from '../api';
import SiteNav from '../components/SiteNav';

// Navbar del sito pubblico solo sul web: nell'app Android la WebView
// porterebbe l'utente fuori dall'app senza un modo di tornare, e un rimando ai
// prezzi con pagamento esterno a Google Play viola le regole dello store.
const showSiteNav = !Capacitor.isNativePlatform();

function AuthShell({ cta, children }) {
  return (
    <div className="flex min-h-screen flex-col bg-bg">
      {showSiteNav && <SiteNav cta={cta} />}
      <div className="flex flex-1 items-center justify-center p-4">
        <div className="w-full max-w-sm">
          {!showSiteNav && (
            <div className="mb-6 flex items-center justify-center gap-2">
              <img src="/favicon.svg" alt="Reflexi" className="h-10 w-10 rounded-xl" />
              <span className="text-lg font-bold tracking-tight">Reflexi</span>
            </div>
          )}
          <p className="mb-4 text-center text-sm italic text-muted">Il riflesso vero delle tue finanze</p>
          <div className="card p-6">{children}</div>
        </div>
      </div>
    </div>
  );
}

function Message({ type, children }) {
  const cls = type === 'error' ? 'bg-red-50 text-red-600' : 'bg-emerald-50 text-emerald-700';
  return <div className={`mb-3 rounded-lg px-3 py-2 text-sm ${cls}`}>{children}</div>;
}

// /app/?mode=register (pulsanti "Inizia la prova" del sito) apre direttamente
// la registrazione; &plan=mensile|annuale porta il piano scelto su /piani. I
// parametri vanno tolti dall'URL appena letti: un refresh o un logout
// successivo devono tornare al login.
export function clearModeParam() {
  const url = new URL(window.location.href);
  if (!url.searchParams.has('mode') && !url.searchParams.has('plan') && !url.searchParams.has('guida')) return;
  url.searchParams.delete('mode');
  url.searchParams.delete('plan');
  url.searchParams.delete('guida');
  window.history.replaceState({}, '', url.pathname + url.search + url.hash);
}

export default function Auth() {
  const { login, register, sessionExpired, clearSessionExpired } = useAuth();
  const params = new URLSearchParams(window.location.search);
  const initialMode = params.get('reset_token') ? 'reset' : params.get('mode') === 'register' ? 'register' : 'login';
  const [mode, setMode] = useState(initialMode); // login | register | forgot | reset
  const [plan] = useState(() => (initialMode === 'register' && ['mensile', 'annuale'].includes(params.get('plan')) ? params.get('plan') : null));
  // &guida=1: arriva dal pulsante "Scarica la guida in PDF" del sito.
  const [fromGuide] = useState(() => params.get('guida') === '1');
  useEffect(clearModeParam, []);
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [passwordConfirm, setPasswordConfirm] = useState('');
  const [firstName, setFirstName] = useState('');
  const [lastName, setLastName] = useState('');
  const [phone, setPhone] = useState('');
  const [experience, setExperience] = useState('');
  const [error, setError] = useState('');
  const [info, setInfo] = useState('');
  const [loading, setLoading] = useState(false);
  const [needsVerification, setNeedsVerification] = useState(false);
  const [resendMsg, setResendMsg] = useState('');
  const [termsAccepted, setTermsAccepted] = useState(false);

  useEffect(() => {
    const verify = params.get('verify');
    if (verify === 'success') setInfo('Email confermata! Ora puoi accedere.');
    if (verify === 'invalid') setError('Il link di conferma non è valido o è scaduto.');
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, []);

  // Pulsante a destra nella navbar: porta alla registrazione, o al login se ci sei già.
  const cta = mode === 'register'
    ? { label: 'Accedi', onClick: () => { setMode('login'); resetMessages(); } }
    : { label: 'Crea un account', onClick: () => { setMode('register'); resetMessages(); } };

  function resetMessages() {
    setError(''); setInfo(''); setNeedsVerification(false); setResendMsg('');
  }

  async function handleLogin(e) {
    e.preventDefault();
    resetMessages(); setLoading(true);
    try {
      await login(email, password);
    } catch (err) {
      setError(err.message);
      if (err.code === 'email_not_verified') setNeedsVerification(true);
    } finally {
      setLoading(false);
    }
  }

  async function handleResend() {
    setResendMsg('');
    try {
      const res = await apiSend('auth/resend_verification.php', 'POST', { email });
      setResendMsg(res.message);
    } catch (err) {
      setResendMsg(err.message);
    }
  }

  async function handleRegister(e) {
    e.preventDefault();
    resetMessages();
    if (password !== passwordConfirm) {
      setError('Le due password non coincidono.');
      return;
    }
    setLoading(true);
    try {
      const res = await register({
        email, password, password_confirm: passwordConfirm, plan,
        first_name: firstName, last_name: lastName, phone, finance_experience: experience || null,
      });
      setInfo(res.message);
      setMode('login');
    } catch (err) {
      setError(err.message);
    } finally {
      setLoading(false);
    }
  }

  async function handleForgot(e) {
    e.preventDefault();
    resetMessages(); setLoading(true);
    try {
      const res = await apiSend('auth/forgot_password.php', 'POST', { email });
      setInfo(res.message);
    } catch (err) {
      setError(err.message);
    } finally {
      setLoading(false);
    }
  }

  async function handleReset(e) {
    e.preventDefault();
    resetMessages(); setLoading(true);
    try {
      await apiSend('auth/reset_password.php', 'POST', { token: params.get('reset_token'), password });
      setInfo('Password aggiornata. Ora puoi accedere.');
      setMode('login');
      window.history.replaceState({}, '', window.location.pathname);
    } catch (err) {
      setError(err.message);
    } finally {
      setLoading(false);
    }
  }

  const sessionBanner = sessionExpired && mode === 'login' && (
    <Message type="error">
      La tua sessione è scaduta. Accedi di nuovo per continuare.
    </Message>
  );

  if (mode === 'reset') {
    return (
      <AuthShell cta={cta}>
        <h2 className="mb-4 text-lg font-semibold">Scegli una nuova password</h2>
        {error && <Message type="error">{error}</Message>}
        {info && <Message type="info">{info}</Message>}
        <form onSubmit={handleReset} className="space-y-3">
          <div>
            <label className="field-label">Nuova password</label>
            <input className="field" type="password" required minLength={8} value={password} onChange={(e) => setPassword(e.target.value)} />
          </div>
          <button className="btn-accent w-full" disabled={loading}>{loading ? 'Attendere...' : 'Imposta password'}</button>
        </form>
      </AuthShell>
    );
  }

  if (mode === 'forgot') {
    return (
      <AuthShell cta={cta}>
        <h2 className="mb-4 text-lg font-semibold">Password dimenticata</h2>
        {error && <Message type="error">{error}</Message>}
        {info && <Message type="info">{info}</Message>}
        <form onSubmit={handleForgot} className="space-y-3">
          <div>
            <label className="field-label">Email</label>
            <input className="field" type="email" required value={email} onChange={(e) => setEmail(e.target.value)} />
          </div>
          <button className="btn-accent w-full" disabled={loading}>{loading ? 'Attendere...' : 'Invia istruzioni'}</button>
        </form>
        <button className="mt-3 text-sm text-accent hover:underline" onClick={() => { setMode('login'); resetMessages(); }}>Torna al login</button>
      </AuthShell>
    );
  }

  if (mode === 'register') {
    return (
      <AuthShell cta={cta}>
        <h2 className="mb-4 text-lg font-semibold">Crea un account</h2>
        {fromGuide && (
          <p className="mb-3 rounded-lg bg-accentSoft px-3 py-2 text-sm text-gray-700">
            <span className="font-semibold text-accent">Guida in PDF gratuita.</span> Crea l'account: dopo aver confermato
            l'email la scarichi dalla dashboard, insieme a 6 mesi di prova di Reflexi senza carta di credito.
            Hai già un account?{' '}
            <button type="button" className="font-semibold text-accent hover:underline" onClick={() => { setMode('login'); resetMessages(); }}>Accedi</button>.
          </p>
        )}
        {plan && (
          <p className="mb-3 rounded-lg bg-accentSoft px-3 py-2 text-sm text-gray-700">
            Hai scelto il piano <span className="font-semibold text-accent">{plan}</span>. Si parte con la prova gratuita:
            ora non paghi nulla e lo attivi quando vuoi, dal Profilo.
          </p>
        )}
        {error && <Message type="error">{error}</Message>}
        {info && <Message type="info">{info}</Message>}
        <form onSubmit={handleRegister} className="space-y-3">
          <div className="grid grid-cols-2 gap-3">
            <div>
              <label className="field-label" htmlFor="reg-first-name">Nome</label>
              <input id="reg-first-name" className="field" required maxLength={75} autoComplete="given-name" value={firstName} onChange={(e) => setFirstName(e.target.value)} />
            </div>
            <div>
              <label className="field-label" htmlFor="reg-last-name">Cognome</label>
              <input id="reg-last-name" className="field" required maxLength={75} autoComplete="family-name" value={lastName} onChange={(e) => setLastName(e.target.value)} />
            </div>
          </div>
          <div>
            <label className="field-label" htmlFor="reg-email">Email</label>
            <input id="reg-email" className="field" type="email" required autoComplete="email" value={email} onChange={(e) => setEmail(e.target.value)} />
          </div>
          <div>
            <label className="field-label">Password (min. 8 caratteri)</label>
            <input className="field" type="password" required minLength={8} autoComplete="new-password" value={password} onChange={(e) => setPassword(e.target.value)} />
          </div>
          <div>
            <label className="field-label" htmlFor="reg-password-confirm">Conferma password</label>
            <input id="reg-password-confirm" className="field" type="password" required minLength={8} autoComplete="new-password"
              value={passwordConfirm} onChange={(e) => setPasswordConfirm(e.target.value)}
              aria-invalid={passwordConfirm !== '' && passwordConfirm !== password} />
            {passwordConfirm !== '' && passwordConfirm !== password && (
              <p className="mt-1 text-xs text-expense">Le password non coincidono</p>
            )}
          </div>
          <div>
            <label className="field-label" htmlFor="reg-phone">Telefono (facoltativo)</label>
            <input id="reg-phone" className="field" type="tel" maxLength={25} autoComplete="tel" pattern="\+?[0-9 .\(\)\/\-]{6,25}"
              title="Solo cifre, con + iniziale facoltativo" value={phone} onChange={(e) => setPhone(e.target.value)} />
          </div>
          <div>
            <label className="field-label" htmlFor="reg-experience">Esperienza in finanza (facoltativo)</label>
            <select id="reg-experience" className="field" value={experience} onChange={(e) => setExperience(e.target.value)}>
              <option value="">Preferisco non dirlo</option>
              <option value="principiante">Principiante</option>
              <option value="intermedio">Intermedio</option>
              <option value="avanzato">Avanzato</option>
            </select>
          </div>
          <label className="flex items-start gap-2 text-xs text-muted">
            <input type="checkbox" required checked={termsAccepted} onChange={(e) => setTermsAccepted(e.target.checked)} className="mt-0.5" />
            <span>
              Accetto i <a href="/termini" target="_blank" rel="noopener" className="text-accent hover:underline">Termini di Servizio</a> e ho letto
              la <a href="/privacy" target="_blank" rel="noopener" className="text-accent hover:underline">Privacy Policy</a>
            </span>
          </label>
          <button className="btn-accent w-full" disabled={loading}>{loading ? 'Attendere...' : 'Registrati'}</button>
        </form>
        <p className="mt-3 text-center text-sm text-muted">
          Hai già un account?{' '}
          <button className="text-accent hover:underline" onClick={() => { setMode('login'); resetMessages(); }}>Accedi</button>
        </p>
      </AuthShell>
    );
  }

  return (
    <AuthShell cta={cta}>
      <h2 className="mb-4 text-lg font-semibold">Accedi</h2>
      {sessionBanner}
      {error && <Message type="error">{error}</Message>}
      {info && <Message type="info">{info}</Message>}
      {needsVerification && (
        <div className="mb-3">
          <button type="button" className="btn-outline w-full" onClick={handleResend}>Rinvia email di verifica</button>
          {resendMsg && <p className="mt-2 text-center text-xs text-muted">{resendMsg}</p>}
        </div>
      )}
      <form onSubmit={handleLogin} className="space-y-3">
        <div>
          <label className="field-label">Email</label>
          <input className="field" type="email" required value={email}
            onChange={(e) => { setEmail(e.target.value); clearSessionExpired(); }} />
        </div>
        <div>
          <label className="field-label">Password</label>
          <input className="field" type="password" required value={password} onChange={(e) => setPassword(e.target.value)} />
        </div>
        <button className="btn-accent w-full" disabled={loading}>{loading ? 'Attendere...' : 'Accedi'}</button>
      </form>
      <div className="mt-3 flex justify-between text-sm">
        <button className="text-accent hover:underline" onClick={() => { setMode('register'); resetMessages(); }}>Crea un account</button>
        <button className="text-muted hover:underline" onClick={() => { setMode('forgot'); resetMessages(); }}>Password dimenticata?</button>
      </div>
    </AuthShell>
  );
}
