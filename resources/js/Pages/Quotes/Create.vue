<script setup>
import { computed, reactive, ref, watch } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { useTenantRouting } from '@/composables/useTenantRouting';
import { useDebounce } from '@/composables/useDebounce';
import { MANUAL_SELECTION, useVehicleCatalog } from '@/composables/useVehicleCatalog';
import axios from 'axios';
import TallerLayout from '@/Layouts/TallerLayout.vue';

const props = defineProps({
    vehicleCatalogBrands: {
        type: Array,
        default: () => [],
    },
});

const { tenantRouteParams } = useTenantRouting();
const { debounce } = useDebounce();

const currentStep = ref(1);

const form = useForm({
    client_id: null,
    vehicle_id: null,
    notes: '',
});

// Plate search
const plateSearch = ref('');
const plateResults = ref([]);
const isSearchingPlate = ref(false);
const selectedClient = ref(null);
const selectedVehicle = ref(null);
const hasSearched = ref(false);

// Create client modal
const showCreateModal = ref(false);
const isCreatingClient = ref(false);
const createErrors = ref({});
const createForm = reactive({
    plate: '',
    vehicle_brand_id: null,
    vehicle_model_id: null,
    vehicle_brand: '',
    vehicle_model: '',
    rut: '',
    name: '',
    phone: '',
    secondary_phone: '',
    address: '',
});

const vehicleCatalog = reactive(useVehicleCatalog({
    form: createForm,
    brands: props.vehicleCatalogBrands,
    tenantRouteParams,
    brandField: 'vehicle_brand',
    modelField: 'vehicle_model',
    brandIdField: 'vehicle_brand_id',
    modelIdField: 'vehicle_model_id',
}));

const fetchPlateMatches = async (search) => {
    const normalized = search.trim().toUpperCase().replace(/[^A-Z0-9]/g, '');

    if (normalized.length < 2) {
        plateResults.value = [];
        hasSearched.value = false;
        return;
    }

    isSearchingPlate.value = true;

    try {
        const response = await axios.get(route('quotes.search-plate', tenantRouteParams.value), {
            params: { plate: normalized },
        });
        plateResults.value = response.data.results || [];
        hasSearched.value = true;
    } catch (error) {
        plateResults.value = [];
    } finally {
        isSearchingPlate.value = false;
    }
};

const debouncedFetchPlate = debounce((value) => {
    void fetchPlateMatches(value);
}, 300);

watch(plateSearch, (value) => {
    if (selectedVehicle.value) return;
    debouncedFetchPlate(value);
});

const selectResult = (vehicle) => {
    selectedVehicle.value = vehicle;
    selectedClient.value = vehicle.client;
    form.client_id = vehicle.client.id;
    form.vehicle_id = vehicle.id;
    plateSearch.value = vehicle.plate;
    plateResults.value = [];
    currentStep.value = 2;
};

const clearSelection = () => {
    selectedVehicle.value = null;
    selectedClient.value = null;
    form.client_id = null;
    form.vehicle_id = null;
    plateSearch.value = '';
    plateResults.value = [];
    hasSearched.value = false;
    currentStep.value = 1;
};

const noResultsAndSearched = computed(() => {
    return hasSearched.value && plateResults.value.length === 0 && !isSearchingPlate.value && plateSearch.value.trim().length >= 2;
});

// Modal logic
const openCreateModal = () => {
    createForm.plate = plateSearch.value.trim().toUpperCase().replace(/[^A-Z0-9]/g, '');
    createForm.vehicle_brand_id = null;
    createForm.vehicle_model_id = null;
    createForm.vehicle_brand = '';
    createForm.vehicle_model = '';
    createForm.rut = '';
    createForm.name = '';
    createForm.phone = '';
    createForm.secondary_phone = '';
    createForm.address = '';
    createErrors.value = {};
    vehicleCatalog.reset();
    showCreateModal.value = true;
};

