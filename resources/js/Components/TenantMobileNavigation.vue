<script setup>
import { computed, onMounted, onUnmounted, ref } from 'vue';
import { Link } from '@inertiajs/vue3';

const props = defineProps({
    items: { type: Array, required: true },
    hasSettings: { type: Boolean, default: false },
});
const primaryItems = computed(() => props.items.slice(0, 4));
const menu = ref(null);
const openMenu = () => menu.value?.showModal();
const closeMenu = () => menu.value?.close();
let desktopMedia;
const closeOnDesktop = (event) => {
    if (event.matches) closeMenu();
};
onMounted(() => {
    desktopMedia = window.matchMedia('(min-width: 1024px)');
    desktopMedia.addEventListener('change', closeOnDesktop);
});
onUnmounted(() => desktopMedia?.removeEventListener('change', closeOnDesktop));
</script>

<template>
    <div class="lg:hidden">
        <nav aria-label="Navegación principal" data-tour="tenant-mobile-navigation" class="fixed inset-x-3 bottom-3 z-50 grid grid-cols-5 gap-1 rounded-2xl border border-gray-100 bg-white p-2 shadow-lg">
            <Link v-for="item in primaryItems" :key="item.url" :href="item.url" :aria-current="item.active ? 'page' : undefined" class="flex min-w-0 flex-col items-center gap-1 rounded-xl px-1 py-2 text-[10px] font-semibold" :class="item.active ? 'bg-orange-50 text-orange-700' : 'text-gray-600 hover:bg-gray-50'">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" :d="item.icon" /></svg>
                <span class="max-w-full truncate">{{ item.label }}</span>
            </Link>
            <button type="button" @click="openMenu" aria-haspopup="dialog" :data-tour="hasSettings ? 'tenant-mobile-settings' : undefined" class="col-start-5 flex flex-col items-center gap-1 rounded-xl px-1 py-2 text-[10px] font-semibold text-gray-600 hover:bg-gray-50">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" d="M5 6h14M5 12h14M5 18h14" /></svg>
                Más
            </button>
        </nav>
        <dialog ref="menu" aria-label="Todas las secciones del taller" class="fixed inset-x-3 bottom-24 top-auto m-auto max-h-[70vh] w-[calc(100%-1.5rem)] max-w-lg overflow-y-auto rounded-2xl border border-gray-100 bg-white p-5 shadow-xl backdrop:bg-slate-900/40">
            <div class="mb-4 flex items-center justify-between gap-3">
                <h2 class="text-lg font-bold text-gray-900">Todas las secciones</h2>
                <button type="button" @click="closeMenu" class="rounded-lg px-3 py-2 text-sm font-semibold text-gray-600 hover:bg-gray-50">Cerrar</button>
            </div>
            <div class="grid grid-cols-2 gap-2">
                <Link v-for="item in items" :key="item.url" :href="item.url" @click="closeMenu" :aria-current="item.active ? 'page' : undefined" class="flex items-center gap-2 rounded-xl p-3 text-sm font-semibold" :class="item.active ? 'bg-orange-50 text-orange-700' : 'text-gray-600 hover:bg-gray-50'">
                    <svg class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" :d="item.icon" /></svg>
                    <span>{{ item.label }}</span>
                </Link>
            </div>
        </dialog>
    </div>
</template>
