import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/js/app.js',
                'resources/js/comercial-dashboard-charts.js',
                'resources/js/requisitions-dashboard-charts.js',
                'resources/js/indicadores-capture.js',
                'resources/js/purchase-request-form.js',
            ],
            refresh: true,
        }),
    ],
});
