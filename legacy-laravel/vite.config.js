import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import { VitePWA } from 'vite-plugin-pwa';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js', 'resources/js/dashboard.js'],
            refresh: true,
        }),
        VitePWA({
            registerType: 'autoUpdate',
            injectRegister: 'auto',
            includeAssets: ['favicon.ico'],
            manifest: {
                name: 'Toko Mba Emi',
                short_name: 'Toko Mba Emi',
                description: 'Manajemen Toko & Kasir Modern',
                lang: 'id',
                start_url: '/',
                scope: '/',
                display: 'standalone',
                background_color: '#0f172a',
                theme_color: '#6366f1',
                icons: [
                    { src: '/logo.png?v=2', sizes: '192x192', type: 'image/png' },
                    { src: '/logo.png?v=2', sizes: '512x512', type: 'image/png' },
                    { src: '/logo.png?v=2', sizes: '512x512', type: 'image/png', purpose: 'any maskable' },
                ],
            },
            workbox: {
                navigateFallback: null,
                globPatterns: ['**/*.{js,css,svg,png,ico,woff2}'],
                runtimeCaching: [
                    {
                        urlPattern: ({ url, sameOrigin }) => sameOrigin && url.pathname.startsWith('/build/'),
                        handler: 'StaleWhileRevalidate',
                        options: {
                            cacheName: 'tokombaemi-assets',
                        },
                    },
                    {
                        urlPattern: ({ url, sameOrigin }) => sameOrigin && url.pathname.startsWith('/icons/'),
                        handler: 'CacheFirst',
                        options: {
                            cacheName: 'tokombaemi-icons',
                        },
                    },
                ],
            },
        }),
    ],
});
