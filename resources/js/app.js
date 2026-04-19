import './bootstrap'
import '../css/app.css'
import { createApp, h } from 'vue'
import { createInertiaApp, Link, Head } from '@inertiajs/vue3'
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers'
import { ZiggyVue } from '../../vendor/tightenco/ziggy'

createInertiaApp({
    title: (title) => `${title} — VoltexaHub`,
    resolve: (name) => resolvePageComponent(`./Pages/${name}.vue`,
        import.meta.glob('./Pages/**/*.vue')),
    setup({ el, App, props, plugin }) {
        createApp({ render: () => h(App, props) })
            .use(plugin)
            .use(ZiggyVue)
            .component('Link', Link)
            .component('Head', Head)
            .mount(el)
    },
    progress: { color: '#7c3aed' },
})
