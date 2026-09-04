<script setup>
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import { Link } from '@inertiajs/vue3';
import SeoHead from '@/Components/SeoHead.vue';
import { onMounted, onUnmounted, ref } from 'vue';
import LoginModal from '@/Components/LoginModal.vue';
import PublicNav from '@/Components/PublicNav.vue';
import PublicFooter from '@/Components/PublicFooter.vue';
import CountryFlagSvg from '@/Components/CountryFlagSvg.vue';

const props = defineProps({
    canLogin: { type: Boolean },
    canRegister: { type: Boolean },
    seo: { type: Object, default: () => ({}) },
    posts: { type: Array, default: () => [] },
    tenants: { type: Array, default: () => [] },
    inventoryImportHighlight: { type: Object, default: () => ({}) },
});

const showLoginModal = ref(false);
const activeSection = ref('features');

// Países con soporte de patentes / apertura en Sudamérica
const supportedCountries = [
    { code: 'CL', name: 'Chile', format: 'ABCD·12 / AB·1234', plateTag: 'PPU Nacional', status: 'Operación Activa' },
    { code: 'CO', name: 'Colombia', format: 'ABC·123 / ABC·12D', plateTag: 'Placa Nacional', status: 'Operación Activa' },
    { code: 'AR', name: 'Argentina', format: 'AB 123 CD (Mercosur)', plateTag: 'Mercosur & Tradicional', status: 'Soporte ALPR & Cuentas' },
    { code: 'BR', name: 'Brasil', format: 'ABC 1D23 (Mercosur)', plateTag: 'Mercosur & Tradicional', status: 'Soporte ALPR & Cuentas' },
    { code: 'PE', name: 'Perú', format: 'A1B·234 / ABC·123', plateTag: 'Nacional & Frontera', status: 'Soporte ALPR & Cuentas' },
    { code: 'BO', name: 'Bolivia', format: '1234·ABC / 123·ABC', plateTag: 'Nacional & Frontera', status: 'Soporte ALPR & Cuentas' },
    { code: 'UY', name: 'Uruguay', format: 'ABC 1234 / Mercosur', plateTag: 'Mercosur & Nacional', status: 'Soporte ALPR & Cuentas' },
    { code: 'PY', name: 'Paraguay', format: 'ABCD 123 / Mercosur', plateTag: 'Mercosur & Nacional', status: 'Soporte ALPR & Cuentas' },
    { code: 'EC', name: 'Ecuador', format: 'ABC·1234 / ABC·123', plateTag: 'Nacional & Frontera', status: 'Soporte ALPR & Cuentas' },
    { code: 'MX', name: 'México', format: 'ABC·1234 / ABC·123D', plateTag: 'SCT & Particular', status: 'Soporte ALPR & Cuentas' },
];

// Slider interactivo de funcionalidades
const currentSlide = ref(0);
const slides = [
    {
        title: 'Agenda Semanal del Taller',
        description: 'Controla citas, asigna mecánicos y programa servicios con un calendario interactivo en tiempo real.',
        tag: 'Calendario',
        tagClass: 'bg-blue-50 text-blue-600 border border-blue-100',
        image: '/images/dashboard_agenda.png',
        imageWebp: '/images/dashboard_agenda.webp'
    },
    {
        title: 'Tablero Kanban de Órdenes',
        description: 'Mueve órdenes de trabajo entre etapas (Recepción, Diagnóstico, Listo) arrastrándolas con un clic en tiempo real.',
        tag: 'Tablero Kanban',
        tagClass: 'bg-orange-50 text-orange-600 border border-orange-100',
        image: '/images/dashboard_kanban.png',
        imageWebp: '/images/dashboard_kanban.webp'
    },
    {
        title: 'Recepción con IA móvil (Multipaís Sudamérica)',
        description: 'Escanea la placa o patente con tu teléfono y la IA completará los datos del vehículo y cliente al instante (Chile, Colombia, Argentina, Brasil y más).',
        tag: 'Recepción IA',
        tagClass: 'bg-purple-50 text-purple-600 border border-purple-100',
        image: '/images/recepcion_ia.png',
        imageWebp: '/images/recepcion_ia.webp'
    },
    {
        title: props.inventoryImportHighlight.title || 'Importación de productos desde Excel',
        description: props.inventoryImportHighlight.description || 'Carga tu inventario desde Excel o CSV para crear o actualizar productos en minutos.',
        tag: props.inventoryImportHighlight.tag || 'Inventario',
        tagClass: 'bg-emerald-50 text-emerald-700 border border-emerald-100',
        image: props.inventoryImportHighlight.image || '/images/screenshots/inventario.png',
        imageWebp: props.inventoryImportHighlight.imageWebp || '/images/screenshots/inventario.webp',
    }
];

let autoplayTimer = null;
const startAutoPlay = () => {
    autoplayTimer = setInterval(() => {
        currentSlide.value = (currentSlide.value + 1) % slides.length;
    }, 7000);
};

const stopAutoPlay = () => {
    if (autoplayTimer) {
        clearInterval(autoplayTimer);
        autoplayTimer = null;
    }
};

let observer = null;

onMounted(() => {
    startAutoPlay();

    if (typeof window !== 'undefined' && 'IntersectionObserver' in window) {
        observer = new IntersectionObserver((entries) => {
            entries.forEach((entry) => {
                if (entry.isIntersecting) {
                    if (entry.target.id === 'pricing') {
                        activeSection.value = 'pricing';
                    } else if (entry.target.id === 'benefits') {
                        activeSection.value = 'benefits';
                    } else if (entry.target.id === 'features' || entry.target.classList.contains('hero')) {
                        activeSection.value = 'features';
                    }
                }
            });
        }, { rootMargin: '-20% 0px -60% 0px', threshold: 0.05 });

        const featuresEl = document.getElementById('features') || document.querySelector('.hero');
        const benefitsEl = document.getElementById('benefits');
        const pricingEl = document.getElementById('pricing');

        if (featuresEl) observer.observe(featuresEl);
        if (benefitsEl) observer.observe(benefitsEl);
        if (pricingEl) observer.observe(pricingEl);
    }
});

onUnmounted(() => {
    if (observer) {
        observer.disconnect();
        observer = null;
    }
    stopAutoPlay();
});
</script>

