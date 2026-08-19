import { createContext, useContext, useState, useCallback, useEffect } from 'react';
import { apiGet } from '../api';

const DataContext = createContext(null);

export function DataProvider({ children }) {
  const [accounts, setAccounts] = useState([]);
  const [categories, setCategories] = useState([]);
  const [instruments, setInstruments] = useState([]);
  const [signal, setSignal] = useState(0);

  // Chiamato da qualsiasi vista dopo una mutazione (crea/modifica/elimina), cosi'
  // le altre viste che dipendono da [signal] nel loro useEffect si ricaricano.
  const bump = useCallback(() => setSignal((s) => s + 1), []);

  const refreshAccounts = useCallback(async () => {
    const data = await apiGet('accounts.php?include_archived=1');
    setAccounts(data);
    return data;
  }, []);

  const refreshCategories = useCallback(async () => {
    const data = await apiGet('categories.php');
    setCategories(data);
    return data;
  }, []);

  const refreshInstruments = useCallback(async () => {
    const data = await apiGet('instruments.php');
    setInstruments(data);
    return data;
  }, []);

  useEffect(() => {
    refreshAccounts();
    refreshCategories();
    refreshInstruments();
  }, [signal, refreshAccounts, refreshCategories, refreshInstruments]);

  return (
    <DataContext.Provider
      value={{
        accounts,
        categories,
        instruments,
        activeAccounts: accounts.filter((a) => !a.archived),
        incomeCategories: categories.filter((c) => c.type === 'entrata'),
        expenseCategories: categories.filter((c) => c.type === 'uscita'),
        refreshAccounts,
        refreshCategories,
        refreshInstruments,
        signal,
        bump,
      }}
    >
      {children}
    </DataContext.Provider>
  );
}

export function useData() {
  const ctx = useContext(DataContext);
  if (!ctx) throw new Error('useData must be used within DataProvider');
  return ctx;
}
