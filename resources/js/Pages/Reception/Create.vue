<script setup>
import { ref, onMounted, onUnmounted } from 'vue';
import TallerLayout from '@/Layouts/TallerLayout.vue';
import { usePage, router } from '@inertiajs/vue3';

const page = usePage();
const tenantId = page.props.tenantId;

const isUploading = ref(false);
const isAnalyzing = ref(false);
const recognizedPlate = ref(null);
const fileInput = ref(null);
const errorMsg = ref(null);

const triggerCamera = () => {
    recognizedPlate.value = null;
    errorMsg.value = null;
    fileInput.value.click();
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
            headers: {
                'Content-Type': 'multipart/form-data'
            }
        });
        
        if (response.data.queue) {
            isUploading.value = false;
            isAnalyzing.value = true;
        }
    } catch (error) {
        console.error("Error uploading image", error);
        errorMsg.value = "Error subiendo la imagen.";
        isUploading.value = false;
        isAnalyzing.value = false;
    }
};

const confirmPlate = () => {
    // Redirigir a la siguiente vista con la patente conformada (para crear orden o verificar cliente)
    alert("¡Patente confirmada!: " + recognizedPlate.value);
};

// Listen to Laravel Echo Reverb events
onMounted(() => {
    if (window.Echo) {
        window.Echo.channel(`tenant.${tenantId}.reception`)
            .listen('PatentRecognized', (e) => {
                isAnalyzing.value = false;
                if (e.patente === "ERROR_FORMATO") {
                    errorMsg.value = "No se pudo leer una patente válida en la foto.";
                } else {
                    recognizedPlate.value = e.patente;
                }
            });
    } else {
        console.warn("Echo no está montado aún. Revisa la instalación de Reverb.");
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
        <template #header>
            <h2 class="text-xl font-bold leading-tight text-gray-900">
                Nueva Recepción
            </h2>
        </template>

        <div class="max-w-xl mx-auto flex flex-col items-center justify-center min-h-[50vh]">
            
            <input 
                type="file" 
                accept="image/*" 
                capture="environment" 
                ref="fileInput" 
                class="hidden" 
                @change="handleImageUpload" 
            />

            <!-- Start State -->
            <div v-if="!isUploading && !isAnalyzing && !recognizedPlate && !errorMsg" class="w-full text-center">
                <button 
                    @click="triggerCamera" 
                    class="w-full py-10 px-6 bg-orange-500 hover:bg-orange-600 active:bg-orange-700 text-white font-bold rounded-2xl shadow-xl transition-all flex flex-col items-center gap-4 min-h-[120px]"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-16 w-16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                    <span class="text-2xl tracking-widest leading-none">ESCANEAR PATENTE</span>
                </button>
            </div>

            <!-- Loading State -->
            <div v-if="isUploading" class="flex flex-col items-center gap-4">
                <div class="animate-spin rounded-full h-16 w-16 border-b-4 border-orange-500"></div>
                <p class="text-xl font-medium text-gray-600 animate-pulse">Subiendo foto...</p>
            </div>

            <!-- Analyzing State -->
            <div v-if="isAnalyzing" class="flex flex-col items-center gap-4">
                <div class="animate-spin rounded-full h-16 w-16 border-b-4 border-blue-500"></div>
                <p class="text-xl font-medium text-blue-600 animate-pulse">Analizando placa...</p>
            </div>

            <!-- Error State -->
            <div v-if="errorMsg" class="w-full text-center flex flex-col gap-6">
                <div class="p-6 bg-red-100 rounded-2xl border-2 border-red-500 flex flex-col items-center gap-3">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <p class="text-xl font-bold text-red-700">{{ errorMsg }}</p>
                </div>
                <button 
                    @click="triggerCamera" 
                    class="w-full py-6 px-4 bg-gray-200 hover:bg-gray-300 text-gray-800 font-bold rounded-2xl text-xl flex items-center justify-center min-h-[80px]"
                >
                    Intentar de nuevo
                </button>
            </div>

            <!-- Success State -->
            <div v-if="recognizedPlate" class="w-full text-center flex flex-col gap-6">
                <!-- Big Plate Display -->
                <div class="px-8 py-6 rounded-2xl border-4 border-gray-900 bg-white shadow-xl">
                    <p class="text-[5rem] md:text-[6rem] font-bold text-gray-900 uppercase tracking-widest font-mono">
                        {{ recognizedPlate.slice(0, 4) }}<br class="sm:hidden" />{{ recognizedPlate.slice(4) }}
                    </p>
                </div>

                <!-- Action Buttons -->
                <div class="grid grid-cols-2 gap-4 w-full">
                    <button 
                        @click="triggerCamera" 
                        class="py-5 bg-gray-200 hover:bg-gray-300 active:bg-gray-400 text-gray-800 font-bold rounded-2xl text-lg min-h-[64px]"
                    >
                        Reintentar
                    </button>
                    <button 
                        @click="confirmPlate"
                        class="py-5 bg-green-500 hover:bg-green-600 active:bg-green-700 text-white font-bold rounded-2xl text-lg min-h-[64px] shadow-lg"
                    >
                        Confirmar
                    </button>
                </div>
            </div>

        </div>
    </TallerLayout>
</template>
