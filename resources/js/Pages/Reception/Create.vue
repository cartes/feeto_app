<script setup>
import { ref, onMounted, onUnmounted, computed } from 'vue';
import { usePage, router } from '@inertiajs/vue3';
import axios from 'axios';
import PpuScanner from '@/Components/PpuScanner.vue';
import TallerLayout from '@/Layouts/TallerLayout.vue';

const page = usePage();
const tenantId = page.props.tenantId;

const isUploading = ref(false);
const isAnalyzing = ref(false);
const isSearching = ref(false); // Buscando en la BD local o Boostr
const showModal = ref(false);
const previewData = ref(null);

const recognizedPlate = ref(null);
const vehicleInfo = ref(null); 
const fileInput = ref(null);
const errorMsg = ref(null);

// Formateo de patente chilena: GK·SB·78
const formattedPlate = computed(() => {
    if (!recognizedPlate.value) return '';
    const clean = recognizedPlate.value.replace(/[^A-Z0-9]/gi, '').toUpperCase();
    if (clean.length >= 6) {
        return `${clean.slice(0, 2)}·${clean.slice(2, 4)}·${clean.slice(4, 6)}`;
    }
    return clean;
});

const triggerCamera = () => {
    recognizedPlate.value = null;
    vehicleInfo.value = null;
    errorMsg.value = null;
    fileInput.value.click();
};

const handleConfirmIngreso = async (ppu) => {
    const finalPpu = ppu || recognizedPlate.value;
    if (!finalPpu || finalPpu === '---') {
        errorMsg.value = "CAPTURE UNA PATENTE PRIMERO";
        return;
    }

    isSearching.value = true;
    errorMsg.value = null;
    
    try {
        const response = await axios.post(route('receptions.preview'), {
            patente: finalPpu
        });
        previewData.value = response.data;
        showModal.value = true;
    } catch (error) {
        errorMsg.value = "ERROR AL CONSULTAR DATOS.";
    } finally {
        isSearching.value = false;
    }
};

const handleCreateOrder = () => {
    if (!previewData.value) return;

    router.post(route('receptions.store_order'), {
        plate: previewData.value.vehicle.plate,
        brand: previewData.value.vehicle.brand,
        model: previewData.value.vehicle.model,
        client_name: previewData.value.client.name,
        client_rut: previewData.value.client.rut,
    });
};

const handleImageUpload = async (event) => {
    const file = event.target.files[0];
    if (!file) return;

    isUploading.value = true;
    errorMsg.value = null;
    recognizedPlate.value = null;

    const formData = new FormData();
    formData.append('image', file);

    try {
        const response = await window.axios.post(route('receptions.store'), formData, {
            headers: { 'Content-Type': 'multipart/form-data' }
        });
        if (response.data.queue) {
            isUploading.value = false;
            isAnalyzing.value = true;
        }
    } catch (error) {
        errorMsg.value = "ERROR DE CONEXIÓN.";
        isUploading.value = false;
        isAnalyzing.value = false;
    }
};

onMounted(() => {
    if (window.Echo) {
        window.Echo.channel(`tenant.${tenantId}.reception`)
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
        window.Echo.leaveChannel(`tenant.${tenantId}.reception`);
    }
});
</script>

