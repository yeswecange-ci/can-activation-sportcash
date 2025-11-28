import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,
        }),
    ],

    // Configuration pour la production
    build: {
        manifest: true,
        outDir: 'public/build',
        rollupOptions: {
            output: {
                manualChunks: undefined,
            },
        },
        // Optimisations pour la production
        minify: 'terser',
        cssMinify: true,
        chunkSizeWarningLimit: 1000,
    },

    // Configuration du serveur de développement
    server: {
        host: true,
        port: 5173,
        strictPort: false,
        hmr: {
            host: 'localhost',
        },
    },

    // Résolution des chemins
    resolve: {
        alias: {
            '@': '/resources/js',
            '~': '/resources',
        },
    },

    // Optimisations des dépendances
    optimizeDeps: {
        include: ['alpinejs'],
        exclude: [],
    },

    // Configuration CSS
    css: {
        postcss: {
            plugins: [
                require('tailwindcss'),
                require('autoprefixer'),
            ],
        },
        devSourcemap: true,
    },
});
