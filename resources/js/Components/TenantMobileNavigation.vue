<script setup>
import { computed, onMounted, onUnmounted, ref } from 'vue';
import { Link } from '@inertiajs/vue3';

const PRIMARY_SLOTS = 4;
const HIGHLIGHT_ROUTE = 'receptions.create';

const props = defineProps({
    items: { type: Array, required: true },
    hasSettings: { type: Boolean, default: false },
    logoutUrl: { type: String, default: null },
    primaryRoutes: {
        type: Array,
        default: () => ['taller.dashboard', 'work-orders.index', HIGHLIGHT_ROUTE, 'appointments.index'],
    },
});

const primaryItems = computed(() => {
    const preferred = props.primaryRoutes
        .map((name) => props.items.find((item) => item.route === name))
        .filter(Boolean);
    const fallback = props.items.filter((item) => !preferred.includes(item));

    return [...preferred, ...fallback].slice(0, PRIMARY_SLOTS);
});
const secondaryItems = computed(() => props.items.filter((item) => !primaryItems.value.includes(item)));
const isHighlighted = (item) => item.route === HIGHLIGHT_ROUTE;

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
        <nav aria-label="Navegación principal" data-tour="tenant-mobile-navigation" class="fixed inset-x-3 bottom-3 z-50 grid grid-cols-5 items-end gap-1 rounded-2xl border border-gray-100 bg-white p-2 shadow-lg">
            <Link v-for="item in primaryItems" :key="item.url" :href="item.url" :aria-current="item.active ? 'page' : undefined" class="flex min-h-[3.5rem] min-w-0 flex-col items-center justify-center gap-1 rounded-xl px-1 py-2 text-[11px] font-semibold" :class="isHighlighted(item) ? 'text-[#FF7A00]' : item.active ? 'bg-orange-50 text-orange-700' : 'text-gray-600 hover:bg-gray-50'">
                <span v-if="isHighlighted(item)" class="-mt-7 flex h-12 w-12 items-center justify-center rounded-full bg-[#FF7A00] text-white shadow-lg ring-4 ring-white">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" :d="item.icon" /></svg>
                </span>
                <svg v-else class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" :d="item.icon" /></svg>
                <span class="max-w-full truncate">{{ item.label }}</span>
            </Link>
            <button type="button" @click="openMenu" aria-haspopup="dialog" data-tour="tenant-mobile-more" class="col-start-5 flex min-h-[3.5rem] flex-col items-center justify-center gap-1 rounded-xl px-1 py-2 text-[11px] font-semibold text-gray-600 hover:bg-gray-50">
                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" d="M5 6h14M5 12h14M5 18h14" /></svg>
                Más
            </button>
        </nav>
        <dialog ref="menu" aria-label="Más secciones del taller" class="fixed inset-x-3 bottom-24 top-auto m-auto max-h-[70vh] w-[calc(100%-1.5rem)] max-w-lg overflow-y-auto rounded-2xl border border-gray-100 bg-white p-5 shadow-xl backdrop:bg-slate-900/40">
            <div class="mb-4 flex items-center justify-between gap-3">
                <h2 class="text-lg font-bold text-gray-900">Más</h2>
                <button type="button" @click="closeMenu" class="rounded-lg px-3 py-2 text-sm font-semibold text-gray-600 hover:bg-gray-50">Cerrar</button>
            </div>
            <div v-if="secondaryItems.length" class="grid grid-cols-2 gap-2">
                <Link v-for="item in secondaryItems" :key="item.url" :href="item.url" @click="closeMenu" :aria-current="item.active ? 'page' : undefined" class="flex min-h-[3rem] items-center gap-2 rounded-xl p-3 text-sm font-semibold" :class="item.active ? 'bg-orange-50 text-orange-700' : 'text-gray-600 hover:bg-gray-50'">
                    <svg class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" :d="item.icon" /></svg>
                    <span>{{ item.label }}</span>
                </Link>
            </div>
            <Link v-if="logoutUrl" :href="logoutUrl" method="post" as="button" data-tour="tenant-mobile-logout" class="mt-4 flex min-h-[3rem] w-full items-center justify-center gap-2 rounded-xl border border-rose-100 px-4 py-3 text-sm font-bold text-rose-500 hover:bg-rose-50">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" /></svg>
                Cerrar sesión
            </Link>
        </dialog>
    </div>
</template>
