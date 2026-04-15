<script setup>
import { ref, watch } from 'vue';

const props = defineProps({
    message: { type: String, default: '' },
    type: { type: String, default: 'success' }, // success | error | warning | info
    duration: { type: Number, default: 4000 },
});

const emit = defineEmits(['dismiss']);

const visible = ref(false);
let timer = null;

watch(() => props.message, (msg) => {
    if (!msg) return;
    visible.value = true;
    clearTimeout(timer);
    timer = setTimeout(() => dismiss(), props.duration);
});

function dismiss() {
    visible.value = false;
    clearTimeout(timer);
    emit('dismiss');
}

const STYLES = {
    success: 'bg-emerald-600 text-white',
    error:   'bg-rose-600 text-white',
    warning: 'bg-amber-500 text-white',
    info:    'bg-blue-600 text-white',
};

const ICONS = {
    success: 'M5 13l4 4L19 7',
    error:   'M6 18L18 6M6 6l12 12',
    warning: 'M12 9v4m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z',
    info:    'M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z',
};
</script>

<template>
    <Transition
        enter-active-class="transition duration-300 ease-out"
        enter-from-class="translate-y-4 opacity-0"
        enter-to-class="translate-y-0 opacity-100"
        leave-active-class="transition duration-200 ease-in"
        leave-from-class="translate-y-0 opacity-100"
        leave-to-class="translate-y-4 opacity-0"
    >
        <div
            v-if="visible && message"
            :class="['fixed bottom-6 left-1/2 -translate-x-1/2 z-50 flex items-center gap-3 rounded-xl px-5 py-3.5 shadow-lg text-sm font-medium max-w-sm w-full', STYLES[type] ?? STYLES.info]"
            role="alert"
        >
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                <path stroke-linecap="round" stroke-linejoin="round" :d="ICONS[type] ?? ICONS.info" />
            </svg>
            <span class="flex-1">{{ message }}</span>
            <button @click="dismiss" class="ml-1 rounded-full p-0.5 hover:bg-white/20 transition-colors">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>
    </Transition>
</template>
