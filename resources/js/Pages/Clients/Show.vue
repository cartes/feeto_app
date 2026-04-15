<script setup>
import { Head, Link } from '@inertiajs/vue3';
import TallerLayout from '@/Layouts/TallerLayout.vue';

const props = defineProps({
    client: Object,
});

const getStatusColor = (status) => {
    switch(status) {
        case 'recepcion': return 'bg-amber-100 text-amber-600 border-amber-200';
        case 'diagnostico': return 'bg-blue-100 text-blue-600 border-blue-200';
        case 'esperando_repuestos': return 'bg-purple-100 text-purple-600 border-purple-200';
        case 'control_calidad': return 'bg-cyan-100 text-cyan-600 border-cyan-200';
        case 'listo': return 'bg-emerald-100 text-emerald-600 border-emerald-200';
        default: return 'bg-gray-100 text-gray-600 border-gray-200';
    }
};

const getStatusLabel = (status) => {
    switch(status) {
        case 'recepcion': return 'En Recepción';
        case 'diagnostico': return 'En Diagnóstico';
        case 'esperando_repuestos': return 'Faltan Repuestos';
        case 'control_calidad': return 'Control de Calidad';
        case 'listo': return 'Listo / Entregado';
        default: return status;
    }
};

const formatDate = (dateString) => {
    if (!dateString) return '';
    return new Date(dateString).toLocaleDateString('es-CL', {
        day: '2-digit', month: 'short', year: 'numeric'
    });
};
</script>

