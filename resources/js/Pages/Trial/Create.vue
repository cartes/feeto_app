<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';
import PublicNav from '@/Components/PublicNav.vue';

const props = defineProps({
    seo: { type: Object, default: () => ({}) },
    countries: { type: Array, default: () => [] },
});

const form = useForm({
    country: 'CL',
    name: '',
    email: '',
    phone: '',
    business_name: '',
    business_type: '',
    city: '',
    users_estimate: '',
    requested_plan: '',
    message: '',
    terms: false,
});

const selectedCountry = computed(() => {
    return props.countries.find(c => c.value === form.country) ?? props.countries[0] ?? null;
});

const phonePlaceholder = computed(() => {
    return selectedCountry.value?.phone_placeholder ?? '+56 9 1234 5678';
});

const businessTypes = [
    'Taller mecánico',
    'Centro de detailing',
    'Mecánica rápida / Lubricentro',
    'Taller de chapa y pintura',
    'Taller de neumáticos',
    'Taller eléctrico automotriz',
    'Taller multimarca',
    'Otro',
];

const plans = [
    { value: 'basico', label: 'Básico' },
    { value: 'profesional', label: 'Profesional' },
    { value: 'empresa', label: 'Empresa / Taller+' },
    { value: 'founder', label: 'Founder / Freemium' },
];

const submit = () => {
    form.post(route('trial.store'));
};
</script>

