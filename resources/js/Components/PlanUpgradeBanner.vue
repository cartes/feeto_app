<script setup>
defineProps({
    title: {
        type: String,
        default: 'Función bloqueada por plan',
    },
    message: {
        type: String,
        default: 'Mejora tu plan para acceder a esta función',
    },
    tone: {
        type: String,
        default: 'amber',
    },
});
</script>

<template>
    <div
        :class="tone === 'slate' ? 'border-slate-200 bg-slate-50 text-slate-700' : 'border-amber-200 bg-amber-50 text-amber-800'"
        class="flex items-start gap-3 rounded-2xl border px-4 py-3"
    >
        <div
            :class="tone === 'slate' ? 'bg-slate-200 text-slate-600' : 'bg-amber-100 text-amber-600'"
            class="mt-0.5 flex h-9 w-9 flex-shrink-0 items-center justify-center rounded-xl"
        >
            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M12 8v4m0 4h.01M4.93 19h14.14c1.54 0 2.5-1.67 1.73-3L13.73 4c-.77-1.33-2.69-1.33-3.46 0L3.2 16c-.77 1.33.19 3 1.73 3z"
                />
            </svg>
        </div>

        <div class="min-w-0">
            <p class="text-sm font-bold">{{ title }}</p>
            <p class="mt-1 text-sm font-medium">
                {{ message }}
            </p>
        </div>
    </div>
</template>
