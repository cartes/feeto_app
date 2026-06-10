<script setup>
import { computed, ref, watch } from 'vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import TallerLayout from '@/Layouts/TallerLayout.vue';
import { useTenantRouting } from '@/composables/useTenantRouting';
import { useFormatting } from '@/composables/useFormatting';
import { useDebounce } from '@/composables/useDebounce';

const props = defineProps({
    clients: Object,
    filters: Object,
});

const { tenantRouteParams } = useTenantRouting();
const { formatCurrency, formatDate } = useFormatting();
const { debounce } = useDebounce();
const search = ref(props.filters.search || '');
const isCreateModalOpen = ref(false);

const form = useForm({
    name: '',
    rut: '',
    phone: '',
    email: '',
    max_credit_limit: '',
});

watch(search, debounce((value) => {
    router.get(route('clients.index', tenantRouteParams.value), { search: value }, {
        preserveState: true,
        replace: true,
    });
}, 300));

const submit = () => {
    form.post(route('clients.store', tenantRouteParams.value), {
        onSuccess: () => {
            isCreateModalOpen.value = false;
            form.reset();
        },
    });
};
</script>

<template>
    <Head title="Directorio de Clientes" />

    <TallerLayout>
        <div class="space-y-8">
            <div class="flex flex-col justify-between gap-4 md:flex-row md:items-center">
                <div>
                    <h1 class="text-3xl font-black uppercase tracking-tight text-gray-900">Directorio de Clientes</h1>
                    <p class="mt-1 text-sm font-medium text-gray-500">Gestiona la relación comercial con una vista rápida de historial, gasto y actividad.</p>
                </div>

                <div class="flex w-full flex-col gap-4 sm:flex-row md:w-auto">
                    <div class="relative w-full sm:w-80">
                        <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-4">
                            <svg class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                        </div>
                        <input v-model="search" type="text" placeholder="Buscar por nombre o RUT..."
                            class="w-full rounded-2xl border border-gray-200 bg-white py-3 pl-11 pr-4 text-sm font-medium text-gray-900 shadow-sm transition-all placeholder:text-gray-400 focus:border-[#FF7A00] focus:outline-none focus:ring-2 focus:ring-[#FF7A00]/50" />
                    </div>

                    <button @click="isCreateModalOpen = true"
                        class="inline-flex items-center justify-center gap-2 whitespace-nowrap rounded-2xl bg-gray-900 px-5 py-3 text-sm font-black text-white shadow-sm transition-colors hover:bg-gray-800">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        Nuevo Cliente
                    </button>
                </div>
            </div>

            <div class="overflow-hidden rounded-[2rem] border border-gray-100 bg-white shadow-sm">
                <div v-if="clients.data.length === 0" class="p-12 text-center">
                    <div class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-2xl bg-gray-50">
                        <svg class="h-8 w-8 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                        </svg>
                    </div>
                    <h3 class="text-lg font-bold text-gray-900">No se encontraron clientes</h3>
                    <p class="mt-1 text-sm text-gray-500">Intenta con otros términos de búsqueda.</p>
                </div>

                <div v-else class="overflow-x-auto">
                    <table class="w-full border-collapse text-left">
                        <thead>
                            <tr class="border-b border-gray-100 bg-gray-50/50 text-[10px] font-black uppercase tracking-widest text-gray-400">
                                <th class="px-6 py-4">Cliente</th>
                                <th class="px-6 py-4">Contacto</th>
                                <th class="px-6 py-4">Señales CRM</th>
                                <th class="px-6 py-4">Última visita</th>
                                <th class="px-6 py-4 text-right">Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            <tr v-for="client in clients.data" :key="client.id" class="group transition-colors hover:bg-gray-50/40">
                                <td class="px-6 py-4 align-top">
                                    <div class="space-y-2">
                                        <div class="text-sm font-bold uppercase text-gray-900">{{ client.name }}</div>
                                        <div class="text-xs font-semibold uppercase tracking-widest text-gray-400">{{ client.rut }}</div>
                                        <div class="flex flex-wrap gap-2">
                                            <span
                                                class="rounded-full border border-gray-200 bg-gray-50 px-2.5 py-1 text-[10px] font-black uppercase tracking-widest text-gray-500">
                                                {{ client.metrics.vehicles_count }} vehículos
                                            </span>
                                            <span
                                                class="rounded-full border border-gray-200 bg-gray-50 px-2.5 py-1 text-[10px] font-black uppercase tracking-widest text-gray-500">
                                                {{ client.metrics.visits_count }} visitas
                                            </span>
                                            <span
                                                class="rounded-full border border-gray-200 bg-gray-50 px-2.5 py-1 text-[10px] font-black uppercase tracking-widest text-gray-500">
                                                {{ client.metrics.notes_count }} notas
                                            </span>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 align-top text-sm text-gray-500">
                                    <div class="flex flex-col gap-1">
                                        <span v-if="client.phone" class="font-medium text-gray-700">{{ client.phone }}</span>
                                        <span v-if="client.email" class="max-w-[220px] truncate font-medium text-gray-700">{{ client.email }}</span>
                                        <span v-if="!client.phone && !client.email" class="text-xs italic text-gray-400">Sin datos</span>
                                    </div>
                                </td>
                                <td class="px-6 py-4 align-top">
                                    <div class="space-y-2">
                                        <p class="text-xs font-black uppercase tracking-widest text-gray-400">Gasto acumulado</p>
                                        <p class="text-sm font-black text-gray-900">{{ formatCurrency(client.metrics.total_spent) }}</p>
                                    </div>
                                </td>
                                <td class="px-6 py-4 align-top text-sm font-medium text-gray-600">
                                    {{ formatDate(client.metrics.last_visit) }}
                                </td>
                                <td class="px-6 py-4 text-right align-top">
                                    <Link :href="route('clients.show', { ...tenantRouteParams, client: client.id })"
                                        class="inline-flex items-center justify-center rounded-lg bg-gray-100 px-3 py-1.5 text-xs font-bold uppercase tracking-wide text-gray-600 transition-colors hover:bg-[#FF7A00] hover:text-white">
                                        Ver Perfil
                                    </Link>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div v-if="clients.links && clients.links.length > 3"
                    class="flex flex-wrap items-center justify-center gap-1 border-t border-gray-100 px-6 py-4">
                    <template v-for="(link, i) in clients.links" :key="i">
                        <Link v-if="link.url" :href="link.url" v-html="link.label"
                            class="rounded-lg px-3 py-1.5 text-sm font-medium transition-colors"
                            :class="link.active ? 'bg-[#FF7A00] text-white shadow-sm' : 'text-gray-500 hover:bg-gray-100'" />
                        <span v-else v-html="link.label" class="px-3 py-1.5 text-sm font-medium text-gray-400"></span>
                    </template>
                </div>
            </div>

            <div v-if="isCreateModalOpen"
                class="fixed inset-0 z-50 flex items-center justify-center overflow-y-auto overflow-x-hidden bg-gray-900/50 p-4 backdrop-blur-sm md:p-0">
                <div class="relative w-full max-w-lg rounded-[2.5rem] bg-white p-8 shadow-2xl">
                    <button @click="isCreateModalOpen = false"
                        class="absolute right-6 top-6 text-gray-400 transition-colors hover:text-gray-600">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>

                    <div class="mb-6">
                        <div class="flex items-center gap-3">
                            <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-[#FF7A00]/10 text-[#FF7A00]">
                                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M12 4v16m8-8H4" />
                                </svg>
                            </div>
                            <h3 class="text-xl font-black uppercase tracking-tight text-gray-900">Nuevo Cliente</h3>
                        </div>
                    </div>

                    <form @submit.prevent="submit" class="space-y-4">
                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <div class="space-y-1 sm:col-span-2">
                                <label class="text-[10px] font-black uppercase tracking-widest text-gray-400">Nombre completo</label>
                                <input v-model="form.name" type="text"
                                    class="w-full rounded-2xl border border-gray-200 bg-gray-50 px-4 py-3 text-sm font-medium text-gray-900 outline-none transition-all focus:border-[#FF7A00] focus:bg-white focus:ring-2 focus:ring-[#FF7A00]/20"
                                    placeholder="Ej. Juan Pérez" required />
                                <p v-if="form.errors.name" class="mt-1 text-xs text-rose-500">{{ form.errors.name }}</p>
                            </div>

                            <div class="space-y-1">
                                <label class="text-[10px] font-black uppercase tracking-widest text-gray-400">RUT</label>
                                <input v-model="form.rut" type="text"
                                    class="w-full rounded-2xl border border-gray-200 bg-gray-50 px-4 py-3 text-sm font-medium text-gray-900 outline-none transition-all focus:border-[#FF7A00] focus:bg-white focus:ring-2 focus:ring-[#FF7A00]/20"
                                    placeholder="Ej. 12.345.678-9" required />
                                <p v-if="form.errors.rut" class="mt-1 text-xs text-rose-500">{{ form.errors.rut }}</p>
                            </div>

                            <div class="space-y-1">
                                <label class="text-[10px] font-black uppercase tracking-widest text-gray-400">Teléfono</label>
                                <input v-model="form.phone" type="text"
                                    class="w-full rounded-2xl border border-gray-200 bg-gray-50 px-4 py-3 text-sm font-medium text-gray-900 outline-none transition-all focus:border-[#FF7A00] focus:bg-white focus:ring-2 focus:ring-[#FF7A00]/20"
                                    placeholder="Ej. +56912345678" />
                                <p v-if="form.errors.phone" class="mt-1 text-xs text-rose-500">{{ form.errors.phone }}</p>
                            </div>

                            <div class="space-y-1 sm:col-span-2">
                                <label class="text-[10px] font-black uppercase tracking-widest text-gray-400">Email</label>
                                <input v-model="form.email" type="email"
                                    class="w-full rounded-2xl border border-gray-200 bg-gray-50 px-4 py-3 text-sm font-medium text-gray-900 outline-none transition-all focus:border-[#FF7A00] focus:bg-white focus:ring-2 focus:ring-[#FF7A00]/20"
                                    placeholder="correo@ejemplo.com" />
                                <p v-if="form.errors.email" class="mt-1 text-xs text-rose-500">{{ form.errors.email }}</p>
                            </div>

                            <div class="space-y-1 sm:col-span-2">
                                <label class="text-[10px] font-black uppercase tracking-widest text-gray-400">Límite de crédito (Opcional)</label>
                                <div class="relative">
                                    <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-4">
                                        <span class="text-sm font-bold text-gray-400">$</span>
                                    </div>
                                    <input v-model="form.max_credit_limit" type="number" min="0"
                                        class="w-full rounded-2xl border border-gray-200 bg-gray-50 py-3 pl-8 pr-4 text-sm font-medium text-gray-900 outline-none transition-all focus:border-[#FF7A00] focus:bg-white focus:ring-2 focus:ring-[#FF7A00]/20"
                                        placeholder="0" />
                                </div>
                                <p v-if="form.errors.max_credit_limit" class="mt-1 text-xs text-rose-500">{{ form.errors.max_credit_limit }}</p>
                            </div>
                        </div>

                        <div class="mt-6 flex justify-end gap-3 border-t border-gray-100 pt-4">
                            <button type="button" @click="isCreateModalOpen = false"
                                class="rounded-2xl px-5 py-3 text-sm font-bold text-gray-500 transition-colors hover:bg-gray-100">
                                Cancelar
                            </button>
                            <button type="submit" :disabled="form.processing"
                                class="inline-flex items-center justify-center rounded-2xl bg-[#FF7A00] px-6 py-3 text-sm font-black text-white transition-all hover:bg-[#CC6200] disabled:opacity-50">
                                <svg v-if="form.processing" class="mr-2 h-4 w-4 animate-spin text-white" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor"
                                        d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                {{ form.processing ? 'Guardando...' : 'Crear Cliente' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </TallerLayout>
</template>
