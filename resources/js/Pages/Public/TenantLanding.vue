<script setup>
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import { ref, computed } from 'vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import LoginModal from '@/Components/LoginModal.vue';
import Toast from '@/Components/Toast.vue';

const props = defineProps({
    tenant: {
        type: Object,
        required: true,
    },
    seo: {
        type: Object,
        default: () => ({}),
    },
});

const page = usePage();
const isSuccess = computed(() => page.props.flash?.booking_success === true);
const showLoginModal = ref(false);

const toast = ref({ message: '', type: 'success' });

function showToast(message, type = 'success') {
    toast.value = { message: '', type };
    setTimeout(() => { toast.value = { message, type }; }, 10);
}

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
        onSuccess: () => {
            showToast('¡Cita confirmada! Te esperamos en el taller.', 'success');
        },
        onError: (errors) => {
            if (errors.appointment_date) {
                showToast(errors.appointment_date, 'error');
            } else {
                showToast('Por favor revisa los campos marcados en rojo.', 'warning');
            }
        },
    });
};

const minDate = computed(() => {
    const d = new Date();
    d.setHours(d.getHours() + 1);
    return d.toISOString().slice(0, 16);
});

const primaryColor = computed(() => props.tenant?.primary_color ?? '#FF7A00');

const phoneNumber = computed(() => {
    const mainBranch = props.tenant.branches?.find(b => b.is_main);
    return mainBranch?.phone || props.tenant.whatsapp_number || null;
});

const hasBranches = computed(() => Array.isArray(props.tenant.branches) && props.tenant.branches.length > 0);

const trackWhatsAppClick = () => {
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content ?? '';
    fetch(route('taller.whatsapp.inquiry', props.tenant.slug), {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
        body: JSON.stringify({}),
    }).catch(() => {});
};
</script>

