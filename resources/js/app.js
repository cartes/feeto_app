import '../css/app.css';
import './bootstrap';

import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { createApp, h } from 'vue';
import { route as ziggyRoute, ZiggyVue } from '../../vendor/tightenco/ziggy';

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

const routeParameterNames = (routeName) => {
    const uri = window.Ziggy?.routes?.[routeName]?.uri || '';

    return [...uri.matchAll(/{([^}?]+)\??}/g)].map((match) => match[1]);
};

const withTenantRouteDefault = (routeName, params) => {
    const parameterNames = routeParameterNames(routeName);

    if (!parameterNames.includes('tenantBySlug')) {
        return params;
    }

    if (params && !Array.isArray(params) && typeof params === 'object' && Object.prototype.hasOwnProperty.call(params, 'tenantBySlug')) {
        return params;
    }

    const currentTenantSlug = ziggyRoute().params.tenantBySlug;

    if (!currentTenantSlug) {
        return params;
    }

    if (params === undefined || params === null) {
        return { tenantBySlug: currentTenantSlug };
    }

    if (Array.isArray(params)) {
        return [currentTenantSlug, ...params];
    }

    if (['string', 'number'].includes(typeof params)) {
        return parameterNames.length === 1 ? params : [currentTenantSlug, params];
    }

    return { tenantBySlug: currentTenantSlug, ...params };
};

const routeWithTenantDefaults = (routeName, params, absolute, config) => ziggyRoute(
    routeName,
    withTenantRouteDefault(routeName, params),
    absolute,
    config,
);

window.route = routeWithTenantDefaults;

createInertiaApp({
    title: (title) => `${title} - ${appName}`,
    resolve: (name) =>
        resolvePageComponent(
            `./Pages/${name}.vue`,
            import.meta.glob('./Pages/**/*.vue'),
        ),
    setup({ el, App, props, plugin }) {
        const vueApp = createApp({ render: () => h(App, props) })
            .use(plugin)
            .use(ZiggyVue);

        vueApp.config.globalProperties.route = routeWithTenantDefaults;
        vueApp.provide('route', routeWithTenantDefaults);

        return vueApp.mount(el);
    },
    progress: {
        color: '#4B5563',
    },
});
