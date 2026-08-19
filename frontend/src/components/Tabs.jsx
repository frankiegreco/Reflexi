export default function Tabs({ tabs, active, onChange }) {
  return (
    <div className="mb-4 inline-flex flex-wrap gap-1 rounded-xl bg-gray-100 p-1">
      {tabs.map((t) => {
        const isActive = active === t.id;
        return (
          <button
            key={t.id}
            onClick={() => onChange(t.id)}
            className={`flex items-center gap-1.5 rounded-lg px-4 py-2 text-sm font-semibold transition-colors ${
              isActive ? 'bg-white text-accent shadow-sm' : 'text-muted hover:text-gray-700'
            }`}
          >
            {t.label}
            {t.count > 0 && (
              <span className={`rounded-full px-1.5 py-0.5 text-[11px] font-bold ${isActive ? 'bg-accentSoft text-accent' : 'bg-gray-200 text-muted'}`}>
                {t.count}
              </span>
            )}
          </button>
        );
      })}
    </div>
  );
}
