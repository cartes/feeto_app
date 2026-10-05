import assert from 'node:assert/strict';
import { after, test } from 'node:test';
import { createServer } from 'vite';
import vue from '@vitejs/plugin-vue';
import { createSSRApp } from 'vue';
import { renderToString } from 'vue/server-renderer';
import { router } from '@inertiajs/vue3';
import { workOrderColumns } from '../../resources/js/utils/workOrderColumns.js';

const server = await createServer({ configFile: false, plugins: [vue()], optimizeDeps: { noDiscovery: true, include: [] }, server: { middlewareMode: true, hmr: false, ws: false }, appType: 'custom' });
const components = {};
for (const name of ['TenantSearch', 'TenantMobileNavigation', 'TenantDailySummary', 'ActionEmptyState']) {
    components[name] = (await server.ssrLoadModule(`/resources/js/Components/${name}.vue`)).default;
}
after(() => server.close());

async function render(name, props) {
    let state;
    const app = createSSRApp(components[name], props);
    app.mixin({ created() { state ??= this.$.setupState; } });
    return { html: await renderToString(app), state };
}

test('global search sends the trimmed term to the actual list endpoint', async () => {
    const { state, html } = await render('TenantSearch', { searchUrl: '/taller/prueba/work-orders', listView: true });
    const original = router.get;
    let request;
    router.get = (url, data) => { request = { url, data }; };
    try {
        state.search = '  AB1234  ';
        state.submitSearch();
        assert.deepEqual(request, { url: '/taller/prueba/work-orders', data: { search: 'AB1234', view: 'list' } });
        assert.match(html, /role="search"/);
        assert.match(html, /type="search"/);
    } finally { router.get = original; }
});

test('customer search uses its own endpoint and does not send a work order view', async () => {
    const { state } = await render('TenantSearch', { searchUrl: '/taller/prueba/clients', label: 'Buscar cliente' });
    const original = router.get;
    let data;
    router.get = (_, values) => { data = values; };
    try {
        state.search = 'María';
        state.submitSearch();
        assert.deepEqual(data, { search: 'María' });
    } finally { router.get = original; }
});

test('empty searches do not navigate', async () => {
    const { state } = await render('TenantSearch', { searchUrl: '/orders' });
    const original = router.get;
    let calls = 0;
    router.get = () => { calls++; };
    try {
        state.search = '  ';
        state.submitSearch();
        assert.equal(calls, 0);
    } finally { router.get = original; }
});

const navItems = Array.from({ length: 10 }, (_, index) => ({ label: index === 0 ? 'Inicio' : `Sección ${index}`, url: `/section-${index}`, active: index === 0, icon: 'M3 12h18' }));
test('mobile navigation shows four labeled links and puts every section in the native modal', async () => {
    const { html } = await render('TenantMobileNavigation', { items: navItems, hasSettings: true });
    const nav = html.slice(html.indexOf('<nav'), html.indexOf('</nav>'));
    const dialog = html.slice(html.indexOf('<dialog'));
    assert.equal((nav.match(/<a /g) ?? []).length, 4);
    assert.match(nav, /Inicio/);
    assert.match(nav, /Más/);
    assert.match(nav, /aria-current="page"/);
    assert.match(nav, /data-tour="tenant-mobile-settings"/);
    assert.equal((dialog.match(/<a /g) ?? []).length, 10);
    assert.match(dialog, /Todas las secciones/);
});

test('mobile menu opens and closes through native dialog methods', async () => {
    const { state } = await render('TenantMobileNavigation', { items: navItems });
    let opened = 0;
    let closed = 0;
    state.menu = { showModal() { opened++; }, close() { closed++; } };
    state.openMenu();
    state.closeMenu();
    assert.equal(opened, 1);
    assert.equal(closed, 1);
    state.closeOnDesktop({ matches: false });
    assert.equal(closed, 1);
    state.closeOnDesktop({ matches: true });
    assert.equal(closed, 2);
});

test('mobile navigation uses only the authorized items provided', async () => {
    const { html } = await render('TenantMobileNavigation', { items: [navItems[0]] });
    assert.doesNotMatch(html, /Sección 1/);
    assert.doesNotMatch(html, /tenant-mobile-settings/);
});

test('daily summary links to pending requests and filtered ready vehicles', async () => {
    const { html } = await render('TenantDailySummary', { items: [
        { id: 'pending', label: 'Citas por revisar', count: 12, url: '#pending-appointments', attention: true },
        { id: 'ready', label: 'Vehículos listos', count: 8, url: '/orders?status=listo', attention: true },
    ] });
    assert.match(html, /href="#pending-appointments"/);
    assert.match(html, /href="\/orders\?status=listo"/);
    assert.match(html, />12<\/p>/);
    assert.match(html, />8<\/p>/);
});

test('empty states offer a direct route or an action button without inventing an action', async () => {
    const link = await render('ActionEmptyState', { title: 'Aún no hay cotizaciones', description: 'Crea la primera.', actionLabel: 'Crear cotización', actionHref: '/quotes/create' });
    assert.match(link.html, /href="\/quotes\/create"/);
    const button = await render('ActionEmptyState', { title: 'Sin resultados', description: 'Prueba otra búsqueda.', actionLabel: 'Limpiar búsqueda' });
    assert.match(button.html, /<button/);
    assert.doesNotMatch(button.html, /<a /);
    const readOnly = await render('ActionEmptyState', { title: 'Sin órdenes', description: 'Todavía no se han registrado.' });
    assert.doesNotMatch(readOnly.html, /<button|<a /);
});

test('basic and professional boards use their actual enabled states', () => {
    const basic = workOrderColumns(['recepcion', 'taller', 'aviso_cliente', 'listo']);
    assert.deepEqual(basic.map(item => item.id), ['recepcion', 'taller', 'aviso_cliente', 'listo']);
    assert.equal(basic[1].title, 'En taller');
    assert.equal(basic[2].title, 'Avisar al cliente');
    const professional = workOrderColumns(['recepcion', 'diagnostico', 'esperando_repuestos', 'control_calidad', 'listo']);
    assert.equal(professional.length, 5);
    assert.equal(professional[4].title, 'Listo para entrega');
});

test('custom board columns retain configured order and readable labels', () => {
    const columns = workOrderColumns(['recepcion', 'lavado_final', 'listo']);
    assert.equal(columns[1].id, 'lavado_final');
    assert.equal(columns[1].title, 'lavado final');
    assert.equal(columns[2].id, 'listo');
});
