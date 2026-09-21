<script setup>
import { onErrorCaptured, ref } from 'vue';
import { Link } from '@inertiajs/vue3';
import { useTenantRouting } from '@/composables/useTenantRouting';

const { tenantRouteParams } = useTenantRouting();
const error = ref(null);

onErrorCaptured((caughtError) => {
    error.value = caughtError;

    if (import.meta.env.DEV) {
        console.error(caughtError);
    }

    return false;
});
</script>

<template>
    <div v-if="error" class="rounded-[2rem] border border-rose-100 bg-rose-50/60 p-10 text-center shadow-sm">
        <div class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-2xl bg-white shadow-sm">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-rose-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 9v3.75m0 3.75h.008v.008H12v-.008zM21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
        </div>
        <h3 class="text-lg font-black uppercase tracking-tight text-gray-900">Ocurrió un error inesperado</h3>
        <p class="mt-1 text-sm font-medium text-gray-500">
            No pudimos mostrar esta vista. Nuestro equipo ya fue notificado.
        </p>
        <Link
            :href="route('taller.dashboard', tenantRouteParams)"
            class="mt-6 inline-flex items-center justify-center gap-2 rounded-2xl bg-gray-900 px-5 py-3 text-sm font-black text-white shadow-sm transition-colors hover:bg-gray-800"
        >
            Volver al dashboard
        </Link>
    </div>
    <slot v-else />
</template>
