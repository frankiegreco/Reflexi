import { useState } from 'react';
import { BookOpen, Download, X } from 'lucide-react';

// Guida PDF gratuita "Gestione della finanza personale in 7 passi", promessa
// a chi si registra (pagina /gestione-finanza-personale del sito). La serve
// api/guide_pdf.php con la sola sessione. In Dashboard si può chiudere (scelta
// ricordata nel browser); nel Profilo resta sempre disponibile (compact).
export const GUIDE_URL = '/api/guide_pdf.php';
const DISMISS_KEY = 'reflexi_guide_card_dismissed';

export default function GuideCard({ compact = false }) {
  const [dismissed, setDismissed] = useState(() => {
    try { return !compact && localStorage.getItem(DISMISS_KEY) === '1'; } catch { return false; }
  });
  if (dismissed) return null;

  function dismiss() {
    try { localStorage.setItem(DISMISS_KEY, '1'); } catch { /* ignora */ }
    setDismissed(true);
  }

  if (compact) {
    return (
      <div className="card p-5">
        <h6 className="mb-1 text-sm font-semibold">Guida alla finanza personale (PDF)</h6>
        <p className="mb-3 text-xs text-muted">I 7 passi per gestire le tue finanze, con esempi e schede da compilare. Gratuita per gli iscritti.</p>
        <a href={GUIDE_URL} className="inline-flex items-center gap-2 rounded-lg border border-border px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50">
          <Download size={16} /> Scarica la guida
        </a>
      </div>
    );
  }

  return (
    <div className="relative flex flex-col gap-4 rounded-xl border border-border bg-white p-5 sm:flex-row sm:items-center">
      <button onClick={dismiss} className="absolute right-3 top-3 text-muted hover:text-gray-900" aria-label="Chiudi">
        <X size={16} />
      </button>
      <div className="flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-xl bg-accentSoft text-accent">
        <BookOpen size={22} />
      </div>
      <div className="flex-1 pr-4">
        <div className="font-semibold">La tua guida gratuita: gestione della finanza personale in 7 passi</div>
        <div className="text-sm text-muted">13 pagine con esempi, tabelle e 7 schede da compilare. Ogni passo ti dice anche dove trovarlo in Reflexi.</div>
      </div>
      <a href={GUIDE_URL} className="btn-accent inline-flex flex-shrink-0 items-center justify-center gap-2">
        <Download size={16} /> Scarica il PDF
      </a>
    </div>
  );
}
