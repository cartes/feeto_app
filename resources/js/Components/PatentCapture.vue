<script setup>
import { ref } from 'vue';
import { useForm } from '@inertiajs/vue3';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import InputError from '@/Components/InputError.vue';

const result = ref(null);
const processing = ref(false);

const form = useForm({
    image: null,
});

const handleFileChange = (e) => {
    const file = e.target.files[0];
    if (file) {
        form.image = file;
        submit();
    }
};

const submit = () => {
    processing.value = true;
    result.value = null;

    form.post(route('ocr.process'), {
        forceFormData: true,
        onSuccess: (page) => {
            // Note: Since we are using response()->json() in the controller,
            // we might need to handle the response differently if Inertia expects a redirect.
            // For now, I'll assume the controller returns the data and we handle it.
        },
        onFinish: () => {
            processing.value = false;
        },
        onError: () => {
            processing.value = false;
        },
    });
};

// Handle manual fetch if using axios instead of Inertia for JSON responses
import axios from 'axios';
const capturePlate = async (e) => {
    const file = e.target.files[0];
    if (!file) return;

    processing.value = true;
    result.value = null;
    
    const formData = new FormData();
    formData.append('image', file);

    try {
        const response = await axios.post(route('ocr.process'), formData);
        result.value = response.data;
    } catch (error) {
        console.error('Error processing OCR:', error);
    } finally {
        processing.value = false;
    }
};
</script>

<template>
    <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6">
        <div class="flex flex-col items-center gap-6">
            <h2 class="text-xl font-semibold text-gray-800 dark:text-gray-200">
                Captura de Patente
            </h2>
            
            <p class="text-gray-600 dark:text-gray-400 text-center max-w-md">
                Usa la cámara para capturar la patente del vehículo. El sistema reconocerá los datos automáticamente.
            </p>

            <div class="relative w-full max-w-xs">
                <label 
                    class="flex flex-col items-center justify-center w-full h-32 border-2 border-dashed rounded-lg cursor-pointer bg-gray-50 dark:hover:bg-bray-800 dark:bg-gray-700 hover:bg-gray-100 border-gray-300 dark:border-gray-600 dark:hover:border-gray-500 dark:hover:bg-gray-600 transition-all duration-300"
                    :class="{ 'opacity-50 cursor-not-allowed': processing }"
                >
                    <div class="flex flex-col items-center justify-center pt-5 pb-6">
                        <svg class="w-8 h-8 mb-4 text-gray-500 dark:text-gray-400" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 20 16">
                            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 13h3a3 3 0 0 0 0-6h-.025A5.56 5.56 0 0 0 16 6.5 5.5 5.5 0 0 0 5.207 5.021C5.137 5.017 5.071 5 5 5a4 4 0 0 0 0 8h2.167M10 15V6m0 0L8 8m2-2 2 2"/>
                        </svg>
                        <p class="mb-2 text-sm text-gray-500 dark:text-gray-400 font-semibold">
                            {{ processing ? 'Procesando...' : 'Tocar para Capturar' }}
                        </p>
                    </div>
                    <input 
                        id="dropzone-file" 
                        type="file" 
                        class="hidden" 
                        accept="image/*" 
                        capture="camera"
                        @change="capturePlate"
                        :disabled="processing"
                    />
                </label>
            </div>

            <!-- Pulsing Loading State -->
            <div v-if="processing" class="w-full max-w-md space-y-4">
                <div class="h-8 bg-gray-200 dark:bg-gray-700 rounded w-1/3 animate-pulse"></div>
                <div class="grid grid-cols-2 gap-4">
                    <div class="h-20 bg-gray-200 dark:bg-gray-700 rounded animate-pulse"></div>
                    <div class="h-20 bg-gray-200 dark:bg-gray-700 rounded animate-pulse"></div>
                    <div class="h-20 bg-gray-200 dark:bg-gray-700 rounded animate-pulse"></div>
                    <div class="h-20 bg-gray-200 dark:bg-gray-700 rounded animate-pulse"></div>
                </div>
            </div>

            <!-- Results -->
            <div v-if="result" class="w-full max-w-md mt-4 animate-in fade-in slide-in-from-bottom-4 duration-500">
                <div v-if="result.valid" class="space-y-4">
                    <div class="flex items-center justify-between">
                        <span class="text-2xl font-bold text-blue-600 dark:text-blue-400 bg-blue-50 dark:bg-blue-900/30 px-4 py-2 rounded-lg border border-blue-200 dark:border-blue-800">
                            {{ result.plate }}
                        </span>
                        <span class="text-xs font-medium text-gray-500 dark:text-gray-400">
                            Confianza: {{ (result.confidence * 100).toFixed(1) }}%
                        </span>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div class="bg-gray-50 dark:bg-gray-700/50 p-4 rounded-xl border border-gray-100 dark:border-gray-600">
                            <span class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wider font-semibold">Propietario</span>
                            <p class="text-gray-900 dark:text-white font-medium">{{ result.name || 'No disponible' }}</p>
                            <p class="text-sm text-gray-500 dark:text-gray-400">{{ result.rut || '-' }}</p>
                        </div>
                        <div class="bg-gray-50 dark:bg-gray-700/50 p-4 rounded-xl border border-gray-100 dark:border-gray-600">
                            <span class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wider font-semibold">Marca / Modelo</span>
                            <p class="text-gray-900 dark:text-white font-medium">{{ result.brand || '-' }}</p>
                            <p class="text-sm text-gray-500 dark:text-gray-400">{{ result.model || '-' }}</p>
                        </div>
                        <div class="bg-gray-50 dark:bg-gray-700/50 p-4 rounded-xl border border-gray-100 dark:border-gray-600 sm:col-span-2">
                            <span class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wider font-semibold">VIN</span>
                            <p class="text-gray-900 dark:text-white font-mono break-all">{{ result.vin || 'No detectado' }}</p>
                        </div>
                    </div>
                </div>
                
                <div v-else class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 p-4 rounded-lg text-red-700 dark:text-red-300 text-sm">
                    No se pudo reconocer una patente válida. Por favor, intenta de nuevo con una imagen más clara.
                </div>
            </div>
        </div>
    </div>
</template>