<template>
    <TallerLayout>
        <!-- Overlay de carga durante búsqueda en BD/API -->
        <div v-if="isSearching" class="fixed inset-0 z-[60] flex items-center justify-center bg-black/40 backdrop-blur-sm">
            <div class="flex flex-col items-center gap-4 bg-white p-8 rounded-3xl shadow-2xl">
                <div class="w-12 h-12 border-4 border-[#F9A826]/20 border-t-[#F9A826] rounded-full animate-spin"></div>
                <p class="text-slate-800 font-bold uppercase tracking-widest text-sm">Buscando en base de datos...</p>
            </div>
        </div>

        <PpuScanner 
            :recognized-ppu="formattedPlate || '---'"
            :is-processing="isUploading || isAnalyzing"
            :vehicle-info="vehicleInfo"
            @confirm="handleConfirmIngreso"
            @retry="triggerCamera"
        />

        <!-- MODAL DE VISTA PREVIA (Estilo Dark Taller) -->
        <div v-if="showModal" class="fixed inset-0 z-[100] flex items-center justify-center p-4">
            <!-- Background opacity -->
            <div class="absolute inset-0 bg-black/80 backdrop-blur-md" @click="showModal = false"></div>
            
            <!-- Contenedor del Modal -->
            <div class="relative w-full max-w-lg bg-panel-bg border border-white/10 rounded-[2.5rem] shadow-[0_32px_64px_rgba(0,0,0,0.5)] overflow-hidden animate-in zoom-in duration-300">
                
                <!-- Encabezado -->
                <div class="p-8 border-b border-white/5">
                    <div class="flex justify-between items-start mb-4">
                        <h2 class="text-2xl font-black text-text-main tracking-tight uppercase">Vista Previa de Orden</h2>
                        <button @click="showModal = false" class="text-white/40 hover:text-white">
                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                        </button>
                    </div>
                    
                    <!-- Badge Dinámico -->
                    <span 
                        v-if="previewData?.is_new" 
                        class="bg-tech-orange text-black text-[10px] font-black px-3 py-1 rounded-full uppercase tracking-widest"
                    >
                        Cliente Nuevo
                    </span>
                    <span 
                        v-else 
                        class="bg-emerald-500 text-white text-[10px] font-black px-3 py-1 rounded-full uppercase tracking-widest"
                    >
                        Cliente Existente
                    </span>
                </div>

                <!-- Detalle -->
                <div class="p-8 space-y-8">
                    <!-- Patente -->
                    <div class="flex flex-col items-center py-4 bg-white/5 rounded-3xl border border-white/5">
                        <p class="text-[10px] font-bold text-white/30 uppercase tracking-[0.3em] mb-2">Placa Única</p>
                        <p class="text-5xl font-mono font-black text-tech-orange tracking-widest plate-font">
                            {{ previewData?.vehicle.plate }}
                        </p>
                    </div>

                    <div class="grid grid-cols-2 gap-6">
                        <div>
                            <p class="text-[10px] font-bold text-white/30 uppercase tracking-widest mb-1">Marca</p>
                            <p class="text-xl font-bold text-text-main uppercase">{{ previewData?.vehicle.brand }}</p>
                        </div>
                        <div>
                            <p class="text-[10px] font-bold text-white/30 uppercase tracking-widest mb-1">Modelo</p>
                            <p class="text-xl font-bold text-text-main uppercase">{{ previewData?.vehicle.model }}</p>
                        </div>
                        <div class="col-span-2">
                             <p class="text-[10px] font-bold text-white/30 uppercase tracking-widest mb-1">Dueño / Cliente</p>
                             <p class="text-xl font-bold text-text-main uppercase">{{ previewData?.client.name }}</p>
                             <p class="text-sm font-medium text-text-muted mt-1">RUT: {{ previewData?.client.rut || 'N/A' }}</p>
                        </div>
                    </div>
                </div>

                <!-- Footer Acciones -->
                <div class="p-6 bg-white/5 flex gap-4">
                    <button 
                        @click="showModal = false"
                        class="flex-1 py-4 bg-gray-600 hover:bg-gray-700 text-white rounded-full font-bold transition-all active:scale-95"
                    >
                        CANCELAR
                    </button>
                    <button 
                        @click="handleCreateOrder"
                        class="flex-1 py-4 bg-tech-red hover:bg-red-700 text-white rounded-full font-black uppercase shadow-lg shadow-red-900/20 transition-all active:scale-95"
                    >
                        GENERAR ORDEN
                    </button>
                </div>
            </div>
        </div>

        <!-- Input oculto para captura de cámara nativa del dispositivo -->
        <input 
            type="file" 
            accept="image/*" 
            capture="environment" 
            ref="fileInput" 
            class="hidden" 
            @change="handleImageUpload" 
        />
        
        <!-- Toast para Error de Lectura -->
        <div v-if="errorMsg" class="fixed top-24 left-1/2 -translate-x-1/2 bg-tech-red/90 backdrop-blur-md text-white px-8 py-4 rounded-full font-bold shadow-2xl z-50 animate-in fade-in slide-in-from-top-10">
            {{ errorMsg }}
        </div>
    </TallerLayout>
</template>

<style scoped>
/* Tipografía FE-Schrift (Similar a Patentes Chilenas) */
@import url('https://fonts.googleapis.com/css2?family=Archivo+Narrow:wght@700&display=swap');

.plate-font {
    font-family: 'Archivo Narrow', sans-serif; /* Usamos Archivo Narrow como fallback similar */
    letter-spacing: -0.05em;
    transform: scaleX(0.9); /* Condensamos ligeramente para igualar el estilo chileno */
}
</style>
