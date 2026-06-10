<script setup>
import { useFormatting } from '@/composables/useFormatting';

const { formatDateTime } = useFormatting();

defineProps({
    events: {
        type: Array,
        default: () => [],
    },
    variant: {
        type: String,
        default: 'desktop', // 'desktop' | 'mobile'
    },
});
</script>

<template>
    <div
        :class="[
            'overflow-hidden rounded-2xl border border-gray-100 bg-white shadow-sm',
            variant === 'desktop' ? 'hidden xl:block' : 'xl:hidden',
        ]"
    >
        <div :class="['flex items-center justify-between border-b border-gray-50 py-4', variant === 'desktop' ? 'px-5' : 'px-6']">
            <h2 class="text-[10px] font-black uppercase tracking-widest text-gray-400">Historial Comercial</h2>
            <span class="text-[10px] font-bold text-gray-400">{{ events?.length ?? 0 }} eventos</span>
        </div>
        <div :class="variant === 'desktop' ? 'p-5' : 'p-6'">
            <div
                v-if="!(events?.length)"
                :class="['rounded-xl border border-dashed border-gray-200 px-4 text-center text-xs font-semibold uppercase tracking-widest text-gray-400', variant === 'desktop' ? 'py-6' : 'py-8']"
            >
                Sin historial todavía
            </div>
            <div v-else class="space-y-3">
                <div v-for="event in events" :key="event.id" class="rounded-xl border border-gray-100 bg-gray-50 px-4 py-3">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <p class="text-sm font-bold text-gray-800">{{ event.description }}</p>
                            <p class="mt-0.5 text-[10px] font-black uppercase tracking-widest text-gray-400">{{ event.actor_type }} · {{ event.event_type }}</p>
                        </div>
                        <span class="shrink-0 text-[10px] font-semibold text-gray-400">{{ formatDateTime(event.created_at) }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
