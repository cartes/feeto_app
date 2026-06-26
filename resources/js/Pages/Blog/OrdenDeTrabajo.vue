<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import PublicNav from '@/Components/PublicNav.vue';

defineProps({
    canLogin: { type: Boolean },
    seo: { type: Object, default: () => ({}) },
});

// Interactive state
const activeSection = ref('');
const dailyOrders = ref(5);
const minutesPerOrder = ref(20);

// Calculator computed values
const monthlyHoursSaved = computed(() => {
    const hours = (dailyOrders.value * minutesPerOrder.value * 26) / 60; // 26 working days in a month
    return Math.round(hours);
});

const yearlyDaysSaved = computed(() => {
    const days = (monthlyHoursSaved.value * 12) / 8; // 8-hour workday
    return Math.round(days);
});

const schema = {
    "@context": "https://schema.org",
    "@type": "Article",
    "headline": "Orden de Trabajo para Taller Mecánico: Guía Completa 2025",
    "description": "Aprende qué es una orden de trabajo para taller mecánico, qué debe incluir y cómo digitalizarla. Incluye template gratuito.",
    "image": "https://tallerflow.cl/images/tallerflow-social-share.png",
    "author": {
        "@type": "Organization",
        "name": "TallerFlow",
        "url": "https://tallerflow.cl"
    },
    "publisher": {
        "@type": "Organization",
        "name": "TallerFlow",
        "logo": {
            "@type": "ImageObject",
            "url": "https://tallerflow.cl/images/tallerflow-logo.png"
        }
    },
    "mainEntityOfPage": {
        "@type": "WebPage",
        "@id": "https://tallerflow.cl/orden-de-trabajo-taller-mecanico"
    }
};

const scrollToSection = (id) => {
    activeSection.value = id;
    const element = document.getElementById(id);
    if (element) {
        element.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }
};
</script>

