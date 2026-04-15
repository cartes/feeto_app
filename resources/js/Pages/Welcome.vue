<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { ref } from 'vue';
import LoginModal from '@/Components/LoginModal.vue';

defineProps({
    canLogin: { type: Boolean },
    canRegister: { type: Boolean },
});

const showLoginModal = ref(false);
</script>


<template>
    <Head title="Feeto · Software para Talleres Mecánicos" />

    <div class="min-h-screen bg-white font-sans antialiased">

        <!-- ═══════════════════════════════════════════════════════════════
             NAVBAR
             ═══════════════════════════════════════════════════════════════ -->
        <header class="sticky top-0 z-50 bg-white/80 backdrop-blur-md border-b border-gray-100">
            <div class="max-w-7xl mx-auto px-6 lg:px-8 h-16 flex items-center justify-between gap-8">

                <!-- Logo -->
                <a href="#" class="flex items-center gap-2 flex-shrink-0">
                    <span class="w-8 h-8 rounded-xl bg-tech-orange flex items-center justify-center shadow-sm">
                        <svg class="w-5 h-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M11.42 15.17L17.25 21A2.652 2.652 0 0021 17.25l-5.877-5.877M11.42 15.17l2.496-3.03c.317-.384.74-.626 1.208-.766M11.42 15.17l-4.655 5.653a2.548 2.548 0 11-3.586-3.586l6.837-5.63m5.108-.233c.55-.164 1.163-.188 1.743-.14a4.5 4.5 0 004.486-6.336l-3.276 3.277a3.004 3.004 0 01-2.25-2.25l3.276-3.276a4.5 4.5 0 00-6.336 4.486c.091 1.076-.071 2.264-.904 2.95l-.102.085m-1.745 1.437L5.909 7.5H4.5L2.25 3.75l1.5-1.5L7.5 4.5v1.409l4.26 4.26m-1.745 1.437l1.745-1.437m6.615 8.206L15.75 15.75M4.867 19.125h.008v.008h-.008v-.008z" />
                        </svg>
                    </span>
                    <span class="text-xl font-black text-gray-900 tracking-tight">Feeto</span>
                </a>

                <!-- Nav links (hidden on mobile) -->
                <nav class="hidden md:flex items-center gap-8">
                    <a href="#features" class="text-sm font-medium text-gray-500 hover:text-gray-900 transition-colors">Características</a>
                    <a href="#benefits" class="text-sm font-medium text-gray-500 hover:text-gray-900 transition-colors">Beneficios</a>
                    <a href="#pricing" class="text-sm font-medium text-gray-500 hover:text-gray-900 transition-colors">Precios</a>
                </nav>

                <!-- Auth actions -->
                <div class="flex items-center gap-3">
                    <template v-if="$page.props.auth?.user">
                        <Link
                            :href="route('dashboard')"
                            class="text-sm font-semibold text-gray-700 hover:text-gray-900 transition-colors px-4 py-2 rounded-xl hover:bg-gray-50"
                        >
                            Dashboard
                        </Link>
                    </template>
                    <template v-else>
                        <button
                            v-if="canLogin"
                            @click="showLoginModal = true"
                            class="inline-flex items-center gap-2 text-sm font-semibold text-gray-700 bg-white border border-gray-200 hover:border-gray-300 hover:bg-gray-50 transition-all px-4 py-2 rounded-xl shadow-sm"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1" />
                            </svg>
                            Iniciar Sesión
                        </button>
                        <a
                            href="#"
                            class="text-sm font-bold text-white bg-tech-orange hover:bg-[#e8920d] px-4 py-2 rounded-xl shadow-sm shadow-tech-orange/30 transition-all active:scale-[0.98]"
                        >
                            Probar Gratis
                        </a>
                    </template>
                </div>
            </div>
        </header>

        <!-- ═══════════════════════════════════════════════════════════════
             HERO
             ═══════════════════════════════════════════════════════════════ -->
        <section class="relative overflow-hidden bg-white pt-20 pb-10 md:pt-28 md:pb-16">

            <!-- Subtle background decoration -->
            <div class="absolute inset-0 pointer-events-none" aria-hidden="true">
                <div class="absolute top-0 left-1/2 -translate-x-1/2 w-[900px] h-[500px] bg-gradient-to-b from-tech-orange/5 to-transparent rounded-full blur-3xl"></div>
            </div>

            <div class="relative max-w-5xl mx-auto px-6 lg:px-8 text-center">

                <!-- Eyebrow badge -->
                <div class="inline-flex items-center gap-2 bg-tech-orange/10 text-tech-orange text-xs font-bold uppercase tracking-widest px-4 py-1.5 rounded-full mb-8">
                    <span class="w-1.5 h-1.5 rounded-full bg-tech-orange animate-pulse"></span>
                    Software para talleres mecánicos
                </div>

                <!-- Headline -->
                <h1 class="text-4xl md:text-6xl font-black text-gray-900 leading-[1.1] tracking-tight max-w-3xl mx-auto">
                    Simplifica la gestión de tu taller y haz crecer tu negocio
                    <span class="text-tech-orange">más rápido.</span>
                </h1>

                <!-- Subtitle -->
                <p class="mt-6 text-lg md:text-xl text-gray-500 leading-relaxed max-w-2xl mx-auto font-normal">
                    Controla tus órdenes de trabajo, agenda, inventario y clientes desde un único sistema digital diseñado específicamente para talleres mecánicos.
                </p>

                <!-- CTA buttons -->
                <div class="mt-10 flex flex-col sm:flex-row items-center justify-center gap-4">
                    <a
                        href="#"
                        class="w-full sm:w-auto inline-flex items-center justify-center gap-2 bg-tech-orange hover:bg-[#e8920d] text-white font-bold text-base px-8 py-4 rounded-2xl shadow-lg shadow-tech-orange/30 transition-all active:scale-[0.98]"
                    >
                        Prueba Gratis 14 Días
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3" />
                        </svg>
                    </a>
                    <a
                        href="#"
                        class="w-full sm:w-auto inline-flex items-center justify-center gap-2 bg-white border border-gray-200 text-gray-700 font-semibold text-base px-8 py-4 rounded-2xl hover:border-gray-300 hover:bg-gray-50 transition-all active:scale-[0.98] shadow-sm"
                    >
                        <svg class="w-4 h-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 10.5l4.72-4.72a.75.75 0 011.28.53v11.38a.75.75 0 01-1.28.53l-4.72-4.72M4.5 18.75h9a2.25 2.25 0 002.25-2.25v-9a2.25 2.25 0 00-2.25-2.25h-9A2.25 2.25 0 002.25 7.5v9a2.25 2.25 0 002.25 2.25z" />
                        </svg>
                        Agendar Demo
                    </a>
                    <button
                        v-if="canLogin"
                        @click="showLoginModal = true"
                        class="w-full sm:w-auto inline-flex items-center justify-center gap-2 text-gray-500 hover:text-gray-900 font-medium text-base px-8 py-4 transition-colors"
                    >
                        Ya tengo cuenta &rarr;
                    </button>
                </div>

                <!-- Social proof micro-text -->
                <p class="mt-5 text-xs text-gray-400 font-medium">
                    Sin tarjeta de crédito · Cancela cuando quieras · Soporte en español
                </p>

                <!-- ── Kanban Mockup ── -->
                <div class="mt-16 relative">
                    <!-- Glow -->
                    <div class="absolute -inset-4 bg-gradient-to-t from-tech-orange/10 via-transparent to-transparent rounded-3xl blur-2xl pointer-events-none"></div>

                    <div class="relative bg-white rounded-3xl shadow-2xl border border-gray-100 overflow-hidden">
                        <!-- Mockup top bar -->
                        <div class="bg-gray-50 border-b border-gray-100 px-5 py-3 flex items-center gap-2">
                            <span class="w-3 h-3 rounded-full bg-red-300"></span>
                            <span class="w-3 h-3 rounded-full bg-yellow-300"></span>
                            <span class="w-3 h-3 rounded-full bg-green-300"></span>
                            <div class="ml-4 flex-1 bg-white border border-gray-200 rounded-lg h-6 max-w-xs"></div>
                        </div>

                        <!-- Kanban columns -->
                        <div class="p-5 grid grid-cols-3 gap-4 bg-gray-50/50">

                            <!-- Column: Recepción -->
                            <div class="flex flex-col gap-3">
                                <div class="flex items-center justify-between mb-1">
                                    <span class="text-xs font-bold text-gray-600 uppercase tracking-wider">Recepción</span>
                                    <span class="text-xs bg-blue-100 text-blue-700 font-bold px-2 py-0.5 rounded-full">3</span>
                                </div>
                                <!-- Card 1 -->
                                <div class="bg-white rounded-2xl border border-gray-100 p-3 shadow-sm">
                                    <div class="flex items-center justify-between mb-2">
                                        <span class="text-xs font-black text-gray-800 font-mono tracking-widest">GKSD72</span>
                                        <span class="w-2 h-2 rounded-full bg-blue-400"></span>
                                    </div>
                                    <p class="text-xs text-gray-500 font-medium">Toyota Hilux · Rojo</p>
                                    <p class="text-[10px] text-gray-400 mt-1.5 truncate">Mantención preventiva 10.000 km</p>
                                </div>
                                <!-- Card 2 -->
                                <div class="bg-white rounded-2xl border border-gray-100 p-3 shadow-sm">
                                    <div class="flex items-center justify-between mb-2">
                                        <span class="text-xs font-black text-gray-800 font-mono tracking-widest">BBTR41</span>
                                        <span class="w-2 h-2 rounded-full bg-blue-400"></span>
                                    </div>
                                    <p class="text-xs text-gray-500 font-medium">Chevrolet Spark · Blanco</p>
                                    <p class="text-[10px] text-gray-400 mt-1.5 truncate">Revisión de frenos y pastillas</p>
                                </div>
                            </div>

                            <!-- Column: En Proceso -->
                            <div class="flex flex-col gap-3">
                                <div class="flex items-center justify-between mb-1">
                                    <span class="text-xs font-bold text-gray-600 uppercase tracking-wider">En Proceso</span>
                                    <span class="text-xs bg-tech-orange/20 text-[#c97a00] font-bold px-2 py-0.5 rounded-full">2</span>
                                </div>
                                <!-- Card 3 -->
                                <div class="bg-white rounded-2xl border border-tech-orange/20 p-3 shadow-sm">
                                    <div class="flex items-center justify-between mb-2">
                                        <span class="text-xs font-black text-gray-800 font-mono tracking-widest">PLMK98</span>
                                        <span class="w-2 h-2 rounded-full bg-tech-orange"></span>
                                    </div>
                                    <p class="text-xs text-gray-500 font-medium">Nissan Frontier · Gris</p>
                                    <p class="text-[10px] text-gray-400 mt-1.5 truncate">Cambio de correa de distribución</p>
                                    <div class="mt-2 h-1.5 bg-gray-100 rounded-full overflow-hidden">
                                        <div class="h-full w-2/3 bg-tech-orange rounded-full"></div>
                                    </div>
                                </div>
                                <!-- Card 4 -->
                                <div class="bg-white rounded-2xl border border-gray-100 p-3 shadow-sm">
                                    <div class="flex items-center justify-between mb-2">
                                        <span class="text-xs font-black text-gray-800 font-mono tracking-widest">RRTN55</span>
                                        <span class="w-2 h-2 rounded-full bg-tech-orange"></span>
                                    </div>
                                    <p class="text-xs text-gray-500 font-medium">Ford Ranger · Negro</p>
                                    <p class="text-[10px] text-gray-400 mt-1.5 truncate">Diagnóstico electrónico OBD</p>
                                    <div class="mt-2 h-1.5 bg-gray-100 rounded-full overflow-hidden">
                                        <div class="h-full w-1/3 bg-tech-orange rounded-full"></div>
                                    </div>
                                </div>
                            </div>

                            <!-- Column: Listo -->
                            <div class="flex flex-col gap-3">
                                <div class="flex items-center justify-between mb-1">
                                    <span class="text-xs font-bold text-gray-600 uppercase tracking-wider">Listo</span>
                                    <span class="text-xs bg-emerald-100 text-emerald-700 font-bold px-2 py-0.5 rounded-full">2</span>
                                </div>
                                <!-- Card 5 -->
                                <div class="bg-white rounded-2xl border border-emerald-100 p-3 shadow-sm">
                                    <div class="flex items-center justify-between mb-2">
                                        <span class="text-xs font-black text-gray-800 font-mono tracking-widest">CCBT19</span>
                                        <svg class="w-3.5 h-3.5 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                    </div>
                                    <p class="text-xs text-gray-500 font-medium">Suzuki Swift · Azul</p>
                                    <p class="text-[10px] text-gray-400 mt-1.5 truncate">Afinación completa</p>
                                </div>
                                <!-- Card 6 -->
                                <div class="bg-white rounded-2xl border border-emerald-100 p-3 shadow-sm">
                                    <div class="flex items-center justify-between mb-2">
                                        <span class="text-xs font-black text-gray-800 font-mono tracking-widest">DKSL77</span>
                                        <svg class="w-3.5 h-3.5 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                    </div>
                                    <p class="text-xs text-gray-500 font-medium">Honda CR-V · Plata</p>
                                    <p class="text-[10px] text-gray-400 mt-1.5 truncate">Cambio de aceite y filtros</p>
                                </div>
                            </div>

                        </div>
                    </div>
                </div>
                <!-- end mockup -->

            </div>
        </section>

        <!-- ═══════════════════════════════════════════════════════════════
             FEATURES – Bento Grid
             ═══════════════════════════════════════════════════════════════ -->
        <section id="features" class="py-24 bg-gray-50">
            <div class="max-w-6xl mx-auto px-6 lg:px-8">

                <div class="text-center mb-16">
                    <p class="text-xs font-bold uppercase tracking-widest text-tech-orange mb-3">Herramientas</p>
                    <h2 class="text-3xl md:text-4xl font-black text-gray-900 tracking-tight">
                        Todo lo que necesita tu taller, en un solo lugar.
                    </h2>
                    <p class="mt-4 text-gray-500 max-w-xl mx-auto">
                        Diseñado con talleres mecánicos reales para resolver los problemas reales del día a día.
                    </p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">

                    <!-- Feature 1: Kanban -->
                    <div class="bg-white rounded-3xl border border-gray-100 p-8 shadow-sm hover:shadow-md hover:border-tech-orange/20 transition-all group">
                        <div class="w-12 h-12 rounded-2xl bg-tech-orange/10 flex items-center justify-center mb-6 group-hover:bg-tech-orange/20 transition-colors">
                            <svg class="w-6 h-6 text-tech-orange" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 17V7m0 10a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h2a2 2 0 012 2m0 10a2 2 0 002 2h2a2 2 0 002-2M9 7a2 2 0 012-2h2a2 2 0 012 2m0 10V7m0 10a2 2 0 002 2h2a2 2 0 002-2V7a2 2 0 00-2-2h-2a2 2 0 00-2 2" />
                            </svg>
                        </div>
                        <h3 class="text-lg font-bold text-gray-900 mb-2">Tablero Kanban en Vivo</h3>
                        <p class="text-sm text-gray-500 leading-relaxed">
                            Coordina equipos y órdenes en un flujo visual. Mueve trabajos entre etapas con un clic y ten siempre el control del piso del taller.
                        </p>
                        <div class="mt-6 flex flex-wrap gap-2">
                            <span class="text-[11px] font-semibold bg-blue-50 text-blue-600 px-2.5 py-1 rounded-full">Tiempo Real</span>
                            <span class="text-[11px] font-semibold bg-gray-100 text-gray-600 px-2.5 py-1 rounded-full">WebSockets</span>
                        </div>
                    </div>

                    <!-- Feature 2: Inventario -->
                    <div class="bg-white rounded-3xl border border-gray-100 p-8 shadow-sm hover:shadow-md hover:border-tech-orange/20 transition-all group">
                        <div class="w-12 h-12 rounded-2xl bg-tech-orange/10 flex items-center justify-center mb-6 group-hover:bg-tech-orange/20 transition-colors">
                            <svg class="w-6 h-6 text-tech-orange" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z" />
                            </svg>
                        </div>
                        <h3 class="text-lg font-bold text-gray-900 mb-2">Inventario Inteligente</h3>
                        <p class="text-sm text-gray-500 leading-relaxed">
                            Controla tu stock de repuestos y evita compras de urgencia. Alertas automáticas cuando el inventario alcanza niveles mínimos.
                        </p>
                        <div class="mt-6 flex flex-wrap gap-2">
                            <span class="text-[11px] font-semibold bg-emerald-50 text-emerald-600 px-2.5 py-1 rounded-full">Stock Mínimo</span>
                            <span class="text-[11px] font-semibold bg-gray-100 text-gray-600 px-2.5 py-1 rounded-full">Alertas</span>
                        </div>
                    </div>

                    <!-- Feature 3: Recepción IA -->
                    <div class="bg-white rounded-3xl border border-gray-100 p-8 shadow-sm hover:shadow-md hover:border-tech-orange/20 transition-all group relative overflow-hidden">
                        <!-- AI badge -->
                        <span class="absolute top-4 right-4 text-[10px] font-black bg-tech-orange text-white px-2 py-0.5 rounded-full tracking-widest uppercase">IA</span>
                        <div class="w-12 h-12 rounded-2xl bg-tech-orange/10 flex items-center justify-center mb-6 group-hover:bg-tech-orange/20 transition-colors">
                            <svg class="w-6 h-6 text-tech-orange" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6.827 6.175A2.31 2.31 0 015.186 7.23c-.38.054-.757.112-1.134.175C2.999 7.58 2.25 8.507 2.25 9.574V18a2.25 2.25 0 002.25 2.25h15A2.25 2.25 0 0021.75 18V9.574c0-1.067-.75-1.994-1.802-2.169a47.865 47.865 0 00-1.134-.175 2.31 2.31 0 01-1.64-1.055l-.822-1.316a2.192 2.192 0 00-1.736-1.039 48.774 48.774 0 00-5.232 0 2.192 2.192 0 00-1.736 1.039l-.821 1.316z" />
                                <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 12.75a4.5 4.5 0 11-9 0 4.5 4.5 0 019 0zM18.75 10.5h.008v.008h-.008V10.5z" />
                            </svg>
                        </div>
                        <h3 class="text-lg font-bold text-gray-900 mb-2">Recepción con IA</h3>
                        <p class="text-sm text-gray-500 leading-relaxed">
                            Escanea patentes con la cámara de tu celular y agiliza el ingreso. Gemini AI lee la patente, cruza la agenda y te muestra los datos del cliente en segundos.
                        </p>
                        <div class="mt-6 flex flex-wrap gap-2">
                            <span class="text-[11px] font-semibold bg-purple-50 text-purple-600 px-2.5 py-1 rounded-full">Gemini AI</span>
                            <span class="text-[11px] font-semibold bg-gray-100 text-gray-600 px-2.5 py-1 rounded-full">ALPR</span>
                        </div>
                    </div>

                </div>
            </div>
        </section>

        <!-- ═══════════════════════════════════════════════════════════════
             BENEFITS – Additional features row
             ═══════════════════════════════════════════════════════════════ -->
        <section id="benefits" class="py-24 bg-white">
            <div class="max-w-6xl mx-auto px-6 lg:px-8">

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-16 items-center">

                    <!-- Left: text -->
                    <div>
                        <p class="text-xs font-bold uppercase tracking-widest text-tech-orange mb-3">Beneficios</p>
                        <h2 class="text-3xl md:text-4xl font-black text-gray-900 tracking-tight leading-tight mb-6">
                            Menos papel, menos caos,<br>más tiempo para trabajar.
                        </h2>
                        <p class="text-gray-500 mb-10 leading-relaxed">
                            Digitaliza tu taller en menos de un día. Sin instalaciones, sin servidores, sin dolores de cabeza. Accede desde cualquier computador o celular.
                        </p>

                        <ul class="flex flex-col gap-5">
                            <li class="flex items-start gap-4">
                                <span class="flex-shrink-0 w-8 h-8 rounded-xl bg-emerald-50 flex items-center justify-center mt-0.5">
                                    <svg class="w-4 h-4 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                                    </svg>
                                </span>
                                <div>
                                    <p class="font-bold text-gray-900 text-sm">Histórico completo de cada vehículo</p>
                                    <p class="text-sm text-gray-500 mt-0.5">Accede al registro de todas las visitas y trabajos realizados a cada auto al instante.</p>
                                </div>
                            </li>
                            <li class="flex items-start gap-4">
                                <span class="flex-shrink-0 w-8 h-8 rounded-xl bg-emerald-50 flex items-center justify-center mt-0.5">
                                    <svg class="w-4 h-4 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                                    </svg>
                                </span>
                                <div>
                                    <p class="font-bold text-gray-900 text-sm">Notificaciones en tiempo real</p>
                                    <p class="text-sm text-gray-500 mt-0.5">Tu equipo se entera al instante cuando cambia el estado de una orden de trabajo.</p>
                                </div>
                            </li>
                            <li class="flex items-start gap-4">
                                <span class="flex-shrink-0 w-8 h-8 rounded-xl bg-emerald-50 flex items-center justify-center mt-0.5">
                                    <svg class="w-4 h-4 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                                    </svg>
                                </span>
                                <div>
                                    <p class="font-bold text-gray-900 text-sm">Multi-sucursal desde el día uno</p>
                                    <p class="text-sm text-gray-500 mt-0.5">Cada taller tiene su propio espacio aislado y seguro. Escala sin límites.</p>
                                </div>
                            </li>
                        </ul>
                    </div>

                    <!-- Right: stat cards -->
                    <div class="grid grid-cols-2 gap-4">
                        <div class="bg-gray-50 rounded-3xl p-6 border border-gray-100">
                            <p class="text-4xl font-black text-gray-900">3×</p>
                            <p class="text-sm text-gray-500 mt-1 font-medium leading-snug">más rápido en recibir un vehículo</p>
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

        <!-- ═══════════════════════════════════════════════════════════════
             TRUST / SOCIAL PROOF
             ═══════════════════════════════════════════════════════════════ -->
        <section id="pricing" class="py-20 bg-gray-50 border-y border-gray-100">
            <div class="max-w-5xl mx-auto px-6 lg:px-8">

                <div class="grid grid-cols-1 md:grid-cols-3 gap-8 text-center">

                    <div class="flex flex-col items-center gap-4 p-6">
                        <div class="w-14 h-14 rounded-2xl bg-white border border-gray-100 shadow-sm flex items-center justify-center">
                            <svg class="w-7 h-7 text-tech-orange" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z" />
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
                        <div class="w-14 h-14 rounded-2xl bg-white border border-gray-100 shadow-sm flex items-center justify-center">
                            <svg class="w-7 h-7 text-tech-orange" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m0-10.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.75c0 5.592 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.57-.598-3.75h-.152c-3.196 0-6.1-1.249-8.25-3.286zm0 13.036h.008v.008H12v-.008z" />
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-base font-bold text-gray-900">Seguridad en la Nube</h3>
                            <p class="text-sm text-gray-500 mt-1.5 leading-relaxed">
                                Tus datos siempre respaldados. Cumplimiento con estándares internacionales de privacidad.
                            </p>
                        </div>
                    </div>

                    <div class="flex flex-col items-center gap-4 p-6">
                        <div class="w-14 h-14 rounded-2xl bg-white border border-gray-100 shadow-sm flex items-center justify-center">
                            <svg class="w-7 h-7 text-tech-orange" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 13.5l10.5-11.25L12 10.5h8.25L9.75 21.75 12 13.5H3.75z" />
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

                <!-- CTA Banner -->
                <div class="mt-16 bg-gray-900 rounded-3xl p-10 md:p-14 flex flex-col md:flex-row items-center justify-between gap-8">
                    <div>
                        <h3 class="text-2xl md:text-3xl font-black text-white leading-tight">
                            ¿Listo para digitalizar tu taller?
                        </h3>
                        <p class="text-gray-400 mt-2 text-sm">
                            14 días gratis. Sin tarjeta de crédito. Cancela cuando quieras.
                        </p>
                    </div>
                    <a
                        href="#"
                        class="flex-shrink-0 inline-flex items-center gap-2 bg-tech-orange hover:bg-[#e8920d] text-white font-bold px-8 py-4 rounded-2xl shadow-lg shadow-tech-orange/30 transition-all active:scale-[0.98] whitespace-nowrap"
                    >
                        Empezar Gratis Ahora
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3" />
                        </svg>
                    </a>
                </div>

            </div>
        </section>

        <!-- ═══════════════════════════════════════════════════════════════
             FOOTER
             ═══════════════════════════════════════════════════════════════ -->
        <footer class="bg-white border-t border-gray-100 py-10">
            <div class="max-w-7xl mx-auto px-6 lg:px-8 flex flex-col md:flex-row items-center justify-between gap-6">

                <!-- Logo -->
                <div class="flex items-center gap-2">
                    <span class="w-7 h-7 rounded-lg bg-tech-orange flex items-center justify-center">
                        <svg class="w-4 h-4 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M11.42 15.17L17.25 21A2.652 2.652 0 0021 17.25l-5.877-5.877M11.42 15.17l2.496-3.03c.317-.384.74-.626 1.208-.766M11.42 15.17l-4.655 5.653a2.548 2.548 0 11-3.586-3.586l6.837-5.63m5.108-.233c.55-.164 1.163-.188 1.743-.14a4.5 4.5 0 004.486-6.336l-3.276 3.277a3.004 3.004 0 01-2.25-2.25l3.276-3.276a4.5 4.5 0 00-6.336 4.486c.091 1.076-.071 2.264-.904 2.95l-.102.085m-1.745 1.437L5.909 7.5H4.5L2.25 3.75l1.5-1.5L7.5 4.5v1.409l4.26 4.26m-1.745 1.437l1.745-1.437m6.615 8.206L15.75 15.75M4.867 19.125h.008v.008h-.008v-.008z" />
                        </svg>
                    </span>
                    <span class="text-base font-black text-gray-900">Feeto</span>
                </div>

                <!-- Legal links -->
                <nav class="flex items-center gap-6 flex-wrap justify-center">
                    <a href="#" class="text-xs text-gray-400 hover:text-gray-600 transition-colors font-medium">Términos de Servicio</a>
                    <a href="#" class="text-xs text-gray-400 hover:text-gray-600 transition-colors font-medium">Política de Privacidad</a>
                    <a href="#" class="text-xs text-gray-400 hover:text-gray-600 transition-colors font-medium">Contacto</a>
                </nav>

                <!-- Copyright -->
                <p class="text-xs text-gray-400 font-medium">
                    &copy; {{ new Date().getFullYear() }} Feeto. Todos los derechos reservados.
                </p>

            </div>
        </footer>

        <!-- Modal de Login Global -->
        <LoginModal :show="showLoginModal" @close="showLoginModal = false" />
    </div>
</template>
