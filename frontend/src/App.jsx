import { useState, useEffect, useRef } from 'react';
import { Menu } from 'lucide-react';
import { Capacitor } from '@capacitor/core';
import { StatusBar, Style } from '@capacitor/status-bar';
import { App as CapacitorApp } from '@capacitor/app';
import { AuthProvider, useAuth } from './context/AuthContext';
import { DataProvider, useData } from './context/DataContext';
import Sidebar from './components/Sidebar';
import Auth from './views/Auth';
import Paywall from './views/Paywall';
import Dashboard from './views/Dashboard';
import Transactions from './views/Transactions';
import Accounts from './views/Accounts';
import Loans from './views/Loans';
import Investments from './views/Investments';
import Forecasts from './views/Forecasts';
import Markets from './views/Markets';
import PaperTrading from './views/PaperTrading';
import Comparator from './views/Comparator';
import Fire from './views/Fire';
import PensionFund from './views/PensionFund';
import Report from './views/Report';
import Budget from './views/Budget';
import Categories from './views/Categories';
import Recurring from './views/Recurring';
import Subscriptions from './views/Subscriptions';
import Goals from './views/Goals';
import Analytics from './views/Analytics';
import Profile from './views/Profile';
import AdminUsers from './views/AdminUsers';
import TransactionModal from './views/TransactionModal';

const VIEWS = {
  dashboard: Dashboard,
  transactions: Transactions,
  accounts: Accounts,
  loans: Loans,
  investments: Investments,
  forecasts: Forecasts,
  markets: Markets,
  papertrading: PaperTrading,
  comparator: Comparator,
  fire: Fire,
  pensionfund: PensionFund,
  report: Report,
  budget: Budget,
  categories: Categories,
  recurring: Recurring,
  subscriptions: Subscriptions,
  goals: Goals,
  analytics: Analytics,
  profile: Profile,
  admin: AdminUsers,
};

function Shell() {
  const [view, setView] = useState('dashboard');
  const [quickAddOpen, setQuickAddOpen] = useState(false);
  const [mobileMenuOpen, setMobileMenuOpen] = useState(false);
  const { bump } = useData();
  const { user, logout } = useAuth();
  const ActiveView = VIEWS[view === 'admin' && !user?.is_admin ? 'dashboard' : view];
  const lastBackPress = useRef(0);
  const exitToastTimer = useRef(null);
  const [showExitToast, setShowExitToast] = useState(false);

  // Sulla dashboard, doppio tap su "indietro" entro 2s chiude l'app (con un
  // toast di conferma dopo il primo tap); sulle altre view il tasto indietro
  // non fa nulla (nessuna navigazione automatica nella cronologia della WebView).
  useEffect(() => {
    if (!Capacitor.isNativePlatform()) return;
    const handler = CapacitorApp.addListener('backButton', () => {
      if (view !== 'dashboard') return;
      const now = Date.now();
      if (now - lastBackPress.current < 2000) {
        CapacitorApp.exitApp();
      } else {
        lastBackPress.current = now;
        setShowExitToast(true);
        clearTimeout(exitToastTimer.current);
        exitToastTimer.current = setTimeout(() => setShowExitToast(false), 2000);
      }
    });
    return () => { handler.remove(); clearTimeout(exitToastTimer.current); };
  }, [view]);

  return (
    <div className="flex min-h-screen bg-bg text-gray-900">
      <Sidebar
        view={view}
        setView={setView}
        onAddTransaction={() => setQuickAddOpen(true)}
        user={user}
        onLogout={logout}
        mobileOpen={mobileMenuOpen}
        onClose={() => setMobileMenuOpen(false)}
      />
      {mobileMenuOpen && (
        <div className="fixed inset-0 z-40 bg-black/50 md:hidden" onClick={() => setMobileMenuOpen(false)} />
      )}
      <div className="flex min-w-0 flex-1 flex-col">
        <header className="sticky top-0 z-30 flex items-center gap-3 border-b border-border bg-white px-4 py-3 md:hidden">
          <button onClick={() => setMobileMenuOpen(true)} className="text-gray-700">
            <Menu size={22} />
          </button>
          <img src="/favicon.svg" alt="Reflexi" className="h-7 w-7 rounded-lg" />
          <span className="font-bold">Reflexi</span>
        </header>
        <main className="min-w-0 flex-1 p-4 sm:p-8">
          <div key={view} className="view-enter">
            <ActiveView />
          </div>
        </main>
      </div>
      <TransactionModal
        open={quickAddOpen}
        onClose={() => setQuickAddOpen(false)}
        onSaved={() => { setQuickAddOpen(false); bump(); }}
      />
      {showExitToast && (
        <div className="fixed bottom-6 left-1/2 z-50 -translate-x-1/2 rounded-full bg-sidebar px-4 py-2.5 text-sm font-medium text-white shadow-lg">
          Premi ancora per uscire
        </div>
      )}
    </div>
  );
}

function Gate() {
  const { user, checking } = useAuth();
  if (checking) return <div className="flex min-h-screen items-center justify-center text-muted">Caricamento...</div>;
  if (!user) return <Auth />;
  if (user.has_access === false) return <Paywall />;
  return (
    <DataProvider>
      <Shell />
    </DataProvider>
  );
}

export default function App() {
  useEffect(() => {
    if (!Capacitor.isNativePlatform()) return;
    StatusBar.setOverlaysWebView({ overlay: false }).catch(() => {});
    StatusBar.setBackgroundColor({ color: '#14162b' }).catch(() => {});
    StatusBar.setStyle({ style: Style.Dark }).catch(() => {});
  }, []);

  return (
    <AuthProvider>
      <Gate />
    </AuthProvider>
  );
}
