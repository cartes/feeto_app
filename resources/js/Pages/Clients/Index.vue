<script setup>
import { computed, ref, watch } from 'vue';
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import TallerLayout from '@/Layouts/TallerLayout.vue';
import { useTenantRouting } from '@/composables/useTenantRouting';
import { useFormatting } from '@/composables/useFormatting';
import { useDebounce } from '@/composables/useDebounce';
import { useIdentification } from '@/composables/useIdentification';

const page = usePage();
const props = defineProps({
    clients: Object,
    filters: Object,
});

const { tenantRouteParams } = useTenantRouting();
const { formatCurrency, formatDate } = useFormatting();
const { debounce } = useDebounce();
const {
    cleanIdentification,
    formatIdentification,
    validateIdentification,
    getCountryConfig,
} = useIdentification();

const tenantCountry = computed(() => page.props.tenantContext?.country || 'CL');
const countryConfig = computed(() => getCountryConfig(tenantCountry.value));
const docLabel = computed(() => countryConfig.value.docName);
const docPlaceholder = computed(() => countryConfig.value.placeholder);

const search = ref(props.filters.search || '');
const isCreateModalOpen = ref(false);
const isImportModalOpen = ref(false);
const importInput = ref(null);
const flash = computed(() => page.props.flash ?? {});
const importSummary = computed(() => flash.value.import_summary?.kind === 'clients' ? flash.value.import_summary : null);
const visibleImportErrors = computed(() => importSummary.value?.errors?.slice(0, 5) ?? []);

const form = useForm({
    name: '',
    rut: '',
    phone: '',
    email: '',
    max_credit_limit: '',
});

const isFormRutValid = computed(() => {
    if (!form.rut) return null;
    return validateIdentification(form.rut, tenantCountry.value);
});

watch(() => form.rut, (newVal) => {
    if (!newVal) return;
    const formatted = formatIdentification(newVal, tenantCountry.value);
    if (formatted !== newVal) {
        form.rut = formatted;
    }
});

