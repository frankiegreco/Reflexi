/** @type {import('tailwindcss').Config} */
module.exports = {
  content: ['./webroot/**/*.php'],
  // Le classi qui sotto sono usate SOLO dentro webroot/news-articolo.php,
  // stampate dal campo `body` di news_articles (contenuto nel DB MariaDB,
  // non un file scansionabile da Tailwind a build time). Corrispondono
  // esattamente alle classi generate dagli helper di
  // build/news/news_html.py (testo, table(), bars()): se in futuro un
  // nuovo articolo usa una classe diversa da questo elenco, va aggiunta qui, altrimenti verrebbe scartata
  // dal build e il testo dell'articolo perderebbe lo stile.
  safelist: [
    'font-bold', 'font-medium', 'hover:underline', 'leading-relaxed',
    'list-disc', 'mb-2', 'mb-3', 'mb-4', 'mt-10', 'mt-6', 'pl-5',
    'space-y-2', 'text-[15px]', 'text-accent', 'text-gray-700',
    'text-gray-900', 'text-lg', 'text-xl',
    // Tabelle e grafici a barre (build/news/news_html.py: table(), bars()).
    'bg-accent', 'bg-expense', 'bg-gray-100', 'bg-gray-400', 'bg-gray-50',
    'bg-income', 'bg-loan', 'bg-white', 'border', 'border-border', 'border-t',
    'flex', 'font-mono', 'font-semibold', 'gap-3', 'h-2.5', 'justify-between',
    'mb-1', 'mt-2', 'mt-4', 'my-6', 'overflow-x-auto', 'p-5', 'px-4',
    'py-2.5', 'rounded-full', 'rounded-xl', 'space-y-3', 'text-[11px]',
    'text-left', 'text-muted', 'text-right', 'text-sm', 'text-xs',
    'tracking-wide', 'uppercase', 'w-full', 'px-3', 'py-2', 'sm:px-4',
    'sm:py-2.5', 'whitespace-nowrap', 'text-[13px]', 'sm:text-sm',
  ],
  theme: {
    extend: {
      // Font del sito (file in webroot/fonts/, @font-face in tailwind-input.css).
      // Va dichiarato qui: la regola .font-mono in @layer base veniva
      // sovrascritta dall'utility font-mono di Tailwind, quindi i numeri
      // uscivano nel monospace di sistema (es. Courier) invece di Plex Mono.
      fontFamily: {
        sans: ['Inter', 'ui-sans-serif', 'system-ui', 'sans-serif'],
        mono: ['"IBM Plex Mono"', 'ui-monospace', 'monospace'],
      },
      colors: {
        accent: '#4f46e5',
        accentDark: '#4338ca',
        accentSoft: '#eef0fd',
        income: '#10b981',
        expense: '#f43f5e',
        loan: '#f59e0b',
        sidebar: '#14162b',
        bg: '#f3f4f8',
        border: '#e7e9f0',
        muted: '#6b7080',
      },
    },
  },
  plugins: [],
};
