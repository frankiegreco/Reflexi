export default function StatCard({ label, value, valueClass = '', hint, icon: Icon, iconColor = '#4f46e5' }) {
  return (
    <div className="card min-w-0 p-4 transition-transform hover:-translate-y-0.5">
      <div className="flex min-w-0 items-start justify-between gap-2">
        <div className="min-w-0 text-[11px] font-semibold uppercase tracking-wide text-muted">{label}</div>
        {Icon && (
          <div className="flex h-7 w-7 flex-shrink-0 items-center justify-center rounded-lg" style={{ background: `${iconColor}18` }}>
            <Icon size={14} style={{ color: iconColor }} />
          </div>
        )}
      </div>
      <div className={`mt-1 break-words font-mono text-lg font-semibold ${valueClass}`}>{value}</div>
      {hint && <div className="mt-1 text-xs text-muted">{hint}</div>}
    </div>
  );
}
