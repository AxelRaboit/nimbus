import { defineConfig } from 'vite';
import vue from '@vitejs/plugin-vue';
import symfonyPlugin from 'vite-plugin-symfony';
import path from 'path';

export default defineConfig({
    plugins: [
        vue(),
        symfonyPlugin(),
    ],
    resolve: {
        alias: {
            '@': path.resolve(__dirname, 'assets'),
        },
    },
    build: {
        rollupOptions: {
            input: {
                app: './assets/app.js',
                sidebar: './assets/sidebar.js',
                flash: './assets/flash.js',
                home: './assets/pages/home/index.js',
                transfer: './assets/pages/transfer/index.js',
                manage: './assets/pages/manage/index.js',
                profile: './assets/pages/profile/index.js',
                'transfer-password': './assets/pages/transfer-password/index.js',
                'transfer-unavailable': './assets/pages/transfer-unavailable/index.js',
                dev: './assets/pages/dev/index.js',
                dashboard: './assets/pages/dashboard/index.js',
                plan: './assets/pages/plan/index.js',
            },
            output: {
                manualChunks(id) {
                    if (!id.includes('node_modules')) return;
                    if (id.includes('chart.js') || id.includes('vue-chartjs')) return 'vendor-charts';
                    if (id.includes('lucide-vue-next')) return 'vendor-icons';
                    if (id.includes('axios') || id.includes('qrcode') || id.includes('tus-js-client')) return 'vendor-utils';
                    if (id.includes('vue-i18n') || id.includes('vue-sonner') || id.includes('/vue/') || id.includes('/vue@')) return 'vendor-vue';
                },
            },
        },
    },
});
