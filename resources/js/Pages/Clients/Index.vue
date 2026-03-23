<script setup>
import { ref, watch } from 'vue';
import { Head, router, Link } from '@inertiajs/vue3';
import TallerLayout from '@/Layouts/TallerLayout.vue';

const props = defineProps({
    clients: Object,
    filters: Object,
});

const search = ref(props.filters.search || '');

// Custom debounce function to avoid lodash dependency
const debounce = (fn, delay) => {
    let timeoutId;
    return (...args) => {
        clearTimeout(timeoutId);
        timeoutId = setTimeout(() => fn(...args), delay);
    };
};

watch(search, debounce((value) => {
    router.get(route('clients.index'), { search: value }, {
        preserveState: true,
        replace: true,
    });
}, 300));
</script>

<template>
    <Head title="Directorio de Clientes" />

    <TallerLayout>
        <div class="mb-8 flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div>
                <h1 class="text-3xl font-black text-gray-900 tracking-tight uppercase">Directorio de Clientes</h1>
                <p class="text-sm font-medium text-gray-500 mt-1">Gestiona la información y el historial de tus clientes.</p>
            </div>
            
            <div class="relative w-full md:w-96">
                <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                    <svg class="w-5 h-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                </div>
                <input v-model="search" type="text" placeholder="Buscar por nombre o RUT..." 
                       class="w-full pl-11 pr-4 py-3 bg-white border border-gray-200 rounded-2xl text-sm font-medium text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-[#F9A826]/50 focus:border-[#F9A826] transition-all shadow-sm" />
            </div>
        </div>

        <div class="bg-white rounded-[2rem] shadow-sm border border-gray-100 overflow-hidden">
            <div v-if="clients.data.length === 0" class="p-12 text-center">
                <div class="w-16 h-16 bg-gray-50 rounded-2xl flex items-center justify-center mx-auto mb-4">
                    <svg class="w-8 h-8 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                    </svg>
                </div>
                <h3 class="text-lg font-bold text-gray-900">No se encontraron clientes</h3>
                <p class="text-gray-500 text-sm mt-1">Intenta con otros términos de búsqueda.</p>
            </div>
            
            <div v-else class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-gray-50/50 border-b border-gray-100 uppercase text-[10px] font-black tracking-widest text-gray-400">
                            <th class="px-6 py-4">Cliente</th>
                            <th class="px-6 py-4">RUT</th>
                            <th class="px-6 py-4">Contacto</th>
                            <th class="px-6 py-4 text-right">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        <tr v-for="client in clients.data" :key="client.id" class="hover:bg-gray-50/30 transition-colors group">
                            <td class="px-6 py-4">
                                <div class="font-bold text-gray-900 text-sm uppercase">{{ client.name }}</div>
                            </td>
                            <td class="px-6 py-4 text-sm font-medium text-gray-600">
                                {{ client.rut }}
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-500">
                                <div class="flex flex-col gap-1">
                                    <span v-if="client.phone" class="flex items-center gap-1.5"><svg class="w-3.5 h-3.5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg> {{ client.phone }}</span>
                                    <span v-if="client.email" class="flex items-center gap-1.5 truncate max-w-[200px]"><svg class="w-3.5 h-3.5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg> {{ client.email }}</span>
                                    <span v-if="!client.phone && !client.email" class="text-xs text-gray-400 italic">Sin datos</span>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <Link :href="route('clients.show', client.id)" class="inline-flex items-center justify-center bg-gray-100 text-gray-600 hover:bg-[#F9A826] hover:text-white px-3 py-1.5 rounded-lg text-xs font-bold transition-colors uppercase tracking-wide">
                                    Ver Perfil
                                </Link>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            
            <!-- Paginación Simple -->
            <div v-if="clients.links && clients.links.length > 3" class="px-6 py-4 border-t border-gray-100 flex items-center flex-wrap justify-center gap-1">
                <template v-for="(link, i) in clients.links" :key="i">
                    <Link v-if="link.url" :href="link.url" v-html="link.label" 
                          class="px-3 py-1.5 rounded-lg text-sm font-medium transition-colors"
                          :class="link.active ? 'bg-[#F9A826] text-white shadow-sm' : 'text-gray-500 hover:bg-gray-100'" />
                    <span v-else v-html="link.label" class="px-3 py-1.5 text-sm font-medium text-gray-400"></span>
                </template>
            </div>
        </div>
    </TallerLayout>
</template>
