import assert from 'node:assert/strict';
import { after, test } from 'node:test';
import { createServer } from 'vite';
import vue from '@vitejs/plugin-vue';
import { createSSRApp, h } from 'vue';
import { renderToString } from 'vue/server-renderer';
import { createInertiaApp, router } from '@inertiajs/vue3';
import { workOrderColumns } from '../../resources/js/utils/workOrderColumns.js';

const server = await createServer({ configFile: false, plugins: [vue()], resolve: { alias: { '@': '/resources/js' } }, optimizeDeps: { noDiscovery: true, include: [] }, server: { middlewareMode: true, hmr: false, ws: false }, appType: 'custom' });
const components = {};
for (const name of ['TenantSearch', 'TenantMobileNavigation', 'TenantDailySummary', 'ActionEmptyState', 'TenantBoard']) {
    components[name] = (await server.ssrLoadModule(`/resources/js/Components/${name}.vue`)).default;
}
after(() => server.close());
globalThis.route = (name) => `/${name}`;

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
test('mobile navigation shows four labeled links and puts the remaining sections in the native modal', async () => {
    const { html } = await render('TenantMobileNavigation', { items: navItems, hasSettings: true });
    const nav = html.slice(html.indexOf('<nav'), html.indexOf('</nav>'));
    const dialog = html.slice(html.indexOf('<dialog'));
    assert.equal((nav.match(/<a /g) ?? []).length, 4);
    assert.match(nav, /Inicio/);
    assert.match(nav, /Más/);
    assert.match(nav, /aria-current="page"/);
    assert.match(nav, /data-tour="tenant-mobile-more"/);
    assert.equal((dialog.match(/<a /g) ?? []).length, 6);
    assert.doesNotMatch(dialog, /Sección 1</);
    assert.match(dialog, /Sección 9/);
});

const routedItems = [
    { label: 'Inicio', route: 'taller.dashboard', url: '/inicio', icon: 'M3 12h18' },
    { label: 'Agenda', route: 'appointments.index', url: '/agenda', icon: 'M3 12h18' },
    { label: 'Clientes', route: 'clients.index', url: '/clientes', icon: 'M3 12h18' },
    { label: 'Órdenes', route: 'work-orders.index', url: '/ordenes', icon: 'M3 12h18' },
    { label: 'Recepción', route: 'receptions.create', url: '/recepcion', icon: 'M3 12h18' },
    { label: 'Reportes', route: 'reports.index', url: '/reportes', icon: 'M3 12h18' },
];
test('mobile navigation prioritises home, orders, reception and agenda and highlights reception in the center', async () => {
    const { html } = await render('TenantMobileNavigation', { items: routedItems });
    const nav = html.slice(html.indexOf('<nav'), html.indexOf('</nav>'));
    const dialog = html.slice(html.indexOf('<dialog'));
    const labels = [...nav.matchAll(/<span class="max-w-full truncate">([^<]+)</g)].map(match => match[1]);
    assert.deepEqual(labels, ['Inicio', 'Órdenes', 'Recepción', 'Agenda']);
    assert.match(nav, /rounded-full bg-\[#FF7A00\]/);
    assert.match(dialog, /Clientes/);
    assert.match(dialog, /Reportes/);
    assert.doesNotMatch(dialog, /Órdenes/);
});

test('mobile navigation falls back to available items when priority sections are not permitted', async () => {
    const { html } = await render('TenantMobileNavigation', { items: [routedItems[0], routedItems[2], routedItems[5]] });
    const nav = html.slice(html.indexOf('<nav'), html.indexOf('</nav>'));
    assert.equal((nav.match(/<a /g) ?? []).length, 3);
    assert.doesNotMatch(nav, /rounded-full bg-\[#FF7A00\]/);
});

test('mobile menu offers logout only when a logout url is provided', async () => {
    const withLogout = await render('TenantMobileNavigation', { items: navItems, logoutUrl: '/logout' });
    assert.match(withLogout.html, /data-tour="tenant-mobile-logout"/);
    assert.match(withLogout.html, /Cerrar sesión/);
    const without = await render('TenantMobileNavigation', { items: navItems });
    assert.doesNotMatch(without.html, /tenant-mobile-logout/);
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

const boardVehicle = (overrides) => ({ id: 1, plate: 'ABCD12', vehicle: 'Toyota Yaris', status: 'recepcion', status_label: 'Recepción', since: new Date().toISOString(), url: '/orders/1', client_name: 'María Pérez', needs_notice: false, whatsapp_url: 'https://wa.me/56912345678?text=Hola', phone_url: 'tel:+56912345678', ...overrides });
async function renderBoard(board) {
    const result = await createInertiaApp({
        page: { component: 'TenantBoard', props: { board, tenant: { slug: 'cartes' } }, url: '/taller/cartes/dashboard', version: null },
        resolve: () => components.TenantBoard,
        setup({ App, props, plugin }) {
            const app = createSSRApp({ render: () => h(App, props) }).use(plugin);
            app.config.globalProperties.route = globalThis.route;
            return app;
        },
        render: renderToString,
    });
    return { html: result.body };
}
const boardWith = (vehicles) => ({ workshop: { total: vehicles.length, stages: [], vehicles }, low_stock: null });

test('board cards put vehicles to notify first and offer one-tap WhatsApp, call and order actions', async () => {
    const { html } = await renderBoard(boardWith([
        boardVehicle({ id: 1, plate: 'AAAA11' }),
        boardVehicle({ id: 2, plate: 'BBBB22', status: 'listo', status_label: 'Listo', needs_notice: true, whatsapp_url: 'https://wa.me/56900000000?text=Listo', phone_url: 'tel:+56900000000' }),
    ]));
    const plates = [...html.matchAll(/tracking-widest[^>]*>([A-Z0-9]+)</g)].map(match => match[1]);
    assert.deepEqual(plates, ['BBBB22', 'AAAA11']);
    assert.equal((html.match(/data-board-vehicle/g) ?? []).length, 2);
    assert.match(html, /href="https:\/\/wa\.me\/56900000000\?text=Listo"[^>]*target="_blank"/);
    assert.match(html, /aria-label="Avisar al cliente por WhatsApp"/);
    assert.match(html, /aria-label="Escribir al cliente por WhatsApp"/);
    assert.match(html, /href="tel:\+56900000000"/);
    assert.match(html, /aria-label="Ver orden"/);
});

test('board cards without a phone explain it instead of showing dead contact buttons', async () => {
    const { html } = await renderBoard(boardWith([boardVehicle({ whatsapp_url: null, phone_url: null })]));
    assert.match(html, /data-board-no-phone/);
    assert.match(html, /Cliente sin teléfono registrado/);
    assert.doesNotMatch(html, /data-board-whatsapp|data-board-call/);
});

test('board cards flag vehicles that have been in the workshop for three days or more', async () => {
    const old = new Date(Date.now() - 4 * 86400000).toISOString();
    const stale = await renderBoard(boardWith([boardVehicle({ since: old })]));
    assert.match(stale.html, /4 días/);
    assert.match(stale.html, /font-bold text-amber-700/);
    const fresh = await renderBoard(boardWith([boardVehicle()]));
    assert.doesNotMatch(fresh.html, /text-amber-700 dark:text-amber-400">\s*Ingresó/);
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
