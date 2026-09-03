<script setup>
import { ref, watch, computed } from 'vue';
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import TallerLayout from '@/Layouts/TallerLayout.vue';
import { useTenantRouting } from '@/composables/useTenantRouting';
import { useFormatting } from '@/composables/useFormatting';

const page = usePage();
const { tenantRouteParams } = useTenantRouting();
const { formatCurrency } = useFormatting();

const props = defineProps({
    services: Object,
    taxName: {
        type: String,
        default: 'IVA',
    },
    defaultTaxRate: {
        type: Number,
        default: 19,
    },
    filters: Object,
});

const search = ref(props.filters?.search || '');
const showModal = ref(false);
const showImportModal = ref(false);
const importInput = ref(null);
const editingService = ref(null);
const showDeleteConfirm = ref(null);

const flash = computed(() => page.props.flash ?? {});
const importSummary = computed(() => flash.value.import_summary?.kind === 'services' ? flash.value.import_summary : null);
const visibleImportErrors = computed(() => importSummary.value?.errors?.slice(0, 5) ?? []);

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
    tax_included: false,
    estimated_minutes: 0,
    is_active: true,
});

const importForm = useForm({
    workbook: null,
});

const modalTitle = computed(() => editingService.value ? 'Editar Servicio' : 'Nuevo Servicio');

const openCreateModal = () => {
    editingService.value = null;
    form.reset();
    form.tax_included = false;
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
    form.tax_included = Boolean(service.tax_included);
    form.estimated_minutes = service.estimated_minutes;
    form.is_active = Boolean(service.is_active);
    form.clearErrors();
    showModal.value = true;
};

const openImportModal = () => {
    importForm.reset();
    importForm.clearErrors();
    showImportModal.value = true;
};

const closeImportModal = () => {
    showImportModal.value = false;
    importForm.reset();
    importForm.clearErrors();
};

const handleWorkbookChange = (event) => {
    const file = event.target.files?.[0] ?? null;
    importForm.workbook = file;
};

