/** @type {import('tailwindcss').Config} */
export default {
  content: ['./index.html', './src/**/*.{js,jsx}'],
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
        sidebarHover: '#1f2240',
        bg: '#f3f4f8',
        border: '#e7e9f0',
        muted: '#6b7080',
      },
      fontFamily: {
        sans: ['Inter', 'ui-sans-serif', 'system-ui', 'sans-serif'],
        mono: ['"IBM Plex Mono"', 'ui-monospace', 'monospace'],
      },
      borderRadius: {
        card: '14px',
      },
      boxShadow: {
        card: '0 1px 2px rgba(16, 24, 40, 0.04)',
      },
    },
  },
  plugins: [],
};
