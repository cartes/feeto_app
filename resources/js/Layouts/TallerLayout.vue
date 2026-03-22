<script setup>
import { computed } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';

const page = usePage();
const user = computed(() => page.props.auth.user);

const navItems = [
    { label: 'Recepción', icon: 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6', route: 'dashboard' }, // Home
    { label: 'Órdenes', icon: 'M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 002-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10', route: 'dashboard' }, // Heart placeholder for orders
    { label: 'Mensajes', icon: 'M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z', route: 'dashboard' }, // Chat
    { label: 'Perfil', icon: 'M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z', route: 'dashboard' }, // User
];
</script>

<template>
    <div class="min-h-screen bg-gradient-to-br from-[#E2EAF4] via-[#F0F4F8] to-[#E2EAF4] text-slate-800 font-sans selection:bg-[#F9A826]/30 overflow-x-hidden flex">
        
        <!-- SIDEBAR - Desktop Only -->
        <aside class="hidden lg:flex lg:flex-col lg:w-72 fixed inset-y-0 left-0 bg-white/60 backdrop-blur-2xl border-r border-white/60 shadow-[4px_0_24px_rgba(0,0,0,0.02)] z-50">
            <!-- Branding -->
            <div class="px-8 py-8 flex items-center gap-3">
                <div class="w-10 h-10 rounded-[12px] bg-[#F9A826] flex items-center justify-center flex-shrink-0 shadow-sm">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z" />
                    </svg>
                </div>
                <div>
                    <h1 class="text-xl font-bold tracking-tight text-slate-800 leading-none">Feeto</h1>
                    <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mt-1">Gestión de Taller</p>
                </div>
            </div>

            <!-- Navegación Desktop -->
            <nav class="flex-1 px-4 py-6 space-y-3">
                <Link
                    v-for="(item, index) in navItems"
                    :key="index"
                    :href="route(item.route)"
                    class="flex items-center gap-4 px-4 py-4 rounded-[1.25rem] font-bold transition-all duration-300 group"
                    :class="index === 0 ? 'bg-[#1C1C1E] text-white shadow-xl' : 'text-slate-500 hover:bg-white hover:shadow-sm hover:text-slate-800'"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 transition-transform group-hover:scale-110" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="item.icon" />
                    </svg>
                    <span>{{ item.label }}</span>
                </Link>
            </nav>

            <div class="p-6">
                 <div class="bg-white/80 p-4 rounded-[1.5rem] flex items-center gap-3 shadow-sm border border-white">
                    <img 
                        :src="`https://ui-avatars.com/api/?name=${user?.name || 'User'}&background=F9A826&color=fff`" 
                        class="w-10 h-10 rounded-full"
                    />
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-bold text-slate-800 truncate">{{ user?.name || 'Usuario' }}</p>
                        <p class="text-xs text-slate-400 truncate">{{ user?.email }}</p>
                    </div>
                 </div>
            </div>
        </aside>

        <!-- MAIN WRAPPER (Flexible para Desktop y Mobile) -->
        <div class="flex-1 flex flex-col min-h-screen relative lg:ml-72 w-full max-w-[100vw] lg:max-w-[calc(100vw-18rem)]">
            
            <!-- HEADER (Mobile & Desktop) -->
            <header class="pt-8 lg:pt-10 pb-4 px-6 lg:px-10 flex items-center justify-between sticky top-0 z-40 bg-gradient-to-b from-[#E2EAF4] via-[#E2EAF4]/90 to-transparent backdrop-blur-sm lg:backdrop-blur-none lg:bg-transparent">
                <!-- Data Izquierda -->
                <div class="flex items-center gap-3">
                    <img 
                        v-if="!$page.props.isDesktop" 
                        :src="`https://ui-avatars.com/api/?name=${user?.name || 'User'}&background=F9A826&color=fff`" 
                        alt="Avatar" 
                        class="w-11 h-11 rounded-full border-2 border-white shadow-sm lg:hidden"
                    />
                    <div class="lg:hidden">
                        <h2 class="text-[15px] font-bold text-slate-800 leading-tight">{{ user?.name || 'Bienvenido' }}</h2>
                        <p class="text-[12px] font-medium text-slate-500">Santiago, Chile</p>
                    </div>

                    <!-- Buscador global en desktop -->
                    <div class="hidden lg:flex relative w-96">
                        <div class="absolute inset-y-0 left-5 flex items-center pointer-events-none">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                        </div>
                        <input 
                            type="text" 
                            placeholder="Buscar patentes, órdenes, reportes..." 
                            class="w-full bg-white/70 backdrop-blur-md text-slate-700 rounded-full py-3.5 pl-14 pr-5 shadow-[0_8px_20px_rgba(0,0,0,0.03)] border-white focus:ring-2 focus:ring-[#F9A826] outline-none font-medium placeholder:text-slate-400 transition-all"
                        />
                    </div>
                </div>

                <!-- Botón Derecha -->
                <button class="w-10 h-10 lg:w-12 lg:h-12 rounded-full bg-white flex items-center justify-center shadow-sm hover:shadow-md transition-shadow">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 lg:h-6 lg:w-6 text-slate-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                    </svg>
                </button>
            </header>

            <!-- MAIN CONTENT AREA -->
            <main class="flex-1 px-6 lg:px-10 pb-28 lg:pb-12 flex flex-col gap-6">
                <!-- Search Bar (Mobile only) -->
                <div class="relative lg:hidden">
                    <div class="absolute inset-y-0 left-5 flex items-center pointer-events-none">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                    </div>
                    <input 
                        type="text" 
                        placeholder="Buscar patentes, órdenes..." 
                        class="w-full bg-white/80 backdrop-blur-md text-slate-700 rounded-full py-3.5 pl-14 pr-5 shadow-sm border border-white focus:ring-2 focus:ring-[#F9A826] outline-none font-medium placeholder:text-slate-400"
                    />
                </div>

                <slot />
            </main>

            <!-- ======== BOTTOM FLOATING NAV (SOLO MOBILE) ======== -->
            <nav class="lg:hidden fixed bottom-6 left-1/2 -translate-x-1/2 w-[calc(100%-3rem)] h-16 bg-[#1C1C1E] rounded-full shadow-2xl flex items-center justify-around px-2 z-50">
                <Link
                    v-for="(item, index) in navItems"
                    :key="index"
                    :href="route(item.route)"
                    class="w-12 h-12 rounded-full flex items-center justify-center transition-all duration-300"
                    :class="index === 0 ? 'bg-white shadow-[0_0_15px_rgba(255,255,255,0.3)] text-black' : 'text-gray-400 hover:text-white'"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="item.icon" />
                    </svg>
                </Link>
            </nav>

        </div>
    </div>
</template>

<style>
/* Ocultar barra de scroll para limpiar diseño */
::-webkit-scrollbar {
  display: none;
}
* {
  -ms-overflow-style: none;  /* IE and Edge */
  scrollbar-width: none;  /* Firefox */
}
</style>
