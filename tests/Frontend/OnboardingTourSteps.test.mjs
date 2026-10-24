import assert from 'node:assert/strict';
import { readdirSync, readFileSync, statSync } from 'node:fs';
import { join } from 'node:path';
import { test } from 'node:test';
import { fileURLToPath } from 'node:url';

const jsRoot = fileURLToPath(new URL('../../resources/js', import.meta.url));
const { TOUR_VARIANTS } = await import('../../resources/js/Components/onboardingTourSteps.js');

function sources(dir) {
    return readdirSync(dir).flatMap((name) => {
        const path = join(dir, name);
        if (statSync(path).isDirectory()) return sources(path);
        return /\.vue$/.test(name) ? [readFileSync(path, 'utf8')] : [];
    });
}

const markup = sources(jsRoot).join('\n');
const steps = Object.entries(TOUR_VARIANTS).flatMap(([variant, modes]) => Object.entries(modes).flatMap(([mode, list]) => list.map((step, index) => ({ variant, mode, index, step }))));

test('every targeted tour step points to a data-tour attribute that exists in the app', () => {
    for (const { variant, mode, index, step } of steps) {
        for (const selector of step.selectors ?? []) {
            const name = selector.match(/data-tour="([^"]+)"/)?.[1];
            assert.ok(name, `${variant}.${mode}[${index}] has an unsupported selector ${selector}`);
            assert.ok(markup.includes(`data-tour="${name}"`), `${variant}.${mode}[${index}] targets missing data-tour="${name}"`);
        }
    }
});

test('every step has a title and a description written for the user', () => {
    for (const { variant, mode, index, step } of steps) {
        assert.ok(step.title?.trim(), `${variant}.${mode}[${index}] needs a title`);
        assert.ok(step.description?.trim().length > 20, `${variant}.${mode}[${index}] needs a description`);
        assert.match(`${step.title} ${step.description}`, /^[^\u0000]*$/);
    }
});

test('copy keeps Spanish accents on the common words', () => {
    const copy = steps.map(({ step }) => `${step.title} ${step.description}`).join(' ');
    assert.doesNotMatch(copy, /\b(Configuracion|Busqueda|Navegacion|rapidamente|cotizacion|recepcion|ordenes|vehiculo)\b/i);
});

test('the general tour opens and closes with an untargeted step on desktop and mobile', () => {
    for (const mode of ['desktop', 'mobile']) {
        const list = TOUR_VARIANTS.tenant[mode];
        assert.equal(list[0].selectors, undefined);
        assert.equal(list.at(-1).selectors, undefined);
    }
});

test('section variants match the ones the server accepts', () => {
    const request = readFileSync(fileURLToPath(new URL('../../app/Http/Requests/CompleteOnboardingTourRequest.php', import.meta.url)), 'utf8');
    const accepted = [...request.matchAll(/'([a-z-]+)',\n/g)].map((match) => match[1]);
    const sections = Object.keys(TOUR_VARIANTS).filter((key) => key !== 'tenant');
    assert.deepEqual([...sections].sort(), [...accepted].sort());
});

test('the support widget can replay the general and the section tour', () => {
    const widget = readFileSync(fileURLToPath(new URL('../../resources/js/Components/FloatingSupportButton.vue', import.meta.url)), 'utf8');
    const tour = readFileSync(fileURLToPath(new URL('../../resources/js/Components/OnboardingTour.vue', import.meta.url)), 'utf8');
    assert.match(widget, /replayTour\('tenant'\)/);
    assert.match(widget, /replayTour\(section\)/);
    assert.match(tour, /ONBOARDING_TOUR_REPLAY_EVENT, handleReplay/);
});
