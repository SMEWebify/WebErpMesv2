import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import react from '@vitejs/plugin-react';

export default defineConfig({
    test: {
        environment: 'jsdom',
        globals: true,
        setupFiles: ['resources/js/tests/setup.js'],
        include: ['resources/js/tests/**/*.test.{js,jsx}'],
        css: false,
    },
    plugins: [
        react(),
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
    },
    build: {
        rollupOptions: {
            output: {
                manualChunks(id) {
                    if (id.includes('node_modules/react-dom') || id.includes('node_modules/react/')) {
                        return 'vendor-react';
                    }
                    if (id.includes('node_modules/chart.js') || id.includes('node_modules/react-chartjs-2')) {
                        return 'vendor-charts';
                    }
                    if (id.includes('node_modules/@dnd-kit') || id.includes('node_modules/react-beautiful-dnd')) {
                        return 'vendor-dnd';
                    }
                },
            },
        },
    },
});