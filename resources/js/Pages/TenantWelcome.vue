<script setup>
import { computed, watch } from 'vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import TenantPublicPageCard from '@/Components/TenantPublicPageCard.vue';

const props = defineProps({ welcome: { type: Object, required: true } });
const page = usePage();
const steps = ['Tu taller', 'Contacto', 'Presentación', 'Tu página'];
const step = computed(() => {
    const requested = Number(new URL(page.url, 'http://localhost').searchParams.get('step'));
    return Math.max(1, Math.min(requested || props.welcome.next_step, props.welcome.next_step, 4));
});
const form = useForm({ ...props.welcome.profile });
watch(() => props.welcome, welcome => Object.assign(form, welcome.profile));
watch(step, value => {
    if (value === 3 && !form.description) form.description = props.welcome.suggested_description;
}, { immediate: true });
const fields = { 1: ['name', 'comuna', 'address'], 2: ['phone', 'whatsapp_number', 'email'], 3: ['description'] };
const submit = () => {
    form.transform(data => step.value === 4 ? { action: 'finish' } : {
        action: 'save', step: step.value,
        ...Object.fromEntries(fields[step.value].map(field => [field, data[field]])),
    }).patch(props.welcome.update_url, { preserveScroll: false });
};
const defer = () => form.transform(() => ({ action: 'defer' })).patch(props.welcome.update_url);
const inputClass = 'mt-2 block w-full rounded-xl border-gray-300 text-gray-900 focus:border-orange-500 focus:ring-orange-500';
</script>

