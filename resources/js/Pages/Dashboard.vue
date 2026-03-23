<script setup>
import { Head, Link, usePage } from '@inertiajs/vue3';
import TallerLayout from '@/Layouts/TallerLayout.vue';
import { ref, onMounted, onUnmounted } from 'vue';

const { props } = usePage();
const recentActivities = ref([]);

const getStatusLabel = (status) => {
    const labels = {
        'recepcion': 'Recepción',
        'diagnostico': 'En Diagnóstico',
        'esperando_repuestos': 'Faltan Repuestos',
        'listo': 'Listo'
    };
    return labels[status] || status;
};

onMounted(() => {
    if (props.auth.user && props.auth.user.tenant_id) {
        window.Echo.private(`taller.${props.auth.user.tenant_id}`)
            .listen('WorkOrderStatusUpdated', (e) => {
                recentActivities.value.unshift({
                    id: Date.now(),
                    message: `El vehículo patente ${e.plate} pasó de '${getStatusLabel(e.old_status)}' a '${getStatusLabel(e.new_status)}'`,
                    vehicle: e.vehicle,
                    timestamp: new Date().toLocaleTimeString('es-CL', { hour: '2-digit', minute: '2-digit' }),
                    isNew: true
                });

                // Limitar a los últimos 5
                if (recentActivities.value.length > 5) {
                    recentActivities.value.pop();
                }

                // Quitar el estado "nuevo" después de unos segundos para la animación
                setTimeout(() => {
                    const activity = recentActivities.value.find(a => a.id === Date.now());
                    if (activity) activity.isNew = false;
                }, 5000);
            });
    }
});