<template>
    <Head>
        <title>{{ seo.title ?? 'Solicitar prueba gratis · TallerFlow' }}</title>
    </Head>

    <PublicNav :can-login="false" />

    <div class="min-h-screen bg-gray-50 font-sans antialiased">
        <!-- Contenido -->
        <div class="max-w-2xl mx-auto px-4 pt-28 pb-12">
            <!-- Header -->
            <div class="text-center mb-10">
                <span class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full bg-amber-50 border border-amber-200 text-amber-700 text-xs font-semibold uppercase tracking-wide mb-4">
                    <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span>
                    Prueba gratuita 14 días
                </span>
                <h1 class="text-3xl font-black text-gray-900 tracking-tight">Empieza a digitalizar tu taller</h1>
                <p class="mt-3 text-gray-500 text-base leading-relaxed">
                    Completa el formulario y nuestro equipo activará tu acceso en menos de 24 horas.<br>
                    Sin tarjeta de crédito. Sin compromisos.
                </p>
            </div>

            <!-- Formulario -->
            <form @submit.prevent="submit" class="bg-white rounded-2xl shadow-sm ring-1 ring-gray-900/5 p-8 space-y-6">

                <!-- País -->
                <div>
                    <label for="country" class="block text-sm font-semibold text-gray-700 mb-1.5">
                        País <span class="text-rose-500">*</span>
                    </label>
                    <select
                        id="country"
                        v-model="form.country"
                        class="w-full rounded-xl border border-gray-200 px-4 py-3 text-sm text-gray-900 focus:outline-none focus:ring-2 focus:ring-amber-400 focus:border-transparent transition bg-white"
                        :class="{ 'border-rose-400 focus:ring-rose-400': form.errors.country }"
                    >
                        <option v-for="c in countries" :key="c.value" :value="c.value">
                            {{ c.flag }} {{ c.label }}
                        </option>
                    </select>
                    <p v-if="form.errors.country" class="mt-1.5 text-xs text-rose-600">{{ form.errors.country }}</p>
                </div>

                <!-- Nombre del responsable -->
                <div>
                    <label for="name" class="block text-sm font-semibold text-gray-700 mb-1.5">
                        Nombre completo <span class="text-rose-500">*</span>
                    </label>
                    <input
                        id="name"
                        v-model="form.name"
                        type="text"
                        autocomplete="name"
                        placeholder="Ej: Juan Pérez"
                        class="w-full rounded-xl border border-gray-200 px-4 py-3 text-sm text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-amber-400 focus:border-transparent transition"
                        :class="{ 'border-rose-400 focus:ring-rose-400': form.errors.name }"
                    />
                    <p v-if="form.errors.name" class="mt-1.5 text-xs text-rose-600">{{ form.errors.name }}</p>
                </div>

                <!-- Email -->
                <div>
                    <label for="email" class="block text-sm font-semibold text-gray-700 mb-1.5">
                        Correo electrónico <span class="text-rose-500">*</span>
                    </label>
                    <input
                        id="email"
                        v-model="form.email"
                        name="email"
                        type="email"
                        autocomplete="email"
                        inputmode="email"
                        pattern="^[^\s@]+@[^\s@]+\.[^\s@]+$"
                        required
                        placeholder="tu@taller.cl"
                        title="Ingresa un correo válido, por ejemplo tu@taller.cl"
                        class="w-full rounded-xl border border-gray-200 px-4 py-3 text-sm text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-amber-400 focus:border-transparent transition"
                        :class="{ 'border-rose-400 focus:ring-rose-400': form.errors.email }"
                    />
                    <p v-if="form.errors.email" class="mt-1.5 text-xs text-rose-600">{{ form.errors.email }}</p>
                </div>

                <!-- Teléfono -->
                <div>
                    <label for="phone" class="block text-sm font-semibold text-gray-700 mb-1.5">
                        Teléfono / WhatsApp <span class="text-rose-500">*</span>
                    </label>
                    <input
                        id="phone"
                        v-model="form.phone"
                        type="tel"
                        autocomplete="tel"
                        :placeholder="phonePlaceholder"
                        class="w-full rounded-xl border border-gray-200 px-4 py-3 text-sm text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-amber-400 focus:border-transparent transition"
                        :class="{ 'border-rose-400 focus:ring-rose-400': form.errors.phone }"
                    />
                    <p v-if="form.errors.phone" class="mt-1.5 text-xs text-rose-600">{{ form.errors.phone }}</p>
                </div>

                <!-- Nombre del negocio -->
                <div>
                    <label for="business_name" class="block text-sm font-semibold text-gray-700 mb-1.5">
                        Nombre del taller o negocio <span class="text-rose-500">*</span>
                    </label>
                    <input
                        id="business_name"
                        v-model="form.business_name"
                        type="text"
                        placeholder="Ej: Taller Mecánico El Maestro"
                        class="w-full rounded-xl border border-gray-200 px-4 py-3 text-sm text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-amber-400 focus:border-transparent transition"
                        :class="{ 'border-rose-400 focus:ring-rose-400': form.errors.business_name }"
                    />
                    <p v-if="form.errors.business_name" class="mt-1.5 text-xs text-rose-600">{{ form.errors.business_name }}</p>
                </div>

                <!-- Rubro -->
                <div>
                    <label for="business_type" class="block text-sm font-semibold text-gray-700 mb-1.5">
                        Rubro del negocio <span class="text-rose-500">*</span>
                    </label>
                    <select
                        id="business_type"
                        v-model="form.business_type"
                        class="w-full rounded-xl border border-gray-200 px-4 py-3 text-sm text-gray-900 focus:outline-none focus:ring-2 focus:ring-amber-400 focus:border-transparent transition bg-white"
                        :class="{ 'border-rose-400 focus:ring-rose-400': form.errors.business_type }"
                    >
                        <option value="" disabled>Selecciona un rubro…</option>
                        <option v-for="type in businessTypes" :key="type" :value="type">{{ type }}</option>
                    </select>
                    <p v-if="form.errors.business_type" class="mt-1.5 text-xs text-rose-600">{{ form.errors.business_type }}</p>
                </div>

                <!-- Ciudad y usuarios en fila -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label for="city" class="block text-sm font-semibold text-gray-700 mb-1.5">Ciudad / Comuna</label>
                        <input
                            id="city"
                            v-model="form.city"
                            type="text"
                            placeholder="Ej: Santiago"
                            class="w-full rounded-xl border border-gray-200 px-4 py-3 text-sm text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-amber-400 focus:border-transparent transition"
                        />
                    </div>
                    <div>
                        <label for="users_estimate" class="block text-sm font-semibold text-gray-700 mb-1.5">N° aproximado de usuarios</label>
                        <input
                            id="users_estimate"
                            v-model="form.users_estimate"
                            type="number"
                            min="1"
                            max="999"
                            placeholder="Ej: 3"
                            class="w-full rounded-xl border border-gray-200 px-4 py-3 text-sm text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-amber-400 focus:border-transparent transition"
                        />
                    </div>
                </div>

                <!-- Plan de interés (opcional) -->
                <div>
                    <label for="requested_plan" class="block text-sm font-semibold text-gray-700 mb-1.5">Plan de interés <span class="text-gray-400 font-normal">(opcional)</span></label>
                    <select
                        id="requested_plan"
                        v-model="form.requested_plan"
                        class="w-full rounded-xl border border-gray-200 px-4 py-3 text-sm text-gray-900 focus:outline-none focus:ring-2 focus:ring-amber-400 focus:border-transparent transition bg-white"
                    >
                        <option value="">No sé aún</option>
                        <option v-for="plan in plans" :key="plan.value" :value="plan.value">{{ plan.label }}</option>
                    </select>
                </div>

                <!-- Mensaje opcional -->
                <div>
                    <label for="message" class="block text-sm font-semibold text-gray-700 mb-1.5">¿Algo que quieras contarnos? <span class="text-gray-400 font-normal">(opcional)</span></label>
                    <textarea
                        id="message"
                        v-model="form.message"
                        rows="3"
                        placeholder="Cuéntanos sobre tu taller, tus necesidades o cualquier consulta…"
                        class="w-full rounded-xl border border-gray-200 px-4 py-3 text-sm text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-amber-400 focus:border-transparent transition resize-none"
                    ></textarea>
                </div>

                <!-- Términos -->
                <div class="flex items-start gap-3">
                    <input
                        id="terms"
                        v-model="form.terms"
                        name="terms"
                        type="checkbox"
                        required
                        class="mt-0.5 h-4 w-4 rounded border-gray-300 text-amber-500 focus:ring-amber-400 cursor-pointer"
                    />
                    <label for="terms" class="text-sm text-gray-600 cursor-pointer">
                        Acepto que TallerFlow almacene mis datos para contactarme con información sobre el servicio. Sin spam.
                    </label>
                </div>
                <p v-if="form.errors.terms" class="mt-1 text-xs text-rose-600">{{ form.errors.terms }}</p>

                <!-- Submit -->
                <button
                    type="submit"
                    :disabled="form.processing"
                    class="w-full inline-flex items-center justify-center gap-2 bg-[#FF7A00] hover:bg-[#CC6200] text-[#1a0e00] font-bold px-6 py-4 rounded-xl shadow-sm transition-all active:scale-[0.99] disabled:opacity-60 disabled:cursor-not-allowed"
                >
                    <span v-if="form.processing">Enviando solicitud…</span>
                    <span v-else>Solicitar mi prueba gratis</span>
                    <svg v-if="!form.processing" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3" />
                    </svg>
                </button>

                <p class="text-center text-xs text-gray-400">
                    14 días gratis · Sin tarjeta de crédito · Cancela cuando quieras
                </p>
            </form>
        </div>
    </div>
</template>
