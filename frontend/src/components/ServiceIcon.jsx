import { useState } from 'react';

/**
 * Mostra l'icona di un servizio noto, con un badge colorato con l'iniziale
 * come fallback — sia quando non abbiamo nessuna fonte per quel servizio,
 * sia se il caricamento dell'icona fallisce.
 *
 * Due fonti possibili:
 * - `domain`: favicon del sito reale, tramite un servizio pubblico (lo stesso
 *   meccanismo usato da browser e password manager per mostrare l'icona di
 *   un sito qualsiasi) — copre praticamente qualunque esercente reale.
 * - `slug`: icona semplificata da Simple Icons (simpleicons.org), usata per
 *   i servizi in abbonamento — non sono i loghi ufficiali (che restano
 *   marchi registrati), ma rappresentazioni pensate apposta per questo uso.
 */
export default function ServiceIcon({ name, slug, domain, color = '#8b95a1', size = 28 }) {
  const [failed, setFailed] = useState(false);

  const src = domain
    ? `https://www.google.com/s2/favicons?sz=128&domain=${domain}`
    : slug
      ? `https://cdn.simpleicons.org/${slug}`
      : null;

  if (!src || failed) {
    return (
      <div
        className="flex flex-shrink-0 items-center justify-center rounded-lg font-bold text-white"
        style={{ width: size, height: size, backgroundColor: color, fontSize: size * 0.45 }}
      >
        {(name || '?').charAt(0).toUpperCase()}
      </div>
    );
  }

  return (
    <img
      src={src}
      alt={name}
      width={size}
      height={size}
      className="flex-shrink-0 rounded-lg bg-white object-contain p-1"
      style={{ border: '1px solid #e7e9f0' }}
      onError={() => setFailed(true)}
    />
  );
}
