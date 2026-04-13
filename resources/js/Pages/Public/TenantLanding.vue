<script setup>
import { ref, computed } from 'vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';

const props = defineProps({
    tenant: {
        type: Object,
        required: true,
    },
});

const page = usePage();
const isSuccess = computed(() => page.props.flash?.booking_success === true);

const form = useForm({
    customer_name: '',
    phone: '',
    plate: '',
    appointment_date: '',
    pre_check_notes: '',
});

const scrollToForm = () => {
    document.getElementById('booking-form')?.scrollIntoView({ behavior: 'smooth' });
};

const formatPlate = (e) => {
    const val = e.target.value.toUpperCase().replace(/[^A-Z0-9]/g, '').slice(0, 6);
    form.plate = val;
};

const submitBooking = () => {
    form.post(route('taller.booking.store', props.tenant.slug), {
        preserveScroll: true,
    });
};

// Min date for the datetime-local input (now + 1 hour)
const minDate = computed(() => {
    const d = new Date();
    d.setHours(d.getHours() + 1);
    return d.toISOString().slice(0, 16);
});
</script>

<template>
    <Head>
        <title>Agendar Cita | {{ tenant.name }}</title>
        <meta name="description" :content="`Agenda tu cita en ${tenant.name}. Diagnóstico rápido, repuestos garantizados y transparencia total. ¡Reserva en minutos!`" />
        <meta name="robots" content="index, follow" />
        <meta property="og:title" :content="`Agendar Cita | ${tenant.name}`" />
        <meta property="og:description" :content="`Taller de confianza. Reserva tu cita en ${tenant.name} hoy.`" />
    </Head>

    <div class="min-h-screen bg-gray-50 font-sans antialiased">

        <!-- ====================================================
             HEADER
        ===================================================== -->
        <header class="bg-white border-b border-gray-100 sticky top-0 z-50 shadow-sm">
            <div class="max-w-5xl mx-auto px-4 sm:px-6 py-4 flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="h-9 w-9 rounded-xl bg-orange-500 flex items-center justify-center shadow-md">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z" />
                        </svg>
                    </div>
                    <span class="text-lg font-bold text-gray-900 tracking-tight">{{ tenant.name }}</span>
                </div>
                <div class="flex items-center gap-3">
                    <Link
                        :href="route('login')"
                        class="hidden sm:inline-flex items-center gap-2 border border-gray-200 bg-white hover:bg-gray-50 text-gray-700 hover:text-gray-900 text-sm font-semibold px-4 py-2.5 rounded-xl transition-all shadow-sm"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1" />
                        </svg>
                        Iniciar Sesión
                    </Link>
                    <button @click="scrollToForm" class="hidden sm:inline-flex items-center gap-2 bg-orange-500 hover:bg-orange-600 text-white text-sm font-bold px-5 py-2.5 rounded-xl transition-all shadow-md hover:shadow-orange-200">
                        Agendar Cita
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                        </svg>
                    </button>
                </div>
            </div>
        </header>

        <main>
            <!-- ====================================================
                 HERO SECTION
            ===================================================== -->
            <section class="relative overflow-hidden bg-white">
                <!-- Decorative background -->
                <div class="absolute inset-0 bg-gradient-to-br from-orange-50 via-white to-gray-50 pointer-events-none" aria-hidden="true"></div>
                <div class="absolute -top-24 -right-24 h-96 w-96 rounded-full bg-orange-100/60 blur-3xl pointer-events-none" aria-hidden="true"></div>

                <div class="relative max-w-5xl mx-auto px-4 sm:px-6 pt-20 pb-24 text-center">
                    <div class="inline-flex items-center gap-2 bg-orange-50 border border-orange-100 text-orange-600 text-xs font-bold px-4 py-2 rounded-full mb-6 tracking-wider uppercase">
                        <span class="h-2 w-2 rounded-full bg-orange-500 animate-pulse"></span>
                        Turnos Disponibles Hoy
                    </div>

                    <h1 class="text-4xl sm:text-5xl lg:text-6xl font-black text-gray-900 leading-tight tracking-tight max-w-3xl mx-auto">
                        Tu vehículo en manos expertas.
                        <span class="text-orange-500"> Rápido, transparente y garantizado.</span>
                    </h1>

                    <p class="mt-6 text-lg sm:text-xl text-gray-500 max-w-2xl mx-auto leading-relaxed">
                        Agenda en menos de 2 minutos. Cuando llegues al taller, leeremos tu patente automáticamente para atenderte sin filas ni demoras.
                    </p>

                    <div class="mt-10 flex flex-col sm:flex-row justify-center gap-4">
                        <button @click="scrollToForm" class="inline-flex items-center justify-center gap-3 bg-orange-500 hover:bg-orange-600 text-white font-bold text-lg px-8 py-4 rounded-2xl shadow-lg hover:shadow-orange-200 transition-all active:scale-95">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                            Agendar mi Cita Ahora
                        </button>
                        <a href="tel:" class="inline-flex items-center justify-center gap-3 bg-white border border-gray-200 text-gray-700 font-bold text-lg px-8 py-4 rounded-2xl shadow-sm hover:border-gray-300 transition-all">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                            </svg>
                            Llamar al Taller
                        </a>
                    </div>
                </div>
            </section>

            <!-- ====================================================
                 TRUST BADGES
            ===================================================== -->
            <section class="border-t border-gray-100 bg-white" aria-label="Propuesta de valor">
                <div class="max-w-5xl mx-auto px-4 sm:px-6 py-12">
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-8">
                        <article class="flex flex-col items-center text-center p-6 rounded-2xl bg-gray-50 border border-gray-100">
                            <div class="h-14 w-14 rounded-2xl bg-blue-50 flex items-center justify-center mb-4 shadow-sm">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-7 w-7 text-blue-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 3H5a2 2 0 00-2 2v4m6-6h10a2 2 0 012 2v4M9 3v18m0 0h10a2 2 0 002-2V9M9 21H5a2 2 0 01-2-2V9m0 0h18" />
                                </svg>
                            </div>
                            <h3 class="font-bold text-gray-900 text-base">Diagnóstico con Scanner</h3>
                            <p class="mt-2 text-sm text-gray-500 leading-relaxed">Inspección digital completa con tecnología OBD-II para detectar fallas en minutos.</p>
                        </article>

                        <article class="flex flex-col items-center text-center p-6 rounded-2xl bg-gray-50 border border-gray-100">
                            <div class="h-14 w-14 rounded-2xl bg-emerald-50 flex items-center justify-center mb-4 shadow-sm">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-7 w-7 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                                </svg>
                            </div>
                            <h3 class="font-bold text-gray-900 text-base">Repuestos Garantizados</h3>
                            <p class="mt-2 text-sm text-gray-500 leading-relaxed">Solo trabajamos con repuestos de primera calidad con garantía incluida en cada servicio.</p>
                        </article>

                        <article class="flex flex-col items-center text-center p-6 rounded-2xl bg-gray-50 border border-gray-100">
                            <div class="h-14 w-14 rounded-2xl bg-amber-50 flex items-center justify-center mb-4 shadow-sm">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-7 w-7 text-amber-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                </svg>
                            </div>
                            <h3 class="font-bold text-gray-900 text-base">Transparencia Total</h3>
                            <p class="mt-2 text-sm text-gray-500 leading-relaxed">Seguimiento en tiempo real de tu orden de trabajo. Siempre informado en cada paso.</p>
                        </article>
                    </div>
                </div>
            </section>

            <!-- ====================================================
                 BOOKING FORM
            ===================================================== -->
            <section id="booking-form" class="py-20 bg-gray-50" aria-label="Formulario de agendamiento">
                <div class="max-w-2xl mx-auto px-4 sm:px-6">
                    <div class="text-center mb-10">
                        <h2 class="text-3xl font-black text-gray-900 tracking-tight">Agenda tu Cita</h2>
                        <p class="mt-3 text-gray-500">Completa el formulario y te confirmaremos en minutos.</p>
                    </div>

                    <!-- ── SUCCESS STATE ── -->
                    <div v-if="isSuccess" class="bg-white shadow-xl rounded-2xl border border-gray-100 p-10 text-center">
                        <div class="h-20 w-20 rounded-full bg-emerald-50 border-4 border-emerald-100 flex items-center justify-center mx-auto mb-6">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                            </svg>
                        </div>
                        <h3 class="text-2xl font-black text-gray-900">¡Cita Confirmada!</h3>
                        <p class="mt-4 text-gray-600 max-w-md mx-auto leading-relaxed">
                            Cuando llegues al taller, <strong>leeremos tu patente automáticamente</strong> para atenderte sin demoras ni filas. ¡Te esperamos!
                        </p>
                        <div class="mt-8 inline-flex items-center gap-2 bg-gray-50 border border-gray-100 text-gray-500 text-sm font-medium px-5 py-3 rounded-xl">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-orange-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            Recibirás un recordatorio antes de tu cita.
                        </div>
                    </div>

                    <!-- ── FORM ── -->
                    <form v-else @submit.prevent="submitBooking" novalidate class="bg-white shadow-xl rounded-2xl border border-gray-100 p-8 space-y-8">

                        <!-- Step 1: Basic Data -->
                        <fieldset>
                            <legend class="flex items-center gap-2 text-xs font-bold text-orange-500 uppercase tracking-widest mb-5">
                                <span class="flex h-5 w-5 items-center justify-center rounded-full bg-orange-500 text-white text-xs font-black">1</span>
                                Datos Básicos
                            </legend>
                            <div class="space-y-4">
                                <div>
                                    <label for="customer_name" class="block text-sm font-semibold text-gray-700 mb-1.5">Nombre Completo</label>
                                    <input
                                        id="customer_name"
                                        type="text"
                                        v-model="form.customer_name"
                                        autocomplete="name"
                                        placeholder="Juan Pérez"
                                        class="block w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-3 text-gray-900 placeholder-gray-400 focus:border-orange-400 focus:ring-2 focus:ring-orange-100 focus:bg-white transition text-sm"
                                    />
                                    <p v-if="form.errors.customer_name" class="mt-1 text-xs text-red-500">{{ form.errors.customer_name }}</p>
                                </div>
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                    <div>
                                        <label for="phone" class="block text-sm font-semibold text-gray-700 mb-1.5">Teléfono / WhatsApp</label>
                                        <input
                                            id="phone"
                                            type="tel"
                                            v-model="form.phone"
                                            autocomplete="tel"
                                            placeholder="+56 9 1234 5678"
                                            class="block w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-3 text-gray-900 placeholder-gray-400 focus:border-orange-400 focus:ring-2 focus:ring-orange-100 focus:bg-white transition text-sm"
                                        />
                                        <p v-if="form.errors.phone" class="mt-1 text-xs text-red-500">{{ form.errors.phone }}</p>
                                    </div>
                                    <div>
                                        <label for="plate" class="block text-sm font-semibold text-gray-700 mb-1.5">
                                            Patente del Vehículo
                                            <span class="text-gray-400 font-normal">(6 caracteres)</span>
                                        </label>
                                        <input
                                            id="plate"
                                            type="text"
                                            :value="form.plate"
                                            @input="formatPlate"
                                            autocomplete="off"
                                            placeholder="BBBB77"
                                            maxlength="6"
                                            class="block w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-3 text-gray-900 placeholder-gray-400 focus:border-orange-400 focus:ring-2 focus:ring-orange-100 focus:bg-white transition text-sm font-mono tracking-widest uppercase"
                                        />
                                        <p v-if="form.errors.plate" class="mt-1 text-xs text-red-500">{{ form.errors.plate }}</p>
                                    </div>
                                </div>
                            </div>
                        </fieldset>

                        <div class="border-t border-gray-100"></div>

                        <!-- Step 2: Date & Time -->
                        <fieldset>
                            <legend class="flex items-center gap-2 text-xs font-bold text-orange-500 uppercase tracking-widest mb-5">
                                <span class="flex h-5 w-5 items-center justify-center rounded-full bg-orange-500 text-white text-xs font-black">2</span>
                                Fecha y Hora Deseada
                            </legend>
                            <div>
                                <label for="appointment_date" class="block text-sm font-semibold text-gray-700 mb-1.5">Selecciona cuándo quieres venir</label>
                                <input
                                    id="appointment_date"
                                    type="datetime-local"
                                    v-model="form.appointment_date"
                                    :min="minDate"
                                    class="block w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-3 text-gray-900 focus:border-orange-400 focus:ring-2 focus:ring-orange-100 focus:bg-white transition text-sm"
                                />
                                <p v-if="form.errors.appointment_date" class="mt-1 text-xs text-red-500">{{ form.errors.appointment_date }}</p>
                            </div>
                        </fieldset>

                        <div class="border-t border-gray-100"></div>

                        <!-- Step 3: Pre-Check Digital -->
                        <fieldset>
                            <legend class="flex items-center gap-2 text-xs font-bold text-orange-500 uppercase tracking-widest mb-5">
                                <span class="flex h-5 w-5 items-center justify-center rounded-full bg-orange-500 text-white text-xs font-black">3</span>
                                Pre-Check Digital
                            </legend>
                            <div>
                                <label for="pre_check_notes" class="block text-sm font-semibold text-gray-700 mb-1.5">¿Qué le ocurre a tu vehículo?</label>
                                <textarea
                                    id="pre_check_notes"
                                    v-model="form.pre_check_notes"
                                    rows="4"
                                    placeholder="Cuéntanos los detalles o si tiene algún daño previo para tener todo listo cuando llegues. Por ejemplo: 'Hace ruido al frenar', 'Luz de motor encendida', 'Golpe en el parachoques'..."
                                    class="block w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-3 text-gray-900 placeholder-gray-400 focus:border-orange-400 focus:ring-2 focus:ring-orange-100 focus:bg-white transition text-sm resize-none"
                                ></textarea>
                                <p class="mt-1.5 text-xs text-gray-400">Opcional, pero nos ayuda a prepararnos mejor para tu visita.</p>
                                <p v-if="form.errors.pre_check_notes" class="mt-1 text-xs text-red-500">{{ form.errors.pre_check_notes }}</p>
                            </div>
                        </fieldset>

                        <!-- Submit -->
                        <div class="pt-2">
                            <button
                                type="submit"
                                :disabled="form.processing"
                                class="w-full inline-flex items-center justify-center gap-3 bg-orange-500 hover:bg-orange-600 disabled:bg-orange-300 text-white font-black text-base py-4 px-6 rounded-2xl shadow-lg hover:shadow-orange-200 transition-all active:scale-[0.98]"
                            >
                                <svg v-if="form.processing" class="animate-spin h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                                </svg>
                                <svg v-else xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                </svg>
                                {{ form.processing ? 'Agendando...' : 'Confirmar mi Cita' }}
                            </button>
                            <p class="mt-4 text-center text-xs text-gray-400">
                                Al agendar, aceptas que el taller se contacte contigo para confirmar el servicio.
                            </p>
                        </div>
                    </form>
                </div>
            </section>
        </main>

        <!-- ====================================================
             FOOTER
        ===================================================== -->
        <footer class="bg-white border-t border-gray-100 py-8 text-center">
            <p class="text-sm text-gray-400">
                Powered by <span class="font-bold text-orange-500">Feeto</span> — La plataforma inteligente para talleres automotrices
            </p>
        </footer>

    </div>
</template>

<style scoped>
/* Smooth scroll nativo */
html {
    scroll-behavior: smooth;
}
</style>
