import '../css/app.css';
import './bootstrap';
import './echo';
import { registerServiceWorker } from './pwa';

import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { createApp, h } from 'vue';
import { createPinia } from 'pinia';
import { ZiggyVue } from '../../vendor/tightenco/ziggy';
import MiniPlayer from '@/Components/Audio/MiniPlayer.vue';
import CookieBanner from '@/Components/CookieBanner.vue';
import PwaInstallPrompt from '@/Components/PwaInstallPrompt.vue';
import PwaUpdatePrompt from '@/Components/PwaUpdatePrompt.vue';
import OfflineBanner from '@/Components/OfflineBanner.vue';
import ArborisisAgent from '@/Components/Agent/ArborisisAgent.vue';
import { usePwaStore } from '@/Stores/pwa';

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

const pinia = createPinia();

if (typeof window !== 'undefined') {
    registerServiceWorker();
}

const registerWebMcpTools = () => {
    const modelContext = navigator?.modelContext;

    if (!modelContext || typeof modelContext.provideContext !== 'function') {
        return;
    }

    modelContext.provideContext({
        tools: [
            {
                name: 'browse_public_sounds',
                description: 'Open the public Arborisis nature sounds catalog.',
                inputSchema: {
                    type: 'object',
                    properties: {},
                    additionalProperties: false,
                },
                execute: async () => {
                    window.location.assign('/sounds');

                    return { opened: '/sounds' };
                },
            },
            {
                name: 'search_public_sounds',
                description: 'Search public Arborisis nature sounds by keyword.',
                inputSchema: {
                    type: 'object',
                    properties: {
                        query: {
                            type: 'string',
                            minLength: 1,
                            maxLength: 120,
                        },
                    },
                    required: ['query'],
                    additionalProperties: false,
                },
                execute: async ({ query }) => {
                    const url = `/sounds?search=${encodeURIComponent(query)}`;
                    window.location.assign(url);

                    return { opened: url };
                },
            },
            {
                name: 'open_sound_map',
                description: 'Open the public sound map with approximate locations.',
                inputSchema: {
                    type: 'object',
                    properties: {},
                    additionalProperties: false,
                },
                execute: async () => {
                    window.location.assign('/map');

                    return { opened: '/map' };
                },
            },
            {
                name: 'open_radio',
                description: 'Open Arborisis Radio.',
                inputSchema: {
                    type: 'object',
                    properties: {},
                    additionalProperties: false,
                },
                execute: async () => {
                    window.location.assign('/radio');

                    return { opened: '/radio' };
                },
            },
        ],
    });
};

if (typeof window !== 'undefined') {
    registerWebMcpTools();
}

createInertiaApp({
    title: (title) => `${title} - ${appName}`,
    resolve: (name) =>
        resolvePageComponent(
            `./Pages/${name}.vue`,
            import.meta.glob('./Pages/**/*.vue'),
        ),
    setup({ el, App, props, plugin }) {
        const app = createApp({
            render: () => h('div', { class: 'relative' }, [
                h(App, props),
                h(MiniPlayer),
                h(CookieBanner),
                h(PwaInstallPrompt),
                h(PwaUpdatePrompt),
                h(OfflineBanner),
                h(ArborisisAgent),
            ]),
        });

        app.use(plugin)
            .use(pinia)
            .use(ZiggyVue)
            .mount(el);

        // Initialize PWA store after mounting
        const pwaStore = usePwaStore();
        pwaStore.init();

        return app;
    },
    progress: {
        color: '#4B5563',
    },
});
