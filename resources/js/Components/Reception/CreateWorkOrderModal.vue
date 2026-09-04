<script setup>
import { computed, reactive, ref, watch } from 'vue';
import { useForm, usePage } from '@inertiajs/vue3';
import axios from 'axios';
import VehicleDamageDiagram from '@/Components/Reception/VehicleDamageDiagram.vue';
import SignaturePad from '@/Components/Reception/SignaturePad.vue';
import { useTenantRouting } from '@/composables/useTenantRouting';
import { useDebounce } from '@/composables/useDebounce';
import { MANUAL_SELECTION, useVehicleCatalog } from '@/composables/useVehicleCatalog';

const props = defineProps({
    show: {
        type: Boolean,
        default: false,
    },
    initialPlate: {
        type: String,
        default: null,
    },
    initialVehicleInfo: {
        type: Object,
        default: null,
    },
    vehicleCatalogBrands: {
        type: Array,
        default: () => [],
    },
});

const emit = defineEmits(['update:show', 'close', 'created']);

const page = usePage();
const { tenantRouteParams } = useTenantRouting();
const { debounce } = useDebounce();

const brandsCatalog = computed(() => {
    if (props.vehicleCatalogBrands && props.vehicleCatalogBrands.length > 0) {
        return props.vehicleCatalogBrands;
    }
    return page.props.vehicleCatalogBrands ?? [];
});

const isSearching = ref(false);
const isSearchingClients = ref(false);
const isExistingVehicle = ref(false);

const recognizedPlate = ref(null);
const vehicleInfo = ref(null);
const errorMsg = ref(null);
const ownerSource = ref('manual');
const clientSearch = ref('');
const clientMatches = ref([]);
const selectedExistingClient = ref(null);
const appointmentData = ref(null);
const isLookingUpRut = ref(false);
const rutLookupResult = ref(null);
const plateOrigin = ref(null);

const createEmptyClient = () => ({
    id: null,
    name: '',
    rut: '',
    email: '',
    phone: '',
});

const defaultClient = ref(createEmptyClient());

const form = useForm({
    plate: '',
    vehicle_brand_id: null,
    vehicle_model_id: null,
    brand: '',
    model: '',
    client_name: '',
    client_rut: '',
    client_email: '',
    client_phone: '',
    selected_client_id: null,
    reassign_vehicle_owner: false,
    appointment_id: null,
    checklist: {
        fuel_level: null,
        damages: [],
        belongings: [],
        notes: '',
        signature: null,
        signed_by_name: '',
    },
});

// --- Checklist de recepción (paso 2 del modal) ---
const modalStep = ref(1);
const customBelonging = ref('');

const FUEL_LEVELS = [
    { value: 0, label: 'E' },
    { value: 25, label: '¼' },
    { value: 50, label: '½' },
    { value: 75, label: '¾' },
    { value: 100, label: 'F' },
];

const BELONGING_PRESETS = [
    'Rueda de repuesto',
    'Gata',
    'Llave de ruedas',
    'Triángulos',
    'Extintor',
    'Botiquín',
    'Documentos',
    'Radio',
];

const STEP_ONE_ERROR_FIELDS = [
    'plate', 'vehicle_brand_id', 'vehicle_model_id', 'brand', 'model',
    'client_name', 'client_rut', 'client_email', 'client_phone',
    'selected_client_id', 'reassign_vehicle_owner',
];

const hasChecklistErrors = computed(() =>
    Object.keys(form.errors).some((key) => key.startsWith('checklist')));

const toggleFuelLevel = (value) => {
    form.checklist.fuel_level = form.checklist.fuel_level === value ? null : value;
};

const toggleBelonging = (item) => {
    const index = form.checklist.belongings.indexOf(item);
    if (index >= 0) {
        form.checklist.belongings.splice(index, 1);
    } else {
        form.checklist.belongings.push(item);
    }
};

const addCustomBelonging = () => {
    const value = customBelonging.value.trim();
    if (value && !form.checklist.belongings.includes(value)) {
        form.checklist.belongings.push(value);
    }
    customBelonging.value = '';
};

const goToChecklistStep = () => {
    if (!form.checklist.signed_by_name) {
        form.checklist.signed_by_name = form.client_name;
    }
    modalStep.value = 2;
};

const goToDataStep = () => {
    modalStep.value = 1;
};

const vehicleCatalog = reactive(useVehicleCatalog({
    form,
    brands: brandsCatalog,
    tenantRouteParams,
    brandField: 'brand',
    modelField: 'model',
    brandIdField: 'vehicle_brand_id',
    modelIdField: 'vehicle_model_id',
}));

const MOTO_PLATE_REGEX = /^([A-Z]{3}[0-9]{2}|[A-Z]{2}[0-9]{3})$/;

const plateOriginMessage = computed(() => {
    const countries = plateOrigin.value?.countries || [];
    if (!countries.length) return null;

    const names = countries.map((c) => `${c.name} ${c.flag}`);
    if (names.length === 1) {
        return `Esta placa patente pertenece a ${names[0]}.`;
    }

    const last = names.pop();
    return `Esta placa patente podría pertenecer a ${names.join(', ')} o ${last}.`;
});