const closeCreateModal = () => {
    showCreateModal.value = false;
};

const submitCreateClient = async () => {
    isCreatingClient.value = true;
    createErrors.value = {};

    try {
        const response = await axios.post(route('quotes.store-client', tenantRouteParams.value), createForm);
        const { client, vehicle } = response.data;

        selectedClient.value = client;
        selectedVehicle.value = { ...vehicle, client };
        form.client_id = client.id;
        form.vehicle_id = vehicle.id;
        plateSearch.value = vehicle.plate;

        showCreateModal.value = false;
        hasSearched.value = false;
        plateResults.value = [];
        currentStep.value = 2;
    } catch (error) {
        if (error.response?.status === 422) {
            createErrors.value = error.response.data.errors || {};
        }
    } finally {
        isCreatingClient.value = false;
    }
};

const goToStep = (step) => {
    if (step === 1) {
        currentStep.value = 1;
    } else if (step === 2 && form.client_id && form.vehicle_id) {
        currentStep.value = 2;
    }
};

const submit = () => {
    form.post(route('quotes.store', tenantRouteParams.value));
};
</script>

<template>
    <Head title="Nueva cotización" />

    <TallerLayout>
        <div class="space-y-6">
            <!-- Header -->
            <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <Link :href="route('quotes.index', tenantRouteParams)"
                        class="inline-flex items-center gap-1 text-xs font-bold uppercase tracking-widest text-gray-400 transition-colors hover:text-[#FF7A00]">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                        </svg>
                        Volver a cotizaciones
                    </Link>
                    <h1 class="mt-3 text-3xl font-black uppercase tracking-tight text-gray-900">Nueva cotización</h1>
                    <p class="mt-1 text-sm font-medium text-gray-500">Busca por patente para comenzar a cotizar.</p>
                </div>
            </div>

            <!-- Main content -->
            <div class="grid gap-6 lg:grid-cols-3">
                <!-- Left: Form Card -->
                <div class="lg:col-span-2">
                    <form @submit.prevent="submit" class="space-y-6 rounded-[2rem] border border-gray-100 bg-white p-6 shadow-sm sm:p-8">
                        <!-- Stepper -->
                        <div class="border-b border-gray-100 pb-6">
                            <div class="flex items-center justify-center gap-4 sm:gap-8">
                                <button type="button" @click="goToStep(1)" class="group flex items-center gap-3 focus:outline-none">
                                    <div class="flex h-10 w-10 items-center justify-center rounded-full border-2 transition-all duration-300"
                                        :class="[
                                            currentStep === 1
                                                ? 'border-[#FF7A00] bg-orange-50 text-[#FF7A00] ring-4 ring-orange-100'
                                                : (form.vehicle_id ? 'border-[#FF7A00] bg-[#FF7A00] text-white' : 'border-gray-200 bg-white text-gray-400')
                                        ]">
                                        <svg v-if="form.vehicle_id && currentStep !== 1" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                        </svg>
                                        <span v-else class="text-sm font-black">1</span>
                                    </div>
                                    <span class="hidden text-[10px] font-black uppercase tracking-widest sm:block"
                                        :class="currentStep === 1 ? 'text-gray-900' : 'text-gray-400 group-hover:text-gray-600'">
                                        Patente & Cliente
                                    </span>
                                </button>

                                <div class="h-0.5 w-12 sm:w-24 transition-all duration-500"
                                    :class="form.vehicle_id ? 'bg-[#FF7A00]' : 'bg-gray-100'"></div>

                                <button type="button" @click="goToStep(2)" :disabled="!form.vehicle_id"
                                    class="group flex items-center gap-3 focus:outline-none disabled:cursor-not-allowed">
                                    <div class="flex h-10 w-10 items-center justify-center rounded-full border-2 transition-all duration-300"
                                        :class="[
                                            currentStep === 2
                                                ? 'border-[#FF7A00] bg-orange-50 text-[#FF7A00] ring-4 ring-orange-100'
                                                : 'border-gray-200 bg-white text-gray-400'
                                        ]">
                                        <span class="text-sm font-black">2</span>
                                    </div>
                                    <span class="hidden text-[10px] font-black uppercase tracking-widest sm:block"
                                        :class="currentStep === 2 ? 'text-gray-900' : 'text-gray-400'">
                                        Confirmación
                                    </span>
                                </button>
                            </div>
                        </div>

                        <!-- STEP 1: PLATE SEARCH -->
                        <div v-if="currentStep === 1" class="space-y-6 animate-fade-in">
                            <div class="space-y-3">
                                <label class="ml-1 block text-[10px] font-black uppercase tracking-widest text-gray-400">Patente del vehículo</label>

                                <div class="relative">
                                    <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-5">
                                        <svg class="h-5 w-5 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                        </svg>
                                    </div>
                                    <input v-model="plateSearch" type="text" :disabled="!!selectedVehicle"
                                        class="w-full rounded-2xl border border-gray-300 bg-white py-4 pl-14 pr-12 text-base font-bold uppercase tracking-widest text-gray-900 placeholder-gray-300 shadow-sm transition-all focus:border-transparent focus:outline-none focus:ring-2 focus:ring-[#FF7A00] disabled:bg-gray-50 disabled:text-gray-500"
                                        placeholder="Ej: BBFL45" />
                                    <div v-if="isSearchingPlate" class="absolute inset-y-0 right-4 flex items-center text-gray-300">
                                        <div class="h-5 w-5 animate-spin rounded-full border-2 border-gray-200 border-t-[#FF7A00]"></div>
                                    </div>
                                </div>

                                <!-- Selected vehicle+client card -->
                                <div v-if="selectedVehicle" class="flex items-start justify-between gap-4 rounded-3xl border border-orange-100 bg-orange-50/30 p-5 shadow-sm">
                                    <div class="min-w-0 space-y-2">
                                        <p class="text-[10px] font-black uppercase tracking-widest text-[#FF7A00]">Vehículo y cliente seleccionados</p>
                                        <div class="flex flex-wrap items-baseline gap-x-4 gap-y-1">
                                            <p class="font-mono text-lg font-black tracking-widest text-slate-900">{{ selectedVehicle.plate }}</p>
                                            <p v-if="selectedVehicle.brand || selectedVehicle.model" class="text-sm font-semibold text-slate-500">
                                                {{ [selectedVehicle.brand, selectedVehicle.model].filter(Boolean).join(' ') }}
                                            </p>
                                        </div>
                                        <div class="flex flex-wrap items-center gap-x-3 gap-y-1 text-xs font-semibold text-slate-500">
                                            <span class="font-bold text-slate-700">{{ selectedClient?.name }}</span>
                                            <span>{{ selectedClient?.rut }}</span>
                                            <span v-if="selectedClient?.phone">{{ selectedClient?.phone }}</span>
                                        </div>
                                    </div>
                                    <button type="button" @click="clearSelection"
                                        class="shrink-0 rounded-full border border-slate-200 bg-white px-4 py-2 text-[10px] font-black uppercase tracking-widest text-slate-500 transition-colors hover:bg-slate-100">
                                        Cambiar
                                    </button>
                                </div>

                                <!-- Search results -->
                                <div v-else-if="plateResults.length > 0" class="overflow-hidden rounded-3xl border border-gray-200 bg-white shadow-sm">
                                    <button v-for="result in plateResults" :key="result.id" type="button" @click="selectResult(result)"
                                        class="flex w-full items-center gap-5 border-b border-gray-100 px-5 py-4 text-left transition-colors last:border-b-0 hover:bg-slate-50">
                                        <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-slate-100">
                                            <svg class="h-6 w-6 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 18.75a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m3 0h6m-9 0H3.375a1.125 1.125 0 01-1.125-1.125V14.25m17.25 4.5a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m3 0H21M3.375 14.25h17.25" />
                                            </svg>
                                        </div>
                                        <div class="min-w-0 flex-1">
                                            <div class="flex items-baseline gap-3">
                                                <p class="font-mono text-sm font-black tracking-widest text-slate-900">{{ result.plate }}</p>
                                                <p v-if="result.brand || result.model" class="truncate text-xs font-semibold text-slate-400">
                                                    {{ [result.brand, result.model].filter(Boolean).join(' ') }}
                                                </p>
                                            </div>
                                            <p v-if="result.client" class="mt-0.5 text-xs font-semibold text-slate-500">
                                                {{ result.client.name }} · {{ result.client.rut }}
                                            </p>
                                        </div>
                                        <svg class="h-5 w-5 shrink-0 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                                        </svg>
                                    </button>
                                </div>

                                <!-- No results -->
                                <div v-else-if="noResultsAndSearched" class="space-y-4 rounded-3xl border border-dashed border-gray-200 bg-gray-50/50 p-6 text-center">
                                    <div>
                                        <svg class="mx-auto h-10 w-10 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z" />
                                        </svg>
                                        <p class="mt-2 text-sm font-bold text-gray-500">No encontramos esta patente en el sistema</p>
                                        <p class="mt-1 text-xs text-gray-400">Puedes crear un nuevo cliente con este vehículo</p>
                                    </div>
                                    <button type="button" @click="openCreateModal"
                                        class="inline-flex items-center gap-2 rounded-2xl bg-[#FF7A00] px-6 py-3 text-sm font-black text-white shadow-sm transition-all hover:bg-[#e86e00] active:scale-[0.98]">
                                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                                        </svg>
                                        Crear cliente nuevo
                                    </button>
                                </div>

                                <p v-if="form.errors.client_id" class="ml-1 text-[10px] font-medium text-red-500">{{ form.errors.client_id }}</p>
                                <p v-if="form.errors.vehicle_id" class="ml-1 text-[10px] font-medium text-red-500">{{ form.errors.vehicle_id }}</p>
                            </div>

                            <!-- Step 1 footer -->
                            <div class="flex items-center justify-between gap-3 border-t border-gray-100 pt-6">
                                <Link :href="route('quotes.index', tenantRouteParams)"
                                    class="rounded-2xl px-5 py-3 text-sm font-bold text-gray-500 transition-colors hover:bg-gray-100">
                                    Cancelar
                                </Link>
                                <button type="button" :disabled="!form.vehicle_id" @click="currentStep = 2"
                                    class="inline-flex items-center justify-center gap-2 rounded-2xl bg-gray-900 px-6 py-3 text-sm font-black text-white shadow-sm transition-all hover:bg-[#FF7A00] active:scale-[0.98] disabled:cursor-not-allowed disabled:opacity-40">
                                    Siguiente
                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                                    </svg>
                                </button>
                            </div>
                        </div>

                        <!-- STEP 2: CONFIRMATION -->
                        <div v-if="currentStep === 2" class="space-y-6 animate-fade-in">
                            <div class="space-y-5">
                                <label class="ml-1 block text-[10px] font-black uppercase tracking-widest text-gray-400">Resumen de la cotización</label>

                                <div class="grid gap-4 sm:grid-cols-2">
                                    <!-- Client Card -->
                                    <div class="rounded-2xl border border-gray-100 bg-gray-50/50 p-5">
                                        <div class="mb-3 flex items-center justify-between">
                                            <p class="text-[10px] font-black uppercase tracking-widest text-gray-400">Cliente</p>
                                            <button type="button" @click="clearSelection" class="text-[10px] font-bold uppercase text-[#FF7A00] hover:underline">
                                                Cambiar
                                            </button>
                                        </div>
                                        <p class="text-sm font-bold text-gray-900">{{ selectedClient?.name }}</p>
                                        <p class="mt-1 text-xs font-semibold text-gray-500">{{ selectedClient?.rut }}</p>
                                        <p v-if="selectedClient?.phone" class="mt-0.5 text-xs font-semibold text-gray-500">{{ selectedClient?.phone }}</p>
                                    </div>

                                    <!-- Vehicle Card -->
                                    <div class="rounded-2xl border border-gray-100 bg-gray-50/50 p-5">
                                        <div class="mb-3 flex items-center justify-between">
                                            <p class="text-[10px] font-black uppercase tracking-widest text-gray-400">Vehículo</p>
                                        </div>
                                        <p class="font-mono text-sm font-black tracking-widest text-gray-900">{{ selectedVehicle?.plate }}</p>
                                        <p class="mt-1 text-xs font-semibold text-gray-500">
                                            {{ [selectedVehicle?.brand, selectedVehicle?.model].filter(Boolean).join(' ') || 'Sin detalle' }}
                                        </p>
                                    </div>
                                </div>

                                <!-- Notes -->
                                <div class="space-y-3 pt-2">
                                    <label class="ml-1 block text-[10px] font-black uppercase tracking-widest text-gray-400">Notas internas (opcional)</label>
                                    <textarea v-model="form.notes" rows="4"
                                        class="w-full rounded-2xl border border-gray-300 bg-white px-5 py-4 text-sm font-medium text-gray-900 placeholder-gray-300 shadow-sm transition-all focus:border-transparent focus:outline-none focus:ring-2 focus:ring-[#FF7A00]"
                                        placeholder="Contexto para el equipo del taller (no visible para el cliente)"></textarea>
                                    <p v-if="form.errors.notes" class="ml-1 text-[10px] font-medium text-red-500">{{ form.errors.notes }}</p>
                                </div>
                            </div>

                            <!-- Step 2 footer -->
                            <div class="flex items-center justify-between gap-3 border-t border-gray-100 pt-6">
                                <button type="button" @click="currentStep = 1"
                                    class="inline-flex items-center justify-center gap-2 rounded-2xl border border-gray-200 bg-white px-6 py-3 text-sm font-bold text-gray-600 shadow-sm transition-all hover:bg-gray-50 active:scale-[0.98]">
                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
                                    </svg>
                                    Atrás
                                </button>
                                <button type="submit" :disabled="form.processing || !form.client_id || !form.vehicle_id"
                                    class="inline-flex items-center justify-center gap-2 rounded-2xl bg-gray-900 px-6 py-3 text-sm font-black text-white shadow-sm transition-all hover:bg-[#FF7A00] active:scale-[0.98] disabled:cursor-not-allowed disabled:opacity-40">
                                    {{ form.processing ? 'Creando...' : 'Crear cotización' }}
                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                    </svg>
                                </button>
                            </div>
                        </div>
                    </form>
                </div>

                <!-- Right: Help sidebar (desktop only) -->
                <div class="hidden lg:block">
                    <div class="sticky top-8 space-y-6">
                        <div class="rounded-[2rem] border border-gray-100 bg-white p-6 shadow-sm">
                            <h3 class="text-[10px] font-black uppercase tracking-widest text-gray-400">Cómo funciona</h3>
                            <ul class="mt-4 space-y-4">
                                <li class="flex items-start gap-3">
                                    <div class="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg bg-orange-50 text-xs font-black text-[#FF7A00]">1</div>
                                    <div>
                                        <p class="text-sm font-bold text-gray-900">Busca la patente</p>
                                        <p class="mt-0.5 text-xs text-gray-500">Ingresa la patente del vehículo para encontrar al cliente automáticamente.</p>
                                    </div>
                                </li>
                                <li class="flex items-start gap-3">
                                    <div class="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg bg-orange-50 text-xs font-black text-[#FF7A00]">2</div>
                                    <div>
                                        <p class="text-sm font-bold text-gray-900">Confirma y crea</p>
                                        <p class="mt-0.5 text-xs text-gray-500">Revisa los datos, agrega notas opcionales y crea la cotización.</p>
                                    </div>
                                </li>
                            </ul>
                        </div>

                        <div class="rounded-[2rem] border border-dashed border-orange-200 bg-orange-50/50 p-6">
                            <p class="text-[10px] font-black uppercase tracking-widest text-[#FF7A00]">Cliente nuevo?</p>
                            <p class="mt-2 text-xs font-medium text-gray-600">Si la patente no está registrada, podrás crear al cliente y su vehículo directamente desde esta pantalla.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- CREATE CLIENT MODAL -->
        <Teleport to="body">
            <Transition name="modal">
                <div v-if="showCreateModal" class="fixed inset-0 z-50 flex items-center justify-center p-4" @click.self="closeCreateModal">
                    <div class="fixed inset-0 bg-black/40 backdrop-blur-sm" @click="closeCreateModal"></div>

                    <div class="relative w-full max-w-2xl max-h-[90vh] overflow-y-auto rounded-[2rem] bg-white p-6 shadow-2xl sm:p-8">
                        <div class="mb-6 flex items-center justify-between">
                            <div>
                                <h2 class="text-xl font-black uppercase tracking-tight text-gray-900">Nuevo cliente</h2>
                                <p class="mt-1 text-xs font-medium text-gray-500">Crea el cliente y su vehículo para comenzar a cotizar.</p>
                            </div>
                            <button type="button" @click="closeCreateModal"
                                class="flex h-9 w-9 items-center justify-center rounded-full text-gray-400 transition-colors hover:bg-gray-100 hover:text-gray-600">
                                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>

                        <form @submit.prevent="submitCreateClient" class="space-y-6">
                            <!-- Vehicle section -->
                            <div class="space-y-4">
                                <p class="text-[10px] font-black uppercase tracking-widest text-[#FF7A00]">Vehículo</p>
                                <div class="grid gap-4 sm:grid-cols-3">
                                    <div>
                                        <label class="mb-1.5 block text-[10px] font-bold uppercase tracking-widest text-gray-400">Patente *</label>
                                        <input v-model="createForm.plate" type="text"
                                            class="w-full rounded-xl border border-gray-300 bg-white px-4 py-3 text-sm font-bold uppercase tracking-widest text-gray-900 placeholder-gray-300 transition-all focus:border-transparent focus:outline-none focus:ring-2 focus:ring-[#FF7A00]"
                                            placeholder="BBFL45" />
                                        <p v-if="createErrors.plate" class="mt-1 text-[10px] font-medium text-red-500">{{ createErrors.plate[0] }}</p>
                                    </div>
                                    <div>
                                        <label class="mb-1.5 block text-[10px] font-bold uppercase tracking-widest text-gray-400">Marca</label>
                                        <select
                                            :value="vehicleCatalog.brandSelection"
                                            @change="vehicleCatalog.applyBrandSelection($event.target.value)"
                                            class="w-full rounded-xl border border-gray-300 bg-white px-4 py-3 text-sm font-medium text-gray-900 transition-all focus:border-transparent focus:outline-none focus:ring-2 focus:ring-[#FF7A00]"
                                        >
                                            <option value="">Selecciona una marca</option>
                                            <option
                                                v-for="brand in props.vehicleCatalogBrands"
                                                :key="brand.id"
                                                :value="String(brand.id)"
                                            >
                                                {{ brand.name }}
                                            </option>
                                            <option :value="MANUAL_SELECTION">Otra marca</option>
                                        </select>
                                        <input
                                            v-if="vehicleCatalog.isManualBrand"
                                            v-model="createForm.vehicle_brand"
                                            type="text"
                                            class="mt-2 w-full rounded-xl border border-gray-300 bg-white px-4 py-3 text-sm font-medium text-gray-900 placeholder-gray-300 transition-all focus:border-transparent focus:outline-none focus:ring-2 focus:ring-[#FF7A00]"
                                            placeholder="Toyota"
                                        />
                                        <p v-if="createErrors.vehicle_brand_id" class="mt-1 text-[10px] font-medium text-red-500">{{ createErrors.vehicle_brand_id[0] }}</p>
                                        <p v-if="createErrors.vehicle_brand" class="mt-1 text-[10px] font-medium text-red-500">{{ createErrors.vehicle_brand[0] }}</p>
                                    </div>
                                    <div>
                                        <label class="mb-1.5 block text-[10px] font-bold uppercase tracking-widest text-gray-400">Modelo</label>
                                        <select
                                            v-if="!vehicleCatalog.isManualBrand"
                                            :value="vehicleCatalog.modelSelection"
                                            @change="vehicleCatalog.applyModelSelection($event.target.value)"
                                            :disabled="vehicleCatalog.loadingModels || !vehicleCatalog.brandSelection"
                                            class="w-full rounded-xl border border-gray-300 bg-white px-4 py-3 text-sm font-medium text-gray-900 transition-all focus:border-transparent focus:outline-none focus:ring-2 focus:ring-[#FF7A00] disabled:bg-gray-50 disabled:text-gray-400"
                                        >
                                            <option value="">
                                                {{ vehicleCatalog.loadingModels ? 'Cargando modelos...' : 'Selecciona un modelo' }}
                                            </option>
                                            <option
                                                v-for="model in vehicleCatalog.modelOptions"
                                                :key="model.id"
                                                :value="String(model.id)"
                                            >
                                                {{ model.name }}
                                            </option>
                                            <option :value="MANUAL_SELECTION">Otro modelo</option>
                                        </select>
                                        <input
                                            v-if="vehicleCatalog.isManualBrand || vehicleCatalog.isManualModel"
                                            v-model="createForm.vehicle_model"
                                            type="text"
                                            class="mt-2 w-full rounded-xl border border-gray-300 bg-white px-4 py-3 text-sm font-medium text-gray-900 placeholder-gray-300 transition-all focus:border-transparent focus:outline-none focus:ring-2 focus:ring-[#FF7A00]"
                                            placeholder="Corolla"
                                        />
                                        <p v-if="createErrors.vehicle_model_id" class="mt-1 text-[10px] font-medium text-red-500">{{ createErrors.vehicle_model_id[0] }}</p>
                                        <p v-if="createErrors.vehicle_model" class="mt-1 text-[10px] font-medium text-red-500">{{ createErrors.vehicle_model[0] }}</p>
                                    </div>
                                </div>
                            </div>

                            <div class="border-t border-gray-100"></div>

                            <!-- Client section -->
                            <div class="space-y-4">
                                <p class="text-[10px] font-black uppercase tracking-widest text-[#FF7A00]">Datos del cliente</p>
                                <div class="grid gap-4 sm:grid-cols-2">
                                    <div>
                                        <label class="mb-1.5 block text-[10px] font-bold uppercase tracking-widest text-gray-400">RUT *</label>
                                        <input v-model="createForm.rut" type="text"
                                            class="w-full rounded-xl border border-gray-300 bg-white px-4 py-3 text-sm font-medium text-gray-900 placeholder-gray-300 transition-all focus:border-transparent focus:outline-none focus:ring-2 focus:ring-[#FF7A00]"
                                            placeholder="12.345.678-9" />
                                        <p v-if="createErrors.rut" class="mt-1 text-[10px] font-medium text-red-500">{{ createErrors.rut[0] }}</p>
                                    </div>
                                    <div>
                                        <label class="mb-1.5 block text-[10px] font-bold uppercase tracking-widest text-gray-400">Nombre *</label>
                                        <input v-model="createForm.name" type="text"
                                            class="w-full rounded-xl border border-gray-300 bg-white px-4 py-3 text-sm font-medium text-gray-900 placeholder-gray-300 transition-all focus:border-transparent focus:outline-none focus:ring-2 focus:ring-[#FF7A00]"
                                            placeholder="Juan Pérez" />
                                        <p v-if="createErrors.name" class="mt-1 text-[10px] font-medium text-red-500">{{ createErrors.name[0] }}</p>
                                    </div>
                                    <div>
                                        <label class="mb-1.5 block text-[10px] font-bold uppercase tracking-widest text-gray-400">Celular (WhatsApp) *</label>
                                        <input v-model="createForm.phone" type="text"
                                            class="w-full rounded-xl border border-gray-300 bg-white px-4 py-3 text-sm font-medium text-gray-900 placeholder-gray-300 transition-all focus:border-transparent focus:outline-none focus:ring-2 focus:ring-[#FF7A00]"
                                            placeholder="+56 9 1234 5678" />
                                        <p v-if="createErrors.phone" class="mt-1 text-[10px] font-medium text-red-500">{{ createErrors.phone[0] }}</p>
                                    </div>
                                    <div>
                                        <label class="mb-1.5 block text-[10px] font-bold uppercase tracking-widest text-gray-400">Otro teléfono</label>
                                        <input v-model="createForm.secondary_phone" type="text"
                                            class="w-full rounded-xl border border-gray-300 bg-white px-4 py-3 text-sm font-medium text-gray-900 placeholder-gray-300 transition-all focus:border-transparent focus:outline-none focus:ring-2 focus:ring-[#FF7A00]"
                                            placeholder="Opcional" />
                                    </div>
                                </div>
                                <div>
                                    <label class="mb-1.5 block text-[10px] font-bold uppercase tracking-widest text-gray-400">Dirección</label>
                                    <input v-model="createForm.address" type="text"
                                        class="w-full rounded-xl border border-gray-300 bg-white px-4 py-3 text-sm font-medium text-gray-900 placeholder-gray-300 transition-all focus:border-transparent focus:outline-none focus:ring-2 focus:ring-[#FF7A00]"
                                        placeholder="Opcional" />
                                </div>
                            </div>

                            <!-- Modal footer -->
                            <div class="flex items-center justify-end gap-3 border-t border-gray-100 pt-6">
                                <button type="button" @click="closeCreateModal"
                                    class="rounded-2xl px-5 py-3 text-sm font-bold text-gray-500 transition-colors hover:bg-gray-100">
                                    Cancelar
                                </button>
                                <button type="submit" :disabled="isCreatingClient"
                                    class="inline-flex items-center justify-center gap-2 rounded-2xl bg-gray-900 px-6 py-3 text-sm font-black text-white shadow-sm transition-all hover:bg-[#FF7A00] active:scale-[0.98] disabled:cursor-not-allowed disabled:opacity-40">
                                    {{ isCreatingClient ? 'Creando...' : 'Crear cliente y continuar' }}
                                    <svg v-if="!isCreatingClient" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                    </svg>
                                    <div v-else class="h-4 w-4 animate-spin rounded-full border-2 border-white/30 border-t-white"></div>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </Transition>
        </Teleport>
    </TallerLayout>
</template>

<style scoped>
@keyframes fadeIn {
    from {
        opacity: 0;
        transform: translateY(4px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.animate-fade-in {
    animation: fadeIn 0.25s cubic-bezier(0.16, 1, 0.3, 1) forwards;
}

.modal-enter-active {
    transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1);
}

.modal-leave-active {
    transition: all 0.15s ease-in;
}

.modal-enter-from,
.modal-leave-to {
    opacity: 0;
}

.modal-enter-from > div:last-child,
.modal-leave-to > div:last-child {
    transform: scale(0.95);
}
</style>
