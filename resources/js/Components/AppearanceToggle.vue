<script setup>
import { APPEARANCE_OPTIONS, useAppearance } from '@/composables/useAppearance';

const { preference, setPreference } = useAppearance();
</script>

<template>
    <div class="inline-flex rounded-full border border-slate-200 bg-white/70 p-0.5 shadow-sm" role="group"
        aria-label="Apariencia del sitio" data-tour="tenant-appearance" data-support="appearance-toggle">
        <button v-for="option in APPEARANCE_OPTIONS" :key="option.id" type="button" :title="`Apariencia: ${option.label}`"
            :aria-pressed="preference === option.id"
            class="flex h-8 w-8 items-center justify-center rounded-full transition"
            :class="preference === option.id ? 'bg-[#FF7A00] text-white' : 'text-slate-500 hover:bg-slate-100 hover:text-slate-800'"
            @click="setPreference(option.id)">
            <span class="sr-only">{{ option.label }}</span>
            <svg v-if="option.id === 'light'" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z" />
            </svg>
            <svg v-else-if="option.id === 'dark'" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" />
            </svg>
            <svg v-else class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
            </svg>
        </button>
    </div>
</template>
