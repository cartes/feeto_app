<script setup>
import { ref, watch, computed } from 'vue';
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import TallerLayout from '@/Layouts/TallerLayout.vue';

const page = usePage();
const tenantRouteParams = computed(() => page.props.tenant?.slug ? { tenantBySlug: page.props.tenant.slug } : {});

const props = defineProps({
    services: Object,
    filters: Object,
});

const search = ref(props.filters?.search || '');
const showModal = ref(false);
const editingService = ref(null);
const showDeleteConfirm = ref(null);

let searchTimeout;
watch(search, (value) => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        router.get(route('services.index', tenantRouteParams.value), { search: value }, {
            preserveState: true,
            replace: true,
        });
    }, 300);
});

const form = useForm({
    name: '',
    code: '',
    description: '',
    cost_price: 0,
    selling_price: 0,
    estimated_minutes: 0,
    is_active: true,
});

const modalTitle = computed(() => editingService.value ? 'Editar Servicio' : 'Nuevo Servicio');

const openCreateModal = () => {
    editingService.value = null;
    form.reset();
    form.is_active = true;
    form.clearErrors();
    showModal.value = true;
};

const openEditModal = (service) => {
    editingService.value = service;
    form.name = service.name;
    form.code = service.code || '';
    form.description = service.description || '';
    form.cost_price = service.cost_price;
    form.selling_price = service.selling_price;
    form.estimated_minutes = service.estimated_minutes;
    form.is_active = service.is_active;
    form.clearErrors();
    showModal.value = true;
};

const handleSubmit = () => {
    if (editingService.value) {
        form.put(route('services.update', { ...tenantRouteParams.value, service: editingService.value.id }), {
            preserveScroll: true,
            onSuccess: () => { showModal.value = false; },
        });
        return;
    }

    form.post(route('services.store', tenantRouteParams.value), {
        preserveScroll: true,
        onSuccess: () => { showModal.value = false; },
    });
};

const handleDelete = (service) => {
    router.delete(route('services.destroy', { ...tenantRouteParams.value, service: service.id }), {
        preserveScroll: true,
        onSuccess: () => { showDeleteConfirm.value = null; },
    });
};

const formatCurrency = (value) => new Intl.NumberFormat('es-CL', {
    style: 'currency',
    currency: 'CLP',
    minimumFractionDigits: 0,
}).format(Number(value || 0));
</script>

