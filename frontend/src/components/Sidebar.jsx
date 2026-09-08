import {
  LayoutDashboard, ListOrdered, Wallet, Landmark, LineChart, Compass, TrendingUp, Scale, Flame, PiggyBank,
  Tags, Repeat, Layers, Target, BarChart3, FileText, Receipt, Plus, LogOut, UserCircle2, X, FlaskConical,
} from 'lucide-react';

const NAV = [
  { key: 'dashboard', label: 'Riepilogo', icon: LayoutDashboard },
  { key: 'transactions', label: 'Transazioni', icon: ListOrdered },
  { key: 'accounts', label: 'Conti', icon: Wallet },
  { key: 'loans', label: 'Mutui', icon: Landmark },
  { key: 'investments', label: 'Investimenti', icon: LineChart },
  { key: 'forecasts', label: 'Previsioni', icon: Compass },
  { key: 'markets', label: 'Mercati', icon: TrendingUp },
  { key: 'papertrading', label: 'Simulazione trading', icon: FlaskConical },
  { key: 'comparator', label: 'Comparatore', icon: Scale },
  { key: 'fire', label: 'FIRE', icon: Flame },
  { key: 'pensionfund', label: 'Fondo pensione', icon: PiggyBank },
  { key: 'budget', label: 'Budget', icon: Receipt },
  { key: 'categories', label: 'Categorie', icon: Tags },
  { key: 'recurring', label: 'Ricorrenti', icon: Repeat },
  { key: 'subscriptions', label: 'Abbonamenti', icon: Layers },
  { key: 'goals', label: 'Obiettivi', icon: Target },
  { key: 'analytics', label: 'Analisi', icon: BarChart3 },
  { key: 'report', label: 'Report', icon: FileText },
];

export default function Sidebar({ view, setView, onAddTransaction, user, onLogout, mobileOpen, onClose }) {
  function go(key) {
    setView(key);
    onClose?.();
  }

  return (
    <nav
      className={`fixed left-0 top-0 z-50 flex h-screen w-64 flex-shrink-0 flex-col bg-sidebar p-4 text-white transition-transform duration-200 md:sticky md:translate-x-0 ${
        mobileOpen ? 'translate-x-0' : '-translate-x-full'
      }`}
    >
      <div className="mb-4 flex items-center justify-between px-1">
        <div className="flex items-center gap-2.5">
          <img src="/favicon.svg" alt="Reflexi" className="h-8 w-8 rounded-xl" />
          <span className="text-[15px] font-bold tracking-tight">Reflexi</span>
        </div>
        <button onClick={onClose} className="text-white/60 hover:text-white md:hidden">
          <X size={20} />
        </button>
      </div>
      <ul className="flex-1 overflow-y-auto">
        {NAV.map(({ key, label, icon: Icon }) => {
          const active = view === key;
          return (
            <li key={key} className="relative">
              {active && <span className="absolute -left-4 top-1/2 h-5 w-1 -translate-y-1/2 rounded-r-full bg-accent" />}
              <button
                onClick={() => go(key)}
                className={`flex w-full items-center gap-3 rounded-lg px-3 py-1.5 text-sm font-medium transition-all duration-150 ${
                  active ? 'bg-white/10 text-white' : 'text-white/55 hover:bg-white/5 hover:text-white/90'
                }`}
              >
                <Icon size={16} strokeWidth={active ? 2.25 : 1.75} className={active ? 'text-accent' : ''} />
                {label}
              </button>
            </li>
          );
        })}
        <li className="relative">
          {view === 'profile' && <span className="absolute -left-4 top-1/2 h-5 w-1 -translate-y-1/2 rounded-r-full bg-accent" />}
          <button
            onClick={() => go('profile')}
            className={`flex w-full items-center gap-3 rounded-lg px-3 py-1.5 text-sm font-medium transition-all duration-150 ${
              view === 'profile' ? 'bg-white/10 text-white' : 'text-white/55 hover:bg-white/5 hover:text-white/90'
            }`}
          >
            <UserCircle2 size={16} strokeWidth={view === 'profile' ? 2.25 : 1.75} className={view === 'profile' ? 'text-accent' : ''} />
            Profilo
          </button>
        </li>
      </ul>
      <button
        onClick={() => { onAddTransaction(); onClose?.(); }}
        className="mt-3 flex items-center justify-center gap-1.5 rounded-xl bg-accent py-2 text-sm font-semibold text-white shadow-lg shadow-accent/25 transition-all hover:bg-accentDark hover:shadow-accent/40 active:scale-[0.98]"
      >
        <Plus size={16} /> Movimento
      </button>
      <div className="mt-3 flex items-center justify-between border-t border-white/10 pt-3">
        <span className="truncate text-xs text-white/50">{user?.name || user?.email}</span>
        <button onClick={onLogout} className="flex-shrink-0 text-white/50 hover:text-white" title="Esci">
          <LogOut size={16} />
        </button>
      </div>
    </nav>
  );
}
