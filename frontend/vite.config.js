import { fileURLToPath, URL } from 'node:url';
import { defineConfig } from 'vite';
import vue from '@vitejs/plugin-vue';

export default defineConfig({
    plugins: [vue()],
    resolve: {
        alias: {
            '@': fileURLToPath(new URL('./src', import.meta.url)),
        },
    },
    server: {
        host: '0.0.0.0',
        port: 5173,
        strictPort: true,
        // The browser calls /api on the Vite server, which forwards it to Laravel.
        // Same origin for the browser, so no CORS setup is needed in development.
        proxy: {
            '/api': {
                target: process.env.API_PROXY_TARGET ?? 'http://localhost:8000',
                changeOrigin: true,
            },
        },
    },
});