<template>
    <Head>
        <title>{{ seo.title ?? `Agendar Cita | ${tenant.name}` }}</title>
    </Head>

    <div class="min-h-screen bg-gray-50 font-sans antialiased" :style="{ '--brand': primaryColor, '--brand-hover': primaryColor + 'dd' }">

        <!-- ====================================================
             HEADER
        ===================================================== -->
        <header class="bg-white border-b border-gray-100 sticky top-0 z-50 shadow-sm">
            <div class="max-w-5xl mx-auto px-4 sm:px-6 py-4 flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <img
                        v-if="tenant.logo_url"
                        :src="tenant.logo_url"
                        :alt="tenant.name"
                        class="h-9 w-9 rounded-xl shadow-md object-contain bg-white"
                    />
                    <ApplicationLogo v-else class="h-9 w-9 rounded-xl shadow-md" />
                    <span class="text-lg font-bold text-gray-900 tracking-tight">{{ tenant.name }}</span>
                </div>
                <div class="flex items-center gap-3">
                    <Link
                        v-if="$page.props.auth?.user"
                        :href="route('dashboard')"
                        class="hidden sm:inline-flex items-center gap-2 border border-gray-200 bg-white hover:bg-gray-50 text-gray-700 hover:text-gray-900 text-sm font-semibold px-4 py-2.5 rounded-xl transition-all shadow-sm"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" />
                        </svg>
                        Dashboard
                    </Link>
                    <button
                        v-else
                        @click="showLoginModal = true"
                        class="hidden sm:inline-flex items-center gap-2 border border-gray-200 bg-white hover:bg-gray-50 text-gray-700 hover:text-gray-900 text-sm font-semibold px-4 py-2.5 rounded-xl transition-all shadow-sm"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013 3v1" />
                        </svg>
                        Iniciar Sesión
                    </button>
                    <button
                        @click="scrollToForm"
                        class="hidden sm:inline-flex items-center gap-2 text-white text-sm font-bold px-5 py-2.5 rounded-xl transition-all shadow-md"
                        :style="{ backgroundColor: primaryColor }"
                    >
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
                <!-- Dynamic brand decorative blobs -->
                <div
                    class="absolute inset-0 pointer-events-none"
                    aria-hidden="true"
                    :style="{ background: `linear-gradient(135deg, ${primaryColor}0d 0%, #ffffff 55%, #f9fafb 100%)` }"
                ></div>
                <div
                    class="absolute -top-32 -right-32 h-[28rem] w-[28rem] rounded-full blur-3xl pointer-events-none"
                    aria-hidden="true"
                    :style="{ backgroundColor: primaryColor + '20' }"
                ></div>
                <div
                    class="absolute top-1/2 -left-40 h-72 w-72 rounded-full blur-3xl pointer-events-none"
                    aria-hidden="true"
                    :style="{ backgroundColor: primaryColor + '0e' }"
                ></div>

                <div class="relative max-w-5xl mx-auto px-4 sm:px-6 pt-20 pb-16 text-center">

                    <!-- Availability badge -->
                    <div
                        class="inline-flex items-center gap-2 border text-xs font-bold px-4 py-2 rounded-full mb-6 tracking-wider uppercase"
                        :style="{ color: primaryColor, backgroundColor: primaryColor + '12', borderColor: primaryColor + '35' }"
                    >
                        <span class="h-2 w-2 rounded-full animate-pulse" :style="{ backgroundColor: primaryColor }"></span>
                        Turnos Disponibles Hoy
                    </div>

                    <!-- Headline -->
                    <h1 class="text-4xl sm:text-5xl lg:text-6xl font-black text-gray-900 leading-tight tracking-tight max-w-3xl mx-auto">
                        Agenda tu cita en
                        <span :style="{ color: primaryColor }">{{ tenant.name }}</span>
                        rápido y sin complicaciones.
                    </h1>

                    <!-- Subheadline -->
                    <p class="mt-6 text-lg sm:text-xl text-gray-500 max-w-2xl mx-auto leading-relaxed">
                        {{ tenant.seo_description || 'Diagnóstico rápido, repuestos garantizados y transparencia total. Cuando llegues, leeremos tu patente automáticamente para atenderte sin esperas.' }}
                    </p>

                    <!-- CTAs -->
                    <div class="mt-10 flex flex-col sm:flex-row justify-center gap-4">
                        <button
                            @click="scrollToForm"
                            class="group inline-flex items-center justify-center gap-3 text-white font-bold text-lg px-8 py-4 rounded-2xl transition-all duration-200 active:scale-[0.97]"
                            :style="{ backgroundColor: primaryColor, boxShadow: `0 8px 28px ${primaryColor}45` }"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                            Agendar mi Cita Ahora
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 group-hover:translate-x-1 transition-transform duration-200" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                            </svg>
                        </button>
                        <a
                            v-if="phoneNumber"
                            :href="`tel:${phoneNumber.replace(/\D/g, '')}`"
                            class="inline-flex items-center justify-center gap-3 bg-white border border-gray-200 text-gray-700 font-bold text-lg px-8 py-4 rounded-2xl shadow-sm hover:border-gray-300 hover:shadow-md transition-all"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                            </svg>
                            Llamar al Taller
                        </a>
                    </div>

                    <!-- ── Sucursales / Ubicaciones ── -->
                    <div v-if="hasBranches" class="mt-14 pt-10 border-t border-gray-100">
                        <p class="flex items-center justify-center gap-2 text-xs uppercase tracking-widest font-semibold text-gray-400 mb-6">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                            Encuéntranos
                        </p>

                        <div class="flex flex-wrap justify-center gap-4">
                            <address
                                v-for="branch in tenant.branches"
                                :key="branch.id"
                                class="not-italic flex flex-col items-start bg-white rounded-2xl border border-gray-100 shadow-sm px-5 py-4 text-left min-w-[180px] max-w-[280px] transition-all duration-200 hover:shadow-md"
                                :style="branch.is_main ? { boxShadow: `0 0 0 2px ${primaryColor}` } : {}"
                                :itemscope="true"
                                itemtype="https://schema.org/AutoRepair"
                            >
                                <div class="flex items-center gap-2 flex-wrap mb-2">
                                    <span
                                        class="h-2 w-2 rounded-full shrink-0"
                                        :style="{ backgroundColor: primaryColor }"
                                    ></span>
                                    <span class="text-sm font-bold text-gray-900" itemprop="name">{{ branch.name }}</span>
                                    <span
                                        v-if="branch.is_main"
                                        class="text-[10px] font-bold px-2 py-0.5 rounded-full text-white shrink-0"
                                        :style="{ backgroundColor: primaryColor }"
                                    >
                                        Principal
                                    </span>
                                </div>

                                <div v-if="branch.address" class="flex items-start gap-1.5 text-xs text-gray-500 leading-snug" itemprop="address" itemscope itemtype="https://schema.org/PostalAddress">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 mt-0.5 shrink-0 text-gray-350" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                    </svg>
                                    <span itemprop="streetAddress">{{ branch.address }}</span>
                                </div>

                                <a
                                    v-if="branch.phone"
                                    :href="`tel:${branch.phone.replace(/\D/g, '')}`"
                                    class="flex items-center gap-1.5 text-xs mt-2 font-medium transition-opacity hover:opacity-75"
                                    :style="{ color: primaryColor }"
                                    itemprop="telephone"
                                >
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                                    </svg>
                                    {{ branch.phone }}
                                </a>
                            </address>
                        </div>
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
                            <legend class="flex items-center gap-2 text-xs font-bold uppercase tracking-widest mb-5" :style="{ color: primaryColor }">
                                <span class="flex h-5 w-5 items-center justify-center rounded-full text-white text-xs font-black" :style="{ backgroundColor: primaryColor }">1</span>
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
                            <legend class="flex items-center gap-2 text-xs font-bold uppercase tracking-widest mb-5" :style="{ color: primaryColor }">
                                <span class="flex h-5 w-5 items-center justify-center rounded-full text-white text-xs font-black" :style="{ backgroundColor: primaryColor }">2</span>
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
                            <legend class="flex items-center gap-2 text-xs font-bold uppercase tracking-widest mb-5" :style="{ color: primaryColor }">
                                <span class="flex h-5 w-5 items-center justify-center rounded-full text-white text-xs font-black" :style="{ backgroundColor: primaryColor }">3</span>
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
                                class="w-full inline-flex items-center justify-center gap-3 text-white font-black text-base py-4 px-6 rounded-2xl shadow-lg transition-all active:scale-[0.98]"
                                :style="{ backgroundColor: form.processing ? primaryColor + '99' : primaryColor }"
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

        <LoginModal :show="showLoginModal" @close="showLoginModal = false" />

        <Toast :message="toast.message" :type="toast.type" @dismiss="toast.message = ''" />

        <!-- ====================================================
             BOTÓN FLOTANTE WHATSAPP — Diseño moderno
        ===================================================== -->
        <a
            v-if="tenant.whatsapp_number"
            :href="`https://wa.me/${tenant.whatsapp_number.replace(/\D/g, '')}?text=${encodeURIComponent(`Hola ${tenant.name}, vi tu página en Feeto y tengo una consulta.`)}`"
            target="_blank"
            rel="noopener noreferrer"
            @click="trackWhatsAppClick"
            class="whatsapp-fab fixed bottom-6 right-6 z-50 flex items-center overflow-hidden rounded-2xl bg-white border border-white/20 transition-all duration-300 group hover:scale-[1.03] focus:outline-none focus:ring-4 focus:ring-[#25D366]/30"
            style="box-shadow: 0 8px 32px rgba(37,211,102,0.25), 0 2px 8px rgba(0,0,0,0.10);"
            aria-label="Consultar por WhatsApp"
        >
            <!-- Icon section -->
            <div class="relative w-14 h-14 flex items-center justify-center shrink-0 bg-[#25D366]">
                <span class="absolute inset-0 bg-[#25D366] animate-ping opacity-20"></span>
                <svg class="w-7 h-7 text-white relative z-10 drop-shadow" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                    <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/>
                </svg>
            </div>

            <!-- Text section (visible en sm+) -->
            <div class="hidden sm:flex flex-col justify-center px-4 pr-5 py-3 bg-white">
                <span class="text-[11px] font-semibold text-gray-400 leading-tight tracking-wide">¿Tienes dudas?</span>
                <span class="text-sm font-bold text-gray-900 leading-tight">Escríbenos ahora</span>
            </div>
        </a>
    </div>
</template>

<style scoped>
html {
    scroll-behavior: smooth;
}

.whatsapp-fab {
    animation: fab-enter 0.5s cubic-bezier(0.34, 1.56, 0.64, 1) both;
    animation-delay: 1s;
    opacity: 0;
}

@keyframes fab-enter {
    from {
        opacity: 0;
        transform: translateY(16px) scale(0.9);
    }
    to {
        opacity: 1;
        transform: translateY(0) scale(1);
    }
}
</style>
