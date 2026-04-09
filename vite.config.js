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
                home: './assets/pages/home/index.js',
                transfer: './assets/pages/transfer/index.js',
                manage: './assets/pages/manage/index.js',
            },
        },
    },
});
