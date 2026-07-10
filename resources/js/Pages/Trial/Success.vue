<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import PublicNav from '@/Components/PublicNav.vue';
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';

const props = defineProps({
    email: { type: String, default: null },
    redirect_url: { type: String, required: true },
    redirect_delay_seconds: { type: Number, default: 6 },
});

const remainingSeconds = ref(props.redirect_delay_seconds);
const confirmationMessage = computed(() => {
    if (props.email) {
        return `Hemos recibido tu formulario y te responderemos al correo ${props.email}. Nuestro equipo revisará tu solicitud y te contactará muy pronto.`;
    }

    return 'Hemos recibido tu formulario. Nuestro equipo revisará tu solicitud y te contactará muy pronto.';
});

let redirectTimeoutId = null;
let countdownIntervalId = null;

onMounted(() => {
    countdownIntervalId = window.setInterval(() => {
        if (remainingSeconds.value > 1) {
            remainingSeconds.value -= 1;
        }
    }, 1000);

    redirectTimeoutId = window.setTimeout(() => {
        router.visit(props.redirect_url);
    }, props.redirect_delay_seconds * 1000);
});

onBeforeUnmount(() => {
    if (countdownIntervalId !== null) {
        window.clearInterval(countdownIntervalId);
    }

    if (redirectTimeoutId !== null) {
        window.clearTimeout(redirectTimeoutId);
    }
});
</script>

<template>
    <Head title="Solicitud recibida · TallerFlow" />

    <PublicNav />

    <div class="min-h-screen bg-gray-50 font-sans antialiased flex flex-col">
        <!-- Contenido centrado -->
        <div class="flex-1 flex items-center justify-center px-4 pt-28 pb-16">
            <div class="text-center max-w-md">
                <div class="mx-auto mb-6 w-16 h-16 rounded-2xl bg-emerald-50 flex items-center justify-center">
                    <svg class="w-8 h-8 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>

                <h1 class="text-2xl font-black text-gray-900 tracking-tight mb-3">¡Solicitud recibida!</h1>
                <p class="text-gray-500 text-base leading-relaxed mb-4">
                    {{ confirmationMessage }}
                </p>
                <p class="text-sm text-gray-400 mb-8">
                    En unos {{ remainingSeconds }} segundos te llevaremos de vuelta al inicio.
                </p>

                <div class="bg-amber-50 border border-amber-200 rounded-2xl p-5 text-left mb-8 space-y-3">
                    <div class="flex items-start gap-3">
                        <svg class="w-4 h-4 text-amber-600 mt-0.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                        </svg>
                        <p class="text-sm text-amber-800">Recibirás una respuesta en el correo que ingresaste.</p>
                    </div>
                    <div class="flex items-start gap-3">
                        <svg class="w-4 h-4 text-amber-600 mt-0.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                        </svg>
                        <p class="text-sm text-amber-800">14 días de acceso completo sin costo.</p>
                    </div>
                    <div class="flex items-start gap-3">
                        <svg class="w-4 h-4 text-amber-600 mt-0.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                        </svg>
                        <p class="text-sm text-amber-800">Sin tarjeta de crédito requerida.</p>
                    </div>
                </div>

                <Link
                    :href="redirect_url"
                    class="inline-flex items-center gap-2 text-sm text-gray-500 hover:text-gray-700 transition-colors font-medium"
                >
                    ← Volver al inicio
                </Link>
            </div>
        </div>
    </div>
</template>
