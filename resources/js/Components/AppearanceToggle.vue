<script setup>
import { useAppearance } from '@/composables/useAppearance';

const { isDark, toggleAppearance } = useAppearance();
</script>

<template>
                <button type="button" role="switch" :aria-checked="isDark"
                    :aria-label="isDark ? 'Cambiar a modo claro' : 'Cambiar a modo oscuro'"
                    :title="isDark ? 'Modo oscuro activado (cambiar a claro)' : 'Modo claro activado (cambiar a oscuro)'"
                    data-tour="tenant-appearance" data-support="appearance-toggle"
                    class="relative inline-flex h-8 w-14 shrink-0 cursor-pointer items-center justify-between rounded-full bg-white/80 p-1 shadow-sm ring-1 ring-inset ring-slate-200/90 transition-colors duration-200 hover:ring-slate-300 focus:outline-none focus-visible:ring-2 focus-visible:ring-[#FF7A00]"
                    @click="toggleAppearance">
                    <span class="sr-only">{{ isDark ? 'Cambiar a modo claro' : 'Cambiar a modo oscuro' }}</span>

                    <span class="flex h-6 w-6 items-center justify-center text-slate-400" aria-hidden="true">
                        <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z" />
                        </svg>
                    </span>

                    <span class="flex h-6 w-6 items-center justify-center text-slate-400" aria-hidden="true">
                        <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" />
                        </svg>
                    </span>

                    <span
                        class="pointer-events-none absolute left-1 top-1 flex h-6 w-6 items-center justify-center rounded-full bg-[#FF7A00] text-white shadow-sm transition-transform duration-200 ease-in-out"
                        :class="isDark ? 'translate-x-6' : 'translate-x-0'" aria-hidden="true">
                        <svg v-if="!isDark" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                            stroke-width="2.2">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z" />
                        </svg>
    <svg v-else class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2">
        <path stroke-linecap="round" stroke-linejoin="round"
            d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" />
    </svg>
    </span>
    </button>
</template>