<template>
    <div class="min-h-screen bg-gray-50 text-gray-900">
        <Head title="Bienvenido a tu taller"><meta name="robots" content="noindex, nofollow" /></Head>
        <header class="border-b border-gray-100 bg-white px-5 py-4">
            <div class="mx-auto flex max-w-4xl flex-wrap items-center justify-between gap-3">
                <ApplicationLogo class="h-9 w-auto" />
                <button type="button" :disabled="form.processing" class="rounded-lg px-3 py-2 text-sm font-semibold text-gray-600 hover:bg-gray-50 disabled:opacity-50" @click="defer">Continuar después</button>
            </div>
        </header>
        <main class="mx-auto max-w-4xl px-5 py-8 sm:py-12">
            <div class="mb-8 space-y-3">
                <p class="text-sm font-bold text-orange-700">Bienvenido a TallerFlow</p>
                <h1 class="text-3xl font-bold tracking-tight sm:text-4xl">Preparemos tu taller y tu página web</h1>
                <p class="max-w-2xl text-gray-600">Tu página para recibir solicitudes de hora ya está incluida. En cuatro pasos, deja listos los datos que verán tus clientes.</p>
                <p class="text-sm text-gray-500">Guardamos el avance al continuar. Tus cambios se publican al finalizar.</p>
            </div>
            <nav aria-label="Pasos de bienvenida" class="mb-6">
                <ol class="grid grid-cols-2 gap-3 sm:grid-cols-4">
                    <li v-for="(label, index) in steps" :key="label">
                        <Link v-if="index + 1 <= welcome.next_step" :href="`${welcome.url}?step=${index + 1}`" :aria-current="step === index + 1 ? 'step' : undefined"
                            :class="['flex items-center gap-2 rounded-xl border px-3 py-3 text-sm font-semibold', step === index + 1 ? 'border-orange-300 bg-orange-50 text-orange-800' : 'border-gray-200 bg-white text-gray-600']">
                            <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-white">{{ index + 1 }}</span>{{ label }}
                        </Link>
                        <span v-else class="flex items-center gap-2 rounded-xl border border-gray-200 px-3 py-3 text-sm text-gray-400"><span>{{ index + 1 }}</span>{{ label }}</span>
                    </li>
                </ol>
            </nav>
            <form class="space-y-6 rounded-2xl border border-gray-200 bg-white p-5 shadow-sm sm:p-8" @submit.prevent="submit">
                <p class="text-sm font-semibold text-gray-500" aria-live="polite">Paso {{ step }} de 4 · {{ steps[step - 1] }}</p>
                <div v-if="Object.keys(form.errors).length" role="alert" class="rounded-xl bg-red-50 p-4 text-sm text-red-700">
                    <p v-for="(error, field) in form.errors" :key="field">{{ error }}</p>
                </div>
                <section v-if="step === 1" class="space-y-5" aria-labelledby="business-title">
                    <h2 id="business-title" class="text-xl font-bold">¿Cómo se llama tu taller?</h2>
                    <div><label for="welcome-name" class="text-sm font-semibold">Nombre del taller *</label><input id="welcome-name" v-model="form.name" :class="inputClass" required maxlength="255" autocomplete="organization" /></div>
                    <div><label for="welcome-comuna" class="text-sm font-semibold">Comuna o ciudad *</label><input id="welcome-comuna" v-model="form.comuna" :class="inputClass" required maxlength="100" autocomplete="address-level2" /></div>
                    <div><label for="welcome-address" class="text-sm font-semibold">Dirección (opcional)</label><input id="welcome-address" v-model="form.address" :class="inputClass" maxlength="255" autocomplete="street-address" /></div>
                    <p class="text-sm text-gray-500">Estos datos identificarán tu taller en la página pública. El enlace seguirá siendo el mismo aunque cambies el nombre.</p>
                </section>
                <section v-else-if="step === 2" class="space-y-5" aria-labelledby="contact-title">
                    <h2 id="contact-title" class="text-xl font-bold">¿Cómo pueden contactarte?</h2>
                    <p class="text-sm text-gray-600">Usa los datos de contacto del taller: se mostrarán en tu página pública y se guardarán en tu sucursal principal.</p>
                    <div><label for="welcome-phone" class="text-sm font-semibold">Teléfono de contacto *</label><input id="welcome-phone" v-model="form.phone" type="tel" :class="inputClass" required maxlength="50" autocomplete="tel" placeholder="Incluye el código de país" /></div>
                    <div><label for="welcome-whatsapp" class="text-sm font-semibold">WhatsApp (opcional)</label><input id="welcome-whatsapp" v-model="form.whatsapp_number" type="tel" :class="inputClass" maxlength="20" placeholder="Incluye el código de país" /></div>
                    <div><label for="welcome-email" class="text-sm font-semibold">Correo del taller (opcional)</label><input id="welcome-email" v-model="form.email" type="email" :class="inputClass" maxlength="255" autocomplete="email" /></div>
                </section>
                <section v-else-if="step === 3" class="space-y-5" aria-labelledby="description-title">
                    <h2 id="description-title" class="text-xl font-bold">Presenta tu taller</h2>
                    <p class="text-sm text-gray-600">Preparamos un texto con tu nombre y ubicación. Puedes adaptarlo con información real sobre tu taller; se usará en tu página pública y su descripción para buscadores.</p>
                    <div><label for="welcome-description" class="text-sm font-semibold">Descripción *</label><textarea id="welcome-description" v-model="form.description" :class="inputClass" required rows="5" maxlength="500" /><p class="mt-2 text-sm text-gray-500">{{ form.description?.length ?? 0 }}/500 caracteres</p></div>
                    <button type="button" class="rounded-lg text-sm font-semibold text-orange-700 hover:underline" @click="form.description = welcome.suggested_description">Usar texto sugerido</button>
                </section>
                <section v-else class="space-y-5" aria-labelledby="preview-title">
                    <h2 id="preview-title" class="text-xl font-bold">Así presentarás tu taller</h2>
                    <div class="space-y-3 rounded-xl border border-orange-100 bg-orange-50 p-5">
                        <h3 class="break-words text-xl font-bold">{{ form.name }}</h3>
                        <p class="break-words text-sm">{{ form.comuna }}<span v-if="form.address"> · {{ form.address }}</span></p>
                        <p class="whitespace-pre-line break-words text-gray-700">{{ form.description }}</p>
                        <p class="break-words text-sm text-gray-600">{{ form.phone }}<span v-if="form.email"> · {{ form.email }}</span><span v-if="form.whatsapp_number"> · WhatsApp: {{ form.whatsapp_number }}</span></p>
                    </div>
                    <p class="text-sm text-gray-600">Al finalizar, estos datos quedarán visibles en tu página. Tus clientes podrán solicitar una hora; el taller revisará y confirmará la disponibilidad.</p>
                    <TenantPublicPageCard :public-url="welcome.public_url" />
                    <p class="text-sm text-gray-500">La página abierta desde el enlace muestra los datos publicados actualmente. Después podrás ajustar logo y horarios en Configuración.</p>
                </section>
                <div class="flex flex-wrap items-center justify-between gap-3 border-t border-gray-100 pt-5">
                    <Link v-if="step > 1" :href="`${welcome.url}?step=${step - 1}`" class="rounded-xl border border-gray-200 px-5 py-3 text-sm font-semibold">Volver</Link>
                    <button type="submit" :disabled="form.processing" class="ml-auto rounded-xl bg-orange-600 px-6 py-3 text-sm font-bold text-white hover:bg-orange-700 disabled:opacity-50">{{ form.processing ? 'Guardando…' : step === 4 ? 'Finalizar y entrar a mi taller' : 'Guardar y continuar' }}</button>
                </div>
            </form>
        </main>
    </div>
</template>
