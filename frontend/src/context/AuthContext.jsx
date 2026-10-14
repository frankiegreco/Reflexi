import { createContext, useContext, useState, useEffect, useCallback } from 'react';
import { apiGet, apiSend } from '../api';

const AuthContext = createContext(null);

// Letto una volta al caricamento: l'effetto più sotto ripulisce l'URL, e in
// StrictMode (dev) verrebbe eseguito due volte.
const RETURNING_FROM_CHECKOUT = new URLSearchParams(window.location.search).get('checkout') === 'return';

export function AuthProvider({ children }) {
  const [user, setUser] = useState(null);
  const [checking, setChecking] = useState(true);
  const [sessionExpired, setSessionExpired] = useState(false);

  useEffect(() => {
    apiGet('auth/me.php')
      .then((u) => setUser(u))
      .catch(() => setUser(null))
      .finally(() => setChecking(false));
  }, []);

  // Ritorno dall'embedded form del Checkout (/app/?checkout=return): il
  // webhook Stripe che attiva l'abbonamento può arrivare qualche secondo dopo
  // il redirect, quindi ricarichiamo auth/me.php finché l'abbonamento compare
  // (has_subscription: con la prova ancora in corso lo stato resta "trialing").
  useEffect(() => {
    if (!RETURNING_FROM_CHECKOUT) return;
    const params = new URLSearchParams(window.location.search);
    params.delete('checkout');
    params.delete('session_id');
    const query = params.toString();
    window.history.replaceState(null, '', window.location.pathname + (query ? `?${query}` : '') + window.location.hash);

    let attempts = 0;
    const timer = setInterval(async () => {
      attempts += 1;
      try {
        const u = await apiGet('auth/me.php');
        setUser(u);
        if (u?.has_subscription || attempts >= 10) clearInterval(timer);
      } catch {
        clearInterval(timer);
      }
    }, 2000);
    return () => clearInterval(timer);
  }, []);

  // Qualsiasi chiamata API (fuori da auth/*) che riceve un 401 significa che
  // la sessione e' scaduta: riportiamo l'utente al login con un messaggio
  // chiaro, invece di lasciare che ogni vista mostri un errore generico.
  useEffect(() => {
    function handleUnauthorized() {
      setUser(null);
      setSessionExpired(true);
    }
    window.addEventListener('auth:unauthorized', handleUnauthorized);
    return () => window.removeEventListener('auth:unauthorized', handleUnauthorized);
  }, []);

  useEffect(() => {
    function handlePaymentRequired() {
      setUser((u) => (u ? { ...u, has_access: false } : u));
    }
    window.addEventListener('auth:payment_required', handlePaymentRequired);
    return () => window.removeEventListener('auth:payment_required', handlePaymentRequired);
  }, []);

  const login = useCallback(async (email, password) => {
    const u = await apiSend('auth/login.php', 'POST', { email, password });
    setUser(u);
    setSessionExpired(false);
    return u;
  }, []);

  // data: email, password, first_name, last_name, phone, finance_experience, plan
  const register = useCallback(async (data) => {
    return apiSend('auth/register.php', 'POST', data);
  }, []);

  const logout = useCallback(async () => {
    await apiSend('auth/logout.php', 'POST', {});
    setUser(null);
  }, []);

  return (
    <AuthContext.Provider value={{
      user, checking, login, register, logout, setUser,
      sessionExpired, clearSessionExpired: () => setSessionExpired(false),
    }}>
      {children}
    </AuthContext.Provider>
  );
}

export function useAuth() {
  const ctx = useContext(AuthContext);
  if (!ctx) throw new Error('useAuth must be used within AuthProvider');
  return ctx;
}