<template>

    <Head>
        <title>Orden de Trabajo para Taller Mecánico: Guía Completa 2025 | TallerFlow</title>
        <meta name="description"
            content="Aprende qué es una orden de trabajo para taller mecánico, qué debe incluir y cómo digitalizarla. Incluye template gratuito." />
        <meta name="robots" content="index, follow" />
        <link rel="canonical" href="https://tallerflow.cl/orden-de-trabajo-taller-mecanico" />

        <!-- Open Graph -->
        <meta property="og:type" content="article" />
        <meta property="og:site_name" content="TallerFlow" />
        <meta property="og:locale" content="es_CL" />
        <meta property="og:title" content="Orden de Trabajo para Taller Mecánico: Guía Completa 2025 | TallerFlow" />
        <meta property="og:description"
            content="Aprende qué es una orden de trabajo para taller mecánico, qué debe incluir y cómo digitalizarla. Incluye template gratuito." />
        <meta property="og:url" content="https://tallerflow.cl/orden-de-trabajo-taller-mecanico" />
        <meta property="og:image" content="https://tallerflow.cl/images/tallerflow-social-share.png" />

        <!-- Twitter Card -->
        <meta name="twitter:card" content="summary_large_image" />
        <meta name="twitter:site" content="@tallerflow" />
        <meta name="twitter:title" content="Orden de Trabajo para Taller Mecánico: Guía Completa 2025 | TallerFlow" />
        <meta name="twitter:description"
            content="Aprende qué es una orden de trabajo para taller mecánico, qué debe incluir y cómo digitalizarla. Incluye template gratuito." />
        <meta name="twitter:image" content="https://tallerflow.cl/images/tallerflow-social-share.png" />

        <!-- JSON-LD Schema (Article) -->
        <component :is="'script'" type="application/ld+json" v-text="JSON.stringify(schema)" />
    </Head>

    <!-- Public Navigation Bar -->
    <PublicNav :can-login="canLogin" />

    <div class="min-h-screen bg-slate-50/50 font-sans antialiased flex flex-col justify-between">

        <main class="flex-grow pt-28 pb-20">
            <!-- Hero Header Segment with modern grid gradient background -->
            <div class="relative overflow-hidden pt-12 pb-16 text-center max-w-7xl mx-auto px-6">
                <!-- Background grid design pattern -->
                <div class="absolute inset-0 pointer-events-none" aria-hidden="true">
                    <div
                        class="absolute top-0 left-1/2 -translate-x-1/2 w-[800px] h-[400px] bg-gradient-to-b from-[#F9A826]/10 to-transparent rounded-full blur-3xl">
                    </div>
                </div>

                <div class="relative max-w-4xl mx-auto">
                    <span
                        class="inline-flex px-2 px-5 items-center gap-2 bg-[#F9A826]/10 text-[#CC6200] text-xs sm:text-sm font-black uppercase tracking-widest px-4.5 py-2 rounded-full mb-6 border border-[#F9A826]/20">
                        <span class="w-1.5 h-1.5 rounded-full bg-[#F9A826] animate-ping"></span>
                        Guía de Gestión Operativa
                    </span>
                    <h1
                        class="text-4xl sm:text-5xl md:text-6xl font-black text-gray-900 tracking-tight leading-[1.1] mb-6">
                        La Orden de Trabajo en el<br />
                        <span class="text-[#F9A826] bg-clip-text">Taller Mecánico</span>
                    </h1>
                    <p class="text-gray-500 text-lg sm:text-xl md:text-2xl max-w-3xl mx-auto leading-relaxed">
                        Aprende qué es una OT, cómo redactarla correctamente para protegerte legalmente y de qué manera
                        digitalizarla para erradicar el caos del papel.
                    </p>
                </div>
            </div>

            <!-- Content Area - Side-by-side premium layout -->
            <div class="max-w-7xl mx-auto px-6 lg:px-8">
                <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">

                    <!-- Left Column: The Main Guide Content (col-span-8) -->
                    <article class="lg:col-span-8 space-y-12">

                        <!-- Section 1 -->
                        <section id="que-es"
                            class="bg-white rounded-3xl border border-slate-100/80 p-8 sm:p-10 shadow-sm relative group scroll-mt-24 hover:shadow-md transition-shadow">
                            <div
                                class="absolute top-0 left-8 -translate-y-1/2 bg-[#F9A826] text-white font-black text-xs sm:text-sm px-5 py-1.5 rounded-full uppercase tracking-wider">
                                Concepto
                            </div>
                            <h2
                                class="text-2xl sm:text-3xl font-extrabold text-gray-900 tracking-tight mb-6 flex items-center gap-3 leading-tight">
                                1. ¿Qué es una orden de trabajo en un taller mecánico?
                            </h2>
                            <p class="text-gray-600 leading-relaxed mb-6 text-base sm:text-lg">
                                La <strong>Orden de Trabajo (OT)</strong> es el documento oficial y vinculante que
                                formaliza el ingreso de un vehículo a tu taller técnico. No se trata simplemente de un
                                "papel para anotar fallas"; representa el <strong>contrato de servicio inicial</strong>
                                entre el cliente y el taller automotriz.
                            </p>
                            <p class="text-gray-600 leading-relaxed text-base sm:text-lg">
                                Al momento en que un cliente te entrega las llaves de su vehículo y firma la orden de
                                trabajo, se establece un marco legal claro: el cliente te autoriza a inspeccionar,
                                diagnosticar y realizar pruebas en ruta en su auto, comprometiéndose a entregar
                                información verídica y a evaluar el presupuesto correspondiente.
                            </p>

                            <!-- Callout note -->
                            <div class="mt-6 border-l-4 border-[#F9A826] bg-slate-50 p-4.5 rounded-r-2xl">
                                <p
                                    class="text-xs sm:text-sm font-semibold text-slate-500 uppercase tracking-wider mb-1">
                                    Nota Legal en Chile</p>
                                <p class="text-sm sm:text-base text-slate-600 leading-relaxed">
                                    Bajo la ley de protección al consumidor en Chile (SERNAC), la orden de trabajo sirve
                                    como documento oficial que detalla el estado de entrada del vehículo y resguarda al
                                    taller ante cualquier reclamo posterior por daños no atribuibles al servicio.
                                </p>
                            </div>
                        </section>

                        <!-- Section 2 -->
                        <section id="para-que-serve"
                            class="bg-white rounded-3xl border border-slate-100/80 p-8 sm:p-10 shadow-sm scroll-mt-24 hover:shadow-md transition-shadow">
                            <h2
                                class="text-2xl sm:text-3xl font-extrabold text-gray-900 tracking-tight mb-6 flex items-center gap-3 leading-tight">
                                2. ¿Para qué sirve? (Beneficios concretos)
                            </h2>
                            <p class="text-gray-600 leading-relaxed mb-8 text-base sm:text-lg">
                                Para los dueños de talleres mecánicos, la orden de trabajo es la principal herramienta
                                de organización y protección. Entre sus ventajas directas destacan:
                            </p>

                            <!-- Premium 2x2 Grid of benefits -->
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div
                                    class="bg-slate-50/50 p-6 rounded-2xl border border-slate-100 hover:border-[#F9A826]/30 transition group">
                                    <div
                                        class="w-10 h-10 rounded-xl bg-orange-100 text-[#F9A826] flex items-center justify-center mb-4 transition-transform group-hover:scale-105 duration-200">
                                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                            stroke-width="2.5">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                    </div>
                                    <h3 class="font-extrabold text-gray-900 text-lg mb-1.5">Resguardo Legal Completo
                                    </h3>
                                    <p class="text-sm text-gray-500 leading-relaxed">
                                        Te protege contra reclamos injustos. Al registrar el estado de pintura,
                                        abolladuras previas y nivel de combustible en el ingreso, evitas disputas del
                                        tipo <i>"mi auto no venía rayado"</i>.
                                    </p>
                                </div>

                                <div
                                    class="bg-slate-50/50 p-6 rounded-2xl border border-slate-100 hover:border-[#F9A826]/30 transition group">
                                    <div
                                        class="w-10 h-10 rounded-xl bg-orange-100 text-[#F9A826] flex items-center justify-center mb-4 transition-transform group-hover:scale-105 duration-200">
                                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                            stroke-width="2.5">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z" />
                                        </svg>
                                    </div>
                                    <h3 class="font-extrabold text-gray-900 text-lg mb-1.5">Transparencia con el Cliente
                                    </h3>
                                    <p class="text-sm text-gray-500 leading-relaxed">
                                        Al detallar los síntomas percibidos y los trabajos a realizar, disminuyen los
                                        malentendidos de precio y se fortalece la confianza de tus clientes.
                                    </p>
                                </div>

                                <div
                                    class="bg-slate-50/50 p-6 rounded-2xl border border-slate-100 hover:border-[#F9A826]/30 transition group">
                                    <div
                                        class="w-10 h-10 rounded-xl bg-orange-100 text-[#F9A826] flex items-center justify-center mb-4 transition-transform group-hover:scale-105 duration-200">
                                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                            stroke-width="2.5">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="M11.42 15.17L17.25 21A2.67 2.67 0 0021 17.25l-5.83-5.83m-5.83 5.83a2.67 2.67 0 11-3.75-3.75l5.83-5.83m0 0l-5.83-5.83A2.67 2.67 0 0110.25 3l5.83 5.83m0 0L21 11.25" />
                                        </svg>
                                    </div>
                                    <h3 class="font-extrabold text-gray-900 text-lg mb-1.5">Orden en el Taller</h3>
                                    <p class="text-sm text-gray-500 leading-relaxed">
                                        El mecánico asignado recibe en su pantalla o ficha los síntomas y requerimientos
                                        exactos de diagnóstico, previniendo errores de comunicación internos.
                                    </p>
                                </div>

                                <div
                                    class="bg-slate-50/50 p-6 rounded-2xl border border-slate-100 hover:border-[#F9A826]/30 transition group">
                                    <div
                                        class="w-10 h-10 rounded-xl bg-orange-100 text-[#F9A826] flex items-center justify-center mb-4 transition-transform group-hover:scale-105 duration-200">
                                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                            stroke-width="2.5">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="M12 6v12m-3-2.818l.879-.659c1.546-1.16 3.093-1.16 4.64 0l.879.66M9.75 9.638c1.546-1.16 3.093-1.16 4.64 0l.879.658" />
                                        </svg>
                                    </div>
                                    <h3 class="font-extrabold text-gray-900 text-lg mb-1.5">Control de Rentabilidad</h3>
                                    <p class="text-sm text-gray-500 leading-relaxed">
                                        Cruza en una sola ficha digital los repuestos comprados, los insumos asignados y
                                        las horas de mano de obra del técnico para verificar el margen real.
                                    </p>
                                </div>
                            </div>
                        </section>

                        <!-- Section 3 -->
                        <section id="que-debe-incluir"
                            class="bg-white rounded-3xl border border-slate-100/80 p-8 sm:p-10 shadow-sm scroll-mt-24 hover:shadow-md transition-shadow">
                            <h2
                                class="text-2xl sm:text-3xl font-extrabold text-gray-900 tracking-tight mb-6 flex items-center gap-3 leading-tight">
                                3. ¿Qué debe incluir una OT? (Campos indispensables)
                            </h2>
                            <p class="text-gray-600 leading-relaxed mb-6 text-base sm:text-lg">
                                Para que una orden de trabajo tenga validez tanto técnica como legal, es fundamental
                                estructurarla en base a los siguientes bloques de datos:
                            </p>

                            <!-- Visual step list of inputs -->
                            <div class="space-y-4">
                                <div v-for="(item, idx) in [
                                    { title: 'Datos del Taller', desc: 'Nombre comercial, RUT de la empresa, dirección fiscal/física y canales de contacto oficiales.' },
                                    { title: 'Datos del Cliente', desc: 'Nombre completo, RUT (clave para la factura), teléfono celular y correo electrónico.' },
                                    { title: 'Datos del Vehículo', desc: 'Patente chilena (historial único), marca, modelo, año, kilometraje de entrada, nivel de combustible y fotos de daños previos.' },
                                    { title: 'Síntomas y Requerimientos', desc: 'Lo que reporta el cliente en sus palabras (ej: ruido al frenar, mantención de 50.000 km).' },
                                    { title: 'Presupuesto Inicial y Plazos', desc: 'Valor estimado de mano de obra y repuestos, aclarando si el desarme o diagnóstico tiene costo.' },
                                    { title: 'Cláusula de Autorización y Firmas', desc: 'Declaración donde el cliente autoriza las pruebas de ruta en calle pública y firma la OT.' }
                                ]" :key="idx" class="flex gap-4 p-4 rounded-xl border border-slate-100 bg-slate-50/30">
                                    <div
                                        class="w-10 h-10 rounded-full bg-slate-900 text-white flex items-center justify-center font-bold text-sm shrink-0 mt-0.5">
                                        {{ idx + 1 }}
                                    </div>
                                    <div>
                                        <h4 class="font-extrabold text-gray-900 text-base sm:text-lg mb-1">{{ item.title
                                        }}</h4>
                                        <p class="text-sm text-gray-500 leading-relaxed">{{ item.desc }}</p>
                                    </div>
                                </div>
                            </div>
                        </section>

                        <!-- Section 4 (VS Paper vs Digital block as requested) -->
                        <section id="errores-comunes"
                            class="bg-white rounded-3xl border border-slate-100/80 p-8 sm:p-10 shadow-sm scroll-mt-24 hover:shadow-md transition-shadow">
                            <h2
                                class="text-2xl sm:text-3xl font-extrabold text-gray-900 tracking-tight mb-6 flex items-center gap-3 leading-tight">
                                4. Errores comunes al gestionar OTs en papel
                            </h2>
                            <p class="text-gray-600 leading-relaxed mb-6 text-base sm:text-lg">
                                Los talonarios físicos tradicionales crean ineficiencias críticas. Compara el impacto de
                                usar papel frente al modelo digital inteligente:
                            </p>

                            <!-- Visual VS layout -->
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 relative">
                                <!-- The Papel Card -->
                                <div class="bg-red-50/30 border border-red-100 p-6 rounded-2xl relative">
                                    <div
                                        class="absolute top-4 right-4 bg-red-100 text-red-700 text-xs font-black uppercase px-2.5 py-0.5 rounded-full">
                                        Papel
                                    </div>
                                    <h3 class="font-black text-red-800 text-lg sm:text-xl mb-4 flex items-center gap-2">
                                        <svg class="w-5 h-5 text-red-500" fill="none" viewBox="0 0 24 24"
                                            stroke="currentColor" stroke-width="2.5">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="M6 18L18 6M6 6l12 12" />
                                        </svg>
                                        El Viejo Modelo
                                    </h3>
                                    <ul class="space-y-3.5 text-sm sm:text-base text-red-900/80">
                                        <li>❌ <strong>Letra de médico:</strong> Malentendidos entre recepcionista,
                                            mecánicos y cobradores por notas escritas a mano y borrosas.</li>
                                        <li>❌ <strong>Pérdida de hojas:</strong> Fichas que se llenan de grasa, se
                                            traspapelan o se pierden, borrando el historial de visitas.</li>
                                        <li>❌ <strong>Sin evidencia real:</strong> Imposibilidad de respaldar fallas
                                            mecánicas con fotos o videos visibles del repuesto dañado.</li>
                                        <li>❌ <strong>Retraso en la aprobación:</strong> Cotizaciones manuales lentas
                                            enviadas por planillas, lo que alarga el tiempo de parada del auto.</li>
                                    </ul>
                                </div>

                                <!-- The Digital Card -->
                                <div class="bg-emerald-50/30 border border-emerald-100 p-6 rounded-2xl relative">
                                    <div
                                        class="absolute top-4 right-4 bg-emerald-100 text-emerald-700 text-xs font-black uppercase px-2.5 py-0.5 rounded-full">
                                        TallerFlow
                                    </div>
                                    <h3
                                        class="font-black text-emerald-800 text-lg sm:text-xl mb-4 flex items-center gap-2">
                                        <svg class="w-5 h-5 text-emerald-500" fill="none" viewBox="0 0 24 24"
                                            stroke="currentColor" stroke-width="2.5">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                        La Era Digital
                                    </h3>
                                    <ul class="space-y-3.5 text-sm sm:text-base text-emerald-900/80">
                                        <li>✅ <strong>Texto legible y claro:</strong> Registro digital ordenado y
                                            accesible para todo el taller desde cualquier pantalla.</li>
                                        <li>✅ <strong>Historial perpetuo:</strong> Datos guardados de forma segura en la
                                            nube para siempre, listos para consultas rápidas.</li>
                                        <li>✅ <strong>Evidencias adjuntas:</strong> Fotos y videos de las fallas o
                                            estado de ingreso subidos directo a la orden digital.</li>
                                        <li>✅ <strong>WhatsApp instantáneo:</strong> Cotizaciones interactivas enviadas
                                            y aprobadas por el cliente en un solo clic por WhatsApp.</li>
                                    </ul>
                                </div>
                            </div>
                        </section>

                        <!-- Section 5: Dynamic template simulator block -->
                        <section id="template-gratuito"
                            class="bg-white rounded-3xl border border-slate-100/80 p-8 sm:p-10 shadow-sm scroll-mt-24 hover:shadow-md transition-shadow">
                            <h2
                                class="text-2xl sm:text-3xl font-extrabold text-gray-900 tracking-tight mb-6 flex items-center gap-3 leading-tight">
                                5. Simulador y Campos de una Orden de Trabajo
                            </h2>
                            <p class="text-gray-600 leading-relaxed mb-6 text-base sm:text-lg">
                                A continuación te mostramos la representación interactiva de los campos clave y su
                                estructura recomendada para tu taller:
                            </p>

                            <!-- Visual simulator of an OT document -->
                            <div
                                class="bg-slate-900 rounded-2xl border border-slate-800 shadow-xl overflow-hidden text-slate-100">
                                <!-- Simulator Top Header -->
                                <div
                                    class="bg-slate-950 px-6 py-4 border-b border-slate-800/80 flex items-center justify-between">
                                    <div class="flex items-center gap-2">
                                        <span
                                            class="w-4.5 h-4.5 rounded-lg bg-[#F9A826] flex items-center justify-center text-[11px] text-gray-900 font-black">OT</span>
                                        <span class="text-sm font-bold font-mono tracking-wider text-slate-300">ORDEN DE
                                            TRABAJO AUTOMOTRIZ</span>
                                    </div>
                                    <span
                                        class="text-xs font-mono text-slate-500 bg-slate-900 px-3 py-1 rounded-md border border-slate-800">ESTADO:
                                        INGRESO</span>
                                </div>

                                <div class="p-6 space-y-6 text-sm sm:text-base">
                                    <!-- Simulator Client/Vehic Grid -->
                                    <div class="grid grid-cols-2 gap-4 border-b border-slate-800 pb-4">
                                        <div>
                                            <p class="text-xs text-slate-500 uppercase font-black tracking-wider mb-1">
                                                CLIENTE</p>
                                            <p class="font-bold text-slate-200 text-sm sm:text-base">Carlos Muñoz R.</p>
                                            <p class="text-xs sm:text-sm text-slate-400">RUT: 15.420.301-K ·
                                                +56987654321</p>
                                        </div>
                                        <div>
                                            <p class="text-xs text-slate-500 uppercase font-black tracking-wider mb-1">
                                                VEHÍCULO</p>
                                            <p class="font-bold text-slate-200 text-sm sm:text-base">Toyota RAV4 2.0
                                                (2019)</p>
                                            <p class="text-xs sm:text-sm text-[#F9A826] font-mono">PATENTE: ABCD-12 ·
                                                84.510 km</p>
                                        </div>
                                    </div>

                                    <!-- Diagnosis details -->
                                    <div>
                                        <p class="text-xs text-slate-500 uppercase font-black tracking-wider mb-2">
                                            SÍNTOMAS REPORTADOS POR EL CLIENTE</p>
                                        <div
                                            class="bg-slate-950/80 p-4 rounded-lg border border-slate-800 text-xs sm:text-sm font-mono text-slate-300">
                                            - Pedal de freno esponjoso al presionar a fondo.<br />
                                            - Leve chirrido metálico en rueda delantera izquierda al frenar.
                                        </div>
                                    </div>

                                    <!-- Visual status inventory check -->
                                    <div class="grid grid-cols-3 gap-3">
                                        <div
                                            class="bg-slate-950/80 p-3 rounded-lg border border-slate-800/80 text-center">
                                            <p class="text-xs text-slate-500 uppercase font-black">Combustible</p>
                                            <p class="font-bold text-xs sm:text-sm text-slate-300 mt-1">1/2 Estanque</p>
                                        </div>
                                        <div
                                            class="bg-slate-950/80 p-3 rounded-lg border border-slate-800/80 text-center">
                                            <p class="text-xs text-slate-500 uppercase font-black">Rueda de repuesto</p>
                                            <p class="font-bold text-xs sm:text-sm text-emerald-500 mt-1">SÍ</p>
                                        </div>
                                        <div
                                            class="bg-slate-950/80 p-3 rounded-lg border border-slate-800/80 text-center">
                                            <p class="text-xs text-slate-500 uppercase font-black">Daños superficiales
                                            </p>
                                            <p class="font-bold text-xs sm:text-sm text-amber-500 mt-1">Rayón
                                                guardabarros izq.</p>
                                        </div>
                                    </div>

                                    <!-- Agreement clause and sign simulator -->
                                    <div class="pt-4 border-t border-slate-800 space-y-4">
                                        <p class="text-xs text-slate-500 leading-relaxed">
                                            * Autorizo al taller a realizar la inspección técnica del vehículo, desarme
                                            de componentes necesarios y pruebas de conducción en vía pública asociadas a
                                            este diagnóstico.
                                        </p>
                                        <div class="flex items-center justify-between gap-4">
                                            <div>
                                                <div
                                                    class="border-b border-slate-800 w-32 h-10 flex items-center justify-center text-slate-600 italic select-none text-xs sm:text-sm">
                                                    Firma Recibido</div>
                                                <p class="text-[10px] text-slate-500 text-center mt-1">Roberto Silva
                                                    (Taller)</p>
                                            </div>
                                            <div>
                                                <div
                                                    class="border-b border-slate-800 w-32 h-10 flex items-center justify-center text-[#F9A826] font-mono italic select-none text-sm">
                                                    Carlos M. R.</div>
                                                <p class="text-[10px] text-slate-500 text-center mt-1">Carlos Muñoz
                                                    (Cliente)</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </section>

                        <!-- Section 6: Digital workflow roadmap timeline -->
                        <section id="como-digitalizar"
                            class="bg-white rounded-3xl border border-slate-100/80 p-8 sm:p-10 shadow-sm scroll-mt-24 hover:shadow-md transition-shadow">
                            <h2
                                class="text-2xl sm:text-3xl font-extrabold text-gray-900 tracking-tight mb-6 flex items-center gap-3 leading-tight">
                                6. Cómo digitalizar las OTs de tu taller con TallerFlow
                            </h2>
                            <p class="text-gray-600 leading-relaxed mb-8 text-base sm:text-lg">
                                Olvídate de transcribir planillas y acumular carpetas de cartón. Con
                                <strong>TallerFlow</strong>, el flujo de trabajo operativa de tu taller queda totalmente
                                integrado y automatizado en la nube:
                            </p>

                            <!-- Digitalization Timeline -->
                            <div class="relative pl-8 border-l border-slate-200 space-y-10 py-2">

                                <!-- Step 1 -->
                                <div class="relative group">
                                    <!-- Bullet -->
                                    <div
                                        class="absolute -left-[48px] top-0.5 w-8 h-8 rounded-full bg-slate-900 border-4 border-white shadow-sm flex items-center justify-center text-xs font-black text-[#F9A826]">
                                        1
                                    </div>
                                    <h3
                                        class="font-extrabold text-gray-900 text-lg sm:text-xl mb-1.5 group-hover:text-[#F9A826] transition-colors duration-200">
                                        Recepción Automática OCR</h3>
                                    <p class="text-sm sm:text-base text-gray-500 leading-relaxed max-w-xl">
                                        Escanea la patente con tu smartphone. TallerFlow reconoce la patente, abre la
                                        ficha técnica del vehículo y recupera las visitas pasadas, kilometraje y notas
                                        previas de manera instantánea.
                                    </p>
                                </div>

                                <!-- Step 2 -->
                                <div class="relative group">
                                    <!-- Bullet -->
                                    <div
                                        class="absolute -left-[48px] top-0.5 w-8 h-8 rounded-full bg-slate-900 border-4 border-white shadow-sm flex items-center justify-center text-xs font-black text-[#F9A826]">
                                        2
                                    </div>
                                    <h3
                                        class="font-extrabold text-gray-900 text-lg sm:text-xl mb-1.5 group-hover:text-[#F9A826] transition-colors duration-200">
                                        Tablero Kanban de Control en Tiempo Real</h3>
                                    <p class="text-sm sm:text-base text-gray-500 leading-relaxed max-w-xl">
                                        Arrastra las órdenes en un panel visual e interactivo entre columnas de estado
                                        (Recepción, Diagnóstico, En Reparación, Control de Calidad, Entregado) para
                                        controlar el cuello de botella del taller.
                                    </p>
                                </div>

                                <!-- Step 3 -->
                                <div class="relative group">
                                    <!-- Bullet -->
                                    <div
                                        class="absolute -left-[48px] top-0.5 w-8 h-8 rounded-full bg-slate-900 border-4 border-white shadow-sm flex items-center justify-center text-xs font-black text-[#F9A826]">
                                        3
                                    </div>
                                    <h3
                                        class="font-extrabold text-gray-900 text-lg sm:text-xl mb-1.5 group-hover:text-[#F9A826] transition-colors duration-200">
                                        Fotos de Evidencia por Celular</h3>
                                    <p class="text-sm sm:text-base text-gray-500 leading-relaxed max-w-xl">
                                        Los técnicos toman fotos del repuesto dañado directamente desde sus teléfonos y
                                        lo vinculan a la OT digital. Transparencia indiscutible para el cliente.
                                    </p>
                                </div>

                                <!-- Step 4 -->
                                <div class="relative group">
                                    <!-- Bullet -->
                                    <div
                                        class="absolute -left-[48px] top-0.5 w-8 h-8 rounded-full bg-slate-900 border-4 border-white shadow-sm flex items-center justify-center text-xs font-black text-[#F9A826]">
                                        4
                                    </div>
                                    <h3
                                        class="font-extrabold text-gray-900 text-lg sm:text-xl mb-1.5 group-hover:text-[#F9A826] transition-colors duration-200">
                                        Aprobación por WhatsApp</h3>
                                    <p class="text-sm sm:text-base text-gray-500 leading-relaxed max-w-xl">
                                        Envía el presupuesto detallado al cliente mediante un enlace seguro por
                                        WhatsApp. El cliente puede aprobar los repuestos y mano de obra en línea desde
                                        su celular, quedando todo registrado legalmente.
                                    </p>
                                </div>

                            </div>
                        </section>

                        <!-- Section 7 / Final CTA Block (exactly matching requested welcome.vue styling) -->
                        <div
                            class="bg-gray-900 rounded-3xl p-10 md:p-14 flex flex-col md:flex-row items-center justify-between gap-8 text-center md:text-left relative overflow-hidden shadow-xl">
                            <div class="absolute inset-0 pointer-events-none" aria-hidden="true">
                                <div class="absolute -top-10 -right-10 w-40 h-40 bg-[#F9A826]/10 rounded-full blur-2xl">
                                </div>
                            </div>
                            <div class="relative z-10">
                                <h3 class="text-3xl md:text-4xl font-black text-white leading-tight">
                                    ¿Listo para digitalizar tu taller?
                                </h3>
                                <p class="text-gray-300 mt-3 text-base">
                                    14 días gratis. Sin tarjeta de crédito. Cancela cuando quieras.
                                </p>
                            </div>
                            <Link :href="route('trial.create')"
                                class="flex-shrink-0 inline-flex items-center gap-2 bg-[#F9A826] hover:bg-[#CC6200] text-gray-900 font-bold px-10 py-5 rounded-2xl shadow-lg shadow-amber-500/20 transition-all active:scale-[0.98] text-base whitespace-nowrap">
                                Prueba TallerFlow gratis 14 días
                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                    stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3" />
                                </svg>
                            </Link>
                        </div>

                    </article>

                    <!-- Right Column: Table of Contents & Interactive Calculator Sidebar (col-span-4) -->
                    <aside class="lg:col-span-4 lg:sticky lg:top-28 space-y-6">

                        <!-- Table of Contents sticky panel -->
                        <div class="bg-white rounded-3xl border border-slate-100/80 p-6 shadow-sm">
                            <h3
                                class="text-sm sm:text-base font-black text-slate-800 uppercase tracking-wider mb-4 border-b border-slate-100 pb-3 flex items-center gap-2">
                                <svg class="w-5 h-5 text-[#F9A826]" fill="none" viewBox="0 0 24 24"
                                    stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h7" />
                                </svg>
                                Contenido de la Guía
                            </h3>
                            <nav class="space-y-2.5">
                                <button v-for="(sec, k) in [
                                    { id: 'que-es', text: '1. ¿Qué es una OT?' },
                                    { id: 'para-que-serve', text: '2. ¿Para qué sirve?' },
                                    { id: 'que-debe-incluir', text: '3. Campos indispensables' },
                                    { id: 'errores-comunes', text: '4. Errores del papel' },
                                    { id: 'template-gratuito', text: '5. Simulador de OT' },
                                    { id: 'como-digitalizar', text: '6. Digitalización' }
                                ]" :key="k" @click="scrollToSection(sec.id)"
                                    class="w-full text-left text-sm font-bold transition-all px-3 py-2.5 rounded-xl border flex items-center justify-between"
                                    :class="activeSection === sec.id
                                        ? 'bg-orange-50 border-orange-200 text-[#CC6200] translate-x-1.5'
                                        : 'bg-white border-transparent text-slate-600 hover:text-slate-900 hover:bg-slate-50'">
                                    {{ sec.text }}
                                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                        stroke-width="2.5">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M8.25 4.5l7.5 7.5-7.5 7.5" />
                                    </svg>
                                </button>
                            </nav>
                        </div>

                        <!-- Interactive Time Saving Calculator -->
                        <div
                            class="bg-slate-900 text-white rounded-3xl border border-slate-800 p-6 shadow-md relative overflow-hidden">
                            <div class="absolute top-0 right-0 w-32 h-32 bg-[#F9A826]/5 rounded-full blur-2xl"></div>

                            <h3
                                class="text-sm sm:text-base font-black text-white uppercase tracking-wider mb-4 border-b border-slate-800 pb-3 flex items-center gap-2">
                                <svg class="w-5 h-5 text-[#F9A826]" fill="none" viewBox="0 0 24 24"
                                    stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                Calculadora de Ahorro
                            </h3>

                            <p class="text-slate-400 text-sm leading-relaxed mb-6">
                                ¿Cuánto tiempo pierdes al mes llenando, ordenando y buscando órdenes de trabajo en
                                papel?
                            </p>

                            <!-- Slider Input 1 -->
                            <div class="space-y-2.5 mb-5">
                                <div class="flex items-center justify-between text-sm font-bold text-slate-300">
                                    <span>Órdenes de trabajo al día:</span>
                                    <span class="text-[#F9A826] font-mono text-base">{{ dailyOrders }} OTs</span>
                                </div>
                                <input type="range" min="2" max="30" v-model.number="dailyOrders"
                                    class="w-full h-1 bg-slate-800 rounded-lg appearance-none cursor-pointer accent-[#F9A826]" />
                            </div>

                            <!-- Slider Input 2 -->
                            <div class="space-y-2.5 mb-6">
                                <div class="flex items-center justify-between text-sm font-bold text-slate-300">
                                    <span>Minutos perdidos por OT:</span>
                                    <span class="text-[#F9A826] font-mono text-base">{{ minutesPerOrder }} min</span>
                                </div>
                                <input type="range" min="5" max="60" v-model.number="minutesPerOrder"
                                    class="w-full h-1 bg-slate-800 rounded-lg appearance-none cursor-pointer accent-[#F9A826]" />
                                <p class="text-xs text-slate-500 leading-normal">
                                    (Tiempo llenando datos, buscando fichas y cotizando repuestos a mano).
                                </p>
                            </div>

                            <!-- Results Section -->
                            <div
                                class="bg-slate-950/80 rounded-2xl p-5 border border-slate-800 space-y-4 mb-6 text-center">
                                <div>
                                    <p class="text-xs text-slate-500 uppercase tracking-widest font-black">TIEMPO
                                        PERDIDO AL MES</p>
                                    <p class="text-3xl font-black text-white mt-1">
                                        {{ monthlyHoursSaved }} <span
                                            class="text-sm text-[#F9A826] font-extrabold">Horas</span>
                                    </p>
                                </div>
                                <div class="border-t border-slate-900 pt-3">
                                    <p class="text-xs text-slate-500 uppercase tracking-widest font-black">PRODUCTIVIDAD
                                        PERDIDA AL AÑO</p>
                                    <p class="text-xl sm:text-2xl font-black text-[#F9A826] mt-1">
                                        {{ yearlyDaysSaved }} <span class="text-sm text-white font-extrabold">Días de
                                            Trabajo</span>
                                    </p>
                                </div>
                            </div>

                            <Link :href="route('trial.create')"
                                class="w-full inline-flex items-center justify-center gap-1.5 bg-[#F9A826] hover:bg-[#CC6200] text-gray-900 font-bold py-4 px-5 rounded-xl shadow-md transition active:scale-[0.98] text-sm">
                                ¡Recupera este tiempo con TallerFlow!
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                    stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3" />
                                </svg>
                            </Link>
                        </div>
                    </aside>

                </div>
            </div>
        </main>

        <!-- Public Footer segment -->
        <footer class="bg-white border-t border-slate-100 py-10">
            <div class="max-w-5xl mx-auto px-6 lg:px-8 flex flex-col md:flex-row items-center justify-between gap-6">
                <div class="flex items-center gap-2">
                    <span class="w-7 h-7 rounded-lg bg-[#F9A826] flex items-center justify-center">
                        <svg class="w-4 h-4 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                            stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z" />
                        </svg>
                    </span>
                    <span class="text-base font-black text-slate-900">Taller <span
                            class="text-[#F9A826]">Flow</span></span>
                </div>

                <nav class="flex items-center gap-6 flex-wrap justify-center">
                    <Link :href="route('home')"
                        class="text-xs text-slate-400 hover:text-slate-600 transition-colors font-medium">Inicio</Link>
                    <Link :href="route('pricing')"
                        class="text-xs text-slate-400 hover:text-slate-600 transition-colors font-medium">Precios</Link>
                    <Link :href="route('servicios')"
                        class="text-xs text-slate-400 hover:text-slate-600 transition-colors font-medium">Servicios
                    </Link>
                    <Link :href="route('trial.create')"
                        class="text-xs text-slate-400 hover:text-slate-600 transition-colors font-medium">Prueba gratis
                    </Link>
                    <Link :href="route('talleres.index')"
                        class="text-xs text-slate-400 hover:text-slate-600 transition-colors font-medium">Talleres
                    </Link>
                    <a href="https://www.instagram.com/tallerflow_app/"
                       target="_blank"
                       rel="noopener noreferrer"
                       class="text-xs text-slate-400 hover:text-slate-600 transition-colors font-medium flex items-center gap-1.5">
                        <svg class="w-4 h-4 fill-current shrink-0" viewBox="0 0 24 24">
                            <path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zM12 0C8.741 0 8.333.014 7.053.072 2.695.272.273 2.69.073 7.051.014 8.333 0 8.741 0 12c0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98C15.668.014 15.259 0 12 0zm0 5.838a6.162 6.162 0 100 12.324 6.162 6.162 0 000-12.324zM12 16a4 4 0 110-8 4 4 0 010 8zm6.406-11.845a1.44 1.44 0 100 2.881 1.44 1.44 0 000-2.881z"/>
                        </svg>
                        <span>Instagram</span>
                    </a>
                </nav>

                <p class="text-xs text-slate-400 font-medium">
                    &copy; {{ new Date().getFullYear() }} Taller Flow. Todos los derechos reservados.
                </p>
            </div>
        </footer>

    </div>
</template>
