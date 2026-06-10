<script setup>
import { computed } from 'vue';
import { useTenantRouting } from '@/composables/useTenantRouting';

const props = defineProps({
    report: {
        type: String,
        required: true,
    },
});

const { page, tenantRouteParams } = useTenantRouting();
const currentQuery = computed(() => Object.fromEntries(new URLSearchParams((page.url?.split('?')[1] ?? ''))));
</script>

<template>
    <div class="flex flex-wrap gap-3">
        <a
            :href="route('reports.export.pdf', { ...tenantRouteParams, report: props.report, ...currentQuery })"
            class="inline-flex items-center rounded-2xl border border-gray-200 bg-white px-5 py-3 text-sm font-black text-gray-700 transition-colors hover:bg-gray-50"
        >
            Descargar PDF
        </a>
        <a
            :href="route('reports.export.excel', { ...tenantRouteParams, report: props.report, ...currentQuery })"
            class="inline-flex items-center rounded-2xl bg-emerald-600 px-5 py-3 text-sm font-black text-white transition-colors hover:bg-emerald-500"
        >
            Descargar Excel
        </a>
    </div>
</template>
