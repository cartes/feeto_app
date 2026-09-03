import '../css/app.css';
import './bootstrap';

import { createInertiaApp, router, usePage } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { createApp, h } from 'vue';
import { route as ziggyRoute, ZiggyVue } from '../../vendor/tightenco/ziggy';

const appName = import.meta.env.VITE_APP_NAME || 'TallerFlow';
let rememberedTenantSlug = window.history.state?.page?.props?.tenant?.slug || null;

const routeParameterNames = (routeName) => {
    const uri = window.Ziggy?.routes?.[routeName]?.uri || '';

    return [...uri.matchAll(/{([^}?]+)\??}/g)].map((match) => match[1]);
};

const tenantSlugFromPath = (path) => {
    const tenantPathMatch = path.match(/^\/taller\/([^/]+)/);

    return tenantPathMatch?.[1] ? decodeURIComponent(tenantPathMatch[1]) : null;
};

const rememberTenantSlug = (page = null) => {
    rememberedTenantSlug = page?.props?.tenant?.slug
        || tenantSlugFromPath(page?.url || '')
        || tenantSlugFromPath(window.location.pathname)
        || rememberedTenantSlug;
};

const currentTenantSlug = () => {
    rememberTenantSlug(window.history.state?.page);

    return rememberedTenantSlug;
};

const withTenantRouteDefault = (routeName, params) => {
    const parameterNames = routeParameterNames(routeName);

    if (!parameterNames.includes('tenantBySlug')) {
        return params;
    }

    if (params && !Array.isArray(params) && typeof params === 'object' && Object.prototype.hasOwnProperty.call(params, 'tenantBySlug')) {
        return params;
    }

    const tenantSlug = currentTenantSlug();

    if (!tenantSlug) {
        return params;
    }

    if (params === undefined || params === null) {
        return { tenantBySlug: tenantSlug };
    }

    if (Array.isArray(params)) {
        return [tenantSlug, ...params];
    }

    if (['string', 'number'].includes(typeof params)) {
        return parameterNames.length === 1 ? params : [tenantSlug, params];
    }

    return { tenantBySlug: tenantSlug, ...params };
};

const routeWithTenantDefaults = (routeName, params, absolute, config) => ziggyRoute(
    routeName,
    withTenantRouteDefault(routeName, params),
    absolute,
    config,
);

window.route = routeWithTenantDefaults;
router.on('beforeUpdate', rememberTenantSlug);
router.on('navigate', (event) => {
    rememberTenantSlug(event);
    if (window.gtag) {
        window.gtag('event', 'page_view', {
            page_location: window.location.href,
            page_path: event.detail.page.url,
            page_title: document.title,
        });
    }
});

createInertiaApp({
    // Las páginas públicas (<SeoHead>) definen su título SEO exacto desde el
    // servidor: no se les agrega el sufijo de la app para no alterar el <title>
    // que ve Google al renderizar JS. El resto de páginas conserva el sufijo.
    title: (title) => {
        const seoTitle = usePage().props?.seo?.title;

        if (title && seoTitle && title === seoTitle) {
            return title;
        }

        return `${title} - ${appName}`;
    },
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

        return vueApp.mount(el);
    },
    progress: {
        color: '#4B5563',
    },
});
