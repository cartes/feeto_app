<script setup>
import { ref, onMounted, onUnmounted, watch } from 'vue';

const props = defineProps({
    modelValue: {
        type: String,
        default: null,
    },
});

const emit = defineEmits(['update:modelValue']);

const canvasRef = ref(null);
const hasStrokes = ref(false);

let ctx = null;
let drawing = false;
let resizeObserver = null;

const setupCanvas = () => {
    const canvas = canvasRef.value;
    if (!canvas) return;

    const dpr = window.devicePixelRatio || 1;
    const rect = canvas.getBoundingClientRect();

    // Preserva el trazo actual si el contenedor cambia de tamaño
    const previous = hasStrokes.value ? canvas.toDataURL('image/png') : null;

    canvas.width = rect.width * dpr;
    canvas.height = rect.height * dpr;

    ctx = canvas.getContext('2d');
    ctx.scale(dpr, dpr);
    ctx.lineWidth = 2.5;
    ctx.lineCap = 'round';
    ctx.lineJoin = 'round';
    ctx.strokeStyle = '#0f172a';

    if (previous) {
        const img = new Image();
        img.onload = () => ctx.drawImage(img, 0, 0, rect.width, rect.height);
        img.src = previous;
    }
};

const pointerPosition = (event) => {
    const rect = canvasRef.value.getBoundingClientRect();
    return { x: event.clientX - rect.left, y: event.clientY - rect.top };
};

const startStroke = (event) => {
    if (!ctx) return;
    drawing = true;
    canvasRef.value.setPointerCapture(event.pointerId);
    const { x, y } = pointerPosition(event);
    ctx.beginPath();
    ctx.moveTo(x, y);
    // Punto inicial visible aunque sea un toque sin arrastre
    ctx.lineTo(x + 0.1, y + 0.1);
    ctx.stroke();
    hasStrokes.value = true;
};

const moveStroke = (event) => {
    if (!drawing || !ctx) return;
    const { x, y } = pointerPosition(event);
    ctx.lineTo(x, y);
    ctx.stroke();
};

const endStroke = () => {
    if (!drawing) return;
    drawing = false;
    emit('update:modelValue', canvasRef.value.toDataURL('image/png'));
};

const clear = () => {
    const canvas = canvasRef.value;
    if (!canvas || !ctx) return;
    const rect = canvas.getBoundingClientRect();
    ctx.clearRect(0, 0, rect.width, rect.height);
    hasStrokes.value = false;
    emit('update:modelValue', null);
};

watch(() => props.modelValue, (value) => {
    // Reset externo (ej: cierre del modal): limpia el lienzo
    if (!value && hasStrokes.value && !drawing) {
        const canvas = canvasRef.value;
        const rect = canvas.getBoundingClientRect();
        ctx?.clearRect(0, 0, rect.width, rect.height);
        hasStrokes.value = false;
    }
});

onMounted(() => {
    setupCanvas();
    resizeObserver = new ResizeObserver(() => setupCanvas());
    resizeObserver.observe(canvasRef.value);
});

onUnmounted(() => {
    resizeObserver?.disconnect();
});
</script>

<template>
    <div class="space-y-2">
        <div class="relative rounded-2xl border-2 border-dashed border-slate-200 bg-white overflow-hidden">
            <canvas ref="canvasRef" class="w-full h-40 touch-none cursor-crosshair"
                @pointerdown.prevent="startStroke" @pointermove.prevent="moveStroke"
                @pointerup="endStroke" @pointercancel="endStroke"></canvas>

            <p v-if="!hasStrokes"
                class="pointer-events-none absolute inset-0 flex items-center justify-center text-[10px] font-black uppercase tracking-widest text-slate-300">
                Firme aquí con el dedo
            </p>

            <button v-if="hasStrokes" type="button" @click="clear"
                class="absolute top-2 right-2 rounded-full bg-slate-100 px-3 py-1.5 text-[9px] font-black uppercase tracking-widest text-slate-500 hover:bg-slate-200 transition-colors">
                Limpiar
            </button>
        </div>
    </div>
</template>
