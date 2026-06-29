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
                    if (id.includes('@tiptap') || id.includes('prosemirror')) {
                        return 'vendor-tiptap';
                    }

                    if (id.includes('apexcharts') || id.includes('vue3-apexcharts')) {
                        return 'vendor-charts';
                    }
                },
            },
        },
    },
});