onUnmounted(() => {
    if (props.auth.user && props.auth.user.tenant_id) {
        window.Echo.leave(`taller.${props.auth.user.tenant_id}`);
    }
});
</script>
<template>

    <Head title="Centro de Comando" />

    <TallerLayout>
        <div class="space-y-10 animate-in fade-in slide-in-from-bottom-4 duration-700">
            <!-- Saludo e info operativa sutil -->
            <div class="flex flex-col md:flex-row md:items-end justify-between gap-6 px-2">
                <div>
                    <h1 class="text-3xl font-bold tracking-tight text-gray-900">
                        Dashboard Operativo
                    </h1>
                    <div class="flex items-center gap-2 mt-2">
                        <span class="relative flex h-2 w-2">
                            <span
                                class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                            <span class="relative inline-flex rounded-full h-2 w-2 bg-emerald-500"></span>
                        </span>
                        <p class="text-[10px] font-bold text-gray-500 uppercase tracking-[0.2em]">
                            Sistema Activo <span class="mx-2 text-gray-300">|</span> Santiago Hub
                        </p>
                    </div>
                </div>
                <div class="hidden md:block text-right">
                    <p class="text-[10px] font-extrabold text-gray-400 uppercase tracking-[0.2em] mb-1">Cronología</p>
                    <p class="text-sm font-semibold text-gray-600 tabular-nums">{{ new
                        Date().toLocaleDateString('es-CL', {
                            weekday: 'long', day: 'numeric', month: 'long', year:
                                'numeric'
                        }) }}</p>
                </div>
            </div>

            <!-- Grid de Accesos (Estilo Técnico High-End) -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">

                <!-- 1. NUEVA RECEPCIÓN -->
                <Link :href="route('receptions.create')"
                    class="group relative min-h-[120px] p-6 bg-white border border-gray-100 rounded-3xl flex flex-col justify-between transition-all duration-300 hover:border-tech-orange/40 hover:shadow-[0_20px_40px_rgba(0,0,0,0.04)] active:scale-[0.98] shadow-sm">
                    <div class="w-8 h-8 flex items-center justify-center">
                        <svg viewBox="0 0 24 24" fill="none" class="w-full h-full stroke-gray-800" stroke-width="1.5"
                            stroke-linecap="round" stroke-linejoin="round">
                            <path
                                d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z" />
                            <circle cx="12" cy="13" r="3" class="stroke-tech-orange" stroke-width="2" />
                        </svg>
                    </div>
                    <span
                        class="text-xl font-black text-gray-900 tracking-tight uppercase leading-tight">Nueva<br />Recepción</span>
                    <div class="absolute top-4 right-4 opacity-0 group-hover:opacity-100 transition-opacity">
                        <svg class="w-4 h-4 text-tech-orange" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M12 4v16m8-8H4" />
                        </svg>
                    </div>
                </Link>

                <!-- 2. TABLERO DE ÓRDENES -->
                <Link :href="route('work-orders.index')"
                    class="group relative min-h-[120px] p-6 bg-white border border-gray-100 rounded-3xl flex flex-col justify-between transition-all duration-300 hover:border-tech-orange/40 hover:shadow-[0_20px_40px_rgba(0,0,0,0.04)] active:scale-[0.98] shadow-sm">
                    <div class="w-8 h-8 flex items-center justify-center">
                        <svg viewBox="0 0 24 24" fill="none" class="w-full h-full stroke-gray-800" stroke-width="1.5"
                            stroke-linecap="round" stroke-linejoin="round">
                            <rect x="3" y="3" width="18" height="18" rx="2" ry="2" />
                            <path d="M9 3v18" class="stroke-tech-orange" stroke-width="2" />
                            <path d="M15 3v18" class="opacity-20" />
                            <path d="M5 8h2m-2 4h2m-2 4h2" />
                        </svg>
                    </div>
                    <span class="text-xl font-black text-gray-900 tracking-tight uppercase leading-tight">Tablero
                        de<br />Órdenes</span>
                </Link>

                <!-- 3. INVENTARIO -->
                <Link href="#"
                    class="group relative min-h-[120px] p-6 bg-white border border-gray-100 rounded-3xl flex flex-col justify-between transition-all duration-300 hover:border-tech-orange/40 hover:shadow-[0_20px_40px_rgba(0,0,0,0.04)] active:scale-[0.98] shadow-sm">
                    <div class="w-8 h-8 flex items-center justify-center">
                        <svg viewBox="0 0 24 24" fill="none" class="w-full h-full stroke-gray-800" stroke-width="1.5"
                            stroke-linecap="round" stroke-linejoin="round">
                            <path d="M21 8a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v10a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V8z" />
                            <path d="M3 10h18" class="stroke-tech-orange" stroke-width="2" />
                        </svg>
                    </div>
                    <span
                        class="text-xl font-black text-gray-900 tracking-tight uppercase leading-tight">Inventario<br />de
                        Stock</span>
                </Link>

                <!-- 4. CLIENTES -->
                <Link href="#"
                    class="group relative min-h-[120px] p-6 bg-white border border-gray-100 rounded-3xl flex flex-col justify-between transition-all duration-300 hover:border-tech-orange/40 hover:shadow-[0_20px_40px_rgba(0,0,0,0.04)] active:scale-[0.98] shadow-sm">
                    <div class="w-8 h-8 flex items-center justify-center">
                        <svg viewBox="0 0 24 24" fill="none" class="w-full h-full stroke-gray-800" stroke-width="1.5"
                            stroke-linecap="round" stroke-linejoin="round">
                            <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2" />
                            <circle cx="9" cy="7" r="3" class="stroke-tech-orange" stroke-width="2" />
                            <path d="M23 21v-2a4 4 0 0 0-3-3.87" class="opacity-20" />
                        </svg>
                    </div>
                    <span class="text-xl font-black text-gray-900 tracking-tight uppercase leading-tight">Gestión
                        de<br />Clientes</span>
                </Link>

            </div>

            <!-- Sección Inferior: Reportes y Alertas Rápidas -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                <div class="bg-white/40 backdrop-blur-sm border border-gray-100 rounded-[2rem] p-8 shadow-sm">
                    <div class="flex items-center justify-between mb-8">
                        <div class="flex items-center gap-3">
                            <h3 class="text-[10px] font-black text-gray-400 uppercase tracking-widest">Actividad en Tiempo Real</h3>
                            <span class="flex h-2 w-2 relative">
                                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-tech-orange opacity-75"></span>
                                <span class="relative inline-flex rounded-full h-2 w-2 bg-tech-orange"></span>
                            </span>
                        </div>
                        <span class="text-[10px] font-bold text-tech-orange bg-tech-orange/10 px-2 py-0.5 rounded-full">En Vivo</span>
                    </div>

                    <div v-if="recentActivities.length === 0" class="flex flex-col items-center justify-center py-12 text-center">
                        <div class="w-12 h-12 bg-gray-50 rounded-2xl flex items-center justify-center mb-4">
                            <svg class="w-6 h-6 text-gray-200" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                            </svg>
                        </div>
                        <p class="text-gray-400 font-semibold text-xs uppercase tracking-tight">Esperando actualizaciones del tablero...</p>
                    </div>

                    <div v-else class="space-y-4">
                        <div v-for="activity in recentActivities" :key="activity.id" 
                            class="p-4 rounded-2xl border transition-all duration-500"
                            :class="[
                                activity.isNew ? 'bg-[#F9A826]/10 border-[#F9A826]/20 scale-[1.02] shadow-sm' : 'bg-white border-gray-50'
                            ]">
                            <div class="flex justify-between items-start gap-4">
                                <div class="flex-1">
                                    <p class="text-xs font-bold text-gray-900 leading-snug">{{ activity.message }}</p>
                                    <p class="text-[10px] font-medium text-gray-400 mt-1 uppercase">{{ activity.vehicle }}</p>
                                </div>
                                <span class="text-[10px] font-mono font-bold text-gray-400 tabular-nums">{{ activity.timestamp }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div
                    class="bg-slate-900 rounded-[2rem] p-10 text-white relative overflow-hidden flex flex-col justify-end min-h-[240px] shadow-lg">
                    <div class="absolute top-10 left-10">
                        <div
                            class="w-10 h-10 bg-white/5 rounded-xl flex items-center justify-center backdrop-blur-md border border-white/10">
                            <svg class="w-5 h-5 text-tech-orange" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M13 10V3L4 14h7v7l9-11h-7z" />
                            </svg>
                        </div>
                    </div>
                    <div>
                        <h4 class="text-xl font-bold leading-tight mb-1">Status: Premium</h4>
                        <p class="text-slate-400 text-xs font-bold uppercase tracking-widest">Nodos operativos al 100%
                        </p>
                    </div>
                    <!-- Decoración sutil -->
                    <div class="absolute -right-16 -bottom-16 w-64 h-64 border border-white/5 rounded-full"></div>
                </div>
            </div>
        </div>
    </TallerLayout>
</template>
