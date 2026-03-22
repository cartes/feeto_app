<script setup>
import { Head, Link } from '@inertiajs/vue3';
import TallerLayout from '@/Layouts/TallerLayout.vue';
</script>

<template>
    <Head title="Panel de Control" />

    <TallerLayout>
        <div class="space-y-6">
            <!-- Saludo -->
            <div>
                <h1 class="text-3xl font-bold text-slate-800 leading-tight tracking-tight">
                    Panel de Control
                </h1>
                <p class="text-sm text-slate-400 mt-1 font-medium">Bienvenido al sistema de gestión de tu taller.</p>
            </div>

            <!-- Accesos Rápidos -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                <!-- Card Recepción -->
                <Link :href="route('receptions.create')"
                    class="group bg-white/90 backdrop-blur-md rounded-3xl p-6 shadow-[0_8px_30px_rgb(0,0,0,0.04)] border border-white hover:border-tech-orange/30 hover:shadow-lg transition-all active:scale-[0.98]">
                    <div class="w-12 h-12 rounded-2xl bg-tech-orange/10 flex items-center justify-center mb-4 group-hover:bg-tech-orange/20 transition-colors">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-tech-orange" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                    </div>
                    <h3 class="text-lg font-bold text-slate-800">Recepción</h3>
                    <p class="text-xs text-slate-400 mt-1">Escanear patente y recibir vehículos</p>
                </Link>

                <!-- Card Órdenes -->
                <Link :href="route('work-orders.index')"
                    class="group bg-white/90 backdrop-blur-md rounded-3xl p-6 shadow-[0_8px_30px_rgb(0,0,0,0.04)] border border-white hover:border-emerald-400/30 hover:shadow-lg transition-all active:scale-[0.98]">
                    <div class="w-12 h-12 rounded-2xl bg-emerald-500/10 flex items-center justify-center mb-4 group-hover:bg-emerald-500/20 transition-colors">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                        </svg>
                    </div>
                    <h3 class="text-lg font-bold text-slate-800">Tablero Kanban</h3>
                    <p class="text-xs text-slate-400 mt-1">Gestión visual de órdenes de trabajo</p>
                </Link>

                <!-- Card Perfil -->
                <Link :href="route('profile.edit')"
                    class="group bg-white/90 backdrop-blur-md rounded-3xl p-6 shadow-[0_8px_30px_rgb(0,0,0,0.04)] border border-white hover:border-blue-400/30 hover:shadow-lg transition-all active:scale-[0.98]">
                    <div class="w-12 h-12 rounded-2xl bg-blue-500/10 flex items-center justify-center mb-4 group-hover:bg-blue-500/20 transition-colors">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-blue-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                        </svg>
                    </div>
                    <h3 class="text-lg font-bold text-slate-800">Mi Perfil</h3>
                    <p class="text-xs text-slate-400 mt-1">Configuración y datos de tu cuenta</p>
                </Link>
            </div>
        </div>
    </TallerLayout>
</template>
