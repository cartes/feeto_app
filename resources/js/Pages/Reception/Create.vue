<script setup>
import { ref, reactive, onMounted, onUnmounted, computed, watch } from 'vue';
import { Head, usePage, useForm } from '@inertiajs/vue3';
import axios from 'axios';
import PpuScanner from '@/Components/PpuScanner.vue';
import PlanUpgradeBanner from '@/Components/PlanUpgradeBanner.vue';
import TallerLayout from '@/Layouts/TallerLayout.vue';
import { useTenantRouting } from '@/composables/useTenantRouting';
import { useDebounce } from '@/composables/useDebounce';
import { MANUAL_SELECTION, useVehicleCatalog } from '@/composables/useVehicleCatalog';

const page = usePage();
const tenantId = page.props.tenantId;
const maxImageUploadBytes = computed(() => Number(page.props.maxImageUploadBytes ?? 5 * 1024 * 1024));
const { tenantRouteParams } = useTenantRouting();
const { debounce } = useDebounce();
const planAccess = computed(() => page.props.planAccess ?? {});
const aiReceptionEnabled = computed(() => planAccess.value?.ai_reception ?? false);
const aiReceptionUpgradeMessage = computed(() => planAccess.value?.upgrade_messages?.ai_reception ?? 'Mejora tu plan para acceder a esta función');
const vehicleCatalogBrands = computed(() => page.props.vehicleCatalogBrands ?? []);

const isUploading = ref(false);
const isAnalyzing = ref(false);
const isSearching = ref(false);
const isSearchingClients = ref(false);
const showModal = ref(false);
const isExistingVehicle = ref(false);

const recognizedPlate = ref(null);
const vehicleInfo = ref(null);
const previewImageUrl = ref(null);
const fileInput = ref(null);
const errorMsg = ref(null);
const ownerSource = ref('manual');
const clientSearch = ref('');
const clientMatches = ref([]);
const selectedExistingClient = ref(null);
const appointmentData = ref(null);
const isLookingUpRut = ref(false);
const rutLookupResult = ref(null);

const createEmptyClient = () => ({
    id: null,
    name: '',
    rut: '',
    email: '',
    phone: '',
});

const defaultClient = ref(createEmptyClient());

const formatFileSize = (bytes) => {
    if (!Number.isFinite(bytes) || bytes <= 0) {
        return '0 MB';
    }

    return `${(bytes / (1024 * 1024)).toFixed(1)} MB`;
};

const revokePreviewImage = () => {
    if (previewImageUrl.value) {
        URL.revokeObjectURL(previewImageUrl.value);
        previewImageUrl.value = null;
    }
};


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
});

const vehicleCatalog = reactive(useVehicleCatalog({
    form,
    brands: vehicleCatalogBrands,
    tenantRouteParams,
    brandField: 'brand',
    modelField: 'model',
    brandIdField: 'vehicle_brand_id',
    modelIdField: 'vehicle_model_id',
}));

const MOTO_PLATE_REGEX = /^([A-Z]{3}[0-9]{2}|[A-Z]{2}[0-9]{3})$/;

