<script setup>
import { Link } from '@inertiajs/vue3';
import { onMounted, ref } from 'vue';

const isDismissed = ref(false);
const storageKey = 'tallerflow_christmas_topbar_dismissed';

onMounted(() => {
    if (typeof window !== 'undefined' && window.sessionStorage) {
        isDismissed.value = window.sessionStorage.getItem(storageKey) === 'true';
    }
});

const dismiss = () => {
    isDismissed.value = true;
    if (typeof window !== 'undefined' && window.sessionStorage) {
        window.sessionStorage.setItem(storageKey, 'true');
    }
};
</script>

<template>
    <div
        v-if="!isDismissed"
        class="relative overflow-hidden rounded-2xl bg-gradient-to-r from-red-900 via-rose-900 to-emerald-950 border border-red-500/30 text-white px-3.5 py-2.5 shadow-lg backdrop-blur-md transition-all duration-300"
    >
        <!-- Decoración festiva de fondo -->
        <div class="pointer-events-none absolute -right-6 -top-6 w-20 h-20 rounded-full bg-red-500/20 blur-xl"></div>
        <div class="pointer-events-none absolute -left-6 -bottom-6 w-20 h-20 rounded-full bg-emerald-500/20 blur-xl"></div>

        <div class="relative flex items-center justify-between gap-3 text-xs md:text-sm">
            <!-- Contenido principal -->
            <div class="flex items-center gap-2.5 flex-1 min-w-0">
                <span class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-red-500/30 text-base flex-shrink-0 animate-bounce">
                    🎄
                </span>
                <p class="truncate font-medium text-white/90">
                    <strong class="text-amber-300 font-bold uppercase tracking-wider text-[11px] md:text-xs mr-1.5 bg-amber-400/20 px-2 py-0.5 rounded-full border border-amber-300/30">
                        Especial Navidad
                    </strong>
                    <span class="hidden sm:inline">Todos los talleres que contraten hasta el 31 de diciembre: </span>
                    <span class="text-amber-200 font-semibold">¡Todas las funcionalidades por 1 año a $19.990/mes!</span>
                </p>
            </div>

            <!-- Botón CTA y Cerrar -->
            <div class="flex items-center gap-2 flex-shrink-0">
                <Link
                    :href="route('pricing')"
                    class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-amber-400 hover:bg-amber-300 text-slate-950 font-bold text-xs transition-colors shadow-sm"
                >
                    <span>Ver Oferta</span>
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3" />
                    </svg>
                </Link>

                <button
                    type="button"
                    class="p-1 rounded-full text-white/70 hover:text-white hover:bg-white/10 transition-colors"
                    aria-label="Cerrar anuncio navideño"
                    @click="dismiss"
                >
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>
    </div>
</template>