const ownerSourceLabel = computed(() => {
    if (ownerSource.value === 'internal') {
        return 'Dueño guardado en Taller Flow';
    }

    if (ownerSource.value === 'boostr') {
        return 'Sugerencia de Boostr';
    }

    return 'Ingreso manual';
});

const formatAppointmentDate = (date) => {
    if (!date) return '';
    const d = new Date(date);
    return d.toLocaleDateString('es-CL', { day: '2-digit', month: 'short', hour: '2-digit', minute: '2-digit' });
};

const formatAppointmentTime = (date) => {
    if (!date) return '';
    const d = new Date(date);
    return d.toLocaleTimeString('es-CL', { hour: '2-digit', minute: '2-digit' });
};

const appointmentAlertLevel = computed(() => appointmentData.value?.alert ?? null);

const applyClientData = (client) => {
    form.client_name = client?.name || '';
    form.client_rut = client?.rut || '';
    form.client_email = client?.email || '';
    form.client_phone = client?.phone || '';
};

const resetClientSearchState = () => {
    clientSearch.value = '';
    clientMatches.value = [];
    selectedExistingClient.value = null;
    form.selected_client_id = null;
    rutLookupResult.value = null;
};

const resetModalState = () => {
    recognizedPlate.value = null;
    vehicleInfo.value = null;
    errorMsg.value = null;
    isExistingVehicle.value = false;
    ownerSource.value = 'manual';
    defaultClient.value = createEmptyClient();
    appointmentData.value = null;
    rutLookupResult.value = null;
    plateOrigin.value = null;
    resetClientSearchState();
    form.reset();
    form.clearErrors();
    form.reassign_vehicle_owner = false;
    vehicleCatalog.reset();
    modalStep.value = 1;
    customBelonging.value = '';
};

const closeModal = () => {
    emit('update:show', false);
    emit('close');
    resetModalState();
};

const fetchVehicleData = async (ppu) => {
    isSearching.value = true;
    errorMsg.value = null;

    try {
        const response = await axios.post(route('receptions.preview', tenantRouteParams.value), {
            patente: ppu,
        });
        const data = response.data;
        isExistingVehicle.value = data.vehicle_exists ?? !data.is_new;
        ownerSource.value = data.owner_source || 'manual';
        appointmentData.value = data.appointment || null;
        plateOrigin.value = data.plate_origin || null;
        defaultClient.value = {
            id: data.client?.id || null,
            name: data.client?.name || '',
            rut: data.client?.rut || '',
            email: data.client?.email || '',
            phone: data.client?.phone || '',
        };

        resetClientSearchState();
        form.plate = data.vehicle?.plate || ppu;
        form.appointment_id = data.appointment?.id || null;

        // Use fallback if preview has no good data
        const placeholder = ['NO IDENTIFICADO', 'N/A', 'SIN DATO', '', null, undefined];
        const previewBrand = data.vehicle?.brand ?? '';
        const previewModel = data.vehicle?.model ?? '';
        const aiBrand = vehicleInfo.value?.brand || props.initialVehicleInfo?.brand;
        const aiModel = vehicleInfo.value?.model || props.initialVehicleInfo?.model;
        form.brand = placeholder.includes(previewBrand) && aiBrand && aiBrand !== 'SIN DATO'
            ? aiBrand
            : previewBrand;
        form.model = placeholder.includes(previewModel) && aiModel && aiModel !== 'SIN DATO'
            ? aiModel
            : previewModel;
        await vehicleCatalog.hydrateFromCurrentValues();

        applyClientData(defaultClient.value);
        form.reassign_vehicle_owner = false;

        return true;
    } catch (error) {
        errorMsg.value = 'ERROR AL CONSULTAR DATOS.';
        return false;
    } finally {
        isSearching.value = false;
    }
};

const fetchClientMatches = async (search) => {
    const normalizedSearch = search.trim();

    if (normalizedSearch.length < 2) {
        clientMatches.value = [];
        return;
    }

    isSearchingClients.value = true;

    try {
        const response = await axios.get(route('receptions.clients.search', tenantRouteParams.value), {
            params: { search: normalizedSearch },
        });

        clientMatches.value = response.data.clients || [];
    } catch (error) {
        clientMatches.value = [];
    } finally {
        isSearchingClients.value = false;
    }
};

const debouncedFetchClientMatches = debounce((value) => {
    void fetchClientMatches(value);
}, 300);

const selectExistingClient = (client) => {
    selectedExistingClient.value = client;
    form.selected_client_id = client.id;
    form.reassign_vehicle_owner = isExistingVehicle.value;
    clientSearch.value = `${client.name} · ${client.rut}`;
    clientMatches.value = [];
    applyClientData(client);
};

const clearSelectedClient = () => {
    resetClientSearchState();
    applyClientData(defaultClient.value);

    if (isExistingVehicle.value) {
        form.reassign_vehicle_owner = false;
    }
};

const lookupClientByRut = async (rut) => {
    const clean = rut.replace(/[.\s-]/g, '').toUpperCase();
    if (clean.length < 7 || selectedExistingClient.value) return;

    isLookingUpRut.value = true;
    rutLookupResult.value = null;

    try {
        const response = await axios.get(route('receptions.clients.search', tenantRouteParams.value), {
            params: { search: clean },
        });
        const clients = response.data.clients || [];
        const exactMatch = clients.find((c) => c.rut?.replace(/[.\s-]/g, '').toUpperCase() === clean);
        if (exactMatch) {
            selectExistingClient(exactMatch);
            rutLookupResult.value = 'found';
        } else {
            rutLookupResult.value = 'not-found';
        }
    } catch {
        // silent fail
    } finally {
        isLookingUpRut.value = false;
    }
};

