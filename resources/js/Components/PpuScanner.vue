<script setup>
import { ref, computed } from 'vue';

const props = defineProps({
    recognizedPpu: {
        type: String,
        default: 'AAAA-11' // Placeholder for UI preview
    },
    isProcessing: {
        type: Boolean,
        default: false
    },
    vehicleInfo: {
        type: Object,
        default: null
    }
});

const emit = defineEmits(['confirm', 'retry']);

const handleConfirm = () => {
    emit('confirm', props.recognizedPpu);
};

const handleRetry = () => {
    emit('retry');
};

const hasValidPlate = computed(() => {
    return props.recognizedPpu && props.recognizedPpu !== '---' && props.recognizedPpu !== 'AAAA-11';
});
</script>

<template>
    <div class="w-full flex flex-col items-center md:items-start justify-center font-sans tracking-tight py-4">

        <!-- Tarjeta Principal con Max Width para Desktop -->
        <div
            class="w-full max-w-xl bg-white/90 backdrop-blur-3xl rounded-[2.5rem] p-6 lg:p-8 shadow-[0_20px_40px_rgba(0,0,0,0.06)] border border-white relative overflow-hidden flex flex-col items-center md:items-start transition-all">

            <!-- Etiqueta superior flotante -->
            <div
                class="absolute top-6 left-6 bg-[#F9A826] text-white text-[10px] font-bold uppercase tracking-wider px-3 py-1.5 rounded-full shadow-md z-10">
                Escáner Inteligente
            </div>

            <!-- Título y Subtítulo estilo imagen -->
            <div class="w-full text-left mt-12 mb-6 lg:mb-8">
                <h1 class="text-3xl font-bold text-slate-800 leading-tight">
                    Gestión <br>
                    <span class="text-slate-500">Más Inteligente.</span>
                </h1>
                <p class="text-xs text-slate-400 mt-2 font-medium">Reconocimiento visual de vehículos</p>
            </div>

            <!-- Sección de Visor de Cámara (Menos alto: aspect-video) -->
            <button @click="!isProcessing && handleRetry()" :class="[
                'w-full aspect-video lg:aspect-[21/9] bg-gradient-to-br from-slate-100 to-slate-200 rounded-[2rem] flex flex-col items-center justify-center relative overflow-hidden border border-slate-50 shadow-inner group transition-all',
                isProcessing ? 'cursor-wait' : 'cursor-pointer hover:shadow-md hover:border-[#F9A826]/30 active:scale-[0.98]'
            ]">
                <div v-if="isProcessing"
                    class="absolute inset-0 flex items-center justify-center bg-white/60 backdrop-blur-md z-10">
                    <div class="flex flex-col items-center gap-3">
                        <div
                            class="w-12 h-12 border-4 border-[#F9A826]/30 border-t-[#F9A826] rounded-full animate-spin">
                        </div>
                        <p class="text-slate-800 text-sm font-bold tracking-widest uppercase">Escaneando...</p>
                    </div>
                </div>

                <svg xmlns="http://www.w3.org/2000/svg"
                    class="h-12 w-12 text-slate-300 mx-auto transition-colors duration-300"
                    :class="{ 'group-hover:text-[#F9A826]': !isProcessing }" fill="none" viewBox="0 0 24 24"
                    stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                        d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z" />
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                        d="M15 13a3 3 0 11-6 0 3 3 0 016 0z" />
                </svg>
                <span v-if="!isProcessing && !hasValidPlate"
                    class="text-slate-400 font-semibold text-[11px] mt-2 transition-colors duration-300 group-hover:text-[#F9A826]">
                    Capturar Imagen
                </span>
            </button>

            <!-- Overlay de Información (Reducido levemente para armonía) -->
            <div
                class="w-full mt-4 bg-white border border-slate-100 p-4 rounded-[2rem] shadow-sm relative overflow-hidden flex flex-col items-center justify-center translate-y-[-1.5rem] z-20 transition-all duration-300">
                <p class="text-[9px] font-bold text-slate-400 uppercase tracking-[0.2em] mb-1">Patente Detectada</p>
                <h2 class="text-4xl lg:text-5xl font-mono font-black tracking-wider transition-colors duration-300"
                    :class="hasValidPlate ? 'text-slate-800' : 'text-slate-300'">
                    {{ recognizedPpu }}
                </h2>

                <!-- Info del Vehículo extraída por IA -->
                <div v-if="hasValidPlate && vehicleInfo"
                    class="mt-3 flex items-center justify-center bg-slate-50 border border-slate-100 px-4 py-2 rounded-full text-xs w-full transition-all">
                    <p class="font-bold text-slate-700 truncate min-w-0 flex items-center gap-1.5 uppercase">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 text-[#F9A826]" fill="none"
                            viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z" />
                        </svg>
                        {{ vehicleInfo.brand || 'Vehículo' }} <span class="text-slate-400 font-medium ml-1">{{
                            vehicleInfo.model || 'Desconocido' }}</span>
                    </p>
                </div>
            </div>

            <!-- Botones de Acción (Ancho Completo pero compactos) -->
            <div class="w-full flex flex-col gap-3 mt-2">
                <!-- CASO 1: Aún no se captura patente -->
                <button v-if="!hasValidPlate" @click="handleRetry" :disabled="isProcessing"
                    class="w-full py-3.5 bg-[#F9A826] text-white rounded-full text-sm font-bold shadow-[0_8px_20px_rgba(249,168,38,0.2)] hover:bg-[#E59A22] transition-colors active:scale-95 disabled:opacity-50 flex items-center justify-center gap-2">
                    Comenzar Escaneo
                </button>

                <!-- CASO 2: Patente detectada, se puede confirmar o re-escanear -->
                <template v-else>
                    <button @click="handleConfirm" :disabled="isProcessing"
                        class="w-full py-3.5 bg-[#F9A826] text-white rounded-full text-sm font-bold shadow-[0_8px_20px_rgba(249,168,38,0.2)] hover:bg-[#E59A22] transition-colors active:scale-95 disabled:opacity-50 flex justify-center items-center gap-2 uppercase tracking-wide">
                        Confirmar Ingreso
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24"
                            stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                    </button>
                    <button @click="handleRetry" :disabled="isProcessing"
                        class="w-full py-2 bg-transparent text-slate-400 rounded-full text-[11px] font-bold hover:bg-slate-50 transition-colors uppercase">
                        Reintentar foto
                    </button>
                </template>
            </div>

        </div>
    </div>
</template>
