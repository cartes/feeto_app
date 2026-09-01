<script setup>
import { ref, computed } from 'vue';

const props = defineProps({
    modelValue: {
        type: Array,
        default: () => [],
    },
    readonly: {
        type: Boolean,
        default: false,
    },
});

const emit = defineEmits(['update:modelValue']);

const damageTypes = [
    { value: 'rayon', label: 'Rayón', color: '#f59e0b' },
    { value: 'abolladura', label: 'Abolladura', color: '#ef4444' },
    { value: 'trizadura', label: 'Trizadura', color: '#8b5cf6' },
    { value: 'pintura', label: 'Pintura', color: '#0ea5e9' },
    { value: 'falta_pieza', label: 'Falta pieza', color: '#64748b' },
    { value: 'otro', label: 'Otro', color: '#334155' },
];

const selectedType = ref('rayon');
const svgRef = ref(null);

const damages = computed(() => props.modelValue || []);

const typeColor = (type) => damageTypes.find((t) => t.value === type)?.color || '#334155';
const typeLabel = (type) => damageTypes.find((t) => t.value === type)?.label || 'Otro';

const handleDiagramClick = (event) => {
    if (props.readonly || !svgRef.value) return;

    const rect = svgRef.value.getBoundingClientRect();
    const x = ((event.clientX - rect.left) / rect.width) * 100;
    const y = ((event.clientY - rect.top) / rect.height) * 100;

    emit('update:modelValue', [
        ...damages.value,
        {
            x: Math.round(x * 10) / 10,
            y: Math.round(y * 10) / 10,
            type: selectedType.value,
            note: '',
        },
    ]);
};

const removeDamage = (index) => {
    const next = [...damages.value];
    next.splice(index, 1);
    emit('update:modelValue', next);
};

const updateNote = (index, note) => {
    const next = damages.value.map((d, i) => (i === index ? { ...d, note } : d));
    emit('update:modelValue', next);
};
</script>

<template>
    <div class="space-y-4">
        <!-- Selector de tipo de daño -->
        <div v-if="!readonly" class="flex flex-wrap gap-2">
            <button v-for="type in damageTypes" :key="type.value" type="button"
                @click="selectedType = type.value"
                class="flex items-center gap-1.5 rounded-full px-3 py-1.5 text-[10px] font-black uppercase tracking-widest border transition-all"
                :class="selectedType === type.value
                    ? 'bg-slate-900 text-white border-slate-900 shadow-md'
                    : 'bg-white text-slate-500 border-slate-200 hover:border-slate-400'">
                <span class="w-2.5 h-2.5 rounded-full" :style="{ backgroundColor: type.color }"></span>
                {{ type.label }}
            </button>
        </div>

        <p v-if="!readonly" class="text-[10px] font-bold uppercase tracking-widest text-slate-400">
            Toca el diagrama donde está el daño
        </p>

        <!-- Diagrama del vehículo (vista superior) -->
        <div class="relative mx-auto w-full max-w-[240px] select-none">
            <svg ref="svgRef" viewBox="0 0 260 480" class="w-full h-auto"
                :class="readonly ? '' : 'cursor-crosshair'"
                @click="handleDiagramClick">
                <!-- Ruedas -->
                <rect x="34" y="86" width="18" height="54" rx="9" fill="#cbd5e1" />
                <rect x="208" y="86" width="18" height="54" rx="9" fill="#cbd5e1" />
                <rect x="34" y="348" width="18" height="54" rx="9" fill="#cbd5e1" />
                <rect x="208" y="348" width="18" height="54" rx="9" fill="#cbd5e1" />

                <!-- Espejos -->
                <rect x="36" y="146" width="16" height="26" rx="5" fill="#e2e8f0" stroke="#94a3b8" stroke-width="1.5" />
                <rect x="208" y="146" width="16" height="26" rx="5" fill="#e2e8f0" stroke="#94a3b8" stroke-width="1.5" />

                <!-- Carrocería -->
                <path
                    d="M130 12 C 78 12, 60 42, 56 96 L 52 384 C 52 440, 82 468, 130 468 C 178 468, 208 440, 208 384 L 204 96 C 200 42, 182 12, 130 12 Z"
                    fill="#f8fafc" stroke="#64748b" stroke-width="2.5" />

                <!-- Capó -->
                <path d="M70 100 C 90 84, 170 84, 190 100" fill="none" stroke="#cbd5e1" stroke-width="2" />

                <!-- Parabrisas -->
                <path d="M78 168 L 182 168 L 172 128 L 88 128 Z" fill="#e0f2fe" stroke="#94a3b8" stroke-width="1.5" />

                <!-- Techo -->
                <rect x="74" y="178" width="112" height="128" rx="16" fill="#f1f5f9" stroke="#cbd5e1" stroke-width="1.5" />

                <!-- Luneta trasera -->
                <path d="M80 316 L 180 316 L 172 352 L 88 352 Z" fill="#e0f2fe" stroke="#94a3b8" stroke-width="1.5" />

                <!-- Maletero -->
                <path d="M72 400 C 95 414, 165 414, 188 400" fill="none" stroke="#cbd5e1" stroke-width="2" />

                <!-- Marcadores de daños -->
                <g v-for="(damage, index) in damages" :key="index">
                    <circle :cx="(damage.x / 100) * 260" :cy="(damage.y / 100) * 480" r="11"
                        :fill="typeColor(damage.type)" stroke="white" stroke-width="2.5" />
                    <text :x="(damage.x / 100) * 260" :y="(damage.y / 100) * 480 + 4" text-anchor="middle"
                        fill="white" font-size="12" font-weight="900">{{ index + 1 }}</text>
                </g>
            </svg>
        </div>

        <!-- Lista de daños marcados -->
        <div v-if="damages.length > 0" class="space-y-2">
            <div v-for="(damage, index) in damages" :key="index"
                class="flex items-center gap-3 rounded-2xl border border-slate-200 bg-white px-4 py-3 shadow-sm">
                <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full text-xs font-black text-white"
                    :style="{ backgroundColor: typeColor(damage.type) }">
                    {{ index + 1 }}
                </span>
                <div class="min-w-0 flex-1">
                    <p class="text-[10px] font-black uppercase tracking-widest text-slate-500">
                        {{ typeLabel(damage.type) }}
                    </p>
                    <input v-if="!readonly" :value="damage.note" type="text" maxlength="255"
                        @input="updateNote(index, $event.target.value)"
                        class="mt-1 w-full border-none bg-slate-50 rounded-xl px-3 py-2 text-xs font-semibold text-slate-700 placeholder-slate-300 focus:ring-2 focus:ring-[#FF7A00]"
                        placeholder="Detalle (ej: puerta conductor)" />
                    <p v-else-if="damage.note" class="mt-0.5 text-xs font-semibold text-slate-700">{{ damage.note }}</p>
                </div>
                <button v-if="!readonly" type="button" @click="removeDamage(index)"
                    class="shrink-0 rounded-full p-2 text-slate-300 hover:bg-red-50 hover:text-red-500 transition-colors">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24"
                        stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>

        <p v-else class="text-center text-[10px] font-bold uppercase tracking-widest text-slate-300">
            Sin daños registrados
        </p>
    </div>
</template>