<template>

    <SeoHead :seo="seo" :fallback-title="'TallerFlow · Software para Talleres Mecánicos en Chile'" />

    <PublicNav :can-login="canLogin" :active-section="activeSection" />

    <main class="min-h-screen bg-white font-sans antialiased">
        <div class="dark-wrapper">
            <div class="hero-page">
                <section class="hero layout-car-left">
                    <div class="hero-img"></div>
                    <div class="hero-glow"></div>
                    <div class="hero-fade"></div>
                    <div class="hero-vignette"></div>
                    <div class="hero-noise"></div>

                    <div class="meta-row">
                        <div class="meta-line">
                            <span class="badge-pill !gap-2 !py-1.5 !px-3.5">
                                <span class="dot"></span>
                                <div class="flex items-center gap-1.5">
                                    <CountryFlagSvg country="CL" class-name="w-4 h-3 rounded-[2px]" />
                                    <CountryFlagSvg country="CO" class-name="w-4 h-3 rounded-[2px]" />
                                    <CountryFlagSvg country="AR" class-name="w-4 h-3 rounded-[2px]" />
                                    <CountryFlagSvg country="BR" class-name="w-4 h-3 rounded-[2px]" />
                                    <CountryFlagSvg country="PE" class-name="w-4 h-3 rounded-[2px]" />
                                </div>
                                <span>Chile, Colombia & Multipaís Sudamérica · Software para Talleres</span>
                            </span>
                        </div>

                        <div class="meta-line">
                            <span class="meta-label">Hecho para mecánicos.</span>
                            <span class="meta-sub">Diseñado con talleres reales para acelerar cada orden.</span>
                        </div>

                        <div class="meta-line">
                            <span class="meta-label">Todo en un lugar.</span>
                            <span class="meta-sub">Órdenes de trabajo, agenda, inventario y clientes en un sistema
                                único, en
                                español, listo para usar hoy.</span>
                        </div>
                    </div>

                    <div class="headline-wrap">
                        <div class="index-num">001 / T-FLOW</div>
                        <h1 class="headline">
                            Acelera tu taller.<br>
                            <em>Domina cada orden.</em>
                        </h1>
                    </div>

                    <div class="cta-strip">
                        <Link v-if="$page.props.auth?.user" :href="route('dashboard')" class="cta primary">
                            <span>
                                <span class="cta-title">Ir al Dashboard</span>
                                <span class="cta-sub">Accede a la administración de tu taller</span>
                            </span>
                            <span class="cta-arrow">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                    stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <line x1="5" y1="12" x2="19" y2="12" />
                                    <polyline points="12 5 19 12 12 19" />
                                </svg>
                            </span>
                        </Link>
                        <Link v-else-if="canRegister" :href="route('trial.create')" class="cta primary">
                            <span>
                                <span class="cta-title">Prueba gratis 14 días</span>
                                <span class="cta-sub">Crea tu cuenta y empieza hoy mismo</span>
                            </span>
                            <span class="cta-arrow">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                    stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <line x1="5" y1="12" x2="19" y2="12" />
                                    <polyline points="12 5 19 12 12 19" />
                                </svg>
                            </span>
                        </Link>
                        <button v-else class="cta primary" type="button" @click="showLoginModal = true">
                            <span>
                                <span class="cta-title">Prueba gratis 14 días</span>
                                <span class="cta-sub">Ingresa y empieza a probar Taller Flow</span>
                            </span>
                            <span class="cta-arrow">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                    stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <line x1="5" y1="12" x2="19" y2="12" />
                                    <polyline points="12 5 19 12 12 19" />
                                </svg>
                            </span>
                        </button>

                        <a class="cta" href="#features">
                            <span>
                                <span class="cta-title">Ver funcionalidades</span>
                                <span class="cta-sub">Explora agenda, órdenes, inventario y recepción con IA</span>
                            </span>
                            <span class="cta-arrow">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                    stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <line x1="5" y1="12" x2="19" y2="12" />
                                    <polyline points="12 5 19 12 12 19" />
                                </svg>
                            </span>
                        </a>
                    </div>
                </section>
            </div>

            <section id="features" class="feat-section">
                <div class="feat-inner">
                    <div class="feat-header">
                        <span class="feat-eyebrow"><span class="dot"></span>Herramientas</span>
                        <h2 class="feat-title">Una sola plataforma para <em>tu taller completo.</em></h2>
                        <p class="feat-sub">Diseñado con talleres mecánicos reales para resolver los problemas del día a
                            día.</p>
                    </div>

                    <div class="feat-grid">
                        <div class="feat-card">
                            <span class="feat-ico">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                    stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <rect x="3" y="4" width="18" height="18" rx="2" />
                                    <line x1="16" y1="2" x2="16" y2="6" />
                                    <line x1="8" y1="2" x2="8" y2="6" />
                                    <line x1="3" y1="10" x2="21" y2="10" />
                                </svg>
                            </span>
                            <h3>Tablero Kanban en Vivo</h3>
                            <p>Coordina equipos y órdenes en un flujo visual. Mueve trabajos entre etapas con un clic y
                                ten siempre
                                el control del piso del taller.</p>
                            <div class="feat-tags">
                                <span class="feat-tag feat-tag-blue">Tiempo Real</span>
                                <span class="feat-tag feat-tag-dim">WebSockets</span>
                            </div>
                        </div>

                        <div class="feat-card">
                            <span class="feat-ico">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                    stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path
                                        d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z" />
                                    <polyline points="3.27 6.96 12 12.01 20.73 6.96" />
                                    <line x1="12" y1="22.08" x2="12" y2="12" />
                                </svg>
                            </span>
                            <h3>Inventario Inteligente</h3>
                            <p>Stock al día con alertas de mínimos, control de proveedores, importación desde Excel y
                                registro automático al cerrar cada orden.</p>
                            <div class="feat-tags">
                                <span class="feat-tag feat-tag-green">Stock Mínimo</span>
                                <span class="feat-tag feat-tag-green">Importación Excel</span>
                                <span class="feat-tag feat-tag-dim">Alertas</span>
                            </div>
                        </div>

                        <div class="feat-card feat-card-ai">
                            <span class="feat-ai-badge">IA MULTIPAÍS</span>
                            <span class="feat-ico">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                    stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2" />
                                    <circle cx="9" cy="7" r="4" />
                                    <path d="M23 21v-2a4 4 0 0 0-3-3.87" />
                                    <path d="M16 3.13a4 4 0 0 1 0 7.75" />
                                </svg>
                            </span>
                            <h3>Recepción con IA Multipaís</h3>
                            <p>Escanea patentes y placas con la cámara de tu celular. Gemini AI reconoce vehículos de Chile, Colombia, Argentina, Brasil, Perú y toda Sudamérica en segundos, ideal para clientes locales y extranjeros que cruzan la frontera.</p>
                            <div class="feat-tags">
                                <span class="feat-tag feat-tag-purple">Gemini AI</span>
                                <span class="feat-tag feat-tag-dim">ALPR Sudamérica</span>
                                <span class="feat-tag feat-tag-blue">Mercosur & Local</span>
                            </div>
                        </div>
                    </div>

                    <!-- Button to link to full services page (delicate pill outline style) -->
                    <div class="mt-12 flex justify-center">
                        <Link
                            :href="route('servicios')"
                            class="inline-flex items-center gap-2 px-6 py-3 border border-white/10 hover:border-[#FF7A00]/40 bg-white/[0.02] hover:bg-[#FF7A00]/5 text-slate-300 hover:text-white text-xs font-bold uppercase tracking-wider rounded-full transition-all duration-300 active:scale-[0.98]"
                        >
                            <span>Mostrar todas las características</span>
                            <svg class="text-[#FF7A00]" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                <line x1="5" y1="12" x2="19" y2="12" />
                                <polyline points="12 5 19 12 12 19" />
                            </svg>
                        </Link>
                    </div>
                </div>
            </section>
        </div>

        <!-- Sección Soporte Multipaís Sudamérica & Patentes Internacionales -->
        <section id="moto-support" class="border-b border-slate-100 bg-white py-24 font-sans antialiased">
            <div class="mx-auto grid max-w-6xl grid-cols-1 items-start gap-12 px-6 lg:grid-cols-[1.05fr_1fr] lg:px-8">
                <div>
                    <span class="mb-3 inline-flex items-center gap-2 rounded-full bg-[#FF7A00] px-4 py-1.5 text-[11px] font-black uppercase tracking-[0.22em] text-white shadow-sm">
                        <span class="h-1.5 w-1.5 rounded-full bg-white animate-pulse"></span>
                        🌎 Soporte Internacional & Mercosur
                    </span>
                    <h2 class="text-3xl font-black tracking-tight text-slate-900 md:text-5xl md:leading-tight">
                        ¿Llega un auto de <em class="not-italic text-[#FF7A00]">Argentina o Brasil</em> a tu taller?
                    </h2>
                    <p class="mt-4 text-xl font-bold text-slate-800">
                        TallerFlow lo reconoce al instante sin fricción técnica.
                    </p>
                    <p class="mt-4 max-w-2xl text-base leading-relaxed text-slate-600">
                        Si tu taller está en <strong>Chile 🇨🇱</strong> o <strong>Colombia 🇨🇴</strong> y recibes vehículos con patente extranjera de <strong>Argentina (Mercosur), Brasil, Perú, Bolivia o Uruguay</strong>, nuestro escáner con IA identifica automáticamente el país emisor, valida el formato correspondiente y crea la orden de trabajo sin trabas.
                    </p>

                    <!-- Tarjeta destacada para apertura de talleres en Sudamérica -->
                    <div class="mt-6 rounded-2xl border border-orange-200/70 bg-gradient-to-br from-orange-50/80 via-amber-50/40 to-white p-5 shadow-sm">
                        <div class="flex items-start gap-3.5">
                            <span class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-xl bg-[#FF7A00] text-white shadow-sm">
                                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 21a9.004 9.004 0 008.716-6.747M12 21a9.004 9.004 0 01-8.716-6.747M12 21c2.485 0 4.5-4.03 4.5-9S14.485 3 12 3m0 18c-2.485 0-4.5-4.03-4.5-9S9.515 3 12 3m0 0a8.997 8.997 0 017.843 4.582M12 3a8.997 8.997 0 00-7.843 4.582m15.686 0A11.953 11.953 0 0112 10.5c-2.998 0-5.74-1.1-7.843-2.918m15.686 0A8.959 8.959 0 0121 12c0 .778-.099 1.533-.284 2.253m0 0A17.919 17.919 0 0112 16.5c-3.162 0-6.133-.815-8.716-2.247m0 0A9.015 9.015 0 013 12c0-.778.099-1.533.284-2.253" />
                                </svg>
                            </span>
                            <div>
                                <h3 class="text-sm font-black uppercase tracking-wider text-slate-900">
                                    ¿Quieres abrir tu taller desde cualquier país de Sudamérica?
                                </h3>
                                <p class="mt-1 text-sm font-medium leading-relaxed text-slate-600">
                                    Tenemos soporte nativo preparado: adaptación de placas, monedas locales, prefijos telefónicos y validaciones fiscales para talleres en toda la región.
                                </p>
                            </div>
                        </div>
                    </div>

                    <ul class="mt-6 space-y-3.5">
                        <li
                            v-for="bullet in [
                                'El escáner con IA reconoce patentes de Chile, Colombia, Argentina (Mercosur), Brasil, Perú, Bolivia y Uruguay con la cámara de tu teléfono.',
                                'Detección inteligente del país de origen para no rechazar vehículos extranjeros en recepción ni en cotizaciones.',
                                'Apertura y registro directo de cuentas de talleres con adaptación automática de prefijos y formatos.',
                                'Historial vehicular unificado por patente o placa para autos, motocicletas, camionetas y furgones.',
                            ]"
                            :key="bullet"
                            class="flex items-start gap-3.5 rounded-2xl border border-slate-100 bg-white px-4 py-3.5 shadow-[0_8px_24px_rgba(15,23,42,0.03)]"
                        >
                            <span class="mt-0.5 flex h-7 w-7 flex-shrink-0 items-center justify-center rounded-lg bg-[#FF7A00]/10 text-[#FF7A00]">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                                </svg>
                            </span>
                            <p class="text-sm font-semibold leading-relaxed text-slate-700">{{ bullet }}</p>
                        </li>
                    </ul>

                    <div class="mt-8 flex flex-wrap items-center gap-4">
                        <Link
                            :href="route('trial.create')"
                            class="inline-flex items-center gap-2 rounded-xl bg-slate-900 px-6 py-3.5 text-xs font-black uppercase tracking-wider text-white shadow-md transition-all duration-200 hover:bg-[#FF7A00] hover:shadow-lg active:scale-95"
                        >
                            <span>Probar gratis 14 días</span>
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3" />
                            </svg>
                        </Link>
                        <a
                            href="#features"
                            class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-5 py-3.5 text-xs font-bold uppercase tracking-wider text-slate-700 transition-all duration-200 hover:border-slate-300 hover:bg-slate-50"
                        >
                            Ver funcionalidades
                        </a>
                    </div>
                </div>

                <div class="relative">
                    <div class="absolute -inset-4 rounded-[2rem] bg-[radial-gradient(circle_at_top,_rgba(255,122,0,0.18),_transparent_65%)] blur-2xl"></div>
                    <div class="relative overflow-hidden rounded-[2rem] border border-slate-100 bg-white p-6 shadow-[0_24px_60px_rgba(15,23,42,0.12)]">
                        <div class="mb-5 flex items-center justify-between rounded-2xl border border-slate-100 bg-slate-50 px-4 py-3">
                            <div>
                                <p class="text-[10px] font-black uppercase tracking-[0.22em] text-slate-400">Recepción Multipaís</p>
                                <p class="mt-0.5 text-sm font-bold text-slate-800">Placas & Patentes Reconocidas</p>
                            </div>
                            <span class="inline-flex items-center gap-1.5 rounded-full bg-[#FF7A00] px-3 py-1 text-[10px] font-black uppercase tracking-[0.18em] text-white shadow-sm">
                                <span class="h-1.5 w-1.5 rounded-full bg-white animate-ping"></span>
                                IA Activa
                            </span>
                        </div>

                        <!-- Grid de Placas visuales representativas -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5 py-1">
                            <!-- Patente Chile Auto (Blanca) -->
                            <div class="flex flex-col items-center gap-1.5 p-3 rounded-xl bg-slate-50 border border-slate-100 hover:border-slate-200 transition-colors">
                                <div class="w-full rounded-lg border-2 border-slate-900 bg-white px-3 py-2 text-center shadow-sm">
                                    <p class="text-[7px] font-black uppercase tracking-[0.3em] text-slate-400">Chile</p>
                                    <p class="mt-0.5 font-mono text-2xl font-black tracking-[0.12em] text-slate-900">GKSB·78</p>
                                </div>
                                <div class="flex items-center gap-1.5">
                                    <CountryFlagSvg country="CL" class-name="w-4 h-3" />
                                    <p class="text-[10px] font-bold uppercase tracking-wider text-slate-600">Chile (PPU Nacional)</p>
                                </div>
                            </div>

                            <!-- Placa Colombia Auto (Amarilla) -->
                            <div class="flex flex-col items-center gap-1.5 p-3 rounded-xl bg-slate-50 border border-slate-100 hover:border-slate-200 transition-colors">
                                <div class="w-full rounded-lg border-2 border-slate-900 bg-[#FFD700] px-3 py-2 text-center shadow-sm">
                                    <p class="text-[7px] font-black uppercase tracking-[0.3em] text-slate-900">Colombia · Bogotá D.C.</p>
                                    <p class="mt-0.5 font-mono text-2xl font-black tracking-[0.12em] text-slate-900">ABC·123</p>
                                </div>
                                <div class="flex items-center gap-1.5">
                                    <CountryFlagSvg country="CO" class-name="w-4 h-3" />
                                    <p class="text-[10px] font-bold uppercase tracking-wider text-slate-600">Colombia (Auto Particular)</p>
                                </div>
                            </div>

                            <!-- Placa Argentina Mercosur -->
                            <div class="flex flex-col items-center gap-1.5 p-3 rounded-xl bg-slate-50 border border-slate-100 hover:border-slate-200 transition-colors">
                                <div class="w-full overflow-hidden rounded-lg border-2 border-slate-900 bg-white text-center shadow-sm">
                                    <div class="bg-[#0038A8] py-0.5 px-2 text-center">
                                        <p class="text-[7px] font-black uppercase tracking-[0.25em] text-white">Mercosur · República Argentina</p>
                                    </div>
                                    <div class="py-1.5 px-2">
                                        <p class="font-mono text-xl font-black tracking-[0.14em] text-slate-900">AB·123·CD</p>
                                    </div>
                                </div>
                                <div class="flex items-center gap-1.5">
                                    <CountryFlagSvg country="AR" class-name="w-4 h-3" />
                                    <p class="text-[10px] font-bold uppercase tracking-wider text-slate-600">Argentina (Mercosur)</p>
                                </div>
                            </div>

                            <!-- Placa Brasil Mercosur -->
                            <div class="flex flex-col items-center gap-1.5 p-3 rounded-xl bg-slate-50 border border-slate-100 hover:border-slate-200 transition-colors">
                                <div class="w-full overflow-hidden rounded-lg border-2 border-slate-900 bg-white text-center shadow-sm">
                                    <div class="bg-[#0038A8] py-0.5 px-2 text-center">
                                        <p class="text-[7px] font-black uppercase tracking-[0.25em] text-white">Mercosur · Brasil</p>
                                    </div>
                                    <div class="py-1.5 px-2">
                                        <p class="font-mono text-xl font-black tracking-[0.14em] text-slate-900">ABC·1D23</p>
                                    </div>
                                </div>
                                <div class="flex items-center gap-1.5">
                                    <CountryFlagSvg country="BR" class-name="w-4 h-3" />
                                    <p class="text-[10px] font-bold uppercase tracking-wider text-slate-600">Brasil (Mercosur)</p>
                                </div>
                            </div>

                            <!-- Placa Perú Auto -->
                            <div class="flex flex-col items-center gap-1.5 p-3 rounded-xl bg-slate-50 border border-slate-100 hover:border-slate-200 transition-colors">
                                <div class="w-full rounded-lg border-2 border-slate-900 bg-white px-3 py-2 text-center shadow-sm">
                                    <p class="text-[7px] font-black uppercase tracking-[0.3em] text-[#D91023]">Perú</p>
                                    <p class="mt-0.5 font-mono text-2xl font-black tracking-[0.12em] text-slate-900">A1B·234</p>
                                </div>
                                <div class="flex items-center gap-1.5">
                                    <CountryFlagSvg country="PE" class-name="w-4 h-3" />
                                    <p class="text-[10px] font-bold uppercase tracking-wider text-slate-600">Perú (Nacional / Frontera)</p>
                                </div>
                            </div>

                            <!-- Placa Colombia Moto (Amarilla) -->
                            <div class="flex flex-col items-center gap-1.5 p-3 rounded-xl bg-slate-50 border border-slate-100 hover:border-slate-200 transition-colors">
                                <div class="w-full rounded-lg border-2 border-slate-900 bg-[#FFD700] px-3 py-2 text-center shadow-sm">
                                    <p class="text-[7px] font-black uppercase tracking-[0.3em] text-slate-900">Colombia · Moto</p>
                                    <p class="mt-0.5 font-mono text-2xl font-black tracking-[0.12em] text-slate-900">ABC·12D</p>
                                </div>
                                <div class="flex items-center gap-1.5">
                                    <CountryFlagSvg country="CO" class-name="w-4 h-3" />
                                    <p class="text-[10px] font-bold uppercase tracking-wider text-slate-600">Colombia (Motocicleta)</p>
                                </div>
                            </div>
                        </div>

                        <!-- Sección de Banderas con soporte técnico y apertura de cuentas -->
                        <div class="mt-5 border-t border-slate-100 pt-4">
                            <div class="mb-3 flex items-center justify-between">
                                <p class="text-[11px] font-black uppercase tracking-wider text-slate-700">
                                    Banderas con soporte activo & apertura de cuentas
                                </p>
                                <span class="text-[10px] font-bold text-[#FF7A00]">10 Países</span>
                            </div>

                            <div class="grid grid-cols-2 sm:grid-cols-5 gap-2">
                                <div
                                    v-for="country in supportedCountries"
                                    :key="country.code"
                                    class="group flex flex-col items-center gap-1 rounded-xl border border-slate-100 bg-slate-50/80 p-2 text-center transition-all duration-200 hover:border-orange-200 hover:bg-orange-50/40 hover:shadow-sm"
                                >
                                    <CountryFlagSvg :country="country.code" class-name="w-7 h-5" />
                                    <span class="text-[11px] font-bold text-slate-800">{{ country.name }}</span>
                                    <span class="text-[8px] font-semibold uppercase tracking-wider text-slate-500">{{ country.plateTag }}</span>
                                </div>
                            </div>
                        </div>

                        <div class="mt-4 flex items-center justify-center gap-2 rounded-full border border-emerald-200 bg-emerald-50 px-4 py-2">
                            <svg class="h-4 w-4 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                            </svg>
                            <span class="text-[11px] font-black uppercase tracking-[0.18em] text-emerald-700">Reconocidas al instante por Gemini AI</span>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section id="inventory-import" class="border-b border-slate-100 bg-[#fff7ef] py-24 font-sans antialiased">
            <div class="mx-auto grid max-w-6xl grid-cols-1 items-center gap-12 px-6 lg:grid-cols-[1.05fr_1fr] lg:px-8">
                <div>
                    <span class="mb-3 block text-xs font-bold uppercase tracking-widest text-[#FF7A00]">
                        {{ inventoryImportHighlight.eyebrow }}
                    </span>
                    <h2 class="text-3xl font-black tracking-tight text-slate-900 md:text-5xl md:leading-tight">
                        {{ inventoryImportHighlight.title }}
                    </h2>
                    <p class="mt-5 max-w-2xl text-base leading-relaxed text-slate-600">
                        {{ inventoryImportHighlight.description }}
                    </p>

                    <div class="mt-6 flex flex-wrap gap-3">
                        <span class="inline-flex items-center rounded-full border border-[#FF7A00]/20 bg-white px-4 py-2 text-[11px] font-black uppercase tracking-[0.18em] text-[#FF7A00]">
                            Excel + CSV
                        </span>
                        <span class="inline-flex items-center rounded-full border border-emerald-200 bg-emerald-50 px-4 py-2 text-[11px] font-black uppercase tracking-[0.18em] text-emerald-700">
                            SKU inteligente
                        </span>
                        <span class="inline-flex items-center rounded-full border border-slate-200 bg-white px-4 py-2 text-[11px] font-black uppercase tracking-[0.18em] text-slate-600">
                            Stock sincronizado
                        </span>
                    </div>

                    <ul class="mt-8 space-y-4">
                        <li
                            v-for="bullet in inventoryImportHighlight.bullets"
                            :key="bullet"
                            class="flex items-start gap-4 rounded-2xl border border-white/80 bg-white/90 px-5 py-4 shadow-[0_12px_30px_rgba(15,23,42,0.04)]"
                        >
                            <span class="mt-0.5 flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-xl bg-[#FF7A00]/10 text-[#FF7A00]">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                                </svg>
                            </span>
                            <p class="text-sm font-semibold leading-relaxed text-slate-700">{{ bullet }}</p>
                        </li>
                    </ul>
                </div>

                <div class="relative">
                    <div class="absolute -inset-4 rounded-[2rem] bg-[radial-gradient(circle_at_top,_rgba(255,122,0,0.18),_transparent_65%)] blur-2xl"></div>
                    <div class="relative overflow-hidden rounded-[2rem] border border-white/80 bg-white p-4 shadow-[0_24px_60px_rgba(15,23,42,0.12)]">
                        <div class="mb-4 flex items-center justify-between rounded-2xl border border-slate-100 bg-slate-50 px-4 py-3">
                            <div>
                                <p class="text-[10px] font-black uppercase tracking-[0.22em] text-slate-400">Inventario</p>
                                <p class="mt-1 text-sm font-semibold text-slate-700">Carga masiva de productos para tu taller</p>
                            </div>
                            <span class="rounded-full bg-[#FF7A00] px-3 py-1 text-[10px] font-black uppercase tracking-[0.18em] text-white">
                                {{ inventoryImportHighlight.tag }}
                            </span>
                        </div>

                        <div class="overflow-hidden rounded-[1.5rem] border border-slate-100 bg-slate-50">
                            <picture>
                                <source :srcset="inventoryImportHighlight.imageWebp" type="image/webp" />
                                <img
                                    :src="inventoryImportHighlight.image"
                                    alt="Inventario TallerFlow con importación de productos desde Excel"
                                    loading="lazy"
                                    decoding="async"
                                    class="h-full w-full object-cover object-top"
                                />
                            </picture>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Sección "Problemas que resolvemos" (dolores del taller mecánico) -->
        <section id="problemas" class="py-24 bg-white border-b border-slate-100 font-sans antialiased">
            <div class="max-w-6xl mx-auto px-6 lg:px-8">
                <div class="text-center max-w-3xl mx-auto mb-16">
                    <span class="text-xs font-bold uppercase tracking-widest text-[#FF7A00] mb-3 block">Problemas que resolvemos</span>
                    <h2 class="text-3xl md:text-5xl font-black text-slate-900 tracking-tight leading-tight">
                        ¿Tu taller sufre estos <em class="text-[#FF7A00] not-italic">dolores de cabeza?</em>
                    </h2>
                    <p class="text-slate-500 mt-4 text-base leading-relaxed">
                        Sabemos cómo se siente el día a día de un taller mecánico. TallerFlow ataca de raíz los problemas
                        que frenan tu operación, hacen perder clientes y proyectan una imagen informal.
                    </p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <!-- Desorden operacional -->
                    <article class="bg-slate-50 rounded-3xl p-8 border border-slate-100 flex flex-col gap-4 transition-all duration-300 hover:border-[#FF7A00]/30 hover:shadow-[0_10px_30px_rgba(0,0,0,0.05)]">
                        <span class="flex-shrink-0 w-11 h-11 rounded-2xl bg-red-50 flex items-center justify-center">
                            <svg class="w-5 h-5 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" />
                            </svg>
                        </span>
                        <h3 class="text-lg font-black text-slate-900 tracking-tight">Desorden operacional</h3>
                        <p class="text-sm text-slate-500 leading-relaxed">
                            Autos sin estado claro, trabajos pendientes que nadie recuerda, repuestos que faltan y
                            mecánicos preguntando constantemente <span class="font-semibold text-slate-600">“¿qué sigue?”</span>.
                        </p>
                        <div class="mt-auto pt-4 flex items-start gap-3 border-t border-slate-200/70">
                            <svg class="w-4 h-4 text-emerald-600 flex-shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                            </svg>
                            <p class="text-sm text-slate-700 leading-relaxed">
                                <span class="font-bold text-slate-900">Con TallerFlow:</span> tablero Kanban en vivo con el
                                estado de cada auto, tareas asignadas e inventario conectado a cada orden.
                            </p>
                        </div>
                    </article>

                    <!-- Cotizaciones perdidas -->
                    <article class="bg-slate-50 rounded-3xl p-8 border border-slate-100 flex flex-col gap-4 transition-all duration-300 hover:border-[#FF7A00]/30 hover:shadow-[0_10px_30px_rgba(0,0,0,0.05)]">
                        <span class="flex-shrink-0 w-11 h-11 rounded-2xl bg-red-50 flex items-center justify-center">
                            <svg class="w-5 h-5 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 6.75c0 8.284 6.716 15 15 15h2.25a2.25 2.25 0 002.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106c-.44-.11-.902.055-1.173.417l-.97 1.293c-.282.376-.769.542-1.21.38a12.035 12.035 0 01-7.143-7.143c-.162-.441.004-.928.38-1.21l1.293-.97c.363-.271.527-.734.417-1.173L6.963 3.102a1.125 1.125 0 00-1.091-.852H4.5A2.25 2.25 0 002.25 4.5v2.25z" />
                            </svg>
                        </span>
                        <h3 class="text-lg font-black text-slate-900 tracking-tight">Cotizaciones perdidas</h3>
                        <p class="text-sm text-slate-500 leading-relaxed">
                            Se manda la cotización por WhatsApp, el cliente no responde y nadie hace seguimiento. Cada
                            cotización olvidada es dinero que se va.
                        </p>
                        <div class="mt-auto pt-4 flex items-start gap-3 border-t border-slate-200/70">
                            <svg class="w-4 h-4 text-emerald-600 flex-shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                            </svg>
                            <p class="text-sm text-slate-700 leading-relaxed">
                                <span class="font-bold text-slate-900">Con TallerFlow:</span> cotizaciones registradas con
                                seguimiento automático y recordatorios por WhatsApp para no perder ninguna venta.
                            </p>
                        </div>
                    </article>

                    <!-- Cliente impaciente -->
                    <article class="bg-slate-50 rounded-3xl p-8 border border-slate-100 flex flex-col gap-4 transition-all duration-300 hover:border-[#FF7A00]/30 hover:shadow-[0_10px_30px_rgba(0,0,0,0.05)]">
                        <span class="flex-shrink-0 w-11 h-11 rounded-2xl bg-red-50 flex items-center justify-center">
                            <svg class="w-5 h-5 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </span>
                        <h3 class="text-lg font-black text-slate-900 tracking-tight">Cliente impaciente</h3>
                        <p class="text-sm text-slate-500 leading-relaxed">
                            Llama una y otra vez porque no sabe si su auto está en diagnóstico, en reparación o listo para
                            retirar. Cada llamada interrumpe el trabajo.
                        </p>
                        <div class="mt-auto pt-4 flex items-start gap-3 border-t border-slate-200/70">
                            <svg class="w-4 h-4 text-emerald-600 flex-shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                            </svg>
                            <p class="text-sm text-slate-700 leading-relaxed">
                                <span class="font-bold text-slate-900">Con TallerFlow:</span> portal de seguimiento para el
                                cliente y avisos automáticos en cada cambio de estado del vehículo.
                            </p>
                        </div>
                    </article>

                    <!-- Historial pobre -->
                    <article class="bg-slate-50 rounded-3xl p-8 border border-slate-100 flex flex-col gap-4 transition-all duration-300 hover:border-[#FF7A00]/30 hover:shadow-[0_10px_30px_rgba(0,0,0,0.05)]">
                        <span class="flex-shrink-0 w-11 h-11 rounded-2xl bg-red-50 flex items-center justify-center">
                            <svg class="w-5 h-5 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 6.75h12M8.25 12h12m-12 5.25h12M3.75 6.75h.007v.008H3.75V6.75zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zM3.75 12h.007v.008H3.75V12zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm-.375 5.25h.007v.008H3.75v-.008zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z" />
                            </svg>
                        </span>
                        <h3 class="text-lg font-black text-slate-900 tracking-tight">Historial pobre</h3>
                        <p class="text-sm text-slate-500 leading-relaxed">
                            No saben qué se hizo hace 6 meses, cuánto gastó ese cliente ni qué vehículo vuelve más seguido.
                            Sin datos, no hay decisiones inteligentes.
                        </p>
                        <div class="mt-auto pt-4 flex items-start gap-3 border-t border-slate-200/70">
                            <svg class="w-4 h-4 text-emerald-600 flex-shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                            </svg>
                            <p class="text-sm text-slate-700 leading-relaxed">
                                <span class="font-bold text-slate-900">Con TallerFlow:</span> historial completo por vehículo
                                y por cliente, con gasto acumulado y frecuencia de visitas a un clic.
                            </p>
                        </div>
                    </article>

                    <!-- Poca imagen profesional -->
                    <article class="bg-slate-50 rounded-3xl p-8 border border-slate-100 flex flex-col gap-4 transition-all duration-300 hover:border-[#FF7A00]/30 hover:shadow-[0_10px_30px_rgba(0,0,0,0.05)]">
                        <span class="flex-shrink-0 w-11 h-11 rounded-2xl bg-red-50 flex items-center justify-center">
                            <svg class="w-5 h-5 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M11.48 3.499a.562.562 0 011.04 0l2.125 5.111a.563.563 0 00.475.345l5.518.442c.499.04.701.663.321.988l-4.204 3.602a.563.563 0 00-.182.557l1.285 5.385a.562.562 0 01-.84.61l-4.725-2.885a.562.562 0 00-.586 0L6.982 20.54a.562.562 0 01-.84-.61l1.285-5.386a.562.562 0 00-.182-.557l-4.204-3.602a.563.563 0 01.321-.988l5.518-.442a.563.563 0 00.475-.345L11.48 3.5z" />
                            </svg>
                        </span>
                        <h3 class="text-lg font-black text-slate-900 tracking-tight">Poca imagen profesional</h3>
                        <p class="text-sm text-slate-500 leading-relaxed">
                            El taller puede ser excelente técnicamente, pero se ve informal sin trazabilidad, sin portal,
                            sin tracking ni mensajes ordenados. La confianza se pierde antes de empezar.
                        </p>
                        <div class="mt-auto pt-4 flex items-start gap-3 border-t border-slate-200/70">
                            <svg class="w-4 h-4 text-emerald-600 flex-shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                            </svg>
                            <p class="text-sm text-slate-700 leading-relaxed">
                                <span class="font-bold text-slate-900">Con TallerFlow:</span> portal de marca, trazabilidad
                                completa y comunicación ordenada que proyectan un taller profesional y confiable.
                            </p>
                        </div>
                    </article>

                    <!-- Dependencia del dueño -->
                    <article class="bg-slate-50 rounded-3xl p-8 border border-slate-100 flex flex-col gap-4 transition-all duration-300 hover:border-[#FF7A00]/30 hover:shadow-[0_10px_30px_rgba(0,0,0,0.05)]">
                        <span class="flex-shrink-0 w-11 h-11 rounded-2xl bg-red-50 flex items-center justify-center">
                            <svg class="w-5 h-5 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z" />
                            </svg>
                        </span>
                        <h3 class="text-lg font-black text-slate-900 tracking-tight">Dependencia del dueño</h3>
                        <p class="text-sm text-slate-500 leading-relaxed">
                            Si el jefe no está, nadie sabe qué pasa. La operación se paraliza y el negocio no puede crecer
                            porque todo vive en la cabeza de una sola persona.
                        </p>
                        <div class="mt-auto pt-4 flex items-start gap-3 border-t border-slate-200/70">
                            <svg class="w-4 h-4 text-emerald-600 flex-shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                            </svg>
                            <p class="text-sm text-slate-700 leading-relaxed">
                                <span class="font-bold text-slate-900">Con TallerFlow:</span> toda la información centralizada
                                y accesible para tu equipo, para que el taller funcione con o sin el dueño presente.
                            </p>
                        </div>
                    </article>
                </div>
            </div>
        </section>

        <section id="preview-slider" class="py-24 bg-[#f8fafc] border-b border-slate-100 font-sans antialiased">
            <div class="max-w-6xl mx-auto px-6 lg:px-8">
                <div class="text-center max-w-3xl mx-auto mb-16">
                    <span class="text-xs font-bold uppercase tracking-widest text-[#FF7A00] mb-3 block">Vista en Vivo</span>
                    <h2 class="text-3xl md:text-5xl font-black text-slate-900 tracking-tight leading-tight">
                        Una interfaz potente y <em class="text-[#FF7A00] not-italic">sorprendentemente simple.</em>
                    </h2>
                    <p class="text-slate-500 mt-4 text-base leading-relaxed">
                        Explora cómo funciona TallerFlow desde adentro. Diseñado para verse increíble en cualquier pantalla y funcionar a la velocidad de tu taller.
                    </p>
                </div>
                
                <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-center">
                    <!-- Main Image Display -->
                    <div class="lg:col-span-8 bg-white rounded-3xl p-4 shadow-[0_20px_50px_rgba(0,0,0,0.05)] border border-slate-100 overflow-hidden relative group">
                        <div class="aspect-[16/10] overflow-hidden rounded-2xl bg-slate-50 relative">
                            <div v-for="(slide, index) in slides" :key="slide.image" 
                                 class="absolute inset-0 transition-all duration-500 ease-in-out"
                                :class="currentSlide === index ? 'opacity-100 scale-100 pointer-events-auto' : 'opacity-0 scale-95 pointer-events-none'">
                                <picture>
                                    <source :srcset="slide.imageWebp" type="image/webp" />
                                    <img :src="slide.image" :alt="slide.title" loading="lazy" decoding="async" class="w-full h-full object-cover object-top" />
                                </picture>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Thumbnails -->
                    <div class="lg:col-span-4 flex flex-col gap-4">
                        <button v-for="(slide, index) in slides" :key="index" @click="currentSlide = index; stopAutoPlay()"
                            class="text-left p-6 rounded-2xl border transition-all duration-300 flex flex-col gap-2 relative overflow-hidden focus:outline-none"
                            :class="currentSlide === index ? 'bg-white border-[#FF7A00] shadow-[0_10px_30px_rgba(255,181,71,0.1)] translate-x-2' : 'bg-transparent border-slate-100 hover:bg-white hover:border-slate-200 hover:shadow-sm'">
                            
                            <div v-if="currentSlide === index" class="absolute left-0 top-0 bottom-0 w-1 bg-[#FF7A00]"></div>
                            
                            <div class="flex items-center justify-between">
                                <span class="text-[10px] font-bold uppercase tracking-widest px-2.5 py-0.5 rounded-full"
                                    :class="slide.tagClass">
                                    {{ slide.tag }}
                                </span>
                                <span class="text-xs text-slate-400 font-mono">0{{ index + 1 }}</span>
                            </div>
                            <h3 class="text-base font-bold text-slate-900 leading-snug">{{ slide.title }}</h3>
                            <p class="text-xs text-slate-500 leading-relaxed">{{ slide.description }}</p>
                        </button>
                    </div>
                </div>
            </div>
        </section>

        <section id="benefits" class="py-24 bg-white">
            <div class="max-w-6xl mx-auto px-6 lg:px-8">
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-16 items-center">
                    <div>
                        <p class="text-xs font-bold uppercase tracking-widest text-tech-orange mb-3">Beneficios</p>
                        <h2 class="text-3xl md:text-4xl font-black text-gray-900 tracking-tight leading-tight mb-6">
                            Menos papel, menos caos,<br>más tiempo para trabajar.
                        </h2>
                        <p class="text-gray-500 mb-10 leading-relaxed">
                            Digitaliza tu taller en menos de un día. Sin instalaciones, sin servidores, sin dolores de
                            cabeza.
                            Accede desde cualquier computador o celular.
                        </p>

                        <ul class="flex flex-col gap-5">
                            <li class="flex items-start gap-4">
                                <span
                                    class="flex-shrink-0 w-8 h-8 rounded-xl bg-emerald-50 flex items-center justify-center mt-0.5">
                                    <svg class="w-4 h-4 text-emerald-600" fill="none" viewBox="0 0 24 24"
                                        stroke="currentColor" stroke-width="2.5">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M4.5 12.75l6 6 9-13.5" />
                                    </svg>
                                </span>
                                <div>
                                    <p class="font-bold text-gray-900 text-sm">Histórico completo de cada vehículo</p>
                                    <p class="text-sm text-gray-500 mt-0.5">Accede al registro de todas las visitas y
                                        trabajos
                                        realizados a cada auto al instante.</p>
                                </div>
                            </li>
                            <li class="flex items-start gap-4">
                                <span
                                    class="flex-shrink-0 w-8 h-8 rounded-xl bg-emerald-50 flex items-center justify-center mt-0.5">
                                    <svg class="w-4 h-4 text-emerald-600" fill="none" viewBox="0 0 24 24"
                                        stroke="currentColor" stroke-width="2.5">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M4.5 12.75l6 6 9-13.5" />
                                    </svg>
                                </span>
                                <div>
                                    <p class="font-bold text-gray-900 text-sm">Notificaciones en tiempo real</p>
                                    <p class="text-sm text-gray-500 mt-0.5">Tu equipo se entera al instante cuando
                                        cambia el
                                        estado de una orden de trabajo.</p>
                                </div>
                            </li>
                            <li class="flex items-start gap-4">
                                <span
                                    class="flex-shrink-0 w-8 h-8 rounded-xl bg-emerald-50 flex items-center justify-center mt-0.5">
                                    <svg class="w-4 h-4 text-emerald-600" fill="none" viewBox="0 0 24 24"
                                        stroke="currentColor" stroke-width="2.5">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M4.5 12.75l6 6 9-13.5" />
                                    </svg>
                                </span>
                                <div>
                                    <p class="font-bold text-gray-900 text-sm">Multi-sucursal desde el día uno</p>
                                    <p class="text-sm text-gray-500 mt-0.5">Cada taller tiene su propio espacio aislado
                                        y
                                        seguro. Escala sin límites.</p>
                                </div>
                            </li>
                        </ul>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div class="bg-gray-50 rounded-3xl p-6 border border-gray-100">
                            <p class="text-4xl font-black text-gray-900">3×</p>
                            <p class="text-sm text-gray-500 mt-1 font-medium leading-snug">más rápido en recibir un
                                vehículo</p>
                        </div>
                        <div class="bg-tech-orange rounded-3xl p-6">
                            <p class="text-4xl font-black text-white">0</p>
                            <p class="text-sm text-white/80 mt-1 font-medium leading-snug">instalaciones requeridas</p>
                        </div>
                        <div class="bg-gray-900 rounded-3xl p-6 col-span-2">
                            <p class="text-4xl font-black text-white">+200</p>
                            <p class="text-sm text-gray-400 mt-1 font-medium">talleres mecánicos ya confían en Taller Flow</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Sección "Visita nuestros clientes" (Talleres activos) -->
        <section v-if="tenants && tenants.length > 0" id="our-clients" class="py-24 bg-slate-50/50 border-t border-slate-100 font-sans antialiased">
            <div class="max-w-6xl mx-auto px-6 lg:px-8">
                <div class="text-center max-w-3xl mx-auto mb-16">
                    <span class="text-xs font-bold uppercase tracking-widest text-[#FF7A00] mb-3 block">Nuestra Red</span>
                    <h2 class="text-3xl md:text-5xl font-black text-slate-900 tracking-tight leading-tight">
                        Visita nuestros <em class="text-[#FF7A00] not-italic">clientes</em>
                    </h2>
                    <p class="text-slate-500 mt-4 text-base leading-relaxed">
                        Conoce a los talleres mecánicos con más actividad que confían en TallerFlow para optimizar su gestión diaria.
                    </p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                    <div
                        v-for="tenant in tenants"
                        :key="tenant.id"
                        class="bg-white rounded-3xl p-8 border border-slate-100 hover:border-slate-200 hover:shadow-xl hover:-translate-y-1 transition-all duration-300 flex flex-col justify-between group h-full"
                    >
                        <div>
                            <span v-if="tenant.comuna" class="inline-flex items-center gap-1 text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-3">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 text-[#FF7A00]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                </svg>
                                {{ tenant.comuna }}
                            </span>
                            <h3 class="text-xl font-black text-slate-900 leading-snug group-hover:text-[#FF7A00] transition-colors mb-2">
                                <Link :href="tenant.landing_url">{{ tenant.name }}</Link>
                            </h3>
                            <p class="text-slate-500 text-sm leading-relaxed mb-6">
                                Agenda tu hora directamente en la landing page del taller para recibir una atención inteligente y sin esperas.
                            </p>
                        </div>
                        <div class="flex items-center justify-between pt-6 border-t border-slate-50 mt-auto">
                            <Link
                                :href="tenant.landing_url"
                                class="inline-flex items-center gap-1.5 text-sm font-bold text-[#FF7A00] hover:text-[#CC6200] group/link transition-colors"
                            >
                                Agendar Hora
                                <svg class="w-4 h-4 transform group-hover/link:translate-x-1 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                    <line x1="5" y1="12" x2="19" y2="12"/>
                                    <polyline points="12 5 19 12 12 19"/>
                                </svg>
                            </Link>
                            <a
                                v-if="tenant.website_url"
                                :href="tenant.website_url"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="inline-flex items-center gap-1 text-xs font-semibold text-slate-400 hover:text-slate-600 hover:underline transition-colors"
                            >
                                Sitio web
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                                </svg>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Sección de Últimos Posts del Blog -->
        <section v-if="posts && posts.length > 0" id="latest-posts" class="py-24 bg-white border-t border-gray-100 font-sans antialiased">
            <div class="max-w-6xl mx-auto px-6 lg:px-8">
                <div class="text-center max-w-3xl mx-auto mb-16">
                    <span class="text-xs font-bold uppercase tracking-widest text-[#FF7A00] mb-3 block">Recursos y Aprendizaje</span>
                    <h2 class="text-3xl md:text-5xl font-black text-slate-900 tracking-tight leading-tight">
                        Consejos y guías para <em class="text-[#FF7A00] not-italic">potenciar tu taller.</em>
                    </h2>
                    <p class="text-slate-500 mt-4 text-base leading-relaxed">
                        Aprende prácticas de optimización de procesos, marketing para talleres y fidelización de clientes con nuestro blog oficial.
                    </p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                    <div v-for="post in posts" :key="post.id" class="group bg-white rounded-3xl border border-slate-100 hover:border-slate-200 overflow-hidden shadow-sm hover:shadow-xl transition-all duration-300 flex flex-col h-full">
                        <!-- Imagen destacada -->
                        <div class="aspect-[16/10] overflow-hidden bg-slate-50 relative">
                            <img v-if="post.featured_image_url" :src="post.featured_image_url" :alt="post.title" loading="lazy" decoding="async" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500" />
                            <div v-else class="w-full h-full flex items-center justify-center bg-slate-100 text-slate-300">
                                <svg class="w-12 h-12" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                            </div>
                            <span class="absolute top-4 left-4 bg-[#FF7A00] text-white text-[10px] font-black uppercase tracking-widest px-3 py-1 rounded-full shadow-sm">Artículo</span>
                        </div>

                        <!-- Información -->
                        <div class="p-6 flex flex-col flex-grow">
                            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-2">{{ post.published_at }}</p>
                            <h3 class="text-lg font-black text-slate-900 leading-snug group-hover:text-[#FF7A00] transition-colors line-clamp-2">
                                <Link :href="route('blog.show', { slug: post.slug })">
                                    {{ post.title }}
                                </Link>
                            </h3>
                            <p class="text-slate-500 text-sm mt-3 leading-relaxed line-clamp-3 mb-6">
                                {{ post.summary }}
                            </p>
                            <!-- Enlace de lectura -->
                            <div class="mt-auto pt-4 border-t border-slate-50">
                                <Link :href="route('blog.show', { slug: post.slug })" class="text-xs font-bold text-[#FF7A00] hover:text-[#CC6200] flex items-center gap-1 group/btn transition-colors">
                                    Leer artículo
                                    <svg class="w-3.5 h-3.5 group-hover/btn:translate-x-1 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
                                </Link>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Botón de explorar todo -->
                <div class="flex justify-center mt-12">
                    <Link :href="route('blog.index')" class="inline-flex items-center gap-2 px-6 py-3 bg-slate-900 text-white rounded-2xl font-bold text-sm shadow-md hover:bg-slate-800 active:scale-[0.98] transition-all">
                        Explorar todo el blog
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
                    </Link>
                </div>
            </div>
        </section>

        <section id="pricing" class="py-20 bg-gray-50 border-y border-gray-100">
            <div class="max-w-5xl mx-auto px-6 lg:px-8">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-8 text-center">
                    <div class="flex flex-col items-center gap-4 p-6">
                        <div
                            class="w-14 h-14 rounded-2xl bg-white border border-gray-100 shadow-sm flex items-center justify-center">
                            <svg class="w-7 h-7 text-tech-orange" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                stroke-width="1.75">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z" />
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-base font-bold text-gray-900">Soporte Humano Real</h3>
                            <p class="text-sm text-gray-500 mt-1.5 leading-relaxed">
                                Habla con personas reales en español. Sin bots, sin tickets que duermen meses.
                            </p>
                        </div>
                    </div>

                    <div class="flex flex-col items-center gap-4 p-6 border-x border-gray-200">
                        <div
                            class="w-14 h-14 rounded-2xl bg-white border border-gray-100 shadow-sm flex items-center justify-center">
                            <svg class="w-7 h-7 text-tech-orange" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                stroke-width="1.75">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M12 9v3.75m0-10.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.75c0 5.592 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.57-.598-3.75h-.152c-3.196 0-6.1-1.249-8.25-3.286zm0 13.036h.008v.008H12v-.008z" />
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-base font-bold text-gray-900">Seguridad en la Nube</h3>
                            <p class="text-sm text-gray-500 mt-1.5 leading-relaxed">
                                Tus datos siempre respaldados. Cumplimiento con estándares internacionales de
                                privacidad.
                            </p>
                        </div>
                    </div>

                    <div class="flex flex-col items-center gap-4 p-6">
                        <div
                            class="w-14 h-14 rounded-2xl bg-white border border-gray-100 shadow-sm flex items-center justify-center">
                            <svg class="w-7 h-7 text-tech-orange" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                stroke-width="1.75">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M3.75 13.5l10.5-11.25L12 10.5h8.25L9.75 21.75 12 13.5H3.75z" />
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-base font-bold text-gray-900">Sin Instalaciones Complejas</h3>
                            <p class="text-sm text-gray-500 mt-1.5 leading-relaxed">
                                Abre el navegador y empieza. Nada que instalar, configurar ni mantener.
                            </p>
                        </div>
                    </div>
                </div>

                <div
                    class="mt-16 bg-gray-900 rounded-3xl p-10 md:p-14 flex flex-col md:flex-row items-center justify-between gap-8">
                    <div>
                        <h3 class="text-2xl md:text-3xl font-black text-white leading-tight">
                            ¿Listo para digitalizar tu taller?
                        </h3>
                        <p class="text-gray-400 mt-2 text-sm">
                            14 días gratis. Sin tarjeta de crédito. Cancela cuando quieras.
                        </p>
                    </div>
                    <Link v-if="$page.props.auth?.user" :href="route('dashboard')"
                        class="flex-shrink-0 inline-flex items-center gap-2 bg-tech-orange hover:bg-[#CC6200] text-white font-bold px-8 py-4 rounded-2xl shadow-lg shadow-tech-orange/30 transition-all active:scale-[0.98] whitespace-nowrap">
                        Ir al Dashboard
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3" />
                        </svg>
                    </Link>
                    <Link v-else-if="canRegister" :href="route('trial.create')"
                        class="flex-shrink-0 inline-flex items-center gap-2 bg-tech-orange hover:bg-[#CC6200] text-white font-bold px-8 py-4 rounded-2xl shadow-lg shadow-tech-orange/30 transition-all active:scale-[0.98] whitespace-nowrap">
                        Empezar Gratis Ahora
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3" />
                        </svg>
                    </Link>
                    <button v-else type="button" @click="showLoginModal = true"
                        class="flex-shrink-0 inline-flex items-center gap-2 bg-tech-orange hover:bg-[#CC6200] text-white font-bold px-8 py-4 rounded-2xl shadow-lg shadow-tech-orange/30 transition-all active:scale-[0.98] whitespace-nowrap">
                        Empezar Gratis Ahora
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3" />
                        </svg>
                    </button>
                </div>
            </div>
        </section>

        <PublicFooter />

        <LoginModal :show="showLoginModal" @close="showLoginModal = false" />
    </main>
</template>

<style scoped>
.hero-page {
    background: var(--bg);
    padding: 18px;
    font-family: 'Inter Variable', 'Inter', ui-sans-serif, system-ui, sans-serif;
    -webkit-font-smoothing: antialiased;
}

.hero {
    position: relative;
    overflow: hidden;
    isolation: isolate;
    min-height: 760px;
    border-radius: var(--radius);
    background: #06070a;
}

.hero-img {
    position: absolute;
    inset: 0;
    z-index: 0;
    background-image: image-set(url('/images/car-hero.webp') type('image/webp'), url('/images/car-hero.jpg') type('image/jpeg'));
    background-repeat: no-repeat;
    background-position: -8% 65%;
    background-size: 95%;
    filter: saturate(1.05) contrast(1.05);
}

.hero-glow {
    position: absolute;
    inset: 0;
    z-index: 0;
    pointer-events: none;
    mix-blend-mode: screen;
    background:
        radial-gradient(60% 50% at 42% 60%, rgba(40, 90, 180, 0.28), transparent 65%),
        radial-gradient(80% 60% at 42% 80%, rgba(255, 80, 40, 0.10), transparent 70%);
}

.hero-fade {
    position: absolute;
    inset: 0;
    z-index: 1;
    pointer-events: none;
    background: linear-gradient(90deg, rgba(6, 7, 10, 0) 30%, rgba(6, 7, 10, 0.65) 55%, rgba(6, 7, 10, 0.96) 85%);
}

.hero-vignette {
    position: absolute;
    inset: 0;
    z-index: 1;
    pointer-events: none;
    background: linear-gradient(rgba(6, 7, 10, 0.55) 0%, transparent 18%, transparent 70%, rgba(6, 7, 10, 0.7) 100%);
}

.hero-noise {
    position: absolute;
    inset: 0;
    z-index: 2;
    pointer-events: none;
    opacity: 0.05;
    mix-blend-mode: overlay;
    background-image:
        repeating-linear-gradient(45deg, rgba(255, 255, 255, 1) 0 1px, transparent 1px 3px),
        repeating-linear-gradient(-45deg, rgba(255, 255, 255, 1) 0 1px, transparent 1px 3px);
}

.dark-wrapper {
    position: relative;
    background: #0a0a0b;
    --bg: #0a0a0b;
    --ink: #ffffff;
    --ink-dim: rgba(255, 255, 255, 0.62);
    --line: rgba(255, 255, 255, 0.12);
    --line-strong: rgba(255, 255, 255, 0.22);
    --accent: #FF7A00;
    --accent-ink: #1a0e00;
    --radius: 28px;
}

.meta-row {
    position: absolute;
    top: 118px;
    right: 48px;
    left: 48px;
    z-index: 5;
    display: grid;
    grid-template-columns: 1.2fr 1fr 1.2fr;
    align-items: start;
    gap: 32px;
    color: var(--ink-dim);
    font-size: 13px;
}

.meta-line {
    position: relative;
    padding-top: 16px;
}

.meta-line::before {
    position: absolute;
    top: 0;
    right: 24px;
    left: 0;
    height: 1px;
    background: var(--line);
    content: '';
}

.meta-label {
    display: block;
    color: var(--ink);
    font-weight: 500;
    letter-spacing: 0.02em;
}

.meta-sub {
    display: block;
    max-width: 32ch;
    margin-top: 4px;
    color: var(--ink-dim);
    line-height: 1.5;
}

.headline-wrap {
    position: absolute;
    right: 48px;
    bottom: 140px;
    left: 48px;
    z-index: 5;
    display: grid;
    grid-template-columns: auto 1fr;
    align-items: end;
    gap: 24px;
}

.index-num {
    padding-bottom: 18px;
    color: var(--ink-dim);
    font-family: 'JetBrains Mono', ui-monospace, monospace;
    font-size: 14px;
    letter-spacing: 0.1em;
}

.headline {
    margin: 0;
    max-width: 18ch;
    color: var(--ink);
    font-size: clamp(48px, 7.4vw, 116px);
    font-weight: 700;
    line-height: 0.95;
    letter-spacing: -0.035em;
    text-wrap: balance;
}

.headline em {
    color: var(--accent);
    font-style: normal;
    font-weight: 700;
}

.badge-pill {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 7px 14px;
    border: 1px solid rgba(255, 181, 71, 0.28);
    border-radius: 999px;
    background: rgba(255, 181, 71, 0.10);
    color: var(--accent);
    font-family: 'JetBrains Mono', monospace;
    font-size: 12px;
    font-weight: 500;
    letter-spacing: 0.04em;
    text-transform: uppercase;
}

.badge-pill .dot {
    width: 6px;
    height: 6px;
    border-radius: 999px;
    background: var(--accent);
    box-shadow: 0 0 12px var(--accent);
}

.cta-strip {
    position: absolute;
    right: 0;
    bottom: 0;
    left: 0;
    z-index: 5;
    display: grid;
    grid-template-columns: 1fr 1fr;
    border-top: 1px solid var(--line);
    background: linear-gradient(180deg, transparent, rgba(0, 0, 0, 0.35));
}

.cta {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 26px 36px;
    border-right: 1px solid var(--line);
    color: var(--ink);
    cursor: pointer;
    text-decoration: none;
    transition: background 0.2s ease;
}

.cta:last-child {
    border-right: 0;
}

.cta:hover {
    background: rgba(255, 255, 255, 0.04);
}

.cta-title {
    display: block;
    font-size: 17px;
    font-weight: 500;
}

.cta-sub {
    display: block;
    margin-top: 4px;
    color: var(--ink-dim);
    font-size: 12px;
}

.cta-arrow {
    display: grid;
    width: 44px;
    height: 44px;
    flex-shrink: 0;
    place-items: center;
    border: 1px solid var(--line-strong);
    border-radius: 999px;
    transition: all 0.2s ease;
}

.cta:hover .cta-arrow {
    border-color: var(--accent);
    background: var(--accent);
    color: var(--accent-ink);
    transform: translateX(4px);
}

.cta.primary .cta-arrow {
    border-color: var(--accent);
    background: var(--accent);
    color: var(--accent-ink);
}

.feat-section {
    padding: 0 18px 18px;
    background: var(--bg, #0a0a0b);
    font-family: 'Inter Variable', 'Inter', ui-sans-serif, system-ui, sans-serif;
    -webkit-font-smoothing: antialiased;
}

.feat-inner {
    padding: 52px 48px 48px;
    border: 1px solid rgba(255, 255, 255, 0.12);
    border-radius: var(--radius, 28px);
    background: #0e0e11;
    color: #ffffff;
}

.feat-header {
    margin-bottom: 40px;
}

.feat-eyebrow {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 20px;
    padding: 7px 14px;
    border: 1px solid rgba(255, 181, 71, 0.28);
    border-radius: 999px;
    background: rgba(255, 181, 71, 0.10);
    color: #FF7A00;
    font-family: 'JetBrains Mono', monospace;
    font-size: 12px;
    font-weight: 500;
    letter-spacing: 0.06em;
    text-transform: uppercase;
}

.feat-eyebrow .dot {
    width: 6px;
    height: 6px;
    border-radius: 999px;
    background: #FF7A00;
    box-shadow: 0 0 10px #FF7A00;
}

.feat-title {
    margin: 0 0 12px;
    color: #ffffff;
    font-size: clamp(26px, 3vw, 42px);
    font-weight: 700;
    line-height: 1.1;
    letter-spacing: -0.025em;
}

.feat-title em {
    color: #FF7A00;
    font-style: normal;
}

.feat-sub {
    max-width: 50ch;
    margin: 0;
    color: rgba(255, 255, 255, 0.55);
    font-size: 15px;
    line-height: 1.6;
}

.feat-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 1px;
    overflow: hidden;
    border: 1px solid rgba(255, 255, 255, 0.12);
    border-radius: 18px;
    background: rgba(255, 255, 255, 0.12);
}

.feat-card {
    position: relative;
    display: flex;
    flex-direction: column;
    gap: 12px;
    padding: 32px 28px;
    background: #0e0e11;
    transition: background 0.2s ease;
}

.feat-card:hover {
    background: #131317;
}

.feat-ico {
    display: grid;
    width: 42px;
    height: 42px;
    flex-shrink: 0;
    place-items: center;
    border: 1px solid rgba(255, 181, 71, 0.25);
    border-radius: 12px;
    background: rgba(255, 181, 71, 0.12);
    color: #FF7A00;
}

.feat-card h3 {
    margin: 6px 0 0;
    color: #ffffff;
    font-size: 17px;
    font-weight: 600;
    letter-spacing: -0.01em;
}

.feat-card p {
    margin: 0;
    color: rgba(255, 255, 255, 0.55);
    font-size: 14px;
    line-height: 1.6;
}

.feat-card-ai {
    overflow: hidden;
}

.feat-ai-badge {
    position: absolute;
    top: 16px;
    right: 16px;
    padding: 3px 8px;
    border-radius: 999px;
    background: #FF7A00;
    color: #1a0e00;
    font-family: 'JetBrains Mono', monospace;
    font-size: 10px;
    font-weight: 800;
    letter-spacing: 0.08em;
    text-transform: uppercase;
}

.feat-tags {
    display: flex;
    flex-wrap: wrap;
    gap: 6px;
    margin-top: 8px;
}

.feat-tag {
    padding: 4px 10px;
    border-radius: 999px;
    font-size: 11px;
    font-weight: 600;
    letter-spacing: 0.02em;
}

.feat-tag-blue {
    border: 1px solid rgba(59, 130, 246, 0.25);
    background: rgba(59, 130, 246, 0.15);
    color: #7ab8ff;
}

.feat-tag-green {
    border: 1px solid rgba(52, 211, 153, 0.22);
    background: rgba(52, 211, 153, 0.12);
    color: #6ee7b7;
}

.feat-tag-purple {
    border: 1px solid rgba(139, 92, 246, 0.25);
    background: rgba(139, 92, 246, 0.15);
    color: #c4b5fd;
}

.feat-tag-dim {
    border: 1px solid rgba(255, 255, 255, 0.10);
    background: rgba(255, 255, 255, 0.06);
    color: rgba(255, 255, 255, 0.45);
}

@media (max-width: 1100px) {
    .meta-row {
        display: none;
    }

    .headline-wrap {
        bottom: 160px;
    }
}

@media (max-width: 900px) {

    .hero-page,
    .feat-section {
        padding-right: 12px;
        padding-left: 12px;
    }

    .hero-page {
        padding-top: 86px;
    }

    .hero {
        min-height: auto;
        padding: 72px 20px 20px;
    }

    .hero-img {
        background-position: center 20%;
        background-size: 180%;
    }

    .hero-fade {
        background: linear-gradient(180deg, rgba(6, 7, 10, 0.82) 0%, rgba(6, 7, 10, 0.46) 26%, rgba(6, 7, 10, 0.88) 70%, rgba(6, 7, 10, 0.98) 100%);
    }

    .headline-wrap {
        position: relative;
        right: auto;
        bottom: auto;
        left: auto;
        display: flex;
        flex-direction: column;
        align-items: flex-start;
        gap: 10px;
    }

    .index-num {
        padding-bottom: 0;
        font-size: 12px;
        letter-spacing: 0.12em;
    }

    .headline {
        max-width: 9ch;
        font-size: clamp(42px, 15vw, 60px);
        line-height: 0.98;
    }

    .cta-strip {
        position: relative;
        right: auto;
        bottom: auto;
        left: auto;
        display: flex;
        flex-direction: column;
        gap: 12px;
        margin-top: 28px;
        border-top: 0;
        background: none;
    }

    .cta {
        gap: 16px;
        padding: 18px;
        border: 1px solid var(--line);
        border-radius: 22px;
        background: rgba(255, 255, 255, 0.04);
    }

    .cta.primary {
        background: linear-gradient(135deg, rgba(255, 181, 71, 0.24), rgba(255, 122, 0, 0.18));
    }

    .cta-title {
        font-size: 16px;
    }

    .cta-sub {
        max-width: 28ch;
        line-height: 1.5;
    }

    .cta-arrow {
        width: 40px;
        height: 40px;
    }

    .feat-grid {
        grid-template-columns: 1fr;
    }

    .feat-inner {
        padding: 32px 20px;
        border-radius: 24px;
    }
}
</style>