const formattedPlate = computed(() => {
    if (!recognizedPlate.value) return '';
    const clean = recognizedPlate.value.replace(/[^A-Z0-9]/gi, '').toUpperCase();
    if (clean.length >= 6) {
        return `${clean.slice(0, 2)}·${clean.slice(2, 4)}·${clean.slice(4, 6)}`;
    }
    if (MOTO_PLATE_REGEX.test(clean)) {
        return clean.replace(/^([A-Z]+)([0-9]+)$/, '$1·$2');
    }
    return clean;
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

const resetReceptionState = () => {
    recognizedPlate.value = null;
    vehicleInfo.value = null;
    revokePreviewImage();
    errorMsg.value = null;
    isExistingVehicle.value = false;
    ownerSource.value = 'manual';
    defaultClient.value = createEmptyClient();
    appointmentData.value = null;
    rutLookupResult.value = null;
    resetClientSearchState();
    form.reset();
    form.clearErrors();
    form.reassign_vehicle_owner = false;
    vehicleCatalog.reset();
};

const closeModal = () => {
    showModal.value = false;
    resetReceptionState();
};

const triggerCamera = () => {
    resetReceptionState();
    showModal.value = false;
    fileInput.value.click();
};

const handleManualEntry = () => {
    resetReceptionState();
    showModal.value = true;
};

const fetchVehicleData = async (ppu) => {
    isSearching.value = true;
    errorMsg.value = null;

    try {
        const response = await axios.post(route('receptions.preview', tenantRouteParams.value), {
            patente: ppu
        });
        const data = response.data;
        isExistingVehicle.value = data.vehicle_exists ?? !data.is_new;
        ownerSource.value = data.owner_source || 'manual';
        appointmentData.value = data.appointment || null;
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

        // Use AI-detected data as fallback when preview has no good data (new vehicle, Boostr unavailable)
        const placeholder = ['NO IDENTIFICADO', 'N/A', 'SIN DATO', '', null, undefined];
        const previewBrand = data.vehicle?.brand ?? '';
        const previewModel = data.vehicle?.model ?? '';
        const aiBrand = vehicleInfo.value?.brand;
        const aiModel = vehicleInfo.value?.model;
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
        errorMsg.value = "ERROR AL CONSULTAR DATOS.";
        return false;
    } finally {
        isSearching.value = false;
    }
};

const handleConfirmIngreso = async (ppu) => {
    const finalPpu = ppu || recognizedPlate.value;
    if (!finalPpu || finalPpu === '---') {
        errorMsg.value = "CAPTURE O INGRESE UNA PATENTE";
        return;
    }
    const success = await fetchVehicleData(finalPpu);
    if (success) showModal.value = true;
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
        const exactMatch = clients.find(c => c.rut?.replace(/[.\s-]/g, '').toUpperCase() === clean);
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

// Autosearch: inmediato con 6 caracteres (auto); con debounce para
// patentes de moto de 5 caracteres, por si el usuario sigue escribiendo.
const debouncedMotoPlateSearch = debounce((value) => {
    if (showModal.value && form.plate?.toUpperCase() === value && MOTO_PLATE_REGEX.test(value) && !isSearching.value) {
        fetchVehicleData(value);
    }
}, 600);

watch(() => form.plate, (newVal) => {
    if (!showModal.value || !newVal || isSearching.value) return;

    const clean = newVal.toUpperCase();

    if (clean.length === 6) {
        fetchVehicleData(clean);
    } else if (MOTO_PLATE_REGEX.test(clean)) {
        debouncedMotoPlateSearch(clean);
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
            showModal.value = false;
            resetReceptionState();
        },
    });
};

const handleImageUpload = async (event) => {
    const file = event.target.files[0];
    if (!file) return;

    event.target.value = '';

    if (file.size > maxImageUploadBytes.value) {
        resetReceptionState();
        errorMsg.value = `LA IMAGEN SUPERA EL MÁXIMO DE ${formatFileSize(maxImageUploadBytes.value)}. REDUCE EL TAMAÑO Y REINTENTA.`;
        return;
    }

    isUploading.value = true;
    isAnalyzing.value = false;
    errorMsg.value = null;
    recognizedPlate.value = null;
    vehicleInfo.value = null;
    revokePreviewImage();
    previewImageUrl.value = URL.createObjectURL(file);

    const formData = new FormData();
    formData.append('image', file);

    try {
        const response = await window.axios.post(route('receptions.store', tenantRouteParams.value), formData, {
            headers: { 'Content-Type': 'multipart/form-data' }
        });

        if (response.data.queue) {
            isUploading.value = false;
            isAnalyzing.value = true;

            return;
        }

        const plate = response.data?.patente;

        if (!response.data?.valid || !plate) {
            errorMsg.value = response.data?.error || "FALLÓ ESCANEO";

            return;
        }

        recognizedPlate.value = plate;
        vehicleInfo.value = {
            brand: response.data.vehicle?.brand || 'SIN DATO',
            model: response.data.vehicle?.model || 'SIN DATO',
            color: response.data.vehicle?.color || 'SIN DATO',
        };
    } catch (error) {
        if (error.response?.status === 413) {
            errorMsg.value = `LA IMAGEN ES DEMASIADO PESADA PARA EL SERVIDOR. USA UNA FOTO MENOR A ${formatFileSize(maxImageUploadBytes.value)}.`;
        } else {
            errorMsg.value = error.response?.data?.error || "ERROR DE CONEXIÓN.";
        }
    } finally {
        isUploading.value = false;
    }
};

onMounted(() => {
    if (window.Echo) {
        window.Echo.private(`tenant.${tenantId}.reception`)
            .listen('PatentRecognized', (e) => {
                isAnalyzing.value = false;
                if (e.patente === "ERROR_FORMATO") {
                    errorMsg.value = "FALLÓ ESCANEO";
                } else {
                    recognizedPlate.value = e.patente;
                    vehicleInfo.value = {
                        brand: e.vehicle?.brand || 'SIN DATO',
                        model: e.vehicle?.model || 'SIN DATO',
                        color: e.vehicle?.color || 'SIN DATO',
                    };
                }
            });
    }
});

onUnmounted(() => {
    if (window.Echo) {
        window.Echo.leave(`tenant.${tenantId}.reception`);
    }

    revokePreviewImage();
});
</script>

<template>
    <Head title="Recepción" />

    <TallerLayout>
        <!-- Overlay de carga durante búsqueda en BD/API -->
        <div v-if="isSearching"
            class="fixed inset-0 z-[60] flex items-center justify-center bg-black/40 backdrop-blur-sm">
            <div class="flex flex-col items-center gap-4 bg-white p-8 rounded-3xl shadow-2xl">
                <div class="w-12 h-12 border-4 border-[#FF7A00]/20 border-t-[#FF7A00] rounded-full animate-spin"></div>
                <p class="text-slate-800 font-bold uppercase tracking-widest text-sm">Buscando en base de datos...</p>
            </div>
        </div>

        <!-- CASO PRO: Escáner de Patente IA -->
        <PpuScanner v-if="aiReceptionEnabled" data-tour="reception-new-entry" :recognized-ppu="formattedPlate || '---'"
            :is-processing="isUploading || isAnalyzing" :vehicle-info="vehicleInfo" :preview-image-url="previewImageUrl"
            @confirm="handleConfirmIngreso"
            @retry="triggerCamera" @manual="handleManualEntry" />

        <!-- CASO GRATUITO: Ingreso Manual Directo -->
        <div v-else class="w-full flex flex-col items-center py-6 px-4" data-tour="reception-new-entry">
            <div class="w-full max-w-lg space-y-4">
                <PlanUpgradeBanner
                    title="Escáner con IA no disponible"
                    :message="`Mejora tu plan para acceder a esta función. ${aiReceptionUpgradeMessage}`"
                />

                <div
                    class="w-full bg-white/80 backdrop-blur-xl rounded-[3rem] p-10 shadow-[0_20px_50px_rgba(0,0,0,0.05)] border border-white flex flex-col items-center text-center">
                    <div class="w-20 h-20 bg-[#FF7A00]/10 rounded-3xl flex items-center justify-center mb-8 rotate-3">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10 text-[#FF7A00]" fill="none"
                            viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                                d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                    </div>

                    <h1 class="text-4xl font-black text-slate-900 mb-3 tracking-tight uppercase">
                        Recepción <br> <span class="text-slate-400">Manual</span>
                    </h1>
                    <p class="text-slate-500 font-medium text-sm mb-10 leading-relaxed max-w-xs">
                        Inicia un nuevo registro de ingreso ingresando la patente del vehículo.
                    </p>

                    <button @click="handleManualEntry"
                        class="group w-full py-6 bg-[#FF7A00] text-white rounded-3xl text-lg font-black uppercase shadow-[0_15px_30px_rgba(249,168,38,0.3)] hover:bg-[#CC6200] transition-all active:scale-95 flex items-center justify-center gap-3">
                        <span>Nueva Recepción</span>
                        <svg xmlns="http://www.w3.org/2000/svg"
                            class="h-6 w-6 transition-transform duration-300 group-hover:translate-x-1" fill="none"
                            viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                        </svg>
                    </button>

                    <div class="mt-8 pt-6 border-t border-slate-50 w-full flex items-center justify-center gap-2">
                        <span class="w-1.5 h-1.5 rounded-full bg-[#FF7A00]"></span>
                        <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Ingreso Manual Obligatorio</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- MODAL DE VISTA PREVIA EDITABLE (Estilo Taller-Friendly & Light Theme) -->
        <div v-if="showModal" class="fixed inset-0 z-[100] flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-slate-900/40 backdrop-blur-sm transition-opacity"
                @click="closeModal"></div>

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
                    <button @click="closeModal"
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

                <!-- Info de cita agendada (si existe y tiene notas) -->
                <div v-if="appointmentData && appointmentData.notes"
                    class="mx-6 lg:mx-8 mt-4 rounded-2xl bg-blue-50 border border-blue-200 px-5 py-4 space-y-1">
                    <p class="text-[9px] font-black uppercase tracking-widest text-blue-600">Notas del agendamiento</p>
                    <p class="text-sm font-semibold text-slate-700">{{ appointmentData.notes }}</p>
                    <p v-if="appointmentData.pre_check_notes" class="text-xs text-slate-500 mt-1">{{ appointmentData.pre_check_notes }}</p>
                </div>

                <!-- Formulario Editable -->
                <form @submit.prevent="handleCreateOrder" class="p-6 lg:p-8 space-y-8">

                    <!-- Patente (Editable) -->
                    <div
                        class="flex flex-col items-center py-6 bg-gray-50 rounded-3xl border border-gray-100 shadow-inner group transition-all focus-within:ring-2 focus-within:ring-[#FF7A00]/20">
                        <p class="text-[9px] font-bold text-gray-400 uppercase tracking-[0.3em] mb-2">Placa de
                            Identificación</p>
                        <input v-model="form.plate" type="text"
                            class="w-full text-center bg-transparent border-none focus:ring-0 text-5xl font-mono font-black text-gray-900 tracking-widest plate-font uppercase placeholder-gray-200"
                            placeholder="AAAA11" maxlength="6" />
                        <p class="text-[9px] text-gray-400 mt-2 tracking-wider">Auto: 6 caracteres · Moto: 5 caracteres</p>
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
                                        v-for="brand in vehicleCatalogBrands"
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

                    <!-- Acciones -->
                    <div class="flex flex-col sm:flex-row gap-4 pt-4 border-t border-gray-100">
                        <button type="button" @click="closeModal"
                            class="order-2 sm:order-1 flex-1 py-4 bg-gray-100 hover:bg-gray-200 text-gray-500 rounded-full font-bold transition-all active:scale-95 text-sm uppercase">
                            CANCELAR
                        </button>
                        <button type="submit" :disabled="form.processing"
                            class="order-1 sm:order-2 flex-[2] py-4 bg-[#FF7A00] hover:bg-[#CC6200] text-white rounded-full font-black uppercase shadow-[0_8px_20px_rgba(249,168,38,0.3)] transition-all active:scale-95 disabled:opacity-50 disabled:cursor-wait flex items-center justify-center gap-2 tracking-wide text-lg">
                            <div v-if="form.processing"
                                class="w-5 h-5 border-2 border-white/30 border-t-white rounded-full animate-spin"></div>
                            {{ form.processing ? 'Procesando...' : 'GENERAR ORDEN' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Input oculto para captura de cámara nativa del dispositivo -->
        <input type="file" accept="image/*" capture="environment" ref="fileInput" class="hidden"
            @change="handleImageUpload" />

        <!-- Toast para Error de Lectura -->
        <div v-if="errorMsg"
            class="fixed top-24 left-1/2 -translate-x-1/2 bg-[#E61919] text-white px-8 py-4 rounded-full font-bold shadow-2xl z-50">
            {{ errorMsg }}
        </div>
    </TallerLayout>
</template>

<style scoped>
@import '@fontsource/archivo-narrow/700.css';

.plate-font {
    font-family: 'Archivo Narrow', sans-serif;
    letter-spacing: -0.05em;
    transform: scaleX(0.9);
}
</style>
