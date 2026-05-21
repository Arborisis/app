import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { createSSRApp, h } from 'vue';
import { renderToString } from '@vue/server-renderer';
import { createPinia } from 'pinia';
import { ZiggyVue } from 'ziggy-js';
import createServer from '@inertiajs/vue3/server';

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

createServer((page) =>
    createInertiaApp({
        page,
        render: renderToString,
        title: (title) => `${title} - ${appName}`,
        resolve: (name) =>
            resolvePageComponent(
                `./Pages/${name}.vue`,
                import.meta.glob('./Pages/**/*.vue'),
            ),
        setup({ App, props, plugin }) {
            return createSSRApp({
                render: () => h(App, props),
            })
                .use(plugin)
                .use(createPinia())
                .use(ZiggyVue, {
                    ...page.props.ziggy,
                    location: new URL(page.props.ziggy.location),
                });
        },
    }),
);
