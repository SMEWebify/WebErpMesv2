import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import react from '@vitejs/plugin-react';
import vue from '@vitejs/plugin-vue';

export default defineConfig({
    server: {
        watch: {
            ignored: ['**/vendor/**', '**/node_modules/**']
        }
    },
    plugins: [
        react(),
        vue(),
        laravel({
            input: [
                'resources/sass/app.scss',
                'resources/js/app.js',
                'resources/js/guest.js',
                'resources/js/spreadsheet.js',
                'node_modules/frappe-gantt/dist/frappe-gantt.css',
            ],
            refresh: true,
        }),
    ],
    css: {
        preprocessorOptions: {
            scss: {
                silenceDeprecations: ['import', 'global-builtin', 'color-functions']
            }
        }
    }
});