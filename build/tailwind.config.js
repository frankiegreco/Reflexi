/** @type {import('tailwindcss').Config} */
module.exports = {
  content: ['./webroot/**/*.php'],
  // Le classi qui sotto sono usate SOLO dentro webroot/news-articolo.php,
  // stampate dal campo `body` di news_articles (contenuto nel DB MariaDB,
  // non un file scansionabile da Tailwind a build time). Corrispondono
  // esattamente alle classi generate dagli helper P()/H2()/H3()/UL()/A() in
  // build/gen_articles*.py: se in futuro un nuovo articolo usa una classe
  // diversa da questo elenco, va aggiunta qui, altrimenti verrebbe scartata
  // dal build e il testo dell'articolo perderebbe lo stile.
  safelist: [
    'font-bold', 'font-medium', 'hover:underline', 'leading-relaxed',
    'list-disc', 'mb-2', 'mb-3', 'mb-4', 'mt-10', 'mt-6', 'pl-5',
    'space-y-2', 'text-[15px]', 'text-accent', 'text-gray-700',
    'text-gray-900', 'text-lg', 'text-xl',
  ],
  theme: {
    extend: {
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