const importForm = useForm({
    workbook: null,
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

const closeImportModal = () => {
    isImportModalOpen.value = false;
    importForm.reset();
    importForm.clearErrors();

    if (importInput.value) {
        importInput.value.value = '';
    }
};

const handleImportFileChange = (event) => {
    importForm.workbook = event.target.files?.[0] ?? null;
};

const submitImport = () => {
    importForm.post(route('clients.import', tenantRouteParams.value), {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => closeImportModal(),
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
                        <input v-model="search" type="text" :placeholder="'Buscar por nombre o ' + docLabel + '...'"
                            data-support="clients-search"
                            class="w-full rounded-2xl border border-gray-200 bg-white py-3 pl-11 pr-4 text-sm font-medium text-gray-900 shadow-sm transition-all placeholder:text-gray-400 focus:border-[#FF7A00] focus:outline-none focus:ring-2 focus:ring-[#FF7A00]/50" />
                    </div>

                    <button @click="isImportModalOpen = true"
                        class="inline-flex items-center justify-center gap-2 whitespace-nowrap rounded-2xl border border-gray-200 bg-white px-5 py-3 text-sm font-black text-gray-700 shadow-sm transition-colors hover:bg-gray-50">
                        <svg class="h-5 w-5 text-[#FF7A00]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                        </svg>
                        Importar Excel
                    </button>

                    <button @click="isCreateModalOpen = true" data-tour="clients-add"
                        class="inline-flex items-center justify-center gap-2 whitespace-nowrap rounded-2xl bg-[#FF7A00] px-5 py-3 text-sm font-black text-white shadow-lg shadow-[#FF7A00]/30 transition-all hover:bg-[#FF7A00]/90">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        Nuevo Cliente
                    </button>
                </div>
            </div>

            <!-- Import feedback alert -->
            <div v-if="importSummary"
                class="rounded-3xl border border-emerald-200 bg-emerald-50/70 p-6 space-y-3">
                <div class="flex items-center justify-between">
                    <p class="text-xs font-black uppercase tracking-widest text-emerald-700">Resumen de importación</p>
                    <span class="text-xs font-bold text-emerald-800">{{ importSummary.total_rows }} filas procesadas</span>
                </div>
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 text-xs font-bold text-slate-700">
                    <div class="bg-white rounded-2xl p-3 border border-emerald-100">
                        <p class="text-[10px] text-gray-400 uppercase tracking-wider">Creados</p>
                        <p class="text-base text-emerald-600 font-black">{{ importSummary.created }}</p>
                    </div>
                    <div class="bg-white rounded-2xl p-3 border border-emerald-100">
                        <p class="text-[10px] text-gray-400 uppercase tracking-wider">Actualizados</p>
                        <p class="text-base text-sky-600 font-black">{{ importSummary.updated }}</p>
                    </div>
                    <div class="bg-white rounded-2xl p-3 border border-emerald-100">
                        <p class="text-[10px] text-gray-400 uppercase tracking-wider">Sin cambios</p>
                        <p class="text-base text-gray-600 font-black">{{ importSummary.unchanged }}</p>
                    </div>
                    <div class="bg-white rounded-2xl p-3 border border-emerald-100">
                        <p class="text-[10px] text-gray-400 uppercase tracking-wider">Errores</p>
                        <p class="text-base text-rose-600 font-black">{{ importSummary.failed }}</p>
                    </div>
                </div>
                <div v-if="visibleImportErrors.length" class="space-y-1 pt-1">
                    <p class="text-[10px] font-black uppercase tracking-widest text-rose-700">Primeros errores:</p>
                    <ul class="text-xs text-rose-700 space-y-0.5">
                        <li v-for="(err, idx) in visibleImportErrors" :key="idx">
                            Fila {{ err.row }}: {{ err.message }}
                        </li>
                    </ul>
                </div>
            </div>

            <!-- Tabla de clientes -->
            <div class="overflow-hidden rounded-[2rem] border border-gray-100 bg-white shadow-sm">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="border-b border-gray-100 bg-gray-50/50 text-[10px] font-black uppercase tracking-widest text-gray-400">
                                <th class="px-6 py-4">Cliente / {{ docLabel }}</th>
                                <th class="px-6 py-4">Contacto</th>
                                <th class="px-6 py-4">Métricas</th>
                                <th class="px-6 py-4">Señales CRM</th>
                                <th class="px-6 py-4">Última visita</th>
                                <th class="px-6 py-4 text-right">Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            <tr v-for="client in clients.data" :key="client.id" class="group transition-colors hover:bg-gray-50/40">
                                <td class="px-6 py-4 align-top">
                                    <div class="space-y-1">
                                        <div class="text-sm font-bold text-gray-900">{{ client.name }}</div>
                                        <div class="text-[10px] font-bold uppercase tracking-widest text-gray-400">{{ client.rut }}</div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 align-top text-sm text-gray-500">
                                    <div class="flex flex-col gap-1">
                                        <span v-if="client.phone" class="font-medium text-gray-700">{{ client.phone }}</span>
                                        <span v-if="client.email" class="max-w-[220px] truncate font-medium text-gray-700">{{ client.email }}</span>
                                        <span v-if="!client.phone && !client.email" class="text-xs text-gray-400 italic">Sin datos de contacto</span>
                                    </div>
                                </td>
                                <td class="px-6 py-4 align-top">
                                    <div class="space-y-1 text-xs">
                                        <div class="font-bold text-gray-900">Total gastado: <span class="text-[#FF7A00] font-black">{{ formatCurrency(client.metrics.total_spent) }}</span></div>
                                        <div class="text-gray-500 font-medium">Ticket prom.: {{ formatCurrency(client.metrics.average_ticket) }}</div>
                                        <div class="text-gray-500 font-medium">OTs abiertas: {{ client.metrics.open_work_orders_count }}</div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 align-top">
                                    <div class="flex flex-wrap gap-1.5">
                                        <span v-for="tag in client.crm.tags" :key="tag.label"
                                            class="inline-flex items-center rounded-full border px-2.5 py-0.5 text-[10px] font-black uppercase tracking-widest"
                                            :class="{
                                                'border-emerald-200 bg-emerald-50 text-emerald-700': tag.tone === 'emerald',
                                                'border-amber-200 bg-amber-50 text-amber-700': tag.tone === 'amber',
                                                'border-rose-200 bg-rose-50 text-rose-700': tag.tone === 'rose',
                                                'border-sky-200 bg-sky-50 text-sky-700': tag.tone === 'sky',
                                                'border-gray-200 bg-gray-50 text-gray-600': tag.tone === 'gray',
                                            }">
                                            {{ tag.label }}
                                        </span>
                                    </div>
                                </td>
                                <td class="px-6 py-4 align-top text-xs text-gray-500 font-medium">
                                    {{ formatDate(client.metrics.last_visit_at, 'Sin visitas') }}
                                </td>
                                <td class="px-6 py-4 align-top text-right">
                                    <Link :href="route('clients.show', { ...tenantRouteParams, client: client.id })"
                                        class="inline-flex items-center gap-1 text-xs font-black uppercase tracking-widest text-[#FF7A00] hover:underline">
                                        Ficha CRM
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                        </svg>
                                    </Link>
                                </td>
                            </tr>
                            <tr v-if="clients.data.length === 0">
                                <td colspan="6" class="px-6 py-12 text-center text-sm font-medium text-gray-400">
                                    No se encontraron clientes registrados.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Paginación -->
                <div v-if="clients.links && clients.links.length > 3" class="border-t border-gray-100 px-6 py-4 flex items-center justify-between">
                    <div class="text-xs font-semibold text-gray-400">
                        Mostrando {{ clients.from || 0 }} - {{ clients.to || 0 }} de {{ clients.total }} clientes
                    </div>
                    <div class="flex gap-1">
                        <Component :is="link.url ? Link : 'span'" v-for="(link, i) in clients.links" :key="i"
                            :href="link.url || '#'" v-html="link.label"
                            class="px-3 py-1.5 text-xs font-bold rounded-xl transition-colors"
                            :class="{
                                'bg-[#FF7A00] text-white': link.active,
                                'text-gray-600 hover:bg-gray-100': !link.active && link.url,
                                'text-gray-300 cursor-not-allowed': !link.url,
                            }" />
                    </div>
                </div>
            </div>

            <!-- Modal Nuevo Cliente -->
            <div v-if="isCreateModalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm">
                <div class="w-full max-w-lg rounded-[2.5rem] bg-white p-6 shadow-2xl border border-gray-100 space-y-6 animate-in zoom-in-95 duration-200">
                    <div class="flex items-center justify-between border-b border-gray-50 pb-4">
                        <div class="flex items-center gap-3">
                            <div class="flex h-10 w-10 items-center justify-center rounded-2xl bg-[#FF7A00]/10 text-[#FF7A00]">
                                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" />
                                </svg>
                            </div>
                            <h3 class="text-xl font-black uppercase tracking-tight text-gray-900">Nuevo Cliente</h3>
                        </div>
                        <button type="button" @click="isCreateModalOpen = false" class="text-gray-400 hover:text-gray-600">
                            ✕
                        </button>
                    </div>

                    <form @submit.prevent="submit" class="space-y-4">
                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <div class="space-y-1 sm:col-span-2">
                                <label class="text-[10px] font-black uppercase tracking-widest text-gray-400">Nombre completo *</label>
                                <input v-model="form.name" type="text"
                                    class="w-full rounded-2xl border border-gray-200 bg-gray-50 px-4 py-3 text-sm font-medium text-gray-900 outline-none transition-all focus:border-[#FF7A00] focus:bg-white focus:ring-2 focus:ring-[#FF7A00]/20"
                                    placeholder="Ej. Juan Pérez" required />
                                <p v-if="form.errors.name" class="mt-1 text-xs text-rose-500">{{ form.errors.name }}</p>
                            </div>

                            <div class="space-y-1">
                                <div class="flex items-center justify-between">
                                    <label class="text-[10px] font-black uppercase tracking-widest text-gray-400">{{ docLabel }} *</label>
                                    <span v-if="countryConfig" class="text-[9px] font-bold uppercase tracking-wider text-slate-400">
                                        {{ countryConfig.flag }}
                                    </span>
                                </div>
                                <div class="relative">
                                    <input v-model="form.rut" type="text"
                                        class="w-full rounded-2xl border px-4 py-3 text-sm font-bold text-gray-900 outline-none transition-all uppercase"
                                        :class="[
                                            form.errors.rut
                                                ? 'border-rose-300 bg-rose-50/20 focus:ring-2 focus:ring-rose-400'
                                                : isFormRutValid === true
                                                    ? 'border-emerald-300 bg-emerald-50/10 focus:ring-2 focus:ring-emerald-400'
                                                    : 'border-gray-200 bg-gray-50 focus:border-[#FF7A00] focus:bg-white focus:ring-2 focus:ring-[#FF7A00]/20',
                                        ]"
                                        :placeholder="docPlaceholder" required />
                                    <div v-if="form.rut" class="absolute inset-y-0 right-3 flex items-center">
                                        <span v-if="isFormRutValid === true" class="flex h-5 w-5 items-center justify-center rounded-full bg-emerald-100 text-emerald-600 text-xs font-black">✓</span>
                                        <span v-else-if="isFormRutValid === false && form.rut.length >= 7" class="flex h-5 w-5 items-center justify-center rounded-full bg-amber-100 text-amber-600 text-xs font-black">!</span>
                                    </div>
                                </div>
                                <p v-if="form.errors.rut" class="mt-1 text-xs text-rose-500">{{ form.errors.rut }}</p>
                                <p v-else-if="isFormRutValid === false && form.rut.length >= 7" class="mt-1 text-[9px] font-semibold text-amber-600">
                                    Revise el dígito verificador (ej: {{ docPlaceholder }})
                                </p>
                            </div>

                            <div class="space-y-1">
                                <label class="text-[10px] font-black uppercase tracking-widest text-gray-400">Teléfono</label>
                                <input v-model="form.phone" type="text"
                                    class="w-full rounded-2xl border border-gray-200 bg-gray-50 px-4 py-3 text-sm font-medium text-gray-900 outline-none transition-all focus:border-[#FF7A00] focus:bg-white focus:ring-2 focus:ring-[#FF7A00]/20"
                                    :placeholder="countryConfig.phonePlaceholder" />
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

            <!-- Modal Importar -->
            <div v-if="isImportModalOpen"
                class="fixed inset-0 z-50 flex items-center justify-center overflow-y-auto overflow-x-hidden bg-gray-900/50 p-4 backdrop-blur-sm md:p-0">
                <div class="relative w-full max-w-2xl rounded-[2.5rem] bg-white p-8 shadow-2xl">
                    <button @click="closeImportModal"
                        class="absolute right-6 top-6 text-gray-400 transition-colors hover:text-gray-600">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>

                    <div class="space-y-6">
                        <div>
                            <div class="flex items-center gap-3">
                                <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-[#FF7A00]/10 text-[#FF7A00]">
                                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                                    </svg>
                                </div>
                                <h3 class="text-xl font-black uppercase tracking-tight text-gray-900">Importar clientes y vehiculos</h3>
                            </div>
                            <p class="mt-3 text-sm font-medium text-gray-500">
                                Usa la plantilla para cargar clientes, patentes y datos del vehiculo. Si un RUT o patente ya existe en este taller, la importacion actualiza el registro.
                            </p>
                        </div>

                        <div class="grid gap-4 rounded-[2rem] border border-gray-100 bg-gray-50/70 p-5 md:grid-cols-2">
                            <div class="rounded-2xl bg-white p-4">
                                <p class="text-[10px] font-black uppercase tracking-widest text-gray-400">Plantilla sugerida</p>
                                <p class="mt-2 text-sm font-medium text-gray-600">Incluye columnas para cliente, contacto y multiples patentes.</p>
                                <a :href="route('clients.import.template', tenantRouteParams)"
                                    class="mt-4 inline-flex items-center gap-2 rounded-2xl bg-gray-900 px-4 py-2.5 text-xs font-black uppercase tracking-wide text-white transition-colors hover:bg-gray-800">
                                    Descargar plantilla
                                </a>
                            </div>

                            <div class="rounded-2xl bg-white p-4">
                                <p class="text-[10px] font-black uppercase tracking-widest text-gray-400">Campos esperados</p>
                                <p class="mt-2 text-sm font-medium text-gray-600">Nombre, RUT, teléfono, email, dirección y patente. Marca, modelo, color y VIN son opcionales.</p>
                            </div>
                        </div>

                        <form @submit.prevent="submitImport" class="space-y-4">
                            <div class="space-y-2">
                                <label class="text-[10px] font-black uppercase tracking-widest text-gray-400">Archivo Excel o CSV</label>
                                <input ref="importInput" type="file" accept=".xlsx,.xls,.csv" @change="handleImportFileChange"
                                    class="block w-full rounded-2xl border border-dashed border-gray-300 bg-gray-50 px-4 py-4 text-sm font-medium text-gray-700 file:mr-4 file:rounded-xl file:border-0 file:bg-[#FF7A00] file:px-4 file:py-2 file:text-sm file:font-black file:text-white hover:file:bg-[#CC6200]" />
                                <p class="text-xs font-medium text-gray-500">Acepta archivos .xlsx, .xls y .csv de hasta 10 MB.</p>
                                <p v-if="importForm.errors.workbook" class="text-xs text-rose-500">{{ importForm.errors.workbook }}</p>
                            </div>

                            <div class="flex justify-end gap-3 border-t border-gray-100 pt-4">
                                <button type="button" @click="closeImportModal"
                                    class="rounded-2xl px-5 py-3 text-sm font-bold text-gray-500 transition-colors hover:bg-gray-100">
                                    Cancelar
                                </button>
                                <button type="submit" :disabled="importForm.processing || !importForm.workbook"
                                    class="inline-flex items-center justify-center rounded-2xl bg-[#FF7A00] px-6 py-3 text-sm font-black text-white transition-all hover:bg-[#CC6200] disabled:opacity-50">
                                    <svg v-if="importForm.processing" class="mr-2 h-4 w-4 animate-spin text-white" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor"
                                            d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                    {{ importForm.processing ? 'Importando...' : 'Importar archivo' }}
                                </button>
                            </div>
                        </form>
                    </div>
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
