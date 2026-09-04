import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';

export default defineConfig({
    plugins: [
        laravel({
            input: 'resources/js/app.js',
            ssr: 'resources/js/ssr.js',
            refresh: true,
        }),
        vue({
            template: {
                transformAssetUrls: {
                    base: null,
                    includeAbsolute: false,
                },
            },
        }),
    ],
    build: {
        rollupOptions: {
            output: {
                manualChunks(id) {
                    if (id.includes('node_modules/vue/') || id.includes('node_modules/@vue/') || id.includes('node_modules/@inertiajs/')) {
                        return 'vendor-core';
                    }

                    if (id.includes('node_modules/apexcharts')) {
                        return 'vendor-charts';
                    }

                    if (id.includes('node_modules/@tiptap') || id.includes('node_modules/prosemirror')) {
                        return 'vendor-tiptap';
                    }

                    if (id.includes('node_modules/pusher-js') || id.includes('node_modules/laravel-echo')) {
                        return 'vendor-reverb';
                    }
                },
            },
        },
    },
});
