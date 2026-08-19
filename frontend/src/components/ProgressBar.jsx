export default function ProgressBar({ pct, color = '#4f46e5', height = 8 }) {
  const clamped = Math.max(0, Math.min(100, pct || 0));
  return (
    <div className="w-full overflow-hidden rounded-full bg-gray-200" style={{ height }}>
      <div className="h-full rounded-full transition-all" style={{ width: `${clamped}%`, background: color }} />
    </div>
  );
}
