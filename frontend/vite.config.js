import { defineConfig } from 'vite';
import react from '@vitejs/plugin-react';

// base: './' cosi' il build funziona sia servito dalla radice del dominio (Docker)
// sia da una sottocartella (es. XAMPP htdocs/finanze/).
export default defineConfig({
  plugins: [react()],
  base: './',
  build: {
    outDir: 'dist',
  },
  server: {
    proxy: {
      // In sviluppo (npm run dev) le chiamate a "api/*.php" vengono girate al backend PHP.
      // Cambia il target se il tuo backend gira altrove (es. XAMPP: http://localhost/finanze).
      '^/api/.*\\.php$': {
        target: 'http://localhost:8080',
        changeOrigin: true,
      },
    },
  },
});
