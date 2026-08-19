import { useState, useEffect } from 'react';
import { useAuth } from '../context/AuthContext';
import { apiSend } from '../api';

function AuthShell({ children }) {
  return (
    <div className="flex min-h-screen items-center justify-center bg-bg p-4">
      <div className="w-full max-w-sm">
        <div className="mb-6 flex items-center justify-center gap-2">
          <img src="/favicon.svg" alt="Reflexi" className="h-10 w-10 rounded-xl" />
          <span className="text-lg font-bold tracking-tight">Reflexi</span>
        </div>
        <p className="mb-4 text-center text-sm italic text-muted">Il riflesso vero delle tue finanze</p>
        <div className="card p-6">{children}</div>
      </div>
    </div>
  );
}

function Message({ type, children }) {
  const cls = type === 'error' ? 'bg-red-50 text-red-600' : 'bg-emerald-50 text-emerald-700';
  return <div className={`mb-3 rounded-lg px-3 py-2 text-sm ${cls}`}>{children}</div>;
}

export default function Auth() {
  const { login, register, sessionExpired, clearSessionExpired } = useAuth();
  const params = new URLSearchParams(window.location.search);
  const initialMode = params.get('reset_token') ? 'reset' : 'login';
  const [mode, setMode] = useState(initialMode); // login | register | forgot | reset
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [name, setName] = useState('');
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
    resetMessages(); setLoading(true);
    try {
      const res = await register(email, password, name);
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
      <AuthShell>
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
      <AuthShell>
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
      <AuthShell>
        <h2 className="mb-4 text-lg font-semibold">Crea un account</h2>
        {error && <Message type="error">{error}</Message>}
        {info && <Message type="info">{info}</Message>}
        <form onSubmit={handleRegister} className="space-y-3">
          <div>
            <label className="field-label">Nome (opzionale)</label>
            <input className="field" value={name} onChange={(e) => setName(e.target.value)} />
          </div>
          <div>
            <label className="field-label">Email</label>
            <input className="field" type="email" required value={email} onChange={(e) => setEmail(e.target.value)} />
          </div>
          <div>
            <label className="field-label">Password (min. 8 caratteri)</label>
            <input className="field" type="password" required minLength={8} value={password} onChange={(e) => setPassword(e.target.value)} />
          </div>
          <label className="flex items-start gap-2 text-xs text-muted">
            <input type="checkbox" required checked={termsAccepted} onChange={(e) => setTermsAccepted(e.target.checked)} className="mt-0.5" />
            <span>
              Accetto i <a href="/termini.html" target="_blank" rel="noopener" className="text-accent hover:underline">Termini di Servizio</a> e ho letto
              la <a href="/privacy.html" target="_blank" rel="noopener" className="text-accent hover:underline">Privacy Policy</a>
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
    <AuthShell>
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
