/// <reference types="vitest" />
import { defineConfig } from 'vite';
import react from '@vitejs/plugin-react';

// En desarrollo, Vite reenvía /api al backend PHP para trabajar en el mismo origen
// (las cookies SameSite=Strict y el token CSRF funcionan igual que en producción).
export default defineConfig({
  plugins: [react()],
  server: {
    port: 5173,
    proxy: {
      '/api': { target: process.env.API_URL ?? 'http://127.0.0.1:8080', changeOrigin: false },
    },
  },
  test: { environment: 'node' },
});
