<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { ref } from 'vue';
import PublicNav from '@/Components/PublicNav.vue';
import PublicFooter from '@/Components/PublicFooter.vue';

defineProps({
    canLogin: { type: Boolean },
    seo: { type: Object, default: () => ({}) },
});

const activeTab = ref(0);

const features = [
    {
        id: 'recepcion',
        badge: 'IA',
        badgeColor: 'bg-tech-orange text-white',
        icon: `<path stroke-linecap="round" stroke-linejoin="round" d="M6.827 6.175A2.31 2.31 0 015.186 7.23c-.38.054-.757.112-1.134.175C2.999 7.58 2.25 8.507 2.25 9.574V18a2.25 2.25 0 002.25 2.25h15A2.25 2.25 0 0021.75 18V9.574c0-1.067-.75-1.994-1.802-2.169a47.865 47.865 0 00-1.134-.175 2.31 2.31 0 01-1.64-1.055l-.822-1.316a2.192 2.192 0 00-1.736-1.039 48.774 48.774 0 00-5.232 0 2.192 2.192 0 00-1.736 1.039l-.821 1.316z" /><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 12.75a4.5 4.5 0 11-9 0 4.5 4.5 0 019 0zM18.75 10.5h.008v.008h-.008V10.5z" />`,
        title: 'Recepción Inteligente',
        subtitle: 'ESCÁNER DE PATENTES',
        description: 'Apunta la cámara a la patente del vehículo y el sistema la reconoce en segundos. Cruza automáticamente con la agenda y muestra el historial del cliente sin escribir nada.',
        tags: ['Reconocimiento OCR', 'Ingreso Manual', 'Historial Automático'],
        image: '/images/screenshots/recepcion.webp',
        accent: 'from-tech-orange/5 to-amber-50/30',
    },
    {
        id: 'ordenes',
        badge: 'KANBAN',
        badgeColor: 'bg-gray-900 text-white',
        icon: `<path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6A2.25 2.25 0 016 3.75h2.25A2.25 2.25 0 0110.5 6v2.25a2.25 2.25 0 01-2.25 2.25H6a2.25 2.25 0 01-2.25-2.25V6zM3.75 15.75A2.25 2.25 0 016 13.5h2.25a2.25 2.25 0 012.25 2.25V18a2.25 2.25 0 01-2.25 2.25H6A2.25 2.25 0 013.75 18v-2.25zM13.5 6a2.25 2.25 0 012.25-2.25H18A2.25 2.25 0 0120.25 6v2.25A2.25 2.25 0 0118 10.5h-2.25a2.25 2.25 0 01-2.25-2.25V6zM13.5 15.75a2.25 2.25 0 012.25-2.25H18a2.25 2.25 0 012.25 2.25V18A2.25 2.25 0 0118 20.25h-2.25A2.25 2.25 0 0113.5 18v-2.25z" />`,
        title: 'Tablero de Órdenes',
        subtitle: 'FLUJO DE TRABAJO',
        description: 'Visualiza todas las órdenes en un tablero Kanban con columnas por estado. Arrastra las tarjetas entre etapas y lleva el control del taller en tiempo real desde cualquier pantalla.',
        tags: ['Recepción', 'Diagnóstico', 'En Reparación', 'Control de Calidad', 'Entregado'],
        image: '/images/screenshots/ordenes.webp',
        accent: 'from-slate-50 to-gray-50/50',
    },
    {
        id: 'agenda',
        badge: 'CALENDARIO',
        badgeColor: 'bg-emerald-100 text-emerald-700',
        icon: `<path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5m-9-6h.008v.008H12v-.008zM12 15h.008v.008H12V15zm0 2.25h.008v.008H12v-.008zM9.75 15h.008v.008H9.75V15zm0 2.25h.008v.008H9.75v-.008zM7.5 15h.008v.008H7.5V15zm0 2.25h.008v.008H7.5v-.008zm6.75-4.5h.008v.008h-.008v-.008zm0 2.25h.008v.008h-.008V15zm0 2.25h.008v.008h-.008v-.008zm2.25-4.5h.008v.008H16.5v-.008zm0 2.25h.008v.008H16.5V15z" />`,
        title: 'Agenda del Taller',
        subtitle: 'AGENDAMIENTO',
        description: 'Calendario interactivo con vista diaria, semanal y mensual. Gestiona citas con confirmación automática y vincula cada reserva con el historial del cliente y su vehículo.',
        tags: ['Vista Mensual', 'Detalle del Día', 'Citas por Patente', 'Calendario Activo'],
        image: '/images/screenshots/dashboard.webp',
        accent: 'from-emerald-50/30 to-green-50/20',
    },
    {
        id: 'cotizaciones',
        badge: 'COTIZACIÓN',
        badgeColor: 'bg-blue-100 text-blue-700',
        icon: `<path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />`,
        title: 'Cotizaciones',
        subtitle: 'PRESUPUESTOS',
        description: 'Genera cotizaciones profesionales en segundos. Agrega servicios, repuestos desde el inventario y envía el presupuesto al cliente por WhatsApp o email con un solo clic.',
        tags: ['Servicios y Repuestos', 'Envío por WhatsApp', 'Conversión a OT', 'Historial de Cotizaciones'],
        image: '/images/screenshots/cotizaciones.webp',
        accent: 'from-blue-50/30 to-indigo-50/20',
    },
    {
        id: 'inventario',
        badge: 'STOCK',
        badgeColor: 'bg-violet-100 text-violet-700',
        icon: `<path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z" />`,
        title: 'Inventario',
        subtitle: 'REPUESTOS E INSUMOS',
        description: 'Gestiona repuestos e insumos del taller con control de stock en tiempo real. Recibe alertas de stock mínimo y mantén la rentabilidad con el margen de cada producto siempre visible.',
        tags: ['SKU y Código', 'Precio de Costo / Venta', 'Alerta Stock Mínimo', 'Búsqueda Rápida'],
        image: '/images/screenshots/inventario.webp',
        accent: 'from-violet-50/30 to-purple-50/20',
    },
    {
        id: 'clientes',
        badge: 'CRM',
        badgeColor: 'bg-rose-100 text-rose-600',
        icon: `<path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z" />`,
        title: 'Directorio de Clientes',
        subtitle: 'GESTIÓN CRM',
        description: 'Base de datos completa con historial de visitas, gasto acumulado y vehículos por cliente. Identifica a tus mejores clientes y activa acciones comerciales con datos reales.',
        tags: ['Historial de Visitas', 'Gasto Acumulado', 'Múltiples Vehículos', 'Notas Comerciales'],
        image: '/images/screenshots/clientes.webp',
        accent: 'from-rose-50/30 to-pink-50/20',
    },
    {
        id: 'reportes',
        badge: 'ANALÍTICA',
        badgeColor: 'bg-amber-100 text-amber-700',
        icon: `<path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z" />`,
        title: 'Reportes del Taller',
        subtitle: 'CENTRO DE MANDO',
        description: 'Panel de control con ventas, cobranza, supervisión de descuentos y análisis CRM desde un solo lugar. Toma decisiones con datos actualizados en tiempo real, no a fin de mes.',
        tags: ['Ventas', 'Supervisión', 'Existencias', 'Cobranza', 'Adquisición'],
        image: '/images/screenshots/reportes.webp',
        accent: 'from-amber-50/30 to-yellow-50/20',
    },
];

