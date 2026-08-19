import { createContext, useContext, useState, useEffect, useCallback } from 'react';
import { apiGet, apiSend } from '../api';

const AuthContext = createContext(null);

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

  const login = useCallback(async (email, password) => {
    const u = await apiSend('auth/login.php', 'POST', { email, password });
    setUser(u);
    setSessionExpired(false);
    return u;
  }, []);

  const register = useCallback(async (email, password, name) => {
    return apiSend('auth/register.php', 'POST', { email, password, name });
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
