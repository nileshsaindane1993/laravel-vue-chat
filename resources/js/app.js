import './echo';
import { createInertiaApp } from '@inertiajs/vue3';
import { echo } from '@inertiajs/vue3/echo';
import { createApp, h } from 'vue';

const pages = import.meta.glob('./Pages/**/*.vue', { eager: true });

createInertiaApp({
    title: (title) => (title ? `${title}` : ' Laravel Vue Chat'),
    resolve: (name) => {
        const page = pages[`./Pages/${name}.vue`];

        if (!page) {
            throw new Error(`Inertia page "${name}" was not found.`);
        }

        return page;
    },
    setup({ el, App, props, plugin }) {
        createApp({ render: () => h(App, props) }).use(plugin).mount(el);
    },
    live: echo(),
});
