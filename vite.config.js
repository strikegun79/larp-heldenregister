import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import inject from '@rollup/plugin-inject';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/vendor.css',
                'resources/js/vendor.js',
                'resources/css/app.css',
                'resources/js/app.js',
            ],
            refresh: true,
        }),
        // jQuery als globale Variable in JS-Dateien verfügbar machen.
        // Nötig, da Fomantic UI ein reines IIFE ist, das jQuery als Global erwartet.
        inject({ $: 'jquery', jQuery: 'jquery', include: ['**/*.js'] }),
    ],
    build: {
        chunkSizeWarningLimit: 600,
    },
});
