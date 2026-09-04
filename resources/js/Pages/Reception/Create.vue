<script setup>
import { computed, onMounted, onUnmounted, ref } from 'vue';
import { Head, usePage } from '@inertiajs/vue3';
import PpuScanner from '@/Components/PpuScanner.vue';
import PlanUpgradeBanner from '@/Components/PlanUpgradeBanner.vue';
import CreateWorkOrderModal from '@/Components/Reception/CreateWorkOrderModal.vue';
import TallerLayout from '@/Layouts/TallerLayout.vue';
import { useTenantRouting } from '@/composables/useTenantRouting';

const page = usePage();
const tenantId = page.props.tenantId;
const maxImageUploadBytes = computed(() => Number(page.props.maxImageUploadBytes ?? 5 * 1024 * 1024));
const { tenantRouteParams } = useTenantRouting();
const planAccess = computed(() => page.props.planAccess ?? {});
const aiReceptionEnabled = computed(() => planAccess.value?.ai_reception ?? false);
const aiReceptionUpgradeMessage = computed(() => planAccess.value?.upgrade_messages?.ai_reception ?? 'Mejora tu plan para acceder a esta función');
const vehicleCatalogBrands = computed(() => page.props.vehicleCatalogBrands ?? []);

const isUploading = ref(false);
const isAnalyzing = ref(false);
const showModal = ref(false);

const recognizedPlate = ref(null);
const vehicleInfo = ref(null);
const previewImageUrl = ref(null);
const fileInput = ref(null);
const errorMsg = ref(null);

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

const MOTO_PLATE_REGEX = /^([A-Z]{3}[0-9]{2}|[A-Z]{2}[0-9]{3})$/;

const formattedPlate = computed(() => {
    if (!recognizedPlate.value) return '';
    const clean = recognizedPlate.value.replace(/[^A-Z0-9]/gi, '').toUpperCase();
    if (clean.length > 6) {
        return clean;
    }
    if (clean.length === 6) {
        return `${clean.slice(0, 2)}·${clean.slice(2, 4)}·${clean.slice(4, 6)}`;
    }
    if (MOTO_PLATE_REGEX.test(clean)) {
        return clean.replace(/^([A-Z]+)([0-9]+)$/, '$1·$2');
    }
    return clean;
});

const resetReceptionState = () => {
    recognizedPlate.value = null;
    vehicleInfo.value = null;
    revokePreviewImage();
    errorMsg.value = null;
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

const handleConfirmIngreso = (ppu) => {
    const finalPpu = ppu || recognizedPlate.value;
    if (!finalPpu || finalPpu === '---') {
        errorMsg.value = 'CAPTURE O INGRESE UNA PATENTE';
        return;
    }
    recognizedPlate.value = finalPpu;
    showModal.value = true;
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
            headers: { 'Content-Type': 'multipart/form-data' },
        });

        if (response.data.queue) {
            isUploading.value = false;
            isAnalyzing.value = true;

            return;
        }

        const plate = response.data?.patente;

        if (!response.data?.valid || !plate) {
            errorMsg.value = response.data?.error || 'FALLÓ ESCANEO';

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
            errorMsg.value = error.response?.data?.error || 'ERROR DE CONEXIÓN.';
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
                if (e.patente === 'ERROR_FORMATO') {
                    errorMsg.value = 'FALLÓ ESCANEO';
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
        <!-- CASO PRO: Escáner de Patente IA -->
        <PpuScanner
            v-if="aiReceptionEnabled"
            data-tour="reception-new-entry"
            :recognized-ppu="formattedPlate || '---'"
            :is-processing="isUploading || isAnalyzing"
            :vehicle-info="vehicleInfo"
            :preview-image-url="previewImageUrl"
            @confirm="handleConfirmIngreso"
            @retry="triggerCamera"
            @manual="handleManualEntry"
        />

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

        <!-- MODAL DE VISTA PREVIA / CREACIÓN DE OT -->
        <CreateWorkOrderModal
            v-model:show="showModal"
            :initial-plate="recognizedPlate"
            :initial-vehicle-info="vehicleInfo"
            :vehicle-catalog-brands="vehicleCatalogBrands"
            @close="resetReceptionState"
        />

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
