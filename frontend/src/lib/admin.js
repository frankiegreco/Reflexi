import { formatDate } from './format';

// I datetime MySQL arrivano come "YYYY-MM-DD HH:MM:SS": formatDate vuole solo la data.
export const day = (dt) => (dt ? formatDate(dt.slice(0, 10)) : '—');
export const dayTime = (dt) => (dt ? `${day(dt)} ${dt.slice(11, 16)}` : '—');

export function duration(sec) {
  if (sec == null) return '—';
  if (sec < 60) return '< 1 min';
  const h = Math.floor(sec / 3600);
  const m = Math.round((sec % 3600) / 60);
  return h ? `${h} h ${m} min` : `${m} min`;
}

// Riassunto leggibile dello user agent: abbastanza per distinguere app/browser
// e dispositivo, senza una libreria di parsing dedicata.
export function device(ua) {
  if (!ua) return 'Sconosciuto';
  const os = /Android/.test(ua) ? 'Android'
    : /iPhone|iPad/.test(ua) ? 'iOS'
    : /Windows/.test(ua) ? 'Windows'
    : /Mac OS X/.test(ua) ? 'macOS'
    : /Linux/.test(ua) ? 'Linux' : 'altro';
  if (/; wv\)/.test(ua)) return `App ${os}`;
  const browser = /Edg\//.test(ua) ? 'Edge'
    : /OPR\//.test(ua) ? 'Opera'
    : /Firefox\//.test(ua) ? 'Firefox'
    : /Chrome\//.test(ua) ? 'Chrome'
    : /Safari\//.test(ua) ? 'Safari' : 'Browser';
  return `${browser} su ${os}`;
}