const debouncedRutLookup = debounce((value) => {
    void lookupClientByRut(value);
}, 400);

const debouncedPlateSearch = debounce((value) => {
    if (props.show && form.plate?.toUpperCase() === value && !isSearching.value) {
        fetchVehicleData(value);
    }
}, 600);

watch(() => props.show, async (isOpen) => {
    if (isOpen) {
        if (props.initialPlate) {
            form.plate = props.initialPlate;
            await fetchVehicleData(props.initialPlate);
        }
    } else {
        resetModalState();
    }
});

watch(() => form.plate, (newVal) => {
    if (!props.show || !newVal || isSearching.value) return;

    const clean = newVal.toUpperCase();

    if (clean.length === 7) {
        fetchVehicleData(clean);
    } else if (clean.length === 6 || MOTO_PLATE_REGEX.test(clean)) {
        debouncedPlateSearch(clean);
    }
});

watch(clientSearch, (value) => {
    const selectedLabel = selectedExistingClient.value
        ? `${selectedExistingClient.value.name} · ${selectedExistingClient.value.rut}`
        : null;

    if (selectedLabel !== null && value === selectedLabel) {
        return;
    }

    debouncedFetchClientMatches(value);
});

watch(() => form.reassign_vehicle_owner, (shouldReassign) => {
    if (!shouldReassign && isExistingVehicle.value) {
        resetClientSearchState();
        applyClientData(defaultClient.value);
    }
});

watch(() => form.client_rut, (newVal) => {
    if (!newVal || selectedExistingClient.value) {
        rutLookupResult.value = null;
        return;
    }
    if (!isExistingVehicle.value || form.reassign_vehicle_owner) {
        debouncedRutLookup(newVal);
    }
});

const handleCreateOrder = () => {
    form.post(route('receptions.store_order', tenantRouteParams.value), {
        onSuccess: () => {
            emit('created');
            closeModal();
        },
        onError: (errors) => {
            if (Object.keys(errors).some((key) => STEP_ONE_ERROR_FIELDS.includes(key))) {
                modalStep.value = 1;
            }
        },
    });
};
</script>

