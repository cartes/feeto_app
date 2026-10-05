import assert from 'node:assert/strict';
import { after, test } from 'node:test';
import { createServer } from 'vite';
import vue from '@vitejs/plugin-vue';
import { createSSRApp } from 'vue';
import { renderToString } from 'vue/server-renderer';

const server = await createServer({ configFile: false, plugins: [vue()], optimizeDeps: { noDiscovery: true, include: [] }, server: { middlewareMode: true, hmr: false, ws: false }, appType: 'custom' });
const { default: Card } = await server.ssrLoadModule('/resources/js/Components/TenantPublicPageCard.vue');
after(() => server.close());

async function render(props) {
    let state;
    const app = createSSRApp(Card, props);
    app.mixin({ created() { if (this.$.setupState.copyLink) state = this.$.setupState; } });
    return { html: await renderToString(app), state };
}

const publicUrl = 'https://example.com/taller/prueba';

test('shows the public page and encodes the WhatsApp message without a customization link for staff', async () => {
    const { html } = await render({ publicUrl });
    assert.match(html, /Tu página de reservas ya está creada/);
    assert.match(html, /Ver mi página/);
    assert.match(html, /Copiar enlace/);
    assert.match(html, /Compartir por WhatsApp/);
    assert.ok(html.includes(`https://wa.me/?text=${encodeURIComponent(`Conoce nuestro taller y solicita tu cita aquí: ${publicUrl}`)}`));
    assert.doesNotMatch(html, /Personalizar/);
});

test('shows customization when the user has a settings URL', async () => {
    const { html } = await render({ publicUrl, settingsUrl: '/settings?tab=website' });
    assert.match(html, /Personalizar/);
    assert.match(html, /href="\/settings\?tab=website"/);
});

test('copies the exact tenant URL and reports success', async () => {
    let copied;
    Object.defineProperty(globalThis, 'navigator', { configurable: true, value: { clipboard: { writeText: async value => { copied = value; } } } });
    const { state } = await render({ publicUrl });
    await state.copyLink();
    assert.equal(copied, publicUrl);
    assert.match(state.copyMessage, /Enlace copiado/);
    assert.equal(state.copying, false);
});

test('offers manual copying when clipboard access is denied', async () => {
    Object.defineProperty(globalThis, 'navigator', { configurable: true, value: { clipboard: { writeText: async () => { throw new Error('Denied'); } } } });
    const { state } = await render({ publicUrl });
    await state.copyLink();
    assert.match(state.copyMessage, /manualmente/);
    assert.equal(state.copying, false);
});
