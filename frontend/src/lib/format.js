export function euro(n, currency = 'EUR') {
  try {
    return new Intl.NumberFormat('it-IT', { style: 'currency', currency }).format(n || 0);
  } catch {
    return new Intl.NumberFormat('it-IT', { style: 'currency', currency: 'EUR' }).format(n || 0);
  }
}

export function formatDate(d) {
  if (!d) return '';
  const [y, m, day] = d.split('-');
  return `${day}/${m}/${y}`;
}

export function capitalize(s) {
  return s ? s.charAt(0).toUpperCase() + s.slice(1).replace('_', ' ') : '';
}

export function monthLabel(yyyyMm) {
  const [y, mo] = yyyyMm.split('-');
  return new Date(y, mo - 1, 1).toLocaleDateString('it-IT', { month: 'short', year: '2-digit' });
}

export const todayISO = () => new Date().toISOString().slice(0, 10);
export const thisMonthISO = () => new Date().toISOString().slice(0, 7);

export function debounce(fn, ms) {
  let t;
  return (...args) => {
    clearTimeout(t);
    t = setTimeout(() => fn(...args), ms);
  };
}