<template>
    <Head :title="`Perfil de Cliente | ${client.name}`" />

    <TallerLayout>
        <div class="mb-6 flex items-center justify-between">
            <Link :href="route('clients.index')" class="flex items-center gap-2 text-sm font-bold text-gray-500 hover:text-[#F9A826] transition-colors uppercase tracking-widest">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
                Volver al Directorio
            </Link>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Perfil Lateral (Tarjeta Blanca) -->
            <div class="lg:col-span-1 border border-gray-100 bg-white rounded-[2.5rem] p-8 shadow-sm h-fit">
                <div class="flex flex-col items-center text-center">
                    <div class="w-24 h-24 bg-[#F9A826]/10 text-[#F9A826] rounded-3xl flex items-center justify-center text-3xl font-black mb-4 uppercase tracking-widest">
                        {{ client.name.charAt(0) }}
                    </div>
                    <h2 class="text-2xl font-black text-gray-900 tracking-tight uppercase">{{ client.name }}</h2>
                    <p class="text-xs font-bold text-gray-400 mt-1 uppercase tracking-widest">Cliente Registrado</p>
                </div>

                <div class="mt-8 space-y-4">
                    <div class="flex items-start gap-4 p-4 rounded-2xl bg-gray-50/50">
                        <svg class="w-5 h-5 text-gray-400 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V8a2 2 0 00-2-2h-5m-4 0V5a2 2 0 114 0v1m-4 0a2 2 0 104 0m-5 8a2 2 0 100-4 2 2 0 000 4zm0 0c1.306 0 2.417.835 2.83 2M9 14a3.001 3.001 0 00-2.83 2M15 11h3m-3 4h2" />
                        </svg>
                        <div>
                            <p class="text-[10px] uppercase font-bold text-gray-400 tracking-widest mb-0.5">RUT</p>
                            <p class="text-sm font-bold text-gray-800">{{ client.rut }}</p>
                        </div>
                    </div>
                    <div class="flex items-start gap-4 p-4 rounded-2xl bg-gray-50/50">
                        <svg class="w-5 h-5 text-gray-400 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                        </svg>
                        <div>
                            <p class="text-[10px] uppercase font-bold text-gray-400 tracking-widest mb-0.5">Teléfono</p>
                            <p class="text-sm font-bold text-gray-800">{{ client.phone || 'No registrado' }}</p>
                        </div>
                    </div>
                    <div class="flex items-start gap-4 p-4 rounded-2xl bg-gray-50/50">
                        <svg class="w-5 h-5 text-gray-400 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                        </svg>
                        <div>
                            <p class="text-[10px] uppercase font-bold text-gray-400 tracking-widest mb-0.5">Email</p>
                            <p class="text-sm font-bold text-gray-800 truncate" :title="client.email">{{ client.email || 'No registrado' }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Historial Clínico (Sección Principal) -->
            <div class="lg:col-span-2 space-y-8">
                
                <div class="bg-gray-50 rounded-[2.5rem] p-8 border border-gray-100">
                    <div class="flex items-center gap-3 mb-6">
                        <span class="w-2.5 h-2.5 rounded-full bg-emerald-400"></span>
                        <h3 class="text-xl font-black text-gray-900 uppercase tracking-tight">Vehículos Asociados</h3>
                    </div>

                    <div v-if="client.vehicles.length === 0" class="text-center py-6">
                        <p class="text-sm text-gray-500 font-medium">Este cliente no tiene vehículos asociados.</p>
                    </div>

                    <div v-else class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div v-for="vehicle in client.vehicles" :key="vehicle.id" class="bg-white p-5 rounded-2xl border border-gray-200 shadow-sm hover:shadow-md transition-shadow">
                            <div class="flex justify-between items-start mb-2">
                                <h4 class="font-black text-lg text-gray-900 uppercase">{{ vehicle.brand }} <span class="text-gray-500">{{ vehicle.model }}</span></h4>
                                <div class="bg-gray-100 px-3 py-1 rounded-lg border border-gray-200">
                                    <span class="font-mono font-bold tracking-widest text-[#F9A826] text-sm">{{ vehicle.plate }}</span>
                                </div>
                            </div>
                            <div class="mt-4 flex flex-wrap gap-2 text-xs">
                                <span class="bg-gray-50 px-2 py-1 flex items-center gap-1 rounded font-medium text-gray-600 border border-gray-100">
                                    <span class="w-1.5 h-1.5 bg-gray-400 rounded-full inline-block"></span> Color: {{ vehicle.color || 'N/A' }}
                                </span>
                                <span class="bg-gray-50 px-2 py-1 flex items-center gap-1 rounded font-medium text-gray-600 border border-gray-100">
                                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V8a2 2 0 00-2-2h-5m-4 0V5a2 2 0 114 0v1m-4 0a2 2 0 104 0m-5 8a2 2 0 100-4 2 2 0 000 4zm0 0c1.306 0 2.417.835 2.83 2M9 14a3.001 3.001 0 00-2.83 2M15 11h3m-3 4h2" /></svg>
                                    VIN: {{ vehicle.vin || 'N/A' }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-[2.5rem] p-8 border border-gray-200 shadow-[0_8px_30px_rgba(0,0,0,0.04)]">
                    <div class="flex items-center gap-3 mb-6 pb-6 border-b border-gray-100">
                        <span class="w-2.5 h-2.5 rounded-full bg-[#F9A826]"></span>
                        <h3 class="text-xl font-black text-gray-900 uppercase tracking-tight">Historial Clínico (Órdenes)</h3>
                    </div>

                    <div v-if="client.vehicles.flatMap(v => v.work_orders).length === 0" class="text-center py-10">
                        <div class="w-16 h-16 bg-gray-50 rounded-2xl flex items-center justify-center mx-auto mb-4">
                            <svg class="w-8 h-8 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                        </div>
                        <p class="text-sm font-bold text-gray-500 uppercase tracking-widest">Sin Entradas</p>
                        <p class="text-xs text-gray-400 mt-1">Este cliente no ha registrado ingresos al taller.</p>
                    </div>

                    <div v-else class="space-y-4">
                        <div v-for="vehicle in client.vehicles" :key="'wo-' + vehicle.id" class="space-y-4">
                            <div v-for="wo in vehicle.work_orders" :key="wo.id" class="group flex flex-col md:flex-row gap-4 md:items-center justify-between p-5 rounded-2xl border border-gray-100 hover:border-gray-200 hover:bg-gray-50/50 transition-all bg-white">
                                <div class="flex flex-col gap-1">
                                    <div class="flex items-center gap-2">
                                        <span class="font-mono font-bold tracking-widest text-sm text-gray-500">OT-#{{ wo.id }}</span>
                                        <span :class="['text-[9px] font-black uppercase tracking-widest px-2 py-0.5 rounded-full border', getStatusColor(wo.status)]">
                                            {{ getStatusLabel(wo.status) }}
                                        </span>
                                    </div>
                                    <div class="flex gap-2 items-center text-sm font-bold text-gray-900">
                                        <span class="uppercase tracking-wide">{{ vehicle.brand }} {{ vehicle.model }}</span>
                                        <span class="text-gray-300">•</span>
                                        <span class="font-mono text-[#F9A826] bg-[#F9A826]/10 px-1.5 py-0.5 rounded">{{ vehicle.plate }}</span>
                                    </div>
                                    <p class="text-xs text-gray-500 mt-1 line-clamp-1 max-w-lg">{{ wo.observations || 'Sin observaciones al ingreso.' }}</p>
                                </div>
                                <div class="flex md:flex-col items-center md:items-end justify-between font-medium text-xs text-gray-400 gap-2">
                                    <div class="flex items-center gap-1.5">
                                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                                        {{ formatDate(wo.created_at) }}
                                    </div>
                                    <!-- Botón de Tracking Público -->
                                    <a target="_blank" v-if="wo.uuid" :href="route('tracking.show', wo.uuid)" class="flex items-center gap-1 text-[10px] uppercase font-bold text-blue-500 hover:text-blue-700 bg-blue-50 px-2 py-1 rounded tracking-widest border border-blue-100 transition-colors">
                                        <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1" /></svg>
                                        Enlace Cliente
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </TallerLayout>
</template>
