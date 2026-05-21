import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';
import { VitePWA } from 'vite-plugin-pwa';
import path from 'path';

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
        VitePWA({
            strategies: 'injectManifest',
            srcDir: 'resources/js',
            filename: 'sw.js',
            injectManifest: {
                globPatterns: ['**/*.{js,css,html,ico,png,svg,woff2}'],
                globDirectory: 'public',
                maximumFileSizeToCacheInBytes: 8000000,
            },
            manifest: false, // We use our own public/manifest.json
            injectRegister: false, // We register manually in pwa.js
            devOptions: {
                enabled: false, // Disable in dev to avoid cache issues
            },
            workbox: {
                navigateFallback: '/offline',
                navigateFallbackDenylist: [/^\/(build|js|css|fonts|images|storage|api|webhooks|radio\/stream)/],
            },
        }),
    ],
    resolve: {
        alias: {
            '@': path.resolve(__dirname, 'resources/js'),
            '@css': path.resolve(__dirname, 'resources/css'),
            'ziggy-js': path.resolve(__dirname, 'vendor/tightenco/ziggy'),
        },
    },
    build: {
        cssCodeSplit: true,
        sourcemap: false,
        rollupOptions: {
            output: {
                manualChunks(id) {
                    // Isolate heavy vendors into separate chunks for parallel loading
                    if (id.includes('node_modules')) {
                        if (id.includes('leaflet') || id.includes('markercluster') || id.includes('vue-leaflet')) {
                            return 'leaflet';
                        }
                        if (id.includes('inertiajs')) {
                            return 'inertia';
                        }
                        if (id.includes('vue') || id.includes('@vue')) {
                            return 'vue-vendor';
                        }
                        if (id.includes('pinia')) {
                            return 'pinia';
                        }
                        if (id.includes('ziggy')) {
                            return 'ziggy';
                        }
                        if (id.includes('workbox')) {
                            return 'workbox';
                        }
                        if (id.includes('three')) {
                            return 'threejs';
                        }
                        if (id.includes('wavesurfer')) {
                            return 'audio';
                        }
                        if (id.includes('plotly')) {
                            return 'charts';
                        }
                        if (id.includes('laravel-echo') || id.includes('pusher')) {
                            return 'realtime';
                        }
                        if (id.includes('tailwindcss')) {
                            return 'tailwind';
                        }
                        return 'vendor';
                    }
                },
                assetFileNames: (assetInfo) => {
                    if (!assetInfo.name) {
                        return 'assets/[name]-[hash][extname]';
                    }
                    const info = assetInfo.name.split('.');
                    const ext = info[info.length - 1];
                    if (/\.(woff2?|ttf|otf|eot)$/i.test(assetInfo.name)) {
                        return 'assets/fonts/[name]-[hash][extname]';
                    }
                    if (/\.(png|jpe?g|gif|svg|webp|ico)$/i.test(assetInfo.name)) {
                        return 'assets/images/[name]-[hash][extname]';
                    }
                    return 'assets/[name]-[hash][extname]';
                },
                chunkFileNames: 'assets/js/[name]-[hash].js',
                entryFileNames: 'assets/js/[name]-[hash].js',
            },
        },
        reportCompressedSize: false,
    },
    esbuild: {
        drop: process.env.NODE_ENV === 'production' ? ['console', 'debugger'] : [],
    },
});