</script>

<template>
    <Head>
        <title>{{ seo.title ?? 'Características y Módulos · TallerFlow — Sistema para Talleres' }}</title>
        <meta name="description" :content="seo.description || 'Conoce todas las características de TallerFlow. Recepción inteligente de patentes con IA, tablero Kanban, agenda integrada, inventario, cotizaciones por WhatsApp y reportes.'" />
        <meta name="robots" content="index, follow" />
        <link rel="canonical" :href="seo.canonical_url" v-if="seo.canonical_url" />

        <!-- Preload all screenshot images so they're ready before the user clicks a tab -->
        <link
            v-for="feature in features"
            :key="feature.id"
            rel="preload"
            as="image"
            :href="feature.image"
        />

        <!-- Open Graph -->
        <meta property="og:type" content="website" />
        <meta property="og:site_name" content="TallerFlow" />
        <meta property="og:locale" content="es_CL" />
        <meta property="og:title" :content="seo.title ?? 'Características y Módulos · TallerFlow — Sistema para Talleres'" />
        <meta property="og:description" :content="seo.description || 'Conoce todas las características de TallerFlow. Recepción inteligente de patentes con IA, tablero Kanban, agenda integrada, inventario, cotizaciones por WhatsApp y reportes.'" />
        <meta property="og:url" :content="seo.canonical_url" v-if="seo.canonical_url" />
        <meta property="og:image" :content="seo.og_image" v-if="seo.og_image" />

        <!-- Twitter Card -->
        <meta name="twitter:card" content="summary_large_image" />
        <meta name="twitter:site" content="@tallerflow" />
        <meta name="twitter:title" :content="seo.title ?? 'Características y Módulos · TallerFlow — Sistema para Talleres'" />
        <meta name="twitter:description" :content="seo.description || 'Conoce todas las características de TallerFlow. Recepción inteligente de patentes con IA, tablero Kanban, agenda integrada, inventario, cotizaciones por WhatsApp y reportes.'" />
        <meta name="twitter:image" :content="seo.og_image" v-if="seo.og_image" />

        <!-- JSON-LD Schema -->
        <component
            v-for="(schema, i) in seo.schema"
            :key="i"
            :is="'script'"
            type="application/ld+json"
            v-text="JSON.stringify(schema)"
        />
    </Head>

    <!-- Public Navigation component handles navigation globally -->
    <PublicNav :can-login="canLogin" />

    <div class="min-h-screen bg-slate-50/40 font-sans antialiased flex flex-col justify-between">
        
        <!-- Main page content padded for the floating navbar -->
        <main class="flex-grow pt-28 pb-20">

            <!-- ═══════════════════════════════════════════════════════════
                 HERO — Servicios
            ════════════════════════════════════════════════════════════ -->
            <section class="relative overflow-hidden pt-12 pb-16">
                <!-- Decorative background elements -->
                <div class="absolute inset-0 pointer-events-none" aria-hidden="true">
                    <div class="absolute top-0 left-1/2 -translate-x-1/2 w-[900px] h-[500px] bg-gradient-to-b from-[#FF7A00]/10 to-transparent rounded-full blur-3xl"></div>
                </div>

                <div class="relative max-w-4xl mx-auto px-6 lg:px-8 text-center">
                    <div class="inline-flex items-center gap-2 bg-[#FF7A00]/10 text-[#FF7A00] text-xs font-bold uppercase tracking-widest px-4 py-1.5 rounded-full mb-8">
                        <span class="w-1.5 h-1.5 rounded-full bg-[#FF7A00] animate-pulse"></span>
                        Todo en un solo lugar
                    </div>

                    <h1 class="text-4xl md:text-5xl lg:text-6xl font-black text-gray-900 leading-[1.1] tracking-tight">
                        Cada módulo diseñado<br />
                        <span class="text-[#FF7A00]">para tu taller.</span>
                    </h1>

                    <p class="mt-6 text-lg md:text-xl text-gray-500 leading-relaxed max-w-2xl mx-auto">
                        Desde recibir un vehículo hasta cobrar y analizar resultados — Taller Flow cubre cada etapa de tu operación sin papel ni caos.
                    </p>

                    <!-- Stats block -->
                    <div class="mt-12 grid grid-cols-3 gap-4 max-w-xl mx-auto">
                        <div class="text-center bg-white p-5 rounded-2xl border border-slate-100 shadow-sm hover:shadow-md transition-shadow">
                            <p class="text-3xl font-black text-gray-900">7</p>
                            <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider mt-1">módulos</p>
                        </div>
                        <div class="text-center bg-white p-5 rounded-2xl border border-slate-100 shadow-sm hover:shadow-md transition-shadow">
                            <p class="text-3xl font-black text-gray-900">100%</p>
                            <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider mt-1">en la nube</p>
                        </div>
                        <div class="text-center bg-white p-5 rounded-2xl border border-slate-100 shadow-sm hover:shadow-md transition-shadow">
                            <p class="text-3xl font-black text-gray-900">0</p>
                            <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider mt-1">instalaciones</p>
                        </div>
                    </div>
                </div>
            </section>

            <!-- ═══════════════════════════════════════════════════════════
                 EXPLORADOR DE MÓDULOS (Interactive Tabbed Section)
            ════════════════════════════════════════════════════════════ -->
            <section class="max-w-7xl mx-auto px-6 lg:px-8 mb-24">
                
                <!-- Mobile horizontal sliding tabs -->
                <div class="lg:hidden flex overflow-x-auto gap-2 pb-4 mb-6 scrollbar-none snap-x px-4 -mx-4">
                    <button
                        v-for="(feature, index) in features"
                        :key="feature.id"
                        @click="activeTab = index"
                        class="snap-center shrink-0 px-4 py-3 rounded-xl border font-bold text-xs transition-all duration-200"
                        :class="activeTab === index
                            ? 'bg-slate-900 border-slate-900 text-white shadow-sm'
                            : 'bg-white border-slate-200 text-slate-600'"
                    >
                        {{ feature.title }}
                    </button>
                </div>

                <!-- Desktop / Tablet Grid -->
                <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-stretch">
                    
                    <!-- Left Sidebar (Module Switchers) -->
                    <div class="hidden lg:flex lg:col-span-5 flex-col gap-3 h-full">
                        <button
                            v-for="(feature, index) in features"
                            :key="feature.id"
                            @click="activeTab = index"
                            class="text-left w-full p-4 rounded-2xl border transition-all duration-300 flex items-center gap-4 relative overflow-hidden focus:outline-none group"
                            :class="activeTab === index
                                ? 'bg-white border-[#FF7A00] shadow-[0_10px_30px_rgba(255,181,71,0.12)] translate-x-2'
                                : 'bg-white/40 border-slate-100/60 hover:bg-white hover:border-slate-200 hover:shadow-sm'"
                        >
                            <div v-if="activeTab === index" class="absolute left-0 top-0 bottom-0 w-1 bg-[#FF7A00]"></div>

                            <!-- Icon Container -->
                            <div
                                class="w-12 h-12 rounded-xl flex items-center justify-center transition-all duration-300 shrink-0"
                                :class="activeTab === index ? 'bg-[#FF7A00]/10 text-[#FF7A00]' : 'bg-slate-100 text-slate-400 group-hover:text-slate-600'"
                            >
                                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75" v-html="feature.icon" />
                            </div>

                            <div>
                                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block mb-0.5">
                                    {{ feature.subtitle }}
                                </span>
                                <h3 class="text-base font-bold text-slate-900 leading-none">
                                    {{ feature.title }}
                                </h3>
                            </div>

                            <!-- Arrow indicator -->
                            <div class="ml-auto shrink-0 text-slate-300 transition-transform" :class="{ 'translate-x-1 text-[#FF7A00]': activeTab === index }">
                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5" />
                                </svg>
                            </div>
                        </button>
                    </div>

                    <!-- Right Main Showcase Panel -->
                    <div class="lg:col-span-7 flex flex-col justify-between h-full">
                        
                        <!-- Mockup Browser Frame -->
                        <div class="bg-slate-950 rounded-2xl shadow-[0_30px_60px_rgba(0,0,0,0.15)] border border-slate-800 overflow-hidden flex flex-col">
                            <div class="bg-slate-900 px-4 py-3 flex items-center gap-2 border-b border-slate-800/80">
                                <!-- Red, yellow, green macOS controls -->
                                <div class="flex gap-1.5 shrink-0">
                                    <span class="w-3 h-3 rounded-full bg-[#FF5F56]"></span>
                                    <span class="w-3 h-3 rounded-full bg-[#FFBD2E]"></span>
                                    <span class="w-3 h-3 rounded-full bg-[#27C93F]"></span>
                                </div>
                                <!-- Address Bar -->
                                <div class="bg-slate-950/80 text-[11px] text-slate-400 px-4 py-1 rounded-lg w-full max-w-md mx-auto text-center font-mono truncate select-none border border-slate-800/60">
                                    tallerflow.cl/dashboard/{{ features[activeTab].id }}
                                </div>
                            </div>

                            <!-- Showcase Image — todas pre-renderizadas, sólo se muestra la activa -->
                            <div class="relative bg-slate-950 overflow-hidden aspect-[16/10]">
                                <template v-for="(feature, index) in features" :key="feature.id">
                                    <transition
                                        enter-active-class="transition duration-300 ease-out"
                                        enter-from-class="opacity-0 scale-98"
                                        enter-to-class="opacity-100 scale-100"
                                        leave-active-class="transition duration-200 ease-in"
                                        leave-from-class="opacity-100 scale-100"
                                        leave-to-class="opacity-0 scale-98"
                                        mode="out-in"
                                    >
                                        <div v-show="activeTab === index" class="absolute inset-0">
                                            <img
                                                :src="feature.image"
                                                :alt="`Módulo ${feature.title}`"
                                                class="w-full h-full object-cover object-left-top"
                                                :loading="index === 0 ? 'eager' : 'lazy'"
                                                @error="$event.target.style.display = 'none'; $event.target.nextElementSibling.style.display = 'flex'"
                                            />
                                            <!-- Mockup fallback when image fails -->
                                            <div class="absolute inset-0 hidden items-center justify-center bg-slate-900">
                                                <div class="text-center">
                                                    <div class="w-16 h-16 rounded-2xl bg-[#FF7A00]/10 flex items-center justify-center mx-auto mb-3 border border-[#FF7A00]/20">
                                                        <svg class="w-8 h-8 text-[#FF7A00]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" v-html="feature.icon" />
                                                    </div>
                                                    <p class="text-sm font-bold text-slate-400">{{ feature.title }}</p>
                                                </div>
                                            </div>
                                        </div>
                                    </transition>
                                </template>
                            </div>
                        </div>

                        <!-- Module detailed details -->
                        <div class="bg-white rounded-2xl border border-slate-100 p-8 shadow-sm flex flex-col justify-between flex-grow mt-6">
                            <div>
                                <!-- Badge & Eyebrow -->
                                <div class="flex items-center gap-3 mb-4">
                                    <span :class="['text-[10px] font-black px-2.5 py-1 rounded-full uppercase tracking-widest', features[activeTab].badgeColor]">
                                        {{ features[activeTab].badge }}
                                    </span>
                                    <span class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">
                                        {{ features[activeTab].subtitle }}
                                    </span>
                                </div>

                                <!-- Title -->
                                <h2 class="text-2xl md:text-3xl font-black text-gray-900 tracking-tight mb-4">
                                    {{ features[activeTab].title }}
                                </h2>

                                <!-- Description -->
                                <p class="text-gray-500 leading-relaxed text-sm md:text-base mb-6">
                                    {{ features[activeTab].description }}
                                </p>

                                <!-- Tag pills -->
                                <div class="flex flex-wrap gap-2 mb-8">
                                    <span
                                        v-for="tag in features[activeTab].tags"
                                        :key="tag"
                                        class="text-[11px] font-semibold bg-slate-50 text-slate-600 px-3 py-1.5 rounded-full border border-slate-200/60"
                                    >
                                        {{ tag }}
                                    </span>
                                </div>
                            </div>

                            <!-- Footer card action -->
                            <div class="pt-6 border-t border-slate-100 flex flex-col sm:flex-row items-center justify-between gap-4">
                                <span class="text-xs text-slate-400 font-medium">Incluido en todos los planes · 14 días gratis</span>
                                <Link
                                    :href="route('trial.create')"
                                    class="w-full sm:w-auto inline-flex items-center justify-center gap-2 bg-[#FF7A00] hover:bg-[#CC6200] text-white font-bold text-sm px-6 py-3 rounded-xl shadow-md shadow-[#FF7A00]/20 transition-all active:scale-[0.98]"
                                >
                                    Probar Módulo Gratis
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3" />
                                    </svg>
                                </Link>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- ═══════════════════════════════════════════════════════════
                 INTEGRACIÓN — Todo conectado
            ════════════════════════════════════════════════════════════ -->
            <section class="py-20 bg-slate-900 text-white rounded-[2.5rem] overflow-hidden relative mb-24 max-w-7xl mx-auto px-6 lg:px-8">
                <div class="absolute inset-0 pointer-events-none" aria-hidden="true">
                    <div class="absolute -top-40 -right-40 w-96 h-96 bg-[#FF7A00]/10 rounded-full blur-3xl"></div>
                    <div class="absolute -bottom-40 -left-40 w-96 h-96 bg-blue-500/10 rounded-full blur-3xl"></div>
                </div>

                <div class="relative text-center max-w-3xl mx-auto mb-16">
                    <span class="inline-flex items-center gap-2 bg-[#FF7A00]/20 text-[#FF7A00] text-xs font-bold uppercase tracking-widest px-4 py-1.5 rounded-full mb-6">
                        Flujo de Trabajo Conectado
                    </span>
                    <h2 class="text-3xl md:text-4xl font-black tracking-tight mb-5 text-white">
                        Todos los módulos hablan entre sí.
                    </h2>
                    <p class="text-slate-400 leading-relaxed text-base md:text-lg">
                        Olvídate de la doble digitación. Taller Flow conecta cada etapa de tu operación de forma inteligente, asegurando que los datos fluyan sin fricción.
                    </p>
                </div>

                <!-- Pipeline Grid -->
                <div class="relative grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <div
                        v-for="(step, i) in [
                            { name: 'Recepción', desc: 'El escáner de patentes recupera los datos y el historial del vehículo en 3 segundos.' },
                            { name: 'Orden de Trabajo', desc: 'Crea la orden de trabajo en el tablero Kanban y notifica al mecánico asignado.' },
                            { name: 'Inventario', desc: 'Reserva automáticamente los repuestos e insumos desde tu stock digital.' },
                            { name: 'Cotización', desc: 'Genera el presupuesto en PDF y lo envía al cliente por WhatsApp con un solo clic.' },
                            { name: 'Cobranza', desc: 'Registra los pagos y emite la boleta o factura de forma integrada.' },
                            { name: 'Reportes', desc: 'Actualiza el panel de ventas, CRM e inventario en tiempo real para la toma de decisiones.' }
                        ]"
                        :key="step.name"
                        class="bg-slate-950/60 border border-slate-800/80 p-6 rounded-2xl hover:border-[#FF7A00]/40 transition-colors duration-300 relative group flex flex-col justify-between"
                    >
                        <div>
                            <div class="flex items-center justify-between mb-4">
                                <span class="text-[10px] font-mono text-slate-500 font-bold">PASO 0{{ i + 1 }}</span>
                                <div class="w-8 h-8 rounded-lg bg-slate-900 border border-slate-800 flex items-center justify-center text-[#FF7A00] font-black text-xs">
                                    {{ i + 1 }}
                                </div>
                            </div>
                            <h3 class="text-lg font-bold text-white mb-2 group-hover:text-[#FF7A00] transition-colors">
                                {{ step.name }}
                            </h3>
                            <p class="text-xs text-slate-400 leading-relaxed">
                                {{ step.desc }}
                            </p>
                        </div>
                    </div>
                </div>
            </section>

            <!-- ═══════════════════════════════════════════════════════════
                 CTA Final
            ════════════════════════════════════════════════════════════ -->
            <section class="pb-24 max-w-7xl mx-auto px-6 lg:px-8">
                <div class="bg-gradient-to-br from-slate-950 to-slate-900 rounded-[2.5rem] p-10 md:p-16 text-center relative overflow-hidden shadow-2xl border border-slate-800">
                    
                    <!-- Background decor elements -->
                    <div class="absolute inset-0 pointer-events-none" aria-hidden="true">
                        <div class="absolute -top-20 -right-20 w-80 h-80 bg-[#FF7A00]/15 rounded-full blur-3xl"></div>
                        <div class="absolute -bottom-20 -left-20 w-80 h-80 bg-blue-500/10 rounded-full blur-3xl"></div>
                    </div>

                    <div class="relative max-w-2xl mx-auto">
                        <div class="inline-flex items-center gap-2 bg-[#FF7A00]/20 text-[#FF7A00] text-xs font-bold uppercase tracking-widest px-4 py-1.5 rounded-full mb-8">
                            <span class="w-1.5 h-1.5 rounded-full bg-[#FF7A00] animate-pulse"></span>
                            Prueba gratis por 14 días
                        </div>

                        <h2 class="text-3xl md:text-4xl lg:text-5xl font-black text-white leading-tight tracking-tight mb-5">
                            Tu taller, digitalizado<br />en menos de un día.
                        </h2>

                        <p class="text-slate-400 text-base md:text-lg mb-10 leading-relaxed">
                            Sin tarjetas de crédito. Sin instalaciones ni servidores complejos. Accede y controla tu taller desde cualquier computador o celular.
                        </p>

                        <div class="flex flex-col sm:flex-row items-center justify-center gap-4">
                            <Link
                                :href="route('trial.create')"
                                class="w-full sm:w-auto inline-flex items-center justify-center gap-2 bg-[#FF7A00] hover:bg-[#CC6200] text-white font-bold text-base px-8 py-4 rounded-2xl shadow-lg shadow-[#FF7A00]/30 transition-all active:scale-[0.98]"
                            >
                                Prueba Gratis 14 Días
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3" />
                                </svg>
                            </Link>
                            <a
                                href="https://wa.me/56912345678"
                                target="_blank"
                                class="w-full sm:w-auto inline-flex items-center justify-center gap-2 bg-white/10 border border-white/20 text-white font-semibold text-base px-8 py-4 rounded-2xl hover:bg-white/20 transition-all active:scale-[0.98]"
                            >
                                <svg class="w-5 h-5 text-[#25D366] fill-current" viewBox="0 0 24 24">
                                    <path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946C.06 5.348 5.397.01 12.008.01c3.202.001 6.212 1.249 8.477 3.518 2.266 2.27 3.51 5.278 3.509 8.484-.004 6.657-5.34 11.997-11.953 11.997-2.005-.001-3.973-.502-5.717-1.458L0 24zm6.075-3.522c1.636.971 3.238 1.483 4.88 1.484 5.542 0 10.051-4.475 10.054-9.982.002-2.667-1.033-5.176-2.915-7.06C16.27 3.036 13.784 2 11.996 2 6.471 2 1.961 6.475 1.958 11.983c-.001 1.767.465 3.493 1.349 5.022l-.995 3.637 3.775-.984zm11.022-7.463c-.3-.149-1.772-.875-2.046-.975-.274-.1-.474-.15-.674.15-.2.3-.773.975-.948 1.175-.175.2-.35.225-.65.075-.3-.15-1.266-.467-2.41-1.485-.89-.794-1.49-1.775-1.665-2.075-.175-.3-.019-.463.13-.61.135-.133.3-.35.45-.525.15-.175.2-.3.3-.5.1-.2.05-.375-.025-.525-.075-.15-.675-1.625-.925-2.225-.244-.589-.491-.51-.674-.52-.175-.008-.375-.01-.575-.01-.2 0-.525.075-.8.375-.275.3-1.05 1.025-1.05 2.5s1.075 2.9 1.225 3.1c.15.2 2.11 3.224 5.116 4.525.715.31 1.273.499 1.71.638.717.228 1.37.196 1.885.12.573-.085 1.772-.725 2.022-1.425.25-.7.25-1.3.175-1.425-.075-.125-.275-.2-.575-.35z"/>
                                </svg>
                                Conversar por WhatsApp
                            </a>
                        </div>

                        <p class="mt-6 text-xs text-slate-500 font-medium">
                            Sin tarjeta de crédito · Cancela cuando quieras · Soporte en español
                        </p>
                    </div>
                </div>
            </section>

        </main>

        <!-- ═══════════════════════════════════════════════════════════
             FOOTER — Standard public footer
        ════════════════════════════════════════════════════════════ -->
        <PublicFooter />

    </div>
</template>

<style scoped>
/* Remove default scrollbar styles on webkit browsers for horizontal tabs sliding */
.scrollbar-none::-webkit-scrollbar {
    display: none;
}
.scrollbar-none {
    -ms-overflow-style: none;
    scrollbar-width: none;
}
</style>
