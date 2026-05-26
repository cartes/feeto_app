<script setup>
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import { Head, Link } from '@inertiajs/vue3';
import { computed, onMounted, onUnmounted, ref } from 'vue';
import LoginModal from '@/Components/LoginModal.vue';
import PublicNav from '@/Components/PublicNav.vue';

const props = defineProps({
    canLogin: { type: Boolean },
    canRegister: { type: Boolean },
    seo: { type: Object, default: () => ({}) },
});

const canonicalUrl = computed(() => {
    if (typeof window !== 'undefined') return window.location.origin + '/';
    return 'https://tallerflow.cl/';
});

const showLoginModal = ref(false);
const activeSection = ref('features');

// Slider interactivo de funcionalidades
const currentSlide = ref(0);
const slides = [
    {
        title: 'Agenda Semanal del Taller',
        description: 'Controla citas, asigna mecánicos y programa servicios con un calendario interactivo en tiempo real.',
        tag: 'Calendario',
        tagClass: 'bg-blue-50 text-blue-600 border border-blue-100',
        image: '/images/dashboard_agenda.png'
    },
    {
        title: 'Tablero Kanban de Órdenes',
        description: 'Mueve órdenes de trabajo entre etapas (Recepción, Diagnóstico, Listo) arrastrándolas con un clic en tiempo real.',
        tag: 'Tablero Kanban',
        tagClass: 'bg-orange-50 text-orange-600 border border-orange-100',
        image: '/images/dashboard_kanban.png'
    },
    {
        title: 'Recepción con IA móvil',
        description: 'Escanea la patente con tu teléfono y la IA completará los datos del vehículo y cliente de inmediato con Gemini.',
        tag: 'Recepción IA',
        tagClass: 'bg-purple-50 text-purple-600 border border-purple-100',
        image: '/images/recepcion_ia.png'
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

let scrollHandler = null;

onMounted(() => {
    scrollHandler = () => {
        const benefits = document.getElementById('benefits');
        const pricing = document.getElementById('pricing');

        if (!benefits) return;

        const scrollY = window.scrollY;
        const benefitsTop = benefits.getBoundingClientRect().top + scrollY;
        const pricingTop = pricing ? pricing.getBoundingClientRect().top + scrollY : Number.POSITIVE_INFINITY;

        if (scrollY >= pricingTop - 180) {
            activeSection.value = 'pricing';
        } else if (scrollY >= benefitsTop - 180) {
            activeSection.value = 'benefits';
        } else {
            activeSection.value = 'features';
        }
    };

    scrollHandler();
    window.addEventListener('scroll', scrollHandler, { passive: true });
    startAutoPlay();
});

onUnmounted(() => {
    if (scrollHandler) window.removeEventListener('scroll', scrollHandler);
    stopAutoPlay();
});
</script>

<template>

    <Head>
        <title>{{ seo.title ?? 'TallerFlow · Software para Talleres Mecánicos en Chile' }}</title>
        <meta name="description" :content="seo.description ?? ''">
        <meta name="robots" content="index, follow">
        <link rel="canonical" :href="canonicalUrl">
        <meta property="og:type" content="website">
        <meta property="og:title" :content="seo.title ?? 'TallerFlow · Software para Talleres Mecánicos en Chile'">
        <meta property="og:description" :content="seo.description ?? ''">
        <meta property="og:url" :content="canonicalUrl">
        <meta v-if="seo.og_image" property="og:image" :content="seo.og_image">
        <meta name="twitter:card" content="summary_large_image">
        <meta name="twitter:title" :content="seo.title ?? 'TallerFlow · Software para Talleres Mecánicos en Chile'">
        <meta name="twitter:description" :content="seo.description ?? ''">
    </Head>

    <PublicNav :can-login="canLogin" :active-section="activeSection" />

    <div class="min-h-screen bg-white font-sans antialiased">
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
                            <span class="badge-pill">
                                <span class="dot"></span>
                                Software para Talleres
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
                        <Link v-if="canRegister" :href="route('trial.create')" class="cta primary">
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
                                <span class="cta-sub">Ingresa y empieza a probar Feeto</span>
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
                            <p>Stock al día con alertas de mínimos, control de proveedores y registro automático al
                                cerrar cada
                                orden.</p>
                            <div class="feat-tags">
                                <span class="feat-tag feat-tag-green">Stock Mínimo</span>
                                <span class="feat-tag feat-tag-dim">Alertas</span>
                            </div>
                        </div>

                        <div class="feat-card feat-card-ai">
                            <span class="feat-ai-badge">IA</span>
                            <span class="feat-ico">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                    stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2" />
                                    <circle cx="9" cy="7" r="4" />
                                    <path d="M23 21v-2a4 4 0 0 0-3-3.87" />
                                    <path d="M16 3.13a4 4 0 0 1 0 7.75" />
                                </svg>
                            </span>
                            <h3>Recepción con IA</h3>
                            <p>Escanea patentes con la cámara de tu celular. Gemini AI lee la patente, cruza la agenda y
                                muestra los
                                datos del cliente en segundos.</p>
                            <div class="feat-tags">
                                <span class="feat-tag feat-tag-purple">Gemini AI</span>
                                <span class="feat-tag feat-tag-dim">ALPR</span>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        </div>

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
                                <img :src="slide.image" :alt="slide.title" class="w-full h-full object-cover object-top" />
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
                            <p class="text-sm text-gray-400 mt-1 font-medium">talleres mecánicos ya confían en Feeto</p>
                        </div>
                    </div>
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
                    <Link v-if="canRegister" :href="route('trial.create')"
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

        <footer class="bg-white border-t border-gray-100 py-10">
            <div class="max-w-7xl mx-auto px-6 lg:px-8 flex flex-col md:flex-row items-center justify-between gap-6">
                <div class="flex items-center gap-2">
                    <ApplicationLogo class="h-7 w-7 rounded-lg" />
                    <span class="text-base font-black text-gray-900">Feeto</span>
                </div>

                <nav class="flex items-center gap-6 flex-wrap justify-center">
                    <a href="#" class="text-xs text-gray-400 hover:text-gray-600 transition-colors font-medium">Términos
                        de
                        Servicio</a>
                    <a href="#" class="text-xs text-gray-400 hover:text-gray-600 transition-colors font-medium">Política
                        de
                        Privacidad</a>
                    <a href="#"
                        class="text-xs text-gray-400 hover:text-gray-600 transition-colors font-medium">Contacto</a>
                </nav>

                <p class="text-xs text-gray-400 font-medium">
                    &copy; {{ new Date().getFullYear() }} Feeto. Todos los derechos reservados.
                </p>
            </div>
        </footer>

        <LoginModal :show="showLoginModal" @close="showLoginModal = false" />
    </div>
</template>

<style scoped>
.hero-page {
    background: var(--bg);
    padding: 18px;
    font-family: 'Inter', ui-sans-serif, system-ui, sans-serif;
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
    background-image: url('/images/car-hero.jpg');
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
    font-family: 'Inter', ui-sans-serif, system-ui, sans-serif;
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