<template>
    <Head title="Servicios" />

    <TallerLayout>
        <div class="mb-8 flex flex-col justify-between gap-4 md:flex-row md:items-center">
            <div>
                <h1 class="text-3xl font-black uppercase tracking-tight text-gray-900">Servicios</h1>
                <p class="mt-1 text-sm font-medium text-gray-500">Catálogo comercial para diagnósticos, mano de obra y reparaciones.</p>
            </div>

            <div class="flex items-center gap-3">
                <div class="relative w-full md:w-80">
                    <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-4">
                        <svg class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                    </div>
                    <input
                        v-model="search"
                        type="text"
                        placeholder="Buscar por nombre o código..."
                        class="w-full rounded-2xl border border-gray-200 bg-white py-3 pl-11 pr-4 text-sm font-medium text-gray-900 shadow-sm outline-none transition-all focus:border-[#F9A826] focus:ring-2 focus:ring-[#F9A826]/50"
                    />
                </div>

                <button
                    type="button"
                    class="flex shrink-0 items-center gap-2 rounded-2xl bg-[#F9A826] px-5 py-3 text-sm font-bold uppercase tracking-wide text-white shadow-sm transition-all active:scale-95 hover:bg-[#E59A22]"
                    @click="openCreateModal"
                >
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4" />
                    </svg>
                    Agregar
                </button>
            </div>
        </div>

        <div class="overflow-hidden rounded-[2rem] border border-gray-100 bg-white shadow-sm">
            <div v-if="services.data.length === 0" class="p-12 text-center">
                <div class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-2xl bg-gray-50">
                    <svg class="h-8 w-8 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19.428 15.428a4 4 0 00-5.656 0L6 23m13.428-7.572l-1.414-1.414m0 0L14 10l-4 4m8 0l-4-4m0 0L9.172 5.172a4 4 0 00-5.656 5.656L10 17.314" />
                    </svg>
                </div>
                <h3 class="text-lg font-bold text-gray-900">Sin servicios registrados</h3>
                <p class="mt-1 text-sm text-gray-500">Agrega tu primer servicio para usarlo en las cotizaciones.</p>
            </div>

            <div v-else class="overflow-x-auto">
                <table class="w-full border-collapse text-left">
                    <thead>
                        <tr class="border-b border-gray-100 bg-gray-50/50 text-[10px] font-black uppercase tracking-widest text-gray-400">
                            <th class="px-6 py-4">Servicio</th>
                            <th class="px-6 py-4">Código</th>
                            <th class="px-6 py-4 text-right">Costo</th>
                            <th class="px-6 py-4 text-right">Venta</th>
                            <th class="px-6 py-4 text-center">Duración</th>
                            <th class="px-6 py-4 text-center">Estado</th>
                            <th class="px-6 py-4 text-right">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        <tr v-for="service in services.data" :key="service.id" class="group transition-colors hover:bg-gray-50/30">
                            <td class="px-6 py-4">
                                <div class="text-sm font-bold text-gray-900">{{ service.name }}</div>
                                <div v-if="service.description" class="mt-0.5 max-w-xs line-clamp-1 text-xs text-gray-400">{{ service.description }}</div>
                            </td>
                            <td class="px-6 py-4">
                                <span class="rounded-lg border border-gray-100 bg-gray-50 px-2 py-1 font-mono text-xs font-bold text-gray-500">
                                    {{ service.code || 'SIN-CODIGO' }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-right text-sm font-medium text-gray-600">{{ formatCurrency(service.cost_price) }}</td>
                            <td class="px-6 py-4 text-right text-sm font-bold text-gray-900">{{ formatCurrency(service.selling_price) }}</td>
                            <td class="px-6 py-4 text-center text-sm font-bold text-gray-700">{{ service.estimated_minutes }} min</td>
                            <td class="px-6 py-4 text-center">
                                <span :class="service.is_active ? 'border-emerald-200 bg-emerald-50 text-emerald-600' : 'border-gray-200 bg-gray-50 text-gray-500'" class="rounded-full border px-2 py-1 text-[9px] font-black uppercase tracking-widest">
                                    {{ service.is_active ? 'Activo' : 'Inactivo' }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <div class="flex items-center justify-end gap-2 opacity-0 transition-opacity group-hover:opacity-100">
                                    <button
                                        type="button"
                                        class="rounded-xl bg-gray-100 p-2 text-gray-500 transition-colors hover:bg-blue-50 hover:text-blue-600"
                                        @click="openEditModal(service)"
                                    >
                                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                        </svg>
                                    </button>
                                    <button
                                        type="button"
                                        class="rounded-xl bg-gray-100 p-2 text-gray-500 transition-colors hover:bg-red-50 hover:text-red-600"
                                        @click="showDeleteConfirm = service.id"
                                    >
                                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                        </svg>
                                    </button>

                                    <div v-if="showDeleteConfirm === service.id" class="absolute right-8 z-50 mt-20 w-56 rounded-2xl border border-gray-200 bg-white p-4 shadow-xl">
                                        <p class="mb-3 text-xs font-bold text-gray-800">¿Eliminar este servicio?</p>
                                        <div class="flex gap-2">
                                            <button type="button" class="flex-1 rounded-xl bg-gray-100 py-2 text-xs font-bold text-gray-600 transition-colors hover:bg-gray-200" @click="showDeleteConfirm = null">No</button>
                                            <button type="button" class="flex-1 rounded-xl bg-red-500 py-2 text-xs font-bold text-white transition-colors hover:bg-red-600" @click="handleDelete(service)">Sí, Eliminar</button>
                                        </div>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div v-if="services.links && services.links.length > 3" class="flex flex-wrap items-center justify-center gap-1 border-t border-gray-100 px-6 py-4">
                <template v-for="(link, index) in services.links" :key="index">
                    <a
                        v-if="link.url"
                        :href="link.url"
                        v-html="link.label"
                        class="rounded-lg px-3 py-1.5 text-sm font-medium transition-colors"
                        :class="link.active ? 'bg-[#F9A826] text-white shadow-sm' : 'text-gray-500 hover:bg-gray-100'"
                        @click.prevent="router.get(link.url, {}, { preserveState: true })"
                    />
                    <span v-else v-html="link.label" class="px-3 py-1.5 text-sm font-medium text-gray-400"></span>
                </template>
            </div>
        </div>

        <div v-if="showModal" class="fixed inset-0 z-[100] flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-slate-900/40 backdrop-blur-sm" @click="showModal = false"></div>

            <div class="relative w-full max-w-lg overflow-y-auto rounded-[2.5rem] border border-gray-100 bg-white shadow-[0_32px_64px_rgba(0,0,0,0.1)]">
                <div class="flex items-center justify-between border-b border-gray-50 bg-gray-50/50 p-6 lg:p-8">
                    <div>
                        <h2 class="text-2xl font-black uppercase tracking-tight text-gray-900">{{ modalTitle }}</h2>
                        <p class="mt-1 text-xs font-medium text-gray-400">Completa los datos del servicio comercial.</p>
                    </div>
                    <button
                        type="button"
                        class="flex h-10 w-10 items-center justify-center rounded-full border border-gray-200 bg-white text-gray-400 shadow-sm transition-all hover:bg-gray-100 hover:text-gray-600"
                        @click="showModal = false"
                    >
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <form class="space-y-6 p-6 lg:p-8" @submit.prevent="handleSubmit">
                    <div class="space-y-1.5">
                        <label class="ml-1 block text-[9px] font-bold uppercase tracking-widest text-gray-400">Nombre del Servicio</label>
                        <input v-model="form.name" type="text" class="w-full rounded-2xl border border-gray-300 px-5 py-3.5 text-sm font-bold text-gray-900 shadow-sm outline-none transition-all focus:border-transparent focus:ring-2 focus:ring-[#F9A826]" placeholder="Ej: Diagnóstico computarizado" />
                        <p v-if="form.errors.name" class="ml-1 text-[10px] font-medium text-red-500">{{ form.errors.name }}</p>
                    </div>

                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div class="space-y-1.5">
                            <label class="ml-1 block text-[9px] font-bold uppercase tracking-widest text-gray-400">Código</label>
                            <input v-model="form.code" type="text" class="w-full rounded-2xl border border-gray-300 px-5 py-3.5 font-mono text-sm font-bold text-gray-900 shadow-sm outline-none transition-all focus:border-transparent focus:ring-2 focus:ring-[#F9A826]" placeholder="SERV-001" />
                            <p v-if="form.errors.code" class="ml-1 text-[10px] font-medium text-red-500">{{ form.errors.code }}</p>
                        </div>
                        <div class="space-y-1.5">
                            <label class="ml-1 block text-[9px] font-bold uppercase tracking-widest text-gray-400">Duración Estimada</label>
                            <input v-model.number="form.estimated_minutes" type="number" min="0" class="w-full rounded-2xl border border-gray-300 px-5 py-3.5 text-sm font-bold text-gray-900 shadow-sm outline-none transition-all focus:border-transparent focus:ring-2 focus:ring-[#F9A826]" placeholder="60" />
                            <p v-if="form.errors.estimated_minutes" class="ml-1 text-[10px] font-medium text-red-500">{{ form.errors.estimated_minutes }}</p>
                        </div>
                    </div>

                    <div class="space-y-1.5">
                        <label class="ml-1 block text-[9px] font-bold uppercase tracking-widest text-gray-400">Descripción</label>
                        <textarea v-model="form.description" rows="3" class="w-full rounded-2xl border border-gray-300 px-5 py-3.5 text-sm font-medium text-gray-900 shadow-sm outline-none transition-all focus:border-transparent focus:ring-2 focus:ring-[#F9A826]" placeholder="Detalle opcional del servicio"></textarea>
                        <p v-if="form.errors.description" class="ml-1 text-[10px] font-medium text-red-500">{{ form.errors.description }}</p>
                    </div>

                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div class="space-y-1.5">
                            <label class="ml-1 block text-[9px] font-bold uppercase tracking-widest text-gray-400">Costo</label>
                            <input v-model.number="form.cost_price" type="number" min="0" class="w-full rounded-2xl border border-gray-300 px-5 py-3.5 text-sm font-bold text-gray-900 shadow-sm outline-none transition-all focus:border-transparent focus:ring-2 focus:ring-[#F9A826]" />
                            <p v-if="form.errors.cost_price" class="ml-1 text-[10px] font-medium text-red-500">{{ form.errors.cost_price }}</p>
                        </div>
                        <div class="space-y-1.5">
                            <label class="ml-1 block text-[9px] font-bold uppercase tracking-widest text-gray-400">Precio Venta</label>
                            <input v-model.number="form.selling_price" type="number" min="0" class="w-full rounded-2xl border border-gray-300 px-5 py-3.5 text-sm font-bold text-gray-900 shadow-sm outline-none transition-all focus:border-transparent focus:ring-2 focus:ring-[#F9A826]" />
                            <p v-if="form.errors.selling_price" class="ml-1 text-[10px] font-medium text-red-500">{{ form.errors.selling_price }}</p>
                        </div>
                    </div>

                    <label class="flex items-center justify-between rounded-2xl border border-gray-200 bg-gray-50 px-4 py-3">
                        <span class="text-xs font-bold uppercase tracking-widest text-gray-500">Servicio activo</span>
                        <input v-model="form.is_active" type="checkbox" class="h-4 w-4 rounded border-gray-300 text-[#F9A826] focus:ring-[#F9A826]" />
                    </label>

                    <button type="submit" class="w-full rounded-2xl bg-gray-900 py-3.5 text-sm font-black uppercase tracking-wide text-white transition-colors hover:bg-[#F9A826]" :disabled="form.processing">
                        {{ form.processing ? 'Guardando...' : 'Guardar Servicio' }}
                    </button>
                </form>
            </div>
        </div>
    </TallerLayout>
</template>