<template>
    <div v-if="show" class="fixed inset-0 z-[100] flex items-center justify-center p-4">
        <!-- Backdrop -->
        <div class="absolute inset-0 bg-slate-900/40 backdrop-blur-sm transition-opacity" @click="closeModal"></div>

        <!-- Modal Dialog -->
        <div
            class="relative w-full max-w-lg max-h-[95vh] overflow-y-auto bg-white border border-gray-100 rounded-[2.5rem] shadow-[0_32px_64px_rgba(0,0,0,0.1)] overflow-x-hidden animate-in zoom-in duration-300">

            <!-- Encabezado -->
            <div class="p-6 lg:p-8 border-b border-gray-50 flex justify-between items-center bg-gray-50/50">
                <div class="flex flex-col gap-1">
                    <h2 class="text-2xl font-black text-gray-900 tracking-tight uppercase">Orden de Trabajo</h2>
                    <span v-if="isExistingVehicle"
                        class="w-fit bg-emerald-100 text-emerald-600 text-[9px] font-black px-2.5 py-1 rounded-full uppercase tracking-widest border border-emerald-200">
                        Vehículo Registrado
                    </span>
                    <span v-else
                        class="w-fit bg-[#FF7A00]/10 text-[#FF7A00] text-[9px] font-black px-2.5 py-1 rounded-full uppercase tracking-widest border border-[#FF7A00]/20">
                        Vehículo Nuevo
                    </span>
                    <span
                        class="w-fit bg-slate-100 text-slate-500 text-[9px] font-black px-2.5 py-1 rounded-full uppercase tracking-widest border border-slate-200">
                        {{ ownerSourceLabel }}
                    </span>
                    <!-- Badge de cita: a tiempo -->
                    <span v-if="appointmentData && !appointmentAlertLevel"
                        class="w-fit bg-blue-100 text-blue-700 text-[9px] font-black px-2.5 py-1 rounded-full uppercase tracking-widest border border-blue-200 flex items-center gap-1">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                        Cita Agendada · {{ formatAppointmentDate(appointmentData.date) }}
                    </span>
                    <!-- Badge de cita: mismo día fuera de horario -->
                    <span v-else-if="appointmentData && appointmentAlertLevel === 'same_day'"
                        class="w-fit bg-amber-100 text-amber-700 text-[9px] font-black px-2.5 py-1 rounded-full uppercase tracking-widest border border-amber-300 flex items-center gap-1">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        Fuera de Horario · {{ formatAppointmentTime(appointmentData.date) }}
                    </span>
                    <!-- Badge de cita: día incorrecto -->
                    <span v-else-if="appointmentData && appointmentAlertLevel === 'wrong_day'"
                        class="w-fit bg-red-100 text-red-700 text-[9px] font-black px-2.5 py-1 rounded-full uppercase tracking-widest border border-red-300 flex items-center gap-1">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                        Cita en Otro Día · {{ formatAppointmentDate(appointmentData.date) }}
                    </span>
                </div>
                <button type="button" @click="closeModal"
                    class="w-10 h-10 rounded-full bg-white flex items-center justify-center text-gray-400 hover:text-gray-600 hover:bg-gray-100 transition-all border border-gray-200 shadow-sm">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor font-bold">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                            d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <!-- Alerta: vehículo llegó en día incorrecto -->
            <div v-if="appointmentAlertLevel === 'wrong_day'"
                class="mx-6 lg:mx-8 mt-6 rounded-2xl bg-red-50 border-2 border-red-300 px-5 py-4 space-y-2">
                <div class="flex items-center gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-red-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                    <p class="text-[9px] font-black uppercase tracking-widest text-red-700">Cita Agendada para Otro Día</p>
                </div>
                <p class="text-sm font-bold text-red-900">
                    Esta patente tiene una cita para el <span class="underline">{{ formatAppointmentDate(appointmentData.date) }}</span>.
                </p>
                <p class="text-xs text-red-700">Verifique si el cliente llegó en la fecha incorrecta o si el agendamiento debe actualizarse.</p>
            </div>

            <!-- Alerta: vehículo llegó mismo día pero fuera de horario -->
            <div v-else-if="appointmentAlertLevel === 'same_day'"
                class="mx-6 lg:mx-8 mt-6 rounded-2xl bg-amber-50 border border-amber-300 px-5 py-4 space-y-2">
                <div class="flex items-center gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-amber-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <p class="text-[9px] font-black uppercase tracking-widest text-amber-700">Fuera del Horario Agendado</p>
                </div>
                <p class="text-sm font-semibold text-amber-900">
                    La cita estaba programada para las <span class="font-black">{{ formatAppointmentTime(appointmentData.date) }}</span>.
                </p>
            </div>

            <!-- Aviso: patente extranjera -->
            <div v-if="plateOriginMessage"
                class="mx-6 lg:mx-8 mt-4 rounded-2xl bg-indigo-50 border border-indigo-300 px-5 py-4 space-y-2">
                <div class="flex items-center gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-indigo-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <p class="text-[9px] font-black uppercase tracking-widest text-indigo-700">Patente Extranjera</p>
                </div>
                <p class="text-sm font-bold text-indigo-900">{{ plateOriginMessage }}</p>
                <p class="text-xs text-indigo-700">Los datos del vehículo no están en el registro local, por lo que deben ingresarse manualmente.</p>
            </div>

            <!-- Info de cita agendada (si existe y tiene notas) -->
            <div v-if="appointmentData && appointmentData.notes"
                class="mx-6 lg:mx-8 mt-4 rounded-2xl bg-blue-50 border border-blue-200 px-5 py-4 space-y-1">
                <p class="text-[9px] font-black uppercase tracking-widest text-blue-600">Notas del agendamiento</p>
                <p class="text-sm font-semibold text-slate-700">{{ appointmentData.notes }}</p>
                <p v-if="appointmentData.pre_check_notes" class="text-xs text-slate-500 mt-1">{{ appointmentData.pre_check_notes }}</p>
            </div>

            <!-- Formulario Editable -->
            <form @submit.prevent="handleCreateOrder" class="p-6 lg:p-8 space-y-8">

                <!-- Indicador de pasos -->
                <div class="flex items-center justify-center gap-3">
                    <button type="button" @click="goToDataStep"
                        class="flex items-center gap-2 text-[9px] font-black uppercase tracking-widest transition-colors"
                        :class="modalStep === 1 ? 'text-[#FF7A00]' : 'text-slate-400'">
                        <span class="flex h-6 w-6 items-center justify-center rounded-full border-2 text-[10px]"
                            :class="modalStep === 1 ? 'border-[#FF7A00] bg-[#FF7A00]/10' : 'border-slate-300'">1</span>
                        Datos
                    </button>
                    <span class="h-px w-8 bg-slate-200"></span>
                    <span class="flex items-center gap-2 text-[9px] font-black uppercase tracking-widest"
                        :class="modalStep === 2 ? 'text-[#FF7A00]' : 'text-slate-400'">
                        <span class="flex h-6 w-6 items-center justify-center rounded-full border-2 text-[10px]"
                            :class="modalStep === 2 ? 'border-[#FF7A00] bg-[#FF7A00]/10' : 'border-slate-300'">2</span>
                        Checklist y Firma
                    </span>
                </div>

                <!-- PASO 1: Datos del vehículo y cliente -->
                <div v-show="modalStep === 1" class="space-y-8">

                    <!-- Patente (Editable) -->
                    <div
                        class="flex flex-col items-center py-6 bg-gray-50 rounded-3xl border border-gray-100 shadow-inner group transition-all focus-within:ring-2 focus-within:ring-[#FF7A00]/20">
                        <p class="text-[9px] font-bold text-gray-400 uppercase tracking-[0.3em] mb-2">Placa de
                            Identificación</p>
                        <input v-model="form.plate" type="text"
                            class="w-full text-center bg-transparent border-none focus:ring-0 text-5xl font-mono font-black text-gray-900 tracking-widest plate-font uppercase placeholder-gray-200"
                            placeholder="AAAA11" maxlength="8" />
                        <p class="text-[9px] text-gray-400 mt-2 tracking-wider">Auto: 6 caracteres · Moto: 5 · Extranjera: hasta 8</p>
                    </div>

                    <!-- Datos del Vehículo -->
                    <div class="space-y-4">
                        <div class="flex items-center gap-2 border-b border-gray-100 pb-2">
                            <span class="w-1.5 h-1.5 rounded-full bg-[#FF7A00]"></span>
                            <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Información
                                Técnica</p>
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <div class="space-y-1.5">
                                <label
                                    class="block text-[9px] font-bold text-gray-400 uppercase tracking-widest ml-1">Marca</label>
                                <select
                                    :value="vehicleCatalog.brandSelection"
                                    @change="vehicleCatalog.applyBrandSelection($event.target.value)"
                                    class="w-full bg-white border border-gray-300 text-gray-900 text-base font-bold rounded-2xl px-5 py-4 focus:outline-none focus:ring-2 focus:ring-[#FF7A00] focus:border-transparent transition-all shadow-sm"
                                >
                                    <option value="">Selecciona una marca</option>
                                    <option
                                        v-for="brand in brandsCatalog"
                                        :key="brand.id"
                                        :value="String(brand.id)"
                                    >
                                        {{ brand.name }}
                                    </option>
                                    <option :value="MANUAL_SELECTION">Otra marca</option>
                                </select>
                                <input
                                    v-if="vehicleCatalog.isManualBrand"
                                    v-model="form.brand"
                                    type="text"
                                    class="w-full bg-white border border-gray-300 text-gray-900 text-lg font-bold rounded-2xl px-5 py-4 placeholder-gray-300 focus:outline-none focus:ring-2 focus:ring-[#FF7A00] focus:border-transparent uppercase transition-all shadow-sm"
                                    placeholder="Ej: TOYOTA"
                                />
                                <p v-if="form.errors.brand" class="text-red-500 text-[10px] font-medium ml-1">{{
                                    form.errors.brand }}</p>
                                <p v-if="form.errors.vehicle_brand_id" class="text-red-500 text-[10px] font-medium ml-1">{{
                                    form.errors.vehicle_brand_id }}</p>
                            </div>
                            <div class="space-y-1.5">
                                <label
                                    class="block text-[9px] font-bold text-gray-400 uppercase tracking-widest ml-1">Modelo</label>
                                <select
                                    v-if="!vehicleCatalog.isManualBrand"
                                    :value="vehicleCatalog.modelSelection"
                                    @change="vehicleCatalog.applyModelSelection($event.target.value)"
                                    :disabled="vehicleCatalog.loadingModels || !vehicleCatalog.brandSelection"
                                    class="w-full bg-white border border-gray-300 text-gray-900 text-base font-bold rounded-2xl px-5 py-4 focus:outline-none focus:ring-2 focus:ring-[#FF7A00] focus:border-transparent transition-all shadow-sm disabled:bg-gray-50 disabled:text-gray-400"
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
                                    v-model="form.model"
                                    type="text"
                                    class="w-full bg-white border border-gray-300 text-gray-900 text-lg font-bold rounded-2xl px-5 py-4 placeholder-gray-300 focus:outline-none focus:ring-2 focus:ring-[#FF7A00] focus:border-transparent uppercase transition-all shadow-sm"
                                    placeholder="Ej: HILUX"
                                />
                                <p v-if="form.errors.model" class="text-red-500 text-[10px] font-medium ml-1">{{
                                    form.errors.model }}</p>
                                <p v-if="form.errors.vehicle_model_id" class="text-red-500 text-[10px] font-medium ml-1">{{
                                    form.errors.vehicle_model_id }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Asociación del Cliente -->
                    <div class="space-y-4">
                        <div class="flex items-center gap-2 border-b border-gray-100 pb-2">
                            <span class="w-1.5 h-1.5 rounded-full bg-sky-500"></span>
                            <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Asignación del
                                Cliente</p>
                        </div>

                        <div v-if="isExistingVehicle"
                            class="rounded-3xl border border-emerald-200 bg-emerald-50/70 p-5 space-y-4">
                            <div class="flex items-start justify-between gap-4">
                                <div class="space-y-1">
                                    <p class="text-[10px] font-black uppercase tracking-widest text-emerald-600">
                                        Vehículo ya asociado
                                    </p>
                                    <p class="text-sm font-bold text-slate-900">
                                        {{ defaultClient.name || 'Cliente sin nombre' }}
                                    </p>
                                    <p class="text-xs text-slate-500">
                                        Si no reasignas, la orden seguirá vinculada a este cliente.
                                    </p>
                                </div>

                                <label class="flex items-center gap-3 rounded-2xl bg-white px-4 py-3 border border-emerald-200 shadow-sm">
                                    <input v-model="form.reassign_vehicle_owner" type="checkbox"
                                        class="rounded border-gray-300 text-[#FF7A00] focus:ring-[#FF7A00]" />
                                    <span class="text-[10px] font-black uppercase tracking-widest text-slate-700">
                                        Reasignar dueño
                                    </span>
                                </label>
                            </div>
                        </div>

                        <div v-else class="rounded-3xl border border-slate-200 bg-slate-50/80 p-5">
                            <p class="text-xs font-semibold leading-relaxed text-slate-600">
                                <span v-if="ownerSource === 'boostr'">
                                    Boostr sugirió un propietario para esta patente. Puedes mantener esa sugerencia,
                                    buscar un cliente existente o ingresar otro manualmente.
                                </span>
                                <span v-else>
                                    No encontramos un propietario confirmado. Busca un cliente existente o completa sus
                                    datos manualmente.
                                </span>
                            </p>
                        </div>

                        <div class="space-y-3">
                            <label
                                class="block text-[9px] font-bold text-gray-400 uppercase tracking-widest ml-1">Buscar
                                cliente existente</label>
                            <div class="relative">
                                <input v-model="clientSearch" type="text"
                                    class="w-full bg-white border border-gray-300 text-gray-900 text-sm font-semibold rounded-2xl px-5 py-4 pr-12 placeholder-gray-300 focus:outline-none focus:ring-2 focus:ring-[#FF7A00] focus:border-transparent transition-all shadow-sm"
                                    placeholder="Buscar por nombre o RUT" />
                                <div v-if="isSearchingClients"
                                    class="absolute inset-y-0 right-4 flex items-center text-gray-300">
                                    <div
                                        class="h-5 w-5 rounded-full border-2 border-gray-200 border-t-[#FF7A00] animate-spin">
                                    </div>
                                </div>
                            </div>

                            <div v-if="selectedExistingClient"
                                class="rounded-3xl border border-sky-200 bg-sky-50/70 p-4 flex items-start justify-between gap-4">
                                <div class="space-y-1">
                                    <p class="text-[10px] font-black uppercase tracking-widest text-sky-600">
                                        Cliente seleccionado
                                    </p>
                                    <p class="text-sm font-bold text-slate-900">{{ selectedExistingClient.name }}</p>
                                    <p class="text-xs font-semibold text-slate-500">
                                        {{ selectedExistingClient.rut }}<span v-if="selectedExistingClient.phone"> · {{
                                            selectedExistingClient.phone }}</span>
                                    </p>
                                </div>
                                <button type="button" @click="clearSelectedClient"
                                    class="shrink-0 rounded-full bg-white px-4 py-2 text-[10px] font-black uppercase tracking-widest text-slate-500 border border-slate-200 hover:bg-slate-100 transition-colors">
                                    Quitar
                                </button>
                            </div>

                            <div v-else-if="clientMatches.length > 0"
                                class="rounded-3xl border border-gray-200 overflow-hidden bg-white shadow-sm">
                                <button v-for="client in clientMatches" :key="client.id" type="button"
                                    @click="selectExistingClient(client)"
                                    class="w-full px-5 py-4 text-left border-b last:border-b-0 border-gray-100 hover:bg-slate-50 transition-colors">
                                    <p class="text-sm font-bold text-slate-900 uppercase">{{ client.name }}</p>
                                    <p class="text-xs font-semibold text-slate-500">
                                        {{ client.rut }}<span v-if="client.phone"> · {{ client.phone }}</span><span
                                            v-if="client.email"> · {{ client.email }}</span>
                                    </p>
                                </button>
                            </div>

                            <p v-else-if="clientSearch.trim().length >= 2 && !isSearchingClients"
                                class="text-[10px] font-bold uppercase tracking-widest text-slate-400 ml-1">
                                No encontramos coincidencias en este taller.
                            </p>

                            <p v-if="form.errors.selected_client_id" class="text-red-500 text-[10px] font-medium ml-1">
                                {{ form.errors.selected_client_id }}
                            </p>
                        </div>
                    </div>

                    <!-- Datos del Cliente -->
                    <div class="space-y-4">
                        <div class="flex items-center gap-2 border-b border-gray-100 pb-2">
                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                            <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Datos del
                                Cliente</p>
                        </div>
                        <div v-if="isExistingVehicle && !form.reassign_vehicle_owner"
                            class="rounded-3xl border border-amber-200 bg-amber-50/80 px-4 py-3">
                            <p class="text-[10px] font-black uppercase tracking-widest text-amber-700">
                                Se mantendrá el dueño actual del vehículo y se actualizarán sus datos de contacto.
                            </p>
                        </div>
                        <div class="space-y-4">
                            <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                                <div class="space-y-1.5">
                                    <label
                                        class="block text-[9px] font-bold text-gray-400 uppercase tracking-widest ml-1">RUT</label>
                                    <div class="relative">
                                        <input v-model="form.client_rut" type="text"
                                            class="w-full bg-white border border-gray-300 text-gray-900 text-lg font-bold rounded-2xl px-5 py-4 placeholder-gray-300 focus:outline-none focus:ring-2 focus:ring-[#FF7A00] focus:border-transparent transition-all shadow-sm"
                                            placeholder="12.345.678-9" />
                                        <div v-if="isLookingUpRut" class="absolute inset-y-0 right-4 flex items-center">
                                            <div class="h-4 w-4 rounded-full border-2 border-gray-200 border-t-[#FF7A00] animate-spin"></div>
                                        </div>
                                    </div>
                                    <p v-if="rutLookupResult === 'found'" class="text-[9px] font-black uppercase tracking-widest text-emerald-600 ml-1">
                                        ✓ Cliente encontrado y vinculado
                                    </p>
                                    <p v-if="form.errors.client_rut" class="text-red-500 text-[10px] font-medium ml-1">
                                        {{ form.errors.client_rut }}</p>
                                </div>
                                <div class="space-y-1.5">
                                    <label
                                        class="block text-[9px] font-bold text-gray-400 uppercase tracking-widest ml-1">Nombre
                                        Completo</label>
                                    <input v-model="form.client_name" type="text"
                                        class="w-full bg-white border border-gray-300 text-gray-900 text-lg font-bold rounded-2xl px-5 py-4 placeholder-gray-300 focus:outline-none focus:ring-2 focus:ring-[#FF7A00] focus:border-transparent uppercase transition-all shadow-sm"
                                        placeholder="JUAN PÉREZ" />
                                    <p v-if="form.errors.client_name" class="text-red-500 text-[10px] font-medium ml-1">
                                        {{ form.errors.client_name }}</p>
                                </div>
                            </div>
                            <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                                <div class="space-y-1.5">
                                    <label
                                        class="block text-[9px] font-bold text-gray-400 uppercase tracking-widest ml-1">Email</label>
                                    <input v-model="form.client_email" type="email"
                                        class="w-full bg-white border border-gray-300 text-gray-900 text-lg font-bold rounded-2xl px-5 py-4 placeholder-gray-300 focus:outline-none focus:ring-2 focus:ring-[#FF7A00] focus:border-transparent transition-all shadow-sm"
                                        placeholder="correo@ejemplo.cl" />
                                </div>
                                <div class="space-y-1.5">
                                    <label
                                        class="block text-[9px] font-bold text-gray-400 uppercase tracking-widest ml-1">Celular</label>
                                    <input v-model="form.client_phone" type="tel"
                                        class="w-full bg-white border border-gray-300 text-gray-900 text-lg font-bold rounded-2xl px-5 py-4 placeholder-gray-300 focus:outline-none focus:ring-2 focus:ring-[#FF7A00] focus:border-transparent transition-all shadow-sm"
                                        placeholder="+56 9 1234 5678" />
                                </div>
                            </div>
                        </div>
                    </div>

                </div>

                <!-- PASO 2: Checklist de recepción -->
                <div v-show="modalStep === 2" class="space-y-8">

                    <!-- Resumen del vehículo -->
                    <div class="flex items-center justify-between rounded-3xl bg-gray-50 border border-gray-100 px-5 py-4">
                        <div>
                            <p class="text-[9px] font-bold text-gray-400 uppercase tracking-widest">Vehículo</p>
                            <p class="text-xl font-mono font-black text-gray-900 tracking-widest plate-font">{{ form.plate }}</p>
                        </div>
                        <p class="text-xs font-bold text-slate-500 uppercase text-right">{{ form.brand }}<br>{{ form.model }}</p>
                    </div>

                    <div v-if="hasChecklistErrors"
                        class="rounded-2xl bg-red-50 border border-red-200 px-5 py-3">
                        <p class="text-xs font-bold text-red-700">Revisa el checklist: hay datos inválidos.</p>
                    </div>

                    <!-- Nivel de combustible -->
                    <div class="space-y-4">
                        <div class="flex items-center gap-2 border-b border-gray-100 pb-2">
                            <span class="w-1.5 h-1.5 rounded-full bg-[#FF7A00]"></span>
                            <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Nivel de Combustible</p>
                        </div>
                        <div class="grid grid-cols-5 gap-2">
                            <button v-for="level in FUEL_LEVELS" :key="level.value" type="button"
                                @click="toggleFuelLevel(level.value)"
                                class="py-4 rounded-2xl border-2 text-lg font-black transition-all active:scale-95"
                                :class="form.checklist.fuel_level === level.value
                                    ? 'border-[#FF7A00] bg-[#FF7A00]/10 text-[#FF7A00]'
                                    : 'border-gray-200 bg-white text-gray-400 hover:border-gray-300'">
                                {{ level.label }}
                            </button>
                        </div>
                    </div>

                    <!-- Inventario de daños -->
                    <div class="space-y-4">
                        <div class="flex items-center gap-2 border-b border-gray-100 pb-2">
                            <span class="w-1.5 h-1.5 rounded-full bg-red-500"></span>
                            <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Inventario de Daños</p>
                        </div>
                        <VehicleDamageDiagram v-model="form.checklist.damages" />
                    </div>

                    <!-- Objetos de valor -->
                    <div class="space-y-4">
                        <div class="flex items-center gap-2 border-b border-gray-100 pb-2">
                            <span class="w-1.5 h-1.5 rounded-full bg-sky-500"></span>
                            <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Objetos de Valor / Pertenencias</p>
                        </div>
                        <div class="flex flex-wrap gap-2">
                            <button v-for="item in BELONGING_PRESETS" :key="item" type="button"
                                @click="toggleBelonging(item)"
                                class="rounded-full px-3 py-1.5 text-[10px] font-black uppercase tracking-widest border transition-all"
                                :class="form.checklist.belongings.includes(item)
                                    ? 'bg-sky-500 text-white border-sky-500 shadow-md'
                                    : 'bg-white text-slate-500 border-slate-200 hover:border-slate-400'">
                                {{ item }}
                            </button>
                        </div>
                        <div class="flex gap-2">
                            <input v-model="customBelonging" type="text" maxlength="255"
                                @keydown.enter.prevent="addCustomBelonging"
                                class="flex-1 bg-white border border-gray-300 text-gray-900 text-sm font-semibold rounded-2xl px-5 py-3 placeholder-gray-300 focus:outline-none focus:ring-2 focus:ring-[#FF7A00] focus:border-transparent transition-all shadow-sm"
                                placeholder="Otro objeto (ej: lentes de sol)" />
                            <button type="button" @click="addCustomBelonging"
                                class="shrink-0 rounded-2xl bg-slate-900 px-5 py-3 text-[10px] font-black uppercase tracking-widest text-white hover:bg-[#FF7A00] transition-all active:scale-95">
                                Agregar
                            </button>
                        </div>
                        <div v-if="form.checklist.belongings.some((b) => !BELONGING_PRESETS.includes(b))"
                            class="flex flex-wrap gap-2">
                            <span v-for="item in form.checklist.belongings.filter((b) => !BELONGING_PRESETS.includes(b))"
                                :key="item"
                                class="flex items-center gap-2 rounded-full bg-sky-50 border border-sky-200 px-3 py-1.5 text-[10px] font-black uppercase tracking-widest text-sky-700">
                                {{ item }}
                                <button type="button" @click="toggleBelonging(item)"
                                    class="text-sky-400 hover:text-sky-700">✕</button>
                            </span>
                        </div>
                    </div>

                    <!-- Observaciones -->
                    <div class="space-y-4">
                        <div class="flex items-center gap-2 border-b border-gray-100 pb-2">
                            <span class="w-1.5 h-1.5 rounded-full bg-slate-400"></span>
                            <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Observaciones del Estado</p>
                        </div>
                        <textarea v-model="form.checklist.notes" rows="2" maxlength="2000"
                            class="w-full bg-white border border-gray-300 text-gray-900 text-sm font-semibold rounded-2xl px-5 py-4 placeholder-gray-300 focus:outline-none focus:ring-2 focus:ring-[#FF7A00] focus:border-transparent transition-all shadow-sm resize-none"
                            placeholder="Ej: llega con luz de check engine encendida, tag en parabrisas..."></textarea>
                    </div>

                    <!-- Firma del cliente -->
                    <div class="space-y-4">
                        <div class="flex items-center gap-2 border-b border-gray-100 pb-2">
                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                            <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Firma del Cliente</p>
                        </div>
                        <p class="text-xs font-medium text-slate-500 leading-relaxed">
                            El cliente declara estar de acuerdo con el estado del vehículo registrado en este checklist.
                        </p>
                        <SignaturePad v-model="form.checklist.signature" />
                        <div class="space-y-1.5">
                            <label class="block text-[9px] font-bold text-gray-400 uppercase tracking-widest ml-1">Firmado por</label>
                            <input v-model="form.checklist.signed_by_name" type="text" maxlength="255"
                                class="w-full bg-white border border-gray-300 text-gray-900 text-sm font-bold rounded-2xl px-5 py-3 placeholder-gray-300 focus:outline-none focus:ring-2 focus:ring-[#FF7A00] focus:border-transparent uppercase transition-all shadow-sm"
                                placeholder="NOMBRE DE QUIEN FIRMA" />
                        </div>
                    </div>
                </div>

                <!-- Acciones -->
                <div class="flex flex-col sm:flex-row gap-4 pt-4 border-t border-gray-100">
                    <template v-if="modalStep === 1">
                        <button type="button" @click="closeModal"
                            class="order-2 sm:order-1 flex-1 py-4 bg-gray-100 hover:bg-gray-200 text-gray-500 rounded-full font-bold transition-all active:scale-95 text-sm uppercase">
                            CANCELAR
                        </button>
                        <button type="button" @click="goToChecklistStep"
                            class="order-1 sm:order-2 flex-[2] py-4 bg-[#FF7A00] hover:bg-[#CC6200] text-white rounded-full font-black uppercase shadow-[0_8px_20px_rgba(249,168,38,0.3)] transition-all active:scale-95 flex items-center justify-center gap-2 tracking-wide text-lg">
                            CONTINUAR
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24"
                                stroke="currentColor" stroke-width="3">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                            </svg>
                        </button>
                    </template>
                    <template v-else>
                        <button type="button" @click="goToDataStep"
                            class="order-2 sm:order-1 flex-1 py-4 bg-gray-100 hover:bg-gray-200 text-gray-500 rounded-full font-bold transition-all active:scale-95 text-sm uppercase">
                            ← VOLVER
                        </button>
                        <button type="submit" :disabled="form.processing"
                            class="order-1 sm:order-2 flex-[2] py-4 bg-[#FF7A00] hover:bg-[#CC6200] text-white rounded-full font-black uppercase shadow-[0_8px_20px_rgba(249,168,38,0.3)] transition-all active:scale-95 disabled:opacity-50 disabled:cursor-wait flex items-center justify-center gap-2 tracking-wide text-lg">
                            <div v-if="form.processing"
                                class="w-5 h-5 border-2 border-white/30 border-t-white rounded-full animate-spin"></div>
                            {{ form.processing ? 'Procesando...' : 'GENERAR ORDEN' }}
                        </button>
                    </template>
                </div>
            </form>
        </div>
    </div>
</template>

<style scoped>
@import '@fontsource/archivo-narrow/700.css';

.plate-font {
    font-family: 'Archivo Narrow', sans-serif;
    letter-spacing: -0.05em;
    transform: scaleX(0.9);
}
</style>
