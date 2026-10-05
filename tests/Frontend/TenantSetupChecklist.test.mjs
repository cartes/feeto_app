import assert from 'node:assert/strict';
import { after, test } from 'node:test';
import { createServer } from 'vite';
import vue from '@vitejs/plugin-vue';
import { createSSRApp } from 'vue';
import { renderToString } from 'vue/server-renderer';

const server = await createServer({ configFile: false, plugins: [vue()], optimizeDeps: { noDiscovery: true, include: [] }, server: { middlewareMode: true, hmr: false, ws: false }, appType: 'custom' });
const { default: Checklist } = await server.ssrLoadModule('/resources/js/Components/TenantSetupChecklist.vue');
after(() => server.close());

const ids = ['business_details', 'branding_contact', 'scheduling', 'public_page', 'share_link'];
function checklist(completedCount = 0) {
    return {
        completed_count: completedCount,
        total: 5,
        is_complete: completedCount === 5,
        update_url: '/taller/prueba/settings/setup-checklist',
        overview_url: '/taller/prueba/settings?tab=website',
        steps: ids.map((id, index) => ({ id, title: `Paso ${index + 1}`, description: 'Revisa y guarda tus cambios.', action: 'Revisar', url: id === 'public_page' ? '/taller/prueba' : `/settings?step=${id}`, external: id === 'public_page', completed: index < completedCount })),
    };
}
async function render(data = checklist(), stepId = null, configure = () => {}) {
    let state;
    const app = createSSRApp(Checklist, { checklist: data, stepId });
    app.mixin({ created() {
        if (this.$.setupState.updateStep) {
            state = this.$.setupState;
            configure(state);
        }
    } });
    return { html: await renderToString(app), state };
}

test('shows all five steps, progress and explicit sharing confirmation', async () => {
    const { html } = await render(checklist(2));
    assert.equal((html.match(/<li /g) ?? []).length, 5);
    assert.match(html, /2 de 5 pasos revisados/);
    assert.match(html, /Ya compartí el enlace/);
    assert.match(html, /target="_blank" rel="noopener noreferrer"/);
    assert.match(html, /<progress value="2" max="5"/);
});

test('completed preparation occupies a compact summary and can be reopened', async () => {
    const data = checklist(5);
    const { html } = await render(data);
    assert.match(html, /Primeros pasos completados/);
    assert.match(html, /Revisar pasos/);
    assert.doesNotMatch(html, /<li /);
    const reopened = await render(data, null, state => { state.expanded = true; });
    assert.equal((reopened.html.match(/<li /g) ?? []).length, 5);
    assert.match(reopened.html, /Volver a pendiente/);
});

test('settings show the relevant step with access to the full checklist', async () => {
    const { html } = await render(checklist(), 'scheduling');
    assert.equal((html.match(/<li /g) ?? []).length, 1);
    assert.match(html, /Paso 3/);
    assert.doesNotMatch(html, /Paso 1/);
    assert.match(html, /Ver todos los pasos/);
});

test('sends the chosen completion state to the tenant endpoint and preserves scroll', async () => {
    const { state } = await render();
    let submitted;
    state.form.patch = (url, options) => { submitted = { url, options, step: state.form.step, completed: state.form.completed }; };
    state.updateStep(checklist().steps[4]);
    assert.equal(submitted.url, '/taller/prueba/settings/setup-checklist');
    assert.equal(submitted.step, 'share_link');
    assert.equal(submitted.completed, true);
    assert.equal(submitted.options.preserveScroll, true);
    state.updateStep(checklist(5).steps[0]);
    assert.equal(submitted.completed, false);
});

test('prevents duplicate saves while a request is processing', async () => {
    const { state, html } = await render(checklist(), null, state => { state.form.processing = true; });
    let calls = 0;
    state.form.patch = () => { calls += 1; };
    state.updateStep(checklist().steps[0]);
    assert.equal(calls, 0);
    assert.equal((html.match(/<button[^>]* disabled/g) ?? []).length, 5);
});

test('shows server validation errors without hiding the checklist', async () => {
    const { html } = await render(checklist(), null, state => { state.form.errors.step = 'No pudimos guardar este paso.'; });
    assert.match(html, /role="alert"/);
    assert.match(html, /No pudimos guardar este paso/);
    assert.equal((html.match(/<li /g) ?? []).length, 5);
});
