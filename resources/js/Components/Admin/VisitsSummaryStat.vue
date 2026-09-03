<script setup>
import { computed } from 'vue';
import { changeMeta, formatNumber, toneClasses } from '@/utils/visits';

const props = defineProps({
    label: { type: String, required: true },
    value: { type: [Number, String, null], default: null },
    hint: { type: String, default: '' },
    change: { type: Number, default: undefined },
    previous: { type: Number, default: undefined },
    accent: { type: String, default: '' },
});

const displayValue = computed(() => (typeof props.value === 'number' ? formatNumber(props.value) : props.value ?? '—'));
const showChange = computed(() => props.change !== undefined || props.previous !== undefined);
const meta = computed(() => changeMeta(props.change ?? null, props.previous ?? 0));
</script>

<template>
    <div class="min-w-0">
        <div class="flex items-center gap-2">
            <span v-if="accent" class="h-2 w-2 shrink-0 rounded-full" :style="{ background: accent }" />
            <span class="truncate text-[11px] font-semibold uppercase tracking-wider text-slate-400">{{ label }}</span>
        </div>
        <div class="mt-1 flex flex-wrap items-baseline gap-x-2 gap-y-1">
            <span class="text-2xl font-black tracking-tight text-slate-900 tabular-nums">{{ displayValue }}</span>
            <span
                v-if="showChange"
                :class="['inline-flex items-center rounded-full px-1.5 py-0.5 text-[11px] font-semibold ring-1 ring-inset', toneClasses[meta.tone]]"
                :title="previous !== undefined ? `Período anterior: ${formatNumber(previous)}` : ''"
            >
                {{ meta.label }}
            </span>
        </div>
        <p v-if="hint" class="mt-0.5 text-xs text-slate-500">{{ hint }}</p>
    </div>
</template>