const handleImportSubmit = () => {
    importForm.post(route('services.import', tenantRouteParams.value), {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => {
            closeImportModal();
        },
    });
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
</script>

<template>
    <Head title="Servicios" />

    <TallerLayout>
        <!-- Import Summary Flash Message -->
        <div v-if="importSummary" class="mb-6 rounded-2xl border p-4 transition-all"
            :class="importSummary.error_rows > 0 ? 'border-amber-200 bg-amber-50 text-amber-900' : 'border-emerald-200 bg-emerald-50 text-emerald-900'">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <p class="text-xs font-black uppercase tracking-widest">Resumen de Importación de Servicios</p>
                    <p class="mt-1 text-xs font-medium">
                        {{ importSummary.processed_rows }} procesadas ({{ importSummary.created_services }} creados, {{ importSummary.updated_services }} actualizados),
                        {{ importSummary.skipped_rows }} omitidas, {{ importSummary.error_rows }} con error.
                    </p>
                    <ul v-if="visibleImportErrors.length" class="mt-2 space-y-1 text-xs font-medium">
                        <li v-for="err in visibleImportErrors" :key="err.row">
                            Fila {{ err.row }}: {{ err.message }}
                        </li>
                    </ul>
                </div>
            </div>
        </div>

        <div class="mb-8 flex flex-col justify-between gap-4 md:flex-row md:items-center">
            <div>
                <h1 class="text-3xl font-black uppercase tracking-tight text-gray-900">Servicios</h1>
                <p class="mt-1 text-sm font-medium text-gray-500">Catálogo comercial para diagnósticos, mano de obra y reparaciones.</p>
            </div>

            <div class="flex flex-wrap items-center gap-3">
                <div class="relative w-full md:w-72">
                    <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-4">
                        <svg class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                    </div>
                    <input
                        v-model="search"
                        type="text"
                        placeholder="Buscar servicio..."
                        class="w-full rounded-2xl border border-gray-200 bg-white py-3 pl-11 pr-4 text-sm font-medium text-gray-900 shadow-sm outline-none transition-all focus:border-[#FF7A00] focus:ring-2 focus:ring-[#FF7A00]/50"
                    />
                </div>

                <!-- Descargar Plantilla -->
                <a
                    :href="route('services.import.template', tenantRouteParams)"
                    class="flex shrink-0 items-center gap-2 rounded-2xl border border-gray-200 bg-white px-4 py-3 text-xs font-bold uppercase tracking-wider text-gray-700 shadow-sm transition-all hover:bg-gray-50 active:scale-95"
                    title="Descargar plantilla Excel para importar servicios"
                >
                    <svg class="h-4 w-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                    </svg>
                    Plantilla
                </a>

                <!-- Botón Importar -->
                <button
                    type="button"
                    class="flex shrink-0 items-center gap-2 rounded-2xl border border-gray-200 bg-white px-4 py-3 text-xs font-bold uppercase tracking-wider text-gray-700 shadow-sm transition-all hover:bg-gray-50 active:scale-95"
                    @click="openImportModal"
                >
                    <svg class="h-4 w-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l4-4m0 0l4 4m-4-4v12" />
                    </svg>
                    Importar
                </button>

                <button
                    type="button"
                    data-tour="services-add"
                    class="flex shrink-0 items-center gap-2 rounded-2xl bg-[#FF7A00] px-5 py-3 text-sm font-bold uppercase tracking-wide text-white shadow-sm transition-all active:scale-95 hover:bg-[#CC6200]"
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
                            <th class="px-6 py-4 text-right">Precio Venta</th>
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
                            <td class="px-6 py-4 text-right">
                                <div class="text-sm font-bold text-gray-900">{{ formatCurrency(service.selling_price) }}</div>
                                <span
                                    :class="service.tax_included ? 'bg-orange-50 text-orange-600 border-orange-200' : 'bg-gray-50 text-gray-500 border-gray-200'"
                                    class="inline-block mt-0.5 text-[9px] font-black uppercase tracking-wider px-1.5 py-0.5 rounded border"
                                >
                                    {{ service.tax_included ? `${taxName} incl.` : `+ ${taxName}` }}
                                </span>
                            </td>
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
                        :class="link.active ? 'bg-[#FF7A00] text-white shadow-sm' : 'text-gray-500 hover:bg-gray-100'"
                        @click.prevent="router.get(link.url, {}, { preserveState: true })"
                    />
                    <span v-else v-html="link.label" class="px-3 py-1.5 text-sm font-medium text-gray-400"></span>
                </template>
            </div>
        </div>

        <!-- Modal Crear/Editar Servicio -->
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
                        <input v-model="form.name" type="text" class="w-full rounded-2xl border border-gray-300 px-5 py-3.5 text-sm font-bold text-gray-900 shadow-sm outline-none transition-all focus:border-transparent focus:ring-2 focus:ring-[#FF7A00]" placeholder="Ej: Diagnóstico computarizado" />
                        <p v-if="form.errors.name" class="ml-1 text-[10px] font-medium text-red-500">{{ form.errors.name }}</p>
                    </div>

                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div class="space-y-1.5">
                            <label class="ml-1 block text-[9px] font-bold uppercase tracking-widest text-gray-400">Código</label>
                            <input v-model="form.code" type="text" class="w-full rounded-2xl border border-gray-300 px-5 py-3.5 font-mono text-sm font-bold text-gray-900 shadow-sm outline-none transition-all focus:border-transparent focus:ring-2 focus:ring-[#FF7A00]" placeholder="SERV-001" />
                            <p v-if="form.errors.code" class="ml-1 text-[10px] font-medium text-red-500">{{ form.errors.code }}</p>
                        </div>
                        <div class="space-y-1.5">
                            <label class="ml-1 block text-[9px] font-bold uppercase tracking-widest text-gray-400">Duración Estimada (min)</label>
                            <input v-model.number="form.estimated_minutes" type="number" min="0" class="w-full rounded-2xl border border-gray-300 px-5 py-3.5 text-sm font-bold text-gray-900 shadow-sm outline-none transition-all focus:border-transparent focus:ring-2 focus:ring-[#FF7A00]" placeholder="60" />
                            <p v-if="form.errors.estimated_minutes" class="ml-1 text-[10px] font-medium text-red-500">{{ form.errors.estimated_minutes }}</p>
                        </div>
                    </div>

                    <div class="space-y-1.5">
                        <label class="ml-1 block text-[9px] font-bold uppercase tracking-widest text-gray-400">Descripción</label>
                        <textarea v-model="form.description" rows="3" class="w-full rounded-2xl border border-gray-300 px-5 py-3.5 text-sm font-medium text-gray-900 shadow-sm outline-none transition-all focus:border-transparent focus:ring-2 focus:ring-[#FF7A00]" placeholder="Detalle opcional del servicio"></textarea>
                        <p v-if="form.errors.description" class="ml-1 text-[10px] font-medium text-red-500">{{ form.errors.description }}</p>
                    </div>

                    <!-- Precios e Impuesto -->
                    <div class="space-y-3">
                        <div class="flex items-center justify-between">
                            <label class="ml-1 block text-[9px] font-bold uppercase tracking-widest text-gray-400">Precios y {{ taxName }}</label>
                            <!-- Selector Tipo de Precio -->
                            <div class="inline-flex rounded-xl bg-gray-100 p-0.5 border border-gray-200">
                                <button
                                    type="button"
                                    class="rounded-lg px-2.5 py-1 text-[10px] font-black uppercase tracking-wider transition-all"
                                    :class="!form.tax_included ? 'bg-white text-gray-900 shadow-sm' : 'text-gray-500 hover:text-gray-900'"
                                    @click="form.tax_included = false"
                                >
                                    + {{ taxName }} (Neto)
                                </button>
                                <button
                                    type="button"
                                    class="rounded-lg px-2.5 py-1 text-[10px] font-black uppercase tracking-wider transition-all"
                                    :class="form.tax_included ? 'bg-[#FF7A00] text-white shadow-sm' : 'text-gray-500 hover:text-gray-900'"
                                    @click="form.tax_included = true"
                                >
                                    Con {{ taxName }} (Bruto)
                                </button>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <div class="space-y-1.5">
                                <label class="ml-1 block text-[9px] font-bold uppercase tracking-widest text-gray-400">Costo ($)</label>
                                <input v-model.number="form.cost_price" type="number" min="0" class="w-full rounded-2xl border border-gray-300 px-5 py-3.5 text-sm font-bold text-gray-900 shadow-sm outline-none transition-all focus:border-transparent focus:ring-2 focus:ring-[#FF7A00]" />
                                <p v-if="form.errors.cost_price" class="ml-1 text-[10px] font-medium text-red-500">{{ form.errors.cost_price }}</p>
                            </div>
                            <div class="space-y-1.5">
                                <label class="ml-1 block text-[9px] font-bold uppercase tracking-widest text-gray-400">
                                    Precio Venta ($) <span class="text-[#FF7A00]">{{ form.tax_included ? `(Con ${taxName})` : `(+ ${taxName})` }}</span>
                                </label>
                                <input v-model.number="form.selling_price" type="number" min="0" class="w-full rounded-2xl border border-gray-300 px-5 py-3.5 text-sm font-bold text-gray-900 shadow-sm outline-none transition-all focus:border-transparent focus:ring-2 focus:ring-[#FF7A00]" />
                                <p v-if="form.errors.selling_price" class="ml-1 text-[10px] font-medium text-red-500">{{ form.errors.selling_price }}</p>
                                <p v-if="Number(form.selling_price) > 0" class="text-[11px] font-semibold text-gray-400 ml-1">
                                    <span v-if="form.tax_included">
                                        Neto estimado: <strong class="text-gray-700 font-mono">{{ formatCurrency(Math.round(form.selling_price / (1 + (defaultTaxRate / 100)))) }}</strong>
                                    </span>
                                    <span v-else>
                                        Total con {{ taxName }} ({{ defaultTaxRate }}%): <strong class="text-orange-600 font-mono">{{ formatCurrency(Math.round(form.selling_price * (1 + (defaultTaxRate / 100)))) }}</strong>
                                    </span>
                                </p>
                            </div>
                        </div>
                    </div>

                    <label class="flex items-center justify-between rounded-2xl border border-gray-200 bg-gray-50 px-4 py-3">
                        <span class="text-xs font-bold uppercase tracking-widest text-gray-500">Servicio activo</span>
                        <input v-model="form.is_active" type="checkbox" class="h-4 w-4 rounded border-gray-300 text-[#FF7A00] focus:ring-[#FF7A00]" />
                    </label>

                    <button type="submit" class="w-full rounded-2xl bg-gray-900 py-3.5 text-sm font-black uppercase tracking-wide text-white transition-colors hover:bg-[#FF7A00]" :disabled="form.processing">
                        {{ form.processing ? 'Guardando...' : 'Guardar Servicio' }}
                    </button>
                </form>
            </div>
        </div>

        <!-- Modal Importar Servicios -->
        <div v-if="showImportModal" class="fixed inset-0 z-[100] flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-slate-900/40 backdrop-blur-sm" @click="closeImportModal"></div>

            <div class="relative w-full max-w-2xl bg-white border border-gray-100 rounded-[2.5rem] shadow-[0_32px_64px_rgba(0,0,0,0.1)]">
                <div class="p-6 lg:p-8 border-b border-gray-50 flex justify-between items-center bg-gray-50/50">
                    <div>
                        <h2 class="text-2xl font-black text-gray-900 tracking-tight uppercase">Importar servicios</h2>
                        <p class="text-xs font-medium text-gray-400 mt-1">Carga tu catálogo comercial de servicios desde Excel o CSV.</p>
                    </div>
                    <button @click="closeImportModal"
                        class="w-10 h-10 rounded-full bg-white flex items-center justify-center text-gray-400 hover:text-gray-600 hover:bg-gray-100 transition-all border border-gray-200 shadow-sm">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <form @submit.prevent="handleImportSubmit" class="p-6 lg:p-8 space-y-6">
                    <div class="rounded-2xl border border-gray-100 bg-gray-50/70 p-5 space-y-3">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-bold text-gray-700 uppercase tracking-wider">Instrucciones</span>
                            <a :href="route('services.import.template', tenantRouteParams)"
                                class="text-xs font-black text-[#FF7A00] hover:text-[#CC6200] transition-colors">
                                Descargar plantilla de ejemplo
                            </a>
                        </div>
                        <ul class="text-xs text-gray-500 space-y-1 list-disc list-inside">
                            <li>Columnas admitidas: <strong>codigo, nombre, descripcion, costo, precio_venta, impuesto, minutos_estimados, activo</strong>.</li>
                            <li>En la columna <strong>impuesto</strong> puedes indicar <code class="bg-gray-200 px-1 rounded">con_iva</code> para precios brutos o <code class="bg-gray-200 px-1 rounded">mas_iva</code> para precios netos.</li>
                            <li>Si el código o nombre ya existe, se actualizarán los datos del servicio.</li>
                        </ul>
                    </div>

                    <div class="space-y-2">
                        <label class="block text-[9px] font-bold text-gray-400 uppercase tracking-widest ml-1">Archivo Excel o CSV</label>
                        <input
                            ref="importInput"
                            type="file"
                            accept=".xlsx,.xls,.csv"
                            @change="handleWorkbookChange"
                            class="w-full text-xs text-gray-600 file:mr-4 file:py-2.5 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-black file:uppercase file:tracking-wider file:bg-gray-900 file:text-white hover:file:bg-[#FF7A00] file:transition-colors cursor-pointer border border-gray-200 rounded-2xl p-2"
                        />
                        <p v-if="importForm.errors.workbook" class="text-red-500 text-xs font-medium ml-1">
                            {{ importForm.errors.workbook }}
                        </p>
                    </div>

                    <div class="flex gap-3 pt-2">
                        <button type="button" @click="closeImportModal"
                            class="flex-1 py-3 bg-gray-100 hover:bg-gray-200 text-gray-600 rounded-2xl font-bold uppercase tracking-wider text-xs transition-all">
                            Cancelar
                        </button>
                        <button type="submit"
                            :disabled="importForm.processing || !importForm.workbook"
                            class="flex-[2] py-3 bg-[#FF7A00] hover:bg-[#CC6200] text-white rounded-2xl font-black uppercase tracking-wider text-xs shadow-sm transition-all disabled:opacity-50">
                            {{ importForm.processing ? 'Importando...' : 'Iniciar Importación' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </TallerLayout>
</template>
