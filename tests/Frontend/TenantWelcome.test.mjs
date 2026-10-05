import assert from 'node:assert/strict';
import { after, test } from 'node:test';
import { fileURLToPath } from 'node:url';
import { createServer } from 'vite';
import vue from '@vitejs/plugin-vue';
import { createSSRApp, h } from 'vue';
import { renderToString } from 'vue/server-renderer';
import { createInertiaApp, router } from '@inertiajs/vue3';

const server = await createServer({ configFile: false, plugins: [vue()], resolve: { alias: { '@': fileURLToPath(new URL('../../resources/js', import.meta.url)) } }, optimizeDeps: { noDiscovery: true, include: [] }, server: { middlewareMode: true, hmr: false, ws: false }, appType: 'custom' });
const { default: Welcome } = await server.ssrLoadModule('/resources/js/Pages/TenantWelcome.vue');
after(() => server.close());
const welcome = {
    next_step: 1, profile: { name: 'Taller Cartes', comuna: 'Concepción', address: 'Calle 123', phone: '+56912345678', whatsapp_number: '', email: '', description: '' },
    suggested_description: 'Taller Cartes es un taller automotriz en Concepción.',
    url: '/taller/cartes/bienvenida', update_url: '/taller/cartes/bienvenida', dashboard_url: '/taller/cartes/dashboard', public_url: 'https://example.com/taller/cartes',
};
async function render(step = 1, profile = {}, query = '') {
    let state;
    const result = await createInertiaApp({
        page: { component: 'TenantWelcome', props: { welcome: { ...welcome, next_step: step, profile: { ...welcome.profile, ...profile } } }, url: welcome.url + query, version: null },
        resolve: () => Welcome,
        setup({ App, props, plugin }) {
            const app = createSSRApp({ render: () => h(App, props) }).use(plugin);
            app.mixin({ created() { if (this.$.setupState.submit) state = this.$.setupState; } });
            return app;
        }, render: renderToString,
    });
    return { html: result.body, head: result.head.join(''), state };
}

test('welcome is a standalone four-step form with business data and a deferral action', async () => {
    const { html, head } = await render();
    assert.match(html, /Paso 1 de 4/);
    assert.match(html, /value="Taller Cartes"/);
    assert.match(html, /Continuar después/);
    assert.match(html, /autocomplete="organization"/);
    assert.match(html, /aria-current="step"/);
    assert.match(head, /noindex, nofollow/);
    assert.doesNotMatch(html, /data-tour="tenant-mobile-navigation"/);
});

test('contact makes publication explicit and allows business email and WhatsApp', async () => {
    const { html } = await render(2);
    assert.match(html, /se mostrarán en tu página pública/);
    assert.match(html, /type="tel"/);
    assert.match(html, /Correo del taller \(opcional\)/);
    assert.match(html, /WhatsApp \(opcional\)/);
});

test('description is suggested from saved data and existing custom text is preserved', async () => {
    const suggested = await render(3);
    assert.equal(suggested.state.form.description, welcome.suggested_description);
    assert.match(suggested.html, /maxlength="500"/);
    const custom = await render(3, { description: 'Nuestro texto propio.' });
    assert.equal(custom.state.form.description, 'Nuestro texto propio.');
});

test('review escapes user input and offers the stable public link', async () => {
    const { html } = await render(4, { name: '<script>alert(1)</script>', description: 'Texto público.' });
    assert.match(html, /&lt;script&gt;/);
    assert.doesNotMatch(html, /<script>alert/);
    assert.match(html, /Texto público\./);
    assert.match(html, /Copiar enlace/);
    assert.match(html, /Finalizar y entrar a mi taller/);
    assert.match(html, /confirmará la disponibilidad/);
});

test('direct links cannot display steps ahead of the saved progress', async () => {
    const { state } = await render(1, {}, '?step=4');
    assert.equal(state.step, 1);
    const back = await render(3, {}, '?step=1');
    assert.equal(back.state.step, 1);
});

test('saving submits only fields for the current step and finishing and deferring are explicit', async () => {
    const original = router.patch;
    const calls = [];
    router.patch = (url, data) => { calls.push({ url, data }); };
    try {
        const first = await render(1);
        first.state.form.name = 'Nombre editado';
        first.state.submit();
        assert.deepEqual(calls.pop(), { url: welcome.update_url, data: { action: 'save', step: 1, name: 'Nombre editado', comuna: 'Concepción', address: 'Calle 123' } });
        const last = await render(4);
        last.state.submit();
        assert.deepEqual(calls.pop().data, { action: 'finish' });
        last.state.defer();
        assert.deepEqual(calls.pop().data, { action: 'defer' });
    } finally { router.patch = original; }
});
