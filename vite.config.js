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
        },
    },
});
